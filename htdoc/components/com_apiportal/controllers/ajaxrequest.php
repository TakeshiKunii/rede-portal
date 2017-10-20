<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.user.component.controller');

/**
 * Class ApiPortalControllerAjaxRequest
 * Use this for ajax requests
 * @since 7.5.1
 */
class ApiPortalControllerAjaxRequest extends JControllerLegacy
{
    /**
     * Only authorised users allowed
     * @return true/false
     */
    public function authorise($task)
    {
        // Make sure the session is valid before proceeding with tasks
        $jInput = JFactory::getApplication()->input;
        // search for ajax param to avoid direct output
        $isAjax = $jInput->get('ajax', null, 'INT');

        // If it's ajax add response code and custom header
        // And use then in javascript to redirect the user to login page
        if ($isAjax) {
            $redirect = $this->checkSession();
            if ($redirect) {
                http_response_code(302);
                // Custom header to recognize the request in js
                header('X-Apiportal-Auto: redirect');
                exit();
            }
        } else {
            // If it's not an ajax call use normal checkSession
            // It will redirect the user to login page
            ApiPortalHelper::checkSession();
        }

        return true;
    }

    /**
     * Check if the user session is expired.
     * Returns true if session is expired.
     * The method is similar to ApiPortalHelper::checkSession. Check it for more details.
     * @return bool|string
     * @throws Exception
     */
    private function checkSession()
    {
        $session = JFactory::getSession();
        if ($session->isActive()) {
            if (JFactory::getUser()->guest) {
                // User is not logged in
                return true;
            } else if (!ApiPortalHelper::isApiManagerUser()) {
                // Non API Manager logged in user
                return true;
            }
        } else {
            $session->destroy();
            return true;
        }

        return false;
    }

    /**
     * Ajax call form API test page
     * Load the swagger definition from API Manager
     * It uses curl chunks
     * @throws Exception
     */
    public function swaggerLoad()
    {
	    // Check for CSRF token
	    if (!JSession::checkToken('get'))
	    {
		    // CSRF Token is not valid
		    print_r(json_encode(['errorMsg' => JText::_('JINVALID_TOKEN')]));
		    exit();
	    }

        $session = JFactory::getSession();
        $jInput = JFactory::getApplication()->input;

        // Get the API name - needed for the request
        $apiName = $jInput->get('apiName', null, 'RAW');

        // Get the auth cookie from the session
        // If there is no cookie an empty cookie header will be returned
        // Better Error Handling for this method is needed
        $headers = ApiPortalHelper::setCookieHeaders($session);

        // Get the model
        $modelAjaxRequest = $this->getModel('ajaxrequest');

        // API name is a must
        if ($apiName) {
            // Catch errors and return as json
            try {
                // Init and set curl opts
                $conn = ApiPortalHelper::getConnection();
                $conn->curl_opts[CURLOPT_CONNECTTIMEOUT] = 60;
                $conn->curl_opts[CURLOPT_TIMEOUT] = 60;
                $conn->curl_opts[CURLOPT_RETURNTRANSFER] = false;
                $conn->curl_opts[CURLOPT_BUFFERSIZE] = 1024;
                // This callback could be moved to ApiPortalHelper (or other). Because it's not the first use of it.
                $conn->curl_opts[CURLOPT_WRITEFUNCTION] = [$modelAjaxRequest, 'curlChunk'];

                // Before send the request - clear session variable if is set
                $session->set(ApiPortalSessionVariables::PROXY_TRY_IT_BASE_PATH, null);

                // Prepare the url
                $path = ApiPortalHelper::getVersionedBaseFolder() . "/discovery/swagger/api" . '/' . rawurlencode($apiName);
                // Sent the request
                $conn->get($path, null, $headers);

                // Also return the headers from the response
                foreach ($conn->last_headers as $lastHeader) {

//	                if (isset($lastHeader['Transfer-Encoding'])) {
//		                unset($lastHeader['Transfer-Encoding']);
//	                } else if (isset($lastHeader['transfer-encoding'])) {
//		                unset($lastHeader['transfer-encoding']);
//	                }
					// Better to return only Content-Type header because it breaks if there are some headers for chunked encoding
	                if (isset($lastHeader['Content-Type'])) {
		                header('Content-Type: '.$lastHeader['Content-Type']);
//                                header('Content-Type: application/json');
	                } else if (isset($lastHeader['content-type'])) {
		                header('Content-Type: '.$lastHeader['content-type']);
	                } else {
		                header('Content-Type: application/json');
	                }

//                    foreach ($lastHeader as $head => $value) {
//                        header($head . ': ' . $value);
//                    }
                }
            } catch (Exception $e) {
                // Catch and return the exception
                print_r('Code: ' . $e->getCode() . ' Message: ' . $e->getMessage());
            }
        } else {
            // If no API name is detected
            print_r(json_encode(['errorMsg' => 'Error. API not found.']));
        }

        exit();
    }
}
