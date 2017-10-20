<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.controller');
jimport('joomla.application.component.helper');
require_once JPATH_SITE.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'Sso.php';

class ApiPortalControllerSsoLogin extends JControllerLegacy
{
	// SSO Helper Class
	protected $ssoHelper;

	/**
	 * ApiPortalControllerSsoLogin constructor.
	 *
	 * @param array $config
	 * @since 7.5.3
	 */
	public function __construct(array $config)
	{
		// SSO Helper init
		$this->ssoHelper = new SSOHelper((new ApiportalModelapiportal()), JFactory::getSession(), ApiPortalHelper::getConnection(), JFactory::getApplication());

		parent::__construct($config);
	}

	/**
	 * Entry point for SSO login
	 *
	 * A SSO login starts from here. User must be pointed to this action - currently is /sso.
	 * There is configuration for that in admin panel
	 * @since 7.5.3
	 */
	public function sso()
	{
		// Interesting feature - display a view
		// but it doesn't stop the execution of the rest of the code
		// We need this view in case of a slow connection - the user will see
		// a loader/spinner instead of only blank page.
		// The view is not optimized - there is css on the page - but I think there is no need to make the page by best practises
		$view = $this->getView('ssologin', 'html');
		$view->setLayout('default');
		$view->display();

		if ($this->ssoHelper->isOn()) {
			// If SSO is on proceed to the flow and exit
			$this->ssoHelper->externalLogin();

			jexit();
		} else {
			// If SSO is not on redirect to the login page and display a warning
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('COM_APIPORTAL_SSO_DISABLED_ERROR'), 'warning');
			$this->setRedirect('/index.php?option=com_users&view=login');
			$this->redirect();
		}
	}

	/**
	 * 'Listener' for the SAML response from IDP
	 *
	 * A post request is made by the IDP to this endpoint with SAML body content.
	 * @since 7.5.3
	 */
	public function ssoExternalLoginPost()
	{
		// Proxy the SAML response to the API Manager
		if ($this->ssoHelper->sendLoginSamlToAPIManager($_POST)) {
			// All went ok and the user is logged - go to home page
			// But not exactly - first go to this location and do some magic - check logged() method in this class
			$this->setRedirect('/index.php?option=com_apiportal&task=ssologin.logged');
			$this->redirect();
		} else {
			// If problem occur redirect to login page and display msg
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('COM_APIPORTAL_SSO_LOGIN_ERROR'), 'error');
			$this->setRedirect('/index.php?option=com_users&view=login');
			$this->redirect();
		}
	}

	/**
	 * Logout 'Listener' for the SAML response from IDP
	 * In the logout process the IDP create a post request and this is the listener for it.
	 * It proxies the SAML responce the API Manager
	 * @since 7.5.3
	 */
	public function ssoExternalLogoutPost()
	{
		// Make a post request to the API Manager with SAML response from the IDP
		$response = ApiPortalHelper::doPost(ApiPortalHelper::getVersionedBaseFolder(). '/sso/externallogout/post', $_POST, CONTENT_TYPE_FORM);

		// The user is logged out set the session variable for logged SSO user to false
		JFactory::getSession()->set(ApiPortalSessionVariables::IS_SSO_LOGGED_USER, false);

		// Trigger (for the second time) Joomla logout and pass a param which will indicate to bypass the requests
		// for logout to the API Manager. (For details check the comments in onUserLogout in our user plugin)
		JFactory::getApplication()->logout(JFactory::getUser()->get('id'), ['bypassAPIManager' => true]);

		// If we have SAMLResponse in the body it's a remote logout and we need to execute the response body
		// The body is html/js and it will redirect the user to the right place
		if (!empty($response) && strpos($response, 'SAMLResponse') !== false) {
			print_r($response);
		} else {
			// This is the normal case when the user has logged out through the API Portal

			// Set the redirect value
			$this->setRedirect('/index.php?option=com_apiportal&task=ssologin.loggedout');

			// Redirect
			$this->redirect();
		}

	}

	/**
	 * This is needed because if the Joomla session timeouts the user must not be redirected to the login page
	 * He should be redirected to the sso path to start the SSO again. And for this when we don't have session
	 * (it's expired) we need another way to determine what is the type of the authentication. For now it's a cookie.
	 * In ssoExternalLoginPost() where the login happens it is not possible to set a cookie this is why we come here
	 * and set a cookie to mark the SSO authentication.
	 * @since 7.5.3
	 */
	public function logged()
	{
		// The user is logged with SSO - set a cookie for further usage
		// And check is the SSO is On because this is accessible path we don't want to set this cookie by accident
		if ($this->ssoHelper->isOn() &&
			!setcookie(API_PORTAL_AUTH_TYPE_SSO_COOKIE, API_PORTAL_AUTH_TYPE_SSO_COOKIE_VALUE, null, null, null, (boolean)$_SERVER['HTTPS'], true)) {
			// Log an error/warning in case of unsuccessful cookie creation
			error_log('[Warning] A Cookie in SSO flow was not be created! This may cause unexpected behavior.');
		}

		// Set the redirect value
		$this->setRedirect('/');
		// Redirect
		$this->redirect();
	}

	/**
	 * The same reason as logged()
	 * But this time for destroying the cookie
	 * @since 7.5.3
	 */
	public function loggedout()
	{
		// Delete this cookie we don't need it
		unset($_COOKIE[API_PORTAL_AUTH_TYPE_SSO_COOKIE]);
		setcookie(API_PORTAL_AUTH_TYPE_SSO_COOKIE, 'off', time()-3600, null, null, (boolean)$_SERVER['HTTPS'], true);

		// Redirect
		$this->setRedirect('/');
		$this->redirect();
	}

}