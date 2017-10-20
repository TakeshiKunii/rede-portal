<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.helper');
jimport('joomla.log.log');

require_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_apiportal'.DS.'script.php';
require_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'apiconfiguration.php';
require_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_apiportal'.DS.'models'.DS.'apiportal.php';
require_once JPATH_SITE.DS.'components'.DS.'com_apiportal'.DS.'lib'.DS.'Pest.php';
require_once JPATH_SITE.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'SessionVariables.php';
require_once JPATH_SITE.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'Sso.php';

const CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';
const CONTENT_TYPE_JSON = 'application/json';
const CONTENT_TYPE_BINARY = 'application/octet-stream';
const CONTENT_TYPE_MULTI = 'multipart/form-data';
const ERROR_COM_APIPORTAL = 'com_apiportal';

abstract class ApiPortalHelper
{

    public static function getVersionedBaseFolder() {
        //The only location where v1.?' is set
	    // And in the router.php file for SSO paths ! don't forget it
        return '/api/portal/v1.3';
    }

    public static function login($username, $password, &$userDetails = null, $throwExc = false)
    {
	    $session = JFactory::getSession();

	    // If we have a userDetails object
	    // It's a SSO flow
        if (is_object($userDetails)) {
	        // Set some needed session variables
	        $session->set(ApiPortalSessionVariables::LOGIN_NAME, $userDetails->loginName);
	        self::cacheCurrentUserInSession($userDetails);

	        // Make some checks if the user is logged with SSO flow
	        if ((isset($userDetails->type) && $userDetails->type == 'externalsso')
		        && (!empty($userDetails->authAttrs) && $userDetails->authAttrs['isSSOLogin'])) {
		        // User is logged in API Manager through SSO
		        return true;
	        }

	        error_log('[SSO] Current user response does not have isSSOLogin ON status!');
	        // Ooo nooo, something isn't right - fall back
	        return false;
        } elseif ($userDetails === true) {
	        // This is form the default behavior
            self::clearCurrentUserSessionAttrs();
        }

        // If we come to this step check what kind of login is this
	    $ssoHelper = new SSOHelper((new ApiportalModelapiportal()), JFactory::getSession(), ApiPortalHelper::getConnection(), JFactory::getApplication());
	    $app = JFactory::getApplication();

	    // If this method is called again that's mean the session has expired (eventually)
	    // and if it's SSO just logout the user.
	    if ($ssoHelper->isOn() && $session->get(ApiPortalSessionVariables::IS_SSO_LOGGED_USER) == true) {
		    // If it's SSO there is something wrong - it should be an expired session
		    // In this case start SSO flow again (it used to logout but we want login now)
		    $app->redirect(JRoute::_('/' . SSOHelper::getSSOPath(), true));
		    return false;
	    }

        $conn = self::getConnection();
        $path = self::getVersionedBaseFolder()."/login";
        $fields = array("username" => $username, "password" => $password);

        try {
            $conn->post($path, $fields);
            $session->set(ApiPortalSessionVariables::LAST_LOGIN_ERROR_FROM_MANAGER, null);
        } catch (Pest_ClientError $e) {
            error_log("WARNING: API Poral Authentication for user '" . (isset($username)?$username:"<null>") . "' has failed and Joomla is going to the next Joomla Authentication plugin: Reason: Pest_ClientError: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);
            $session->set(ApiPortalSessionVariables::LAST_LOGIN_ERROR_FROM_MANAGER, $e->getMessage());
            self::handleException($session, $conn, $e, $throwExc);
            return false;
        } catch (Pest_Exception $e) {
            error_log("WARNING: API Poral Authentication for user '" . (isset($username)?$username:"<null>") . "' has failed and Joomla is going to the next Joomla Authentication plugin: Reason: Pest_Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, 'PestException');
            $session->set(ApiPortalSessionVariables::LAST_LOGIN_ERROR_FROM_MANAGER, $e->getMessage());
            self::handleException($session, $conn, $e, $throwExc);
            return false;
        } catch (Exception $e) {
            error_log("WARNING: API Poral Authentication for user '" . (isset($username)?$username:"<null>") . "' has failed and Joomla is going to the next Joomla Authentication plugin: Reason: Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);
            $session->set(ApiPortalSessionVariables::LAST_LOGIN_ERROR_FROM_MANAGER, $e->getMessage());
            self::handleException($session, $conn, $e, $throwExc);
            return false;
        }
        $session->set(ApiPortalSessionVariables::LAST_HEADERS_FROM_MANAGER, $conn->last_headers);
        $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);

        $authCookie = $conn->lastHeader('Set-Cookie');
        $session->set(ApiPortalSessionVariables::AUTH_COOKIE_FROM_MANAGER, $authCookie);

        $cookies = $conn->getHeaders($conn->last_headers, 'Set-Cookie');
        $authCookiesContent = array();
        if (is_array($cookies)) {
            foreach ($cookies as $cookie) {
                $authCookiesContent[] = $cookie;
            }
        }
        $session->set(ApiPortalSessionVariables::AUTH_COOKIES_FORM_MANAGER, $authCookiesContent);

        if ($userDetails) {
            // Get the User details
            $userDetails = self::getUser();
            if (isset($userDetails)) {
                $session->set(ApiPortalSessionVariables::LOGIN_NAME, $username);
                $session->set(ApiPortalSessionVariables::LOGIN_PASSWORD, self::encrypt($password));
            }
        }

        return true;
    }

    public static function getPassword () {
        $session = JFactory::getSession();
        $encryptedPassword = $session->get(ApiPortalSessionVariables::LOGIN_PASSWORD);
        if (isset($encryptedPassword)) {
            return self::decrypt($encryptedPassword);
        }
        return false;
    }

    public static function changePassword($userid, $password) {
        $path = self::getVersionedBaseFolder()."/users/$userid/changepassword";
        $fields = array("newPassword" => $password);

        self::doPost($path, $fields, CONTENT_TYPE_FORM, false, true);

        // Only change the cached session password if user is changing their own password.
        $session = JFactory::getSession();
        if (self::getCurrentUserPortalId() === $userid) { // do update of user details in session only for the current user
            $session->set(ApiPortalSessionVariables::LOGIN_PASSWORD, self::encrypt($password));
        }
        return true;
    }

    public static function deleteUser($userid, $throwExc = false) {
        $path = self::getVersionedBaseFolder()."/users/$userid";

        return self::doDelete($path, $throwExc);
    }

    public static function updateUser($userDetails, $isReloginRequired=false) {
        // Check if it is Current user request
        $isCurrentUserRequest = isset($userDetails) && ($userDetails->id===self::getCurrentUserPortalId());
        // Start getting user details
        $path = self::getVersionedBaseFolder() . ($isCurrentUserRequest?"/currentuser":"/users/".$userDetails->id);
        // Update user in API Manager/Portal
        self::doPut($path, $userDetails, CONTENT_TYPE_JSON, false, true);

        if ($isReloginRequired) {
            ApiPortalHelper::login($userDetails->loginName, self::getPassword());
        }

        // Reload users data from the API Manager/Portal
        $userDetails = self::getUser($userDetails->id);

        if (isset($userDetails) && $userDetails!=null) {
            $session = JFactory::getSession();
            if ($userDetails->id == self::getCurrentUserPortalId()) { // do update of user details in session only for the current user
                // Cache user details object in the session
                self::cacheCurrentUserInSession($userDetails);
            }
        }

        // Return value
        return $userDetails;
    }

    public static function getUser($pid = "current-user") {
        // Check if it is Current user request
        $isCurrentUserRequest = isset($pid) && ($pid==="current-user" || $pid===self::getCurrentUserPortalId());
        // Start getting user details
        $path = self::getVersionedBaseFolder() . ($isCurrentUserRequest?"/currentuser":"/users/$pid");
        $userDetails = isset($pid)?self::doGet($path):null;

        if ($isCurrentUserRequest) {
            // if it is current user request only
            if (isset($userDetails)) {
                // Cache current user details object in the session
                self::cacheCurrentUserInSession($userDetails);
            }
        }

        // Return value
        return $userDetails;
    }

    public static function clearCurrentUserSessionAttrs($session=null) {
        $session = isset($session)?$session:JFactory::getSession();

        $session->clear(ApiPortalSessionVariables::MANAGER_USER_ID);
        $session->clear(ApiPortalSessionVariables::LOGIN_NAME);
        $session->clear(ApiPortalSessionVariables::MANAGER_EMAIL);
        $session->clear(ApiPortalSessionVariables::MANAGER_ROLE);
        $session->clear(ApiPortalSessionVariables::MANAGER_ORG_ID);

        $session->clear(ApiPortalSessionVariables::LOGIN_PASSWORD);
        $session->clear(ApiPortalSessionVariables::AUTH_COOKIES_FORM_MANAGER);
        $session->clear(ApiPortalSessionVariables::AUTH_COOKIE_FROM_MANAGER);

        $session->clear(ApiPortalSessionVariables::LAST_HEADERS_FROM_MANAGER);
        $session->clear(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER);
        $session->clear(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER);
    }

    public static function cacheCurrentUserInSession($userDetails, $session=null) {
        $session = isset($session)?$session:JFactory::getSession();

        // Cache current user details object in the session

        // Cache some details of current user in the session
        $session->set(ApiPortalSessionVariables::MANAGER_USER_ID, $userDetails->id);
        $session->set(ApiPortalSessionVariables::MANAGER_EMAIL, $userDetails->email);
        $session->set(ApiPortalSessionVariables::MANAGER_ROLE, $userDetails->role);
        $session->set(ApiPortalSessionVariables::MANAGER_ORG_ID, $userDetails->organizationId);
    }

    public static function getCurrentUserPortalId($session=null) {
        $session = isset($session)?$session:JFactory::getSession();
        return $session->get(ApiPortalSessionVariables::MANAGER_USER_ID);
    }

    public static function getCurrentUserOrganizationId($session=null) {
        $session = isset($session)?$session:JFactory::getSession();
        return $session->get(ApiPortalSessionVariables::MANAGER_ORG_ID);
    }

    // TODO: This method could return current user org id or selected organization in case of apiadmin user
    public static function getActiveOrganizationId($session=null) {
        return self::getCurrentUserOrganizationId($session);
    }

    private static function handleException($session, $conn, $e, $throwExc = false) {
        $session->set(ApiPortalSessionVariables::LAST_HEADERS_FROM_MANAGER, $conn->last_headers);

        $exc = null;
        $contentType = $conn->lastHeader('Content-Type');
        if ($contentType == CONTENT_TYPE_JSON) {
            // Convert API Manager error response to Exception object
            $err = json_decode($e->getMessage());
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    $exc = new RuntimeException($err->errors[0]->message, $err->errors[0]->code, $e);
                    break;
                default:
                    $exc = $e;
                    break;
            }
        } else {
            // Otherwise, be ready to use native exception
            $exc = $e;
        }

        // I
        $exc = $exc!=null?$exc:$e;
        if ($exc!==null && $throwExc) {
            throw $exc;
        } else {
            return null;//$exc;
        }

    }

    public static function doGet($path, $data = array(), $assoc = false, $parse = true, $throwExc = false) {
        $session = JFactory::getSession();
        $conn = self::getConnection();

        $result = self::_doGet($conn, $path, $data, $assoc, $parse, $throwExc);
        // One shot at re-authentication - it should not fail!
        if (self::getStatus() == 401) {
            $username = $session->get(ApiPortalSessionVariables::LOGIN_NAME);
            $password = self::getPassword();
            if (self::login($username, $password)) {
                $result = self::_doGet($conn, $path, $data, $assoc, $parse, $throwExc);
            } else {
                $result = null;
            }
        }

        return $result;
    }

    protected static function _doGet($conn, $path, $data = null, $assoc = false, $parse = true, $throwExc = false) {
        $session = JFactory::getSession();

        $headers = self::setCookieHeaders($session);

        $body = null;
        try {
            $body = $conn->get($path, $data, $headers);
        } catch (Pest_ClientError $e) {
            error_log("Caught Pest Client Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);
            $session->set(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER, $e->getMessage());
            return self::handleException($session, $conn, $e, $throwExc);
        } catch (Pest_Exception $e) {
            error_log("Caught Pest Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, 'PestException');
            $session->set(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER, $e->getMessage());
            return self::handleException($session, $conn, $e, $throwExc);
        } catch (Exception $e) {
            error_log("Caught Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);
            $session->set(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER, $e->getMessage());
            return self::handleException($session, $conn, $e, $throwExc);
        }
        $session->set(ApiPortalSessionVariables::LAST_HEADERS_FROM_MANAGER, $conn->last_headers);
        $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);

        $contentType = $conn->lastHeader('Content-Type');
        if ($contentType == CONTENT_TYPE_JSON && $parse) {
            return json_decode($body, $assoc);
        } else {
            return $body;
        }
    }

    public static function doPost($path, $data, $contentType = CONTENT_TYPE_JSON, $assoc = false, $throwExc = false) {
        $session = JFactory::getSession();
        $conn = self::getConnection();

        $result = self::_doPost($conn, $path, $data, $contentType, $assoc, $throwExc);
        // One shot at re-authentication - it should not fail!
        if (self::getStatus() == 401) {
            $username = $session->get(ApiPortalSessionVariables::LOGIN_NAME);
            $password = self::getPassword();
            if (self::login($username, $password)) {
                $result = self::_doPost($conn, $path, $data, $contentType, $assoc, $throwExc);
            } else {
                $result = null;
            }
        }

        return $result;
    }

    protected static function _doPost($conn, $path, $data, $contentType = CONTENT_TYPE_JSON, $assoc = false, $throwExc = false) {
        $session = JFactory::getSession();

        $headers = self::setCookieHeaders($session, ['Content-Type: ' . $contentType]);

        if ($contentType == CONTENT_TYPE_JSON) {
            $data = json_encode($data);
        }

        $body = null;
        try {
            $body = $conn->post($path, $data, $headers);
        } catch (Pest_ClientError $e) {
            error_log("Caught Pest Client Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);
            $session->set(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER, $e->getMessage());
            return self::handleException($session, $conn, $e, $throwExc);
        } catch (Pest_Exception $e) {
            error_log("Caught Pest Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, 'PestException');
            $session->set(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER, $e->getMessage());
            return self::handleException($session, $conn, $e, $throwExc);
        } catch (Exception $e) {
            error_log("Caught Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);
            $session->set(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER, $e->getMessage());
            return self::handleException($session, $conn, $e, $throwExc);
        }
        $session->set(ApiPortalSessionVariables::LAST_HEADERS_FROM_MANAGER, $conn->last_headers);
        $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);

        $contentType = $conn->lastHeader('Content-Type');
        if ($contentType == CONTENT_TYPE_JSON) {
            return json_decode($body, $assoc);
        } else {
            return $body;
        }
    }

    public static function doPut($path, $data, $contentType = CONTENT_TYPE_JSON, $assoc = false, $throwExc = false) {
        $session = JFactory::getSession();
        $conn = self::getConnection();

        $body = self::_doPut($conn, $path, $data, $contentType, $assoc, $throwExc);
        // One shot at re-authentication - it should not fail!
        if (self::getStatus() == 401) {
            $username = $session->get(ApiPortalSessionVariables::LOGIN_NAME);
            $password = self::getPassword();
            if (self::login($username, $password)) {
                $body = self::_doPut($conn, $path, $data, $contentType, $assoc);
            } else {
                $body = null;
            }
        }

        return $body;
    }

    protected static function _doPut($conn, $path, $data, $contentType = CONTENT_TYPE_JSON, $assoc = false, $throwExc = false) {
        $session = JFactory::getSession();

        $headers = self::setCookieHeaders($session, ['Content-Type: ' . $contentType]);

        if ($contentType == CONTENT_TYPE_JSON) {
            $data = json_encode($data);
        }

        $body = null;
        try {
            $body = $conn->put($path, $data, $headers);
        } catch (Pest_ClientError $e) {
            error_log("Caught Pest Client Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);
            $session->set(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER, $e->getMessage());
            return self::handleException($session, $conn, $e, $throwExc);
        } catch (Pest_Exception $e) {
            error_log("Caught Pest Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, 'PestException');
            $session->set(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER, $e->getMessage());
            return self::handleException($session, $conn, $e, $throwExc);
        } catch (Exception $e) {
            error_log("Caught Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);
            $session->set(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER, $e->getMessage());
            return self::handleException($session, $conn, $e, $throwExc);
        }
        $session->set(ApiPortalSessionVariables::LAST_HEADERS_FROM_MANAGER, $conn->last_headers);
        $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);

        $contentType = $conn->lastHeader('Content-Type');
        if ($contentType == CONTENT_TYPE_JSON) {
            return json_decode($body, $assoc);
        } else {
            return $body;
        }
    }

    public static function doDelete($path, $throwExc = false) {
        $session = JFactory::getSession();
        $conn = self::getConnection();

        $body = self::_doDelete($conn, $path, $throwExc);
        // One shot at re-authentication - it should not fail!
        if (self::getStatus() == 401) {
            $username = $session->get(ApiPortalSessionVariables::LOGIN_NAME);
            $password = self::getPassword();
            if (self::login($username, $password)) {
                $body = self::_doDelete($conn, $path);
            } else {
                return null;
            }
        }

        return $body;
    }

    protected static function _doDelete($conn, $path, $throwExc = false) {
        $session = JFactory::getSession();

        $headers = self::setCookieHeaders($session);
        $body = null;
        try {
            $body = $conn->delete($path, $headers);
        } catch (Pest_ClientError $e) {
            error_log("Caught Pest Client Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);
            $session->set(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER, $e->getMessage());
            return self::handleException($session, $conn, $e, $throwExc);
        } catch (Pest_Exception $e) {
            error_log("Caught Pest Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, 'PestException');
            $session->set(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER, $e->getMessage());
            return self::handleException($session, $conn, $e, $throwExc);
        } catch (Exception $e) {
            error_log("Caught Exception: " . $e->getMessage());
            $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);
            $session->set(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER, $e->getMessage());
            return self::handleException($session, $conn, $e, $throwExc);
        }
        $session->set(ApiPortalSessionVariables::LAST_HEADERS_FROM_MANAGER, $conn->last_headers);
        $session->set(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER, $conn->last_response['meta']['http_code']);

        $contentType = $conn->lastHeader('Content-Type');
        if ($contentType == CONTENT_TYPE_JSON) {
            return json_decode($body, true);
        } else {
            return $body;
        }
    }

    public static function getHeader($header) {
        $session = JFactory::getSession();
        $lastHeaders =  $session->get(ApiPortalSessionVariables::LAST_HEADERS_FROM_MANAGER);

        foreach ($lastHeaders as $lastHeader) {
            if (isset($lastHeader[strtolower($header)])) {
                return $lastHeader[strtolower($header)];
            }
        }

        return null;
    }

    public static function getStatus() {
        $session = JFactory::getSession();
        return $session->get(ApiPortalSessionVariables::LAST_STATUS_FROM_MANAGER);
    }

    /**
     * Retrieves the last error message from the session. That error message is usually
     * set by the doGet(), doPost(), doDelete(), doPut() methods. It indicates an error
     * in the communication between API Portal and API Manager.
     * @return mixed the error message.
     */
    public static function getErrorMessage() {
        $session = JFactory::getSession();
        return $session->get(ApiPortalSessionVariables::LAST_ERROR_MSG_FROM_MANAGER);
    }

    /**
     * Observed session/user states:
     *
     * Normal logged out (or never logged in) state:
     *   Session State: active
     *   Guest User: 1
     *
     * Normal logged in state:
     *   Session State: active
     *   Guest User: 0
     *
     * Transient expired state: Problematic
     *   Session State: expired
     *   Guest User: 0
     *
     *   Session State: expired
     *   Guest User: 1 (Yes, guest user sessions can be expired)
     *
     * What is really strange is how an expired non-guest user session
     * gets turned into an active guest session after a redirect!
     * @param bool $redirect To redirect ot not after login
     */
    public static function checkSession($redirect = true) {
        $uriInstance = JURI::getInstance();
        $returnUrl = $uriInstance->getScheme() . '://' . $uriInstance->getHost() . $uriInstance->getPath() . '?' . $uriInstance->getQuery();
        $app = JFactory::getApplication();
        $session = JFactory::getSession();
		// Get SSO path - it's dynamic
	    $ssoPath = SSOHelper::getSSOPath();
	    // Get SSO Cookie
	    $ssoCookie = (isset($_COOKIE[API_PORTAL_AUTH_TYPE_SSO_COOKIE]) && $_COOKIE[API_PORTAL_AUTH_TYPE_SSO_COOKIE] == API_PORTAL_AUTH_TYPE_SSO_COOKIE_VALUE)
		    ? true : false;

        if ($session->isActive()) {
            if (JFactory::getUser()->guest) {
	            // Ok, but if it's SSO redirect the user to the SSO start point
	            if ($ssoCookie === true) {
		            $app->redirect(JRoute::_('/' . $ssoPath, true));
	            }

                // User is not logged in
                $app->enqueueMessage(JText::_('JGLOBAL_SIGN_IN_REQUIRED') , 'message');
                $app->redirect(JRoute::_('index.php?option=com_users&view=login&return=' . base64_encode($returnUrl)));
            } else if (!self::isApiManagerUser()) {
	            // Ok, but if it's SSO redirect the user to the SSO start point
	            if ($ssoCookie === true) {
		            $app->redirect(JRoute::_('/' . $ssoPath, true));
	            }

                // Non API Manager logged in user
                $app->enqueueMessage(JText::_('JGLOBAL_NOT_AUTHORIZED'), 'warning');
                $app->redirect(JRoute::_('index.php?option=com_users&view=login'));
            } else {
                // API Manger User. Do we need to do anything else?
            }
        } else {
            /*
             * Based on some experiments, this is always an 'expired' session. However it seems that this
             * state is transient, and gets set back to 'active' (but not logged in) by the time we reach
             * the login page. Solution is to add a ?session=expired to the redirect URL so it can be detected.
             *
             * It is not possible to enqueue a message here, it will get lost when the session state goes
             * from 'expired' to 'active', which happens somewhere between here and the login page.
             *
             * NOTE: Joomla session timeout handling is very flaky. I've seen 'expired' sessions 'resurrect'
             * themselves after a redirect to the login page, which is completly unacceptable. There are also
             * session fixation issues: the session id never gets changed unless the user logs out, so destroy
             * the sesssion here before the redirect. This only solves the problem for our pages, further
             * investigation is required to figure out what happens on session timeout to other pages.
             */
            $state = $session->getState();
            $return = urlencode(base64_encode($returnUrl));

            $session->destroy();

	        // Ok, but if it's SSO redirect the user to the SSO start point
	        if ($ssoCookie === true) {
		        $app->redirect(JRoute::_('/' . $ssoPath, true));
	        }

            //if redirect is true use return statement else redirect to login page only
            if ($redirect) {
                $url = 'index.php?option=com_users&view=login&session=' . $state . '&return=' . $return;
            } else {
                $url = 'index.php?option=com_users&view=login&session=' . $state;
            }

            $app->redirect(JRoute::_($url, false));
        }
    }

    /**
     * Check the permissions of the user
     * @param stdObject $application
     * @return string
     */
    public static function getPermission($application) {

        $session = JFactory::getSession();
        $role = $session->get(ApiPortalSessionVariables::MANAGER_ROLE);

        //get the current user API Manager ID
        $params = json_decode(JFactory::getSession()->get('user')->params);
        $userPid = $params->pid;

        $permission = '';
        if (isset($role)) {
            if ($role == 'user') {
                if (isset($application->permission)) {
                    $permission =  $application->permission->permission;
                }
            } else if ($role == 'admin' || $role == 'oadmin') {
                $permission = 'manage';
                $config = ApiPortalHelper::getAPIMangerConfig();
                if($role == "oadmin" && $config->delegateApplicationAdministration === FALSE &&
                    (!isset($application->permission) || $userPid != $application->permission->userId)){
                    $permission = '';
                } else if (isset($application->permission)) {
                    $permission =  $application->permission->permission;
                }
            }
        }

        return $permission;
    }

    public static function getAPIMangerConfig() {
        // Get the API Manager configuration
        $path = self::getVersionedBaseFolder() . "/config";
        $config = self::doGet($path);
        $status = self::getStatus();

        // Don't bother erroring out, it's not a critical failure and we can continue
        if ($status >= 400 || $status == 'PestException') {
            $config = new stdClass();
        }

        return $config;
    }

    /**
     * Get app info from the API Manager
     * It's different from getAPIMangerConfig() method.
     * This request doen't not require authentication
     * @return mixed|null|stdClass
     */
    public static function getAPIManagerAppInfo()
    {
        // Get the API Manager app info
        $path = self::getVersionedBaseFolder() . "/appinfo";
        $config = self::doGet($path);
        $status = self::getStatus();

        // Don't bother erroring out, it's not a critical failure and we can continue
        if ($status >= 400 || $status == 'PestException') {
            $config = new stdClass();
        }

        return $config;
    }

    public static function displayView($viewname, $layout, $tab, $controller, $model, $format="html", $document=null) {
        if ($layout) {
            JRequest::setVar('layout', $layout);
        }
        if ($tab) {
            JRequest::setVar('tab', $tab);
        }

        $view = $controller->getView($viewname, $format);
        if ($layout) {
            $view->setLayout($layout);
        }
        if ($model) {
            $view->setModel($model, true);
        }
        if ($document) {
            $view->document = $document;
        }
        $view->display();
    }

    public static function enqueueErrors($app, $errors) {
        for ($i = 0, $n = count($errors); $i < $n; $i++) {
            if ($errors[$i] instanceof Exception) {
                $app->enqueueMessage($errors[$i]->getMessage(), 'error');
            } else {
                $app->enqueueMessage($errors[$i], 'warning');
            }
        }
    }

    public static function isHttpError() {
        // Check for HTTP error responses

        $status = ApiPortalHelper::getStatus();
        $errorMsg = ApiPortalHelper::getErrorMessage();
        $code = null;

        $app = JFactory::getApplication();
        if ($status == 'PestException') {
            $app->enqueueMessage(JText::_('COM_APIPORTAL_HTTP_CONNECTION'), 'error');
            return true;
        } else if ($status == 'Exception') {
            $app->enqueueMessage(JText::_('COM_APIPORTAL_HTTP_EXCEPTION'), 'error');
            return true;
        } else if ($status >= 400) {
            # If the error message is encoded in JSON, decode it. If not, this will throw exception caught below
            $errors = json_decode($errorMsg);
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    # In case the returned error message is empty, set default error message.
                    $error = isset($errors->errors[0]->message) ? $errors->errors[0]->message : JText::_('COM_APIPORTAL_HTTP_ERROR');
                    # In case the returned error code is not set, set to null (used below).
                    $code = isset($errors->errors[0]->code) ? $errors->errors[0]->code : null;
                    break;
                default:
                    # Case when the error message was not encoded as JSON. Then simply take it as it is
                    $error = $errorMsg;
                    break;
            }

            $app->enqueueMessage(($code ? $code.': ' : null). $error, 'error');

            return true;
        } else {
            return false;
        }
    }

    public static function isApiManagerUser() {
        $userId = self::getCurrentUserPortalId();
        return isset($userId);
    }

    public static function hasAdminRole() {
        $session = JFactory::getSession();
        $role = $session->get(ApiPortalSessionVariables::MANAGER_ROLE);

        if (isset($role)) {
            return ($role === 'admin');
        } else {
            return false;
        }
    }

    public static function hasGroupAdminRole() {
        $session = JFactory::getSession();
        $role = $session->get(ApiPortalSessionVariables::MANAGER_ROLE);

        if (isset($role)) {
            return ($role == 'oadmin');
        } else {
            return false;
        }
    }

    public static function isEnabled($object) {
        if (isset($object->enabled)) {
            // This can be either 0/1 or 'true'/'false'
            if ($object->enabled == 'true') {
                return true;
            } else if ($object->enabled == 'false') {
                return false;
            } else {
                return ($object->enabled);
            }
        } else {
            return false;
        }
    }

    public static function isPending($object) {
        if (isset($object->state)) {
            return ($object->state == 'pending');
        } else {
            return false;
        }
    }

    public static function hasPendingAPIs($application) {
        if (!isset($application->apis)) {
            return false;
        }
        foreach ($application->apis as $api) {
            if (self::isPending($api)) {
                return true;
            }
        }
        return false;
    }

    public static function isCommunity($obj) { //object is user & application with customly added ->organizationName
        if (isset($obj->organizationName)) {
            return ($obj->organizationName == 'Community');
        } else {
            return false;
        }
    }

    public static function isComponentEnabled($componentName) {
        try {
            $db = JFactory::getDbo();
            $db->setQuery("SELECT enabled FROM #__extensions WHERE name = '$componentName'");
            return $db->loadResult();
        } catch (Exception $e) {
            error_log("Caught Exception: " . $e->getMessage());
            return false;
        }
    }

    public static function getOrganizationName($id, $organizations) {
        foreach ($organizations as $organization) {
            if ($id == $organization->id) {
                return $organization->name;
            }
        }
        return '';
    }

    public static function getCreatedByName($id, $users) {
        foreach ($users as $user) {
            if ($id == $user->id) {
                return $user->name;
            }
        }
        return '';
    }

    public static function getConnection() {
        $params = JComponentHelper::getParams('com_apiportal');
        $config = new ApiportalModelapiportal();

        $api_manager_addr = $config->getHost();
        $api_manager_port = $config->getPort();
        $api_manager_crt_file = $config->getCertFile();

        // API Manager connection is always 'https'
        $conn = new Pest("https://$api_manager_addr:$api_manager_port", JFactory::getSession());

        $verifyCrt = $config->getVefiryCert();
        $verifyHost = $config->getVerifyHost();
        $verbosPath = $config->getCertDir() .DS.'curl.log';

        $conn->curl_opts[CURLOPT_SSL_VERIFYPEER] = FALSE;
        $conn->curl_opts[CURLOPT_SSL_VERIFYHOST] = 0;
        $conn->curl_opts[CURLOPT_STDERR] = fopen($verbosPath, "w+");
        $conn->curl_opts[CURLOPT_VERBOSE] = TRUE;

        if($verifyCrt == "1"){
            $conn->curl_opts[CURLOPT_SSL_VERIFYPEER] = TRUE;
            $conn->curl_opts[CURLOPT_CAINFO] = $api_manager_crt_file;
        }

        if ($verifyHost == "1"){
            $conn->curl_opts[CURLOPT_SSL_VERIFYHOST] = 2;
        }

        return $conn;
    }

    public static function isCaptchaRequired() {
        $sysCaptcha = JFactory::getConfig()->get('captcha');
        $usrCaptcha = JComponentHelper::getParams('com_users')->get('captcha');

        return ($sysCaptcha || $usrCaptcha);
    }

    private static function encrypt($string) {
        $token = self::getCurrentUserPortalId();
        $encrypted = null;
        if (function_exists('mcrypt_get_iv_size') && function_exists('mcrypt_create_iv')) {
            $key = hash('sha256', $token, TRUE);
            $size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
            $iv = mcrypt_create_iv($size);
            $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_ECB, $iv));
            return $encrypted;
        } else {
            error_log("WARNING: API PORTAL LOCAL ENCRYPTION IS NOT ENABLED. Install 'mcrypt' php extension to enable it.");
            $encrypted = $string;
        }
        return $encrypted;
    }

    private static function decrypt($string) {
        $token = self::getCurrentUserPortalId();
        $decrypted = null;
        if (function_exists('mcrypt_get_iv_size') && function_exists('mcrypt_create_iv')) {
            $key = hash('sha256', $token, TRUE);
            $size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
            $iv = mcrypt_create_iv($size);
            $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($string), MCRYPT_MODE_ECB, $iv));
        } else {
            $decrypted = $string;
        }
        return $decrypted;
    }

    public static function saveApiUser($user_id, $email, $name, $loginname, $user_id_jm=null, $tcAccepted) {
        // Calculates joomla user ID
        if (!isset($user_id_jm)) {
            $user = JFactory::getUser();
            $user_id_jm = isset($user) && $user->id!=0?$user->id:$user_id_jm;
        }

        // Create entry for API Manager user in apiportal_user table if not already there.
        $db = JFactory::getDbo();

        $email = $db->Quote($email);
        $loginname = $db->Quote($loginname);
        $user_id = $db->Quote($user_id);
        $name = $db->Quote($name);
        $user_id_jm = isset($user_id_jm)?$db->Quote($user_id_jm):$user_id_jm;

        $db = JFactory::getDbo();
        $db->getQuery(true);
        $db->setQuery('SELECT * FROM #__apiportal_user WHERE user_id = '.$user_id);
        if (!$db->query()) {
            throw new Exception($db->getErrorMsg());
        }
        $row = $db->loadRow();

        if (!isset($row)) {
            if (isset($user_id_jm) && $user_id_jm!='0') {
                $db = JFactory::getDbo();
                $db->getQuery(true);
                $db->setQuery('INSERT INTO #__apiportal_user (user_id, email, name, loginname, user_id_jm, termsAndCond) VALUES ('.$user_id.','.$email.','.$name.','.$loginname.','.$user_id_jm.','.$tcAccepted.')');
                if (!$db->query()) {
                    throw new Exception($db->getErrorMsg());
                }

                // There is possibility to have extra records in #__apiportal_users, so try to purge the table
                self::purgeUnassignedApiPortalUserEntries();
            } else {
                $db = JFactory::getDbo();
                $db->getQuery(true);
                $db->setQuery('INSERT INTO #__apiportal_user (user_id, email, name, loginname, termsAndCond) VALUES ('.$user_id.','.$email.','.$name.','.$loginname.','.$tcAccepted.')');
                if (!$db->query()) {
                    throw new Exception($db->getErrorMsg());
                }
            }
        } else {
            if (isset($user_id_jm) && $user_id_jm!='0') {
                $db = JFactory::getDbo();
                $db->getQuery(true);
                $db->setQuery('UPDATE #__apiportal_user set email = '.$email.', name = '.$name.', loginname = '.$loginname.', user_id_jm = '.$user_id_jm.', termsAndCond = ' . $tcAccepted . ' where user_id = '.$user_id);
                if (!$db->query()) {
                    throw new Exception($db->getErrorMsg());
                }

                $current_user_id_jm = $row[1];
                if ($db->Quote($current_user_id_jm)!=$user_id_jm) {
                    // There is possibility to have extra records in #__apiportal_users, so try to purge the table
                    self::purgeUnassignedApiPortalUserEntries();
                }

            } else {
                $db = JFactory::getDbo();
                $db->getQuery(true);
                $db->setQuery('UPDATE #__apiportal_user set email = '.$email.', name = '.$name.', loginname = '.$loginname.', termsAndCond = ' . $tcAccepted . ' where user_id = '.$user_id);
                if (!$db->query()) {
                    throw new Exception($db->getErrorMsg());
                }
            }
        }
    }

    public static function deleteApiPortalUserEntry($portalUserId) {
        $db = JFactory::getDbo();

        $db->setQuery("DELETE FROM #__apiportal_user WHERE user_id = ".$db->Quote($portalUserId));
        if (!$db->query()) {
            throw new Exception($db->getErrorMsg());
        }
    }

    public static function purgeUnassignedApiPortalUserEntries() {
        $db = JFactory::getDbo();
        $db->getQuery(true);
        $db->setQuery("DELETE FROM #__apiportal_user where user_id_jm!=0 AND ( user_id_jm NOT IN (select id from #__users) || user_id_jm IN ( select id from #__users where id=#__apiportal_user.user_id_jm AND NOT params like concat('%\"pid\":\"',#__apiportal_user.user_id,'\"%') ) )");
        if (!$db->query()) {
            throw new Exception($db->getErrorMsg());
        }
    }

    public static function laodApiUser($user_id, &$email, &$name, &$user_id_jm) {
        // Load data stored for API User with a provided ID
        $db = JFactory::getDbo();

        $user_id = $db->Quote($user_id);

        $db->getQuery(true);
        $db->setQuery('SELECT user_id, email, name, user_id_jm  FROM #__apiportal_user WHERE user_id = '.$user_id);
        if (!$db->query()) {
            throw new Exception($db->getErrorMsg());
        }
        $row = $db->loadRow();
        if (isset($row)) {
            $email = $row[1];
            $name = $row[2];
            $user_id_jm = $row[3];
            return isset($user_id_jm) && $user_id_jm!='0';
        } else {
            return false;
        }

    }

    public static function findJoomlaUserByUsername($username) {
        // Load data stored for API User with a provided ID
        $db = JFactory::getDbo();

        $username = $db->Quote($username);

        $db->getQuery(true);
        $db->setQuery('SELECT id FROM #__users WHERE username = ' . $username);
        if (!$db->query()) {
            throw new Exception($db->getErrorMsg());
        }
        $row = $db->loadRow();
        if (isset($row)) {
            return $row[0];
        } else {
            return null;
        }
    }

    // !!! IMPORTANT !!! Prevent recognizing a wrong Joomla user as API Portal User
    // As per history till now, Joomla User COULD BE AN API Portal user, only if his:
    //  - Username is equal to Name 
    //      or
    //  - Username is equal tp Email 
    //      or
    //  - Parameter 'pid' is same as API Portal user ID
    // !!!!Username is separate property now and don't have to be equal to email ot name!!!!!
    public static function couldBeAPIPortalUser($email, $name, $username, $pid = null, $jPid = null) {
        return isset($email) && $name===$username || isset($pid) && $pid===$jPid;
    }

    // Creates Joomla users
    public static function makeJoomlaAccount($name, $username, $email)
    {
        // Load data stored for API User with a provided ID
        $db = JFactory::getDbo();

        // Insert user in #_users;
        $name = $db->Quote($name);
        $username = $db->Quote($username);
        $email = $db->Quote($email);
        $password = $db->Quote("#");

        $db->getQuery(true);
        $db->setQuery('INSERT INTO #__users (name, username, email, password) values ('.$name.', '.$username.', '.$email.', '.$password.')' );
        if (!$db->query()) {
            throw new Exception($db->getErrorMsg());
        }
        $user_id = $db->Quote($db->insertid());

        return $user_id;
    }

    // Assign a joomla user to the Joomla Amdinistrator group
    public static function assignJoomlaUserToAdministratorGroup($user_id, $user_groups)
    {
        $toGroups = "'Registered','Administrator'";
        self::assignJoomlaUserToGroups($user_id, $user_groups, $toGroups);
    }

    // Assign a joomla user to the Joomla Manager group
    public static function assignJoomlaUserToManagerGroup($user_id, $user_groups)
    {
        $toGroups = "'Registered','Manager'";
        self::assignJoomlaUserToGroups($user_id, $user_groups, $toGroups);
    }

    // Assign a joomla user to the Joomla Manager group
    public static function assignJoomlaUserToRegisteredGroupOnly($user_id, $user_groups)
    {
        $toGroups = "'Registered'";
        self::assignJoomlaUserToGroups($user_id, $user_groups, $toGroups);
    }

    // Assign a joomla user to the amdinistrator group
    private static function assignJoomlaUserToGroups($user_id, $currentGroups, $toGroups)
    {
        // Get IDs for Registered and Administrator groups;
        $newGroups = array();
        $extraGroups = array();
        $requestedGroupIds = array();
        $db = JFactory::getDbo();
        $db->getQuery(true);
        $groupRegistered = $db->Quote("Registered");
        $groupAdministrator = $db->Quote("Administrator");
        $db->setQuery('select id, title from #__usergroups where title in (' . $toGroups . ')');
        $rows = $db->loadObjectList();
        // Fill helper areas
        // - $newGroups - the delta betweed current and new groups to which user have to be assigned
        // - $requestedGroupIds - IDs of the requested groups to which user has to be assigned 
        // - $extraGroups - list of groups that user will be unassigned from
        foreach ($rows as $row)
        {
            if (!isset($currentGroups) || !array_key_exists($row->id , $currentGroups)) {
                array_push($newGroups, $row->id);
            }
            $requestedGroupIds[$row->id]=$row->title;
        }
        if (isset($currentGroups)) {
            foreach ($currentGroups as $group)
            {
                if (isset($requestedGroupIds) && !array_key_exists($group , $requestedGroupIds)) {
                    array_push($extraGroups, $group);
                }
            }
        }

        // Assign user to the groups user in #_users;
        foreach ($newGroups as $k => $group_id) {
            try {
                $db = JFactory::getDbo();
                $db->getQuery(true);
                $db->setQuery('INSERT INTO #__user_usergroup_map (user_id, group_id) values ('.$user_id.', '.$group_id.')' );
                if (!$db->query()) {
                    error_log("Error: Assign a joomla user to the amdinistrator group: '" . (isset($user_id)?$user_id:"<null>") . "'; " . $db->getErrorMsg());
                }
            } catch (Exception $e) {
                error_log("Error: Assign a joomla user (user_id) to the amdinistrator group: '" . (isset($user_id)?$user_id:"<null>") . "'; " . $e->getMessage());
            }
        }

        // Remove user assigments to the extra groups
        $extraGroupsAsCSV = "";
        foreach ($extraGroups as $id) {
            $extraGroupsAsCSV = $extraGroupsAsCSV.(empty($extraGroupsAsCSV)?"":",").$id;
        }
        if (!empty($extraGroupsAsCSV)) {
            try {
                $db = JFactory::getDbo();
                $db->getQuery(true);
                $db->setQuery('delete from #__user_usergroup_map where user_id = '.$user_id.' and group_id in ('.$extraGroupsAsCSV.')');
                if (!$db->query()) {
                    error_log("Error: Assign a joomla user to the amdinistrator group: '" . (isset($user_id)?$user_id:"<null>") . "'; " . $db->getErrorMsg());
                }
            } catch (Exception $e) {
                error_log("Error: Assign a joomla user (user_id) to the amdinistrator group: '" . (isset($user_id)?$user_id:"<null>") . "'; " . $e->getMessage());
            }
        }

    }

    public static function resizeImage($filepath, $filename, $newwidth, $newheight) {
        /*
        Resize image to less than n,m  (for example 300, 300) widht or height proportionally.
        */
        if(!function_exists('imagecreatetruecolor')){
            error_log('PHP Function imagecreatetruecolor() missing. API Portal prerequisite library php-gd not available or not configured. Image will not not resized.');
            return;
        }

        list($width, $height) = getimagesize($filepath);
        if($width > $height && $newheight < $height){
            $newheight = $height / ($width / $newwidth);
        } else if ($width < $height && $newwidth < $width) {
            $newwidth = $width / ($height / $newheight);
        } else {
            $newwidth = $width;
            $newheight = $height;
        }

        $filepath = stripslashes($filepath);
        $extension = self::getExtension($filename);
        $extension = strtolower($extension);

        $thumb = imagecreatetruecolor($newwidth, $newheight);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transp = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0,0,  $transp);
        imagesavealpha($thumb, TRUE);

        $source = null;
        if($extension=="jpg" || $extension=="jpeg" )
        {
            $source = imagecreatefromjpeg($filepath);
        }
        else if($extension=="png")
        {
            $source = imagecreatefrompng($filepath);
        }
        else
        {
            $source = imagecreatefromgif($filepath);
        }

        // Check whether the image is corrupted
        if ($source===false) {
            // Image is corrupted here. Throws error and prevent request to the server
            throw new Exception(JText::_('COM_APIPORTAL_CORRUPTED_IMAGE'));
        }
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

        if($extension=="jpg" || $extension=="jpeg" )
        {
            imagejpeg($thumb,$filepath);
        }
        else if($extension=="png")
        {
            imagepng($thumb,$filepath);;
        }
        else
        {
            imagegif($thumb,$filepath);
        }

        imagedestroy($thumb );
        imagedestroy($source );
    }

    public static function getExtension($str) {
        $i = strrpos($str,".");
        if (!$i) { return ""; }
        $l = strlen($str) - $i;
        $ext = substr($str,$i+1,$l);
        return $ext;
    }

    /**
     * Clean string/array for save rendering in view
     * @param $input
     * @param bool $strict
     * @param bool $stripTags
     * @param string $allowedTags
     * @return array|mixed|string
     */
    public static function cleanHtml($input, $strict = false, $stripTags = false, $allowedTags = '')
    {
        if (is_string($input) || is_numeric($input)) {
            if ($stripTags) {
                $input = self::strictCleanHtml($input);
                $input = strip_tags(trim($input), $allowedTags);
                $input = htmlentities($input);
            } else {
                $input = htmlentities(trim($input));
            }
        } else if (is_array($input)) {
            $input = array_map("trim", $input);
            if ($stripTags) {
                $input = array_map(function($n) use ($allowedTags) {
                    $n = strip_tags(trim($n), $allowedTags);
                    $n = self::strictCleanHtml($n);
                    return htmlentities($n);
                }, $input);
            } else {
                $input = array_map("htmlentities", $input);
            }
        } else if (is_object($input)) {
            error_log('Error. Trying to clean object');
        }

        if ($strict) {
            $input = self::strictCleanHtml($input);
        }

        return $input;
    }

    /**
     * Helper method for cleanHtml.
     * If strict is set to true only matched
     * characters are allowed.
     * @param $input
     * @return array|mixed
     */
    public static function strictCleanHtml($input)
    {
        if (is_string($input) || is_numeric($input)) {
            $input = preg_replace('/[^\p{L}0-9_:.@\s\-();%&\\\\?$+#[]<>{}!^\*]+/u', '', $input); // /[^a-zA-Z\p{Cyrillic}0-9\s\-]+/u
        } else if (is_array($input)) {
            $input = array_map(function($n) {
                return preg_replace('/[^\p{L}0-9_:.@\s\-();%&\\\\?$+#[]<>{}!^\*]+/u', '', $n);
            }, $input);
        } else if (is_object($input)) {
            error_log('Error! Trying to clean object.');
        }

        return $input;
    }

    /**
     * SubStr with custom length
     * @param $string
     * @param $sub
     * @param $ifEmpty
     * @return string
     */
    public static function subStrCustom($string, $sub, $ifEmpty='-')
    {
        if ($sub < 1 || $sub > 1000) {
            error_log('Error! Trying to substr very large string.');
        } else {
            if (strlen($string) > 0) {
                return substr($string, 0, $sub) . ' ...';
            }
        }

        return $ifEmpty;
    }

    /**
     * Get Set-Cookie headers from session variable
     * and return them ready for sending.
     * @param $headers
     * @param $session
     * @return array
     */
    public static function setCookieHeaders($session, $headers=null)
    {
        $cookieContent = null;
        $headerContent = null;
        $authCookies = $session->get(ApiPortalSessionVariables::AUTH_COOKIES_FORM_MANAGER);
        if (!empty($authCookies) && is_array($authCookies)) {
            $authCookiesLength = count($authCookies) - 1;
            for ($i = 0; $i <= $authCookiesLength; $i++) {
                if ($i < $authCookiesLength) {
                    $cookieContent .= $authCookies[$i] . '; ';
                } else {
                    $cookieContent .= $authCookies[$i];
                }
            }
        }

        if (is_array($headers) && !empty($headers)) {
            $headerContent = implode($headers);
        }

        if ($headerContent) {
            return array('Cookie: ' . $cookieContent, $headerContent);
        }

        return array('Cookie: ' . $cookieContent);
    }

    /**
     * When API Manager user tries to sign in API Portal but there is already a Joomla (admin) user with that username
     * a conflict occur
     * Logs a simple message which explains why the conflict is happening
     * @param string $userName
     */
    public static function logUserConflictMessage($userName = '')
    {
        JLog::addLogger(
            array(
                // Sets file name
                'text_file' => 'com_apiportal.reports.txt'
            ),
            // Sets messages of all log levels to be sent to the file
            JLog::ALL,
            // The log category/categories which should be recorded in this file
            // In this case, it's just the one category from our extension, still
            // we need to put it inside an array
            array('com_apiportal_reports')
        );
        JLog::add(JText::sprintf('COM_APIPORTAL_REGISTRATION_USER_CONFLICT_MSG', $userName), JLog::INFO, 'com_apiportal_reports');
    }

	/**
	 * Function to make pages as 404 not found in PUBLIC API Mode
	 * @return boolean
	 */
	public static function publicAPiPagesNotFoundforUser()
	{
		$uri = & JFactory::getURI();
		$pageURL = $uri->toString();
		$allowedPage = true;
		 
		//Below pages not allowed in public mode
		$parameter ="/monitoring|users|profile|create|profile-menu|(apps\/application\/edit\/*)|(apps\/application\/view\/(metrics\/*))/";

		// Check for Not allowed pages  
		preg_match($parameter, $pageURL, $matches);
			if(count($matches)>0)
		{
				$allowedPage = false;
			
		}
		return $allowedPage;
	}

	/**
	 * Common function to make tab hidden if Public API mode is enabled and credentials are correct
	 * @return string
	 */
	public static function hasHiddenTabforPublicUser()
	{
		$action="";
		if(JFactory::getSession()->get('PublicAPIMode',0) ==1)
		{
			$action = "style='display:none'";
		}
		 
		return $action;
	}


	/**
	 * Method to show Mega Menu in Public API mode
	 * @param string $menulink
	 * @return boolean
	 */
	public static function isMenuAllowedInPublicMode($menulink)
	{
		$allowed=false;
		$PublicAPIAllowedMeneTypeLinks = array(
				'index.php?option=com_apiportal&view=apicatalog',
				
		);
		
		if(in_array($menulink, $PublicAPIAllowedMeneTypeLinks)){
			$allowed = true;
		}
		
		return $allowed;
	}


	/**
	 * This method tries to find out what is the request method.
	 * By default it checks from the global $_SERVER array or search for passed post param.
	 * @param        $jInput
	 * @param string $postValue
	 *
	 * @return bool
	 * @since 7.5.2
	 */
    public static function isPost($jInput, $postValue = 'submitted')
    {
	    $isPostValue = $jInput->post->get($postValue, false, 'RAW');
	    if ((isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') || $isPostValue) {
		    return true;
	    }

	    return false;
    }
    
    /**
     * This function will  convert timestamp into date in given format
     * @param string $timestamp
     * @param string $format
     * @return string
     * 
     */
    public static function  convertDateTime($timestamp, $format)
	{
		if(strlen($timestamp)>10) { 
    		$timestamp = $timestamp / 1000;
    	}
		$unFormatDate = JFactory::getDate(date('y-m-d H:i:s e',$timestamp));
		return   $unFormatDate->format($format);
	}

	/**
	 * Method check whether application in Public API Mode or not 
	 * with correct Account Login name and password
	 * If credentials are incorrect storing error message in com_apiportal.txt file.
	 */

	public static function PublicUserLogin()
	{

		$config = new ApiPortalConfiguration();
		$PublicAPIModeFlag = $config->getPublicApi();

		if($PublicAPIModeFlag == 1 && !JFactory::getSession()->get('RegularUserMode') ==1){
				
			$allowedPage = self::publicAPiPagesNotFoundforUser();
			if(!$allowedPage)
			{
				JError::raiseError(404, "Message");
			}
				
			if(JFactory::getSession()->get('PublicAPIMode',0) ==1) return ;
			$credentials = array();
			$credentials['username']  =  $config->publicApiAccountLoginName();
			$credentials['password']  =  base64_decode($config->publicApiAccountPassword());
				
			$options = array();
			//$options['return'] = $data['return'];
				
			$app = JFactory::getApplication();
				
			if (true === $app->login($credentials, $options)) {

				JFactory::getSession()->set('PublicAPIMode', 1);

				// Success
				//$app->redirect(JRoute::_($app->getUserState('users.login.form.return'), false));
			} else {
				// Failed
				$script = new com_APIPortalInstallerScript();
				JLog::add(JText::_('COM_APIPORTAL_PUBLIC_API_AUTHENTICATION_FAILED'), JLog::ERROR, ERROR_COM_APIPORTAL);
				//$app->setUserState('users.login.form.data', $data);
				//ApiPortalHelper::displayView($viewname, $layout, null, $this, $model, $format, $document);
			}
		}else{
			JFactory::getSession()->set('PublicAPIMode', 0);
				
		}
	}
	
	/**
	 * This function shows the menus that need to be displayed in PUBLIC API mode
	 * @param string $id
	 * @return string
	 * 
	 * */
	public static function getMenuRecord($id)
	{
		$db = JFactory::getDbo();

		$menuId = $db->Quote($id);

		$db->getQuery(true);
		$db->setQuery('SELECT * FROM #__menu WHERE id = '.$menuId);
		if (!$db->query()) {
			throw new Exception($db->getErrorMsg());
		}
		$row = $db->loadAssocList();
		if (isset($row)) {
			
			return $row;
		} else {
			return false;
		}
	}
	
	/**
	 * Get Params Value from Menu Table
	 * @param string $id
	 * @return string
	 */
	public static function getParams($id)
	{
		$value = self::getMenuRecord($id);
		if (isset($value[0]['params'])){
		$result = json_decode($value[0]['params'], true);
		}
		return $result;
	}
	
	
	/**
	 * Get Client SDK Value from Menu Params field using Menu Id
	 * @param string $id
	 * @return boolean
	 */
	public static function getClientSdkValue($id)
	{
		$clientSdk = false;
		$result=self::getParams($id);
		if($result['clientSdk'])
		{
			$clientSdk = true;
		}
		return $clientSdk;
	}
	
	/**
	 * Get Swagger Value from Menu Params field using Menu Id
	 * @param string $id
	 * @return boolean
	 */
	public static function getSwaggerValue($id)
	{
		$swagger = true;
		$result=self::getParams($id);
		if(isset($result['swagger']))
		{
			return $result['swagger'];
		}
		return $swagger;
	}
	
	/**
	 * Get Menus Params Value for Masthead title/slogan
	 * @param string $id
	 * @return string
	 */
	public function getMenuParamsValue($id)
	{
		$value=self::getParams($id);
		$result=array();
		$result['masthead-title'] = $value['masthead-title'];
		$result['masthead-slogan']= $value['masthead-slogan'];
		
		return $result;
	}

	/**
	 * This function will check for the Inline try it option values for 
	 * Each menu entry
	 * @param Number $id
	 * @return boolean
	 */
	public static function getEnableInlineTryIt($id)
	{
		$result=self::getParams($id);
		$enableInlineTryIt = $result['enableInlineTryIt'];
		if($enableInlineTryIt)
		{
			if($enableInlineTryIt == 1){
				return true;
			}else if($result['enableInlineTryIt'] == 2){
				
				if(JFactory::getSession()->get('PublicAPIMode') == 1) return false;// No for Public API mode
				else return true;
			}else{
				return false;
			}
			
		}
		return true;
	}
	

}

function startsWith($needle, $haystack) {
    return preg_match('/^' . preg_quote($needle, '/') . '/', $haystack);
}

function endsWith ($needle, $haystack) {
    return preg_match('/' . preg_quote($needle, '/') . '$/', $haystack);
}