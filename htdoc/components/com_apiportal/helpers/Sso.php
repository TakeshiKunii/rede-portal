<?php
defined('_JEXEC') or die('Restricted access');

JLoader::import('SessionVariables', JPATH_SITE.'/components/com_apiportal/helpers');

/**
 * This class is for SSO login
 *
 * Here is the main logic for successful SSO
 * API Portal acts as a proxy between API Manager and IDP
 * @since 7.5.3
 */
class SSOHelper
{
	// Important header - a must in every request with SSO flow
	// This is it's name, the value is configurable from the admin panel
	const SSO_CLIENT_ID_HEADER_NAME = 'SSOClient-ID';

	// API Portal configuration
	protected $config;
	// Joomla! session object
	protected $session;
	// Pest lib for curl requests
	protected $pestConn;
	// Joomla application object
	protected $app;

	/**
	 * SSOHelper constructor.
	 *
	 * @param object $portalConfig
	 * @param object $session
	 * @param object $pest
	 * @param object $app
	 *
	 * @since 7.5.3
	 */
	public function __construct($portalConfig, $session, $pest, $app)
	{
		// Class dependencies
		$this->config = $portalConfig;
		$this->session = $session;
		$this->pestConn = $pest;
		$this->app = $app;
	}

	/**
	 * Base version of the API Manager API
	 * SSO needs v1.3 but API Portal used to use v1.2
	 * This wrapper is if we have to have different versions of the API
	 * (just in case)
	 *
	 * @return string
	 * @since 7.5.3
	 */
	public function apiBaseVersion()
	{
		// Get the version from API Portal Helper class - the only place where the version is set
		return ApiPortalHelper::getVersionedBaseFolder();
	}

	/**
	 * The entry point for SSO calls this method
	 * It prepare a request to the API Manager to start SSO flow
	 *
	 * @since 7.5.3
	 */
	public function externalLogin()
	{
		// Mandatory Header for the SSO flow
		// The value of the header is configurable form the admin panel
		$headers[self::SSO_CLIENT_ID_HEADER_NAME] = $this->config->getSsoEntityID();
		$this->pestConn->curl_opts[CURLOPT_RETURNTRANSFER] = TRUE;
		$this->pestConn->get($this->apiBaseVersion().'/sso/externallogin');
		// Send the request and output the response to the user
		// The response contains a HTML with JavaScript which redirect the user to the IDP page
		print_r($this->pestConn->last_response['body']);
	}

	/**
	 * After successful login IDP sends back a post request
	 *
	 * This method is called from the API Portal endpoint
	 * It proxies the response from the IDP to the API Manager
	 * It's a SAML in the response body
	 * @param array $data $_POST
	 *
	 * @return bool
	 * @since 7.5.3
	 */
	public function sendLoginSamlToAPIManager($data)
	{
		// To hold the cookie headers
		$authCookiesContent = [];
		$authSecondCookiesContent = [];

		// Don't have work here is SSO is disabled
		if (!$this->isOn()) {
			// User is not logged in
			error_log('[SSO] SSO is not turned ON. Can not use this.');
			return false;
		}

		// Mandatory header for SSO workflow
		// The value of the header is configurable form the admin panel
		$headers[self::SSO_CLIENT_ID_HEADER_NAME] = $this->config->getSsoEntityID();
		$this->pestConn->curl_opts[CURLOPT_RETURNTRANSFER] = TRUE;
		// Send SAML response from IDP to API Manager
		$this->pestConn->post($this->apiBaseVersion().'/sso/externallogin/post', $data);
		// We expect set-cookie header if everything is fine
		// So far user is almost logged
		// But use the approach for multi Set-Cookie header, we had this problem before when there is a load balancer
		// in front of the API Manager
		$cookies = $this->pestConn->getHeaders($this->pestConn->last_headers, 'Set-Cookie');

		// If we have cookies
		if (!empty($cookies)) {
			// Rearrange cookies
			foreach ($cookies as $cookie) {
				$authCookiesContent[] = $cookie;
			}

			// Set session variable with the cookies
			// They have to be available in the future requests to the API Mnanager
			$this->session->set(ApiPortalSessionVariables::AUTH_COOKIES_FORM_MANAGER, $authCookiesContent);
			// Now add the cookies to the request headers
			$headers = ApiPortalHelper::setCookieHeaders($this->session);
			// The mandatory SSO header
			$headers[] = self::SSO_CLIENT_ID_HEADER_NAME.': '.$this->config->getSsoEntityID();
			// Send the request to the API Manager to finish the login process
			$this->pestConn->get($this->apiBaseVersion().'/sso/externallogin', [], $headers);

			// Take APIMANAGERSESSION cookie from the response and save it for all future requests
			$secondCookies = $this->pestConn->getHeaders($this->pestConn->last_headers, 'Set-Cookie');
			if (!empty($secondCookies)) {
				// Rearrange cookies
				foreach ($secondCookies as $cookie) {
					$authSecondCookiesContent[] = $cookie;
				}

				$currentCookies = $this->session->get(ApiPortalSessionVariables::AUTH_COOKIES_FORM_MANAGER);
				$mergedCookies  = array_merge($currentCookies, $authSecondCookiesContent);
				$this->session->set(ApiPortalSessionVariables::AUTH_COOKIES_FORM_MANAGER, $mergedCookies);

				// We need user details so send a current user request
				$user = $this->pestConn->get(ApiPortalHelper::getVersionedBaseFolder() . '/currentuser', [], $headers);
				$user = json_decode($user, true);

				// Check if we actually have a user details
				if (!isset($user['id'])) {
					// User is not logged in
					error_log('[SSO] Did not receive current user response!');

					return false;
				}

				// At this point the user is logged against API Manager
				// We have to include the mandatory SSO header in all future requests so let's save it in the session
				$this->session->set(ApiPortalSessionVariables::SSO_HEADER_NAME, self::SSO_CLIENT_ID_HEADER_NAME);
				$this->session->set(ApiPortalSessionVariables::SSO_HEADER_VALUE, $this->config->getSsoEntityID());
				// Also set a variable for on/off SSO, separate session variable for this one because it can't be triggered
				// at any time from the admin panel, with a session variable we have saved current SSO status
				$this->session->set(ApiPortalSessionVariables::IS_SSO_ON, true);

				// Take the status for the logged user and save it in the session for further use
				// Status for if the user is SSO or normal login
				$this->session->set(ApiPortalSessionVariables::IS_SSO_LOGGED_USER, $user['authAttrs']['isSSOLogin']);

				// Ok, let's try to login the user in API Portal
				// To trigger the Joomla login process we are using build in functionality
				// Also send the user details for further usage
				if (true !== $this->app->login(['username' => $user['loginName'], 'password' => ''], ['userDetails' => $user])) {
					error_log('[SSO] Login in API Poltal and Joomla is not successful!');

					return false;
				}

				// User is logged in
				return true;
			}

			return false;
		} else {
			// User is not logged in
			error_log('[SSO] Did not receive cookie from the API Manager!');
			return false;
		}

	}

	/**
	 * Return the status of the SSO - On/Off
	 * It takes the result from the API Portal DB configuration
	 *
	 * @return bool
	 * @since 7.5.3
	 */
	public function isOn()
	{
		if ($this->config->getIsSsoOn()) {
			return true;
		}

		return false;
	}

	/**
	 * A helper function for getting the SSO Path from the DB
	 * In fact we have this method in router.php but if we want to use this there we need to include the class first
	 * That's way I don't use it there - it's too eraly stage and this class is not yet loaded.
	 * It also can be non-static in the future
	 * @return string
	 * @since 7.5.3
	 */
	public static function getSSOPath()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(['property', 'value']));
		$query->from($db->quoteName('#__apiportal_configuration'));
		$query->where($db->quoteName('property') . ' = '. $db->quote('ssoPath'));
		$db->setQuery($query);

		$result = $db->loadObject();
		if ($result) {
			return $result->value;
		}

		return 'sso';
	}
}