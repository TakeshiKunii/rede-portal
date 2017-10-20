<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.helper');

/**
 * Class ApiPortalCommonUtils
 *
 * @since 7.5.1
 */

// Define some global const
const API_PORTAL_AUTH_TYPE_SSO_COOKIE = 'apiportal_authentication';
const API_PORTAL_AUTH_TYPE_SSO_COOKIE_VALUE = 'sso_user';

class ApiPortalSessionVariables
{
    /* Session variable names */
    // For Parsing swagger definition in chunks
    const PROXY_TRY_IT_BASE_PATH = 'com_apiportal.proxyTryItBasePath';
    // com_apiportal.user.http.lastLoginError
    const LAST_LOGIN_ERROR_FROM_MANAGER = 'com_apiportal.lastLoginErrorFromManager';
    // com_apiportal.user.id
    const MANAGER_USER_ID = 'com_apiportal.managerUserId';
    // com_apiportal.user.http.lastStatus
    const LAST_STATUS_FROM_MANAGER = 'com_apiportal.lastStatus';
    // com_apiportal.user.http.lastHeaders
    const LAST_HEADERS_FROM_MANAGER = 'com_apiportal.lastHeaders';
    // com_apiportal.user.authCookie
    const AUTH_COOKIE_FROM_MANAGER = 'com_apiportal.authCookie';
    // com_apiportal.user.authCookies
    const AUTH_COOKIES_FORM_MANAGER = 'com_apiportal.authCookies';
    // com_apiportal.user.email - it was used for loginName but now it's separated
    const LOGIN_NAME = 'com_apiportal.loginName';
    // com_apiportal.user.password
    const LOGIN_PASSWORD = 'com_apiportal.loginPassword';
    // com_apiportal.user.email - this one is for real email
    const MANAGER_EMAIL = 'com_apiportal.managerEmail';
    // com_apiportal.user.role
    const MANAGER_ROLE = 'com_apiportal.managerRole';
    // com_apiportal.user.organization.id
    const MANAGER_ORG_ID = 'com_apiportal.managerOrgId';
    // com_apiportal.user.http.lastErrorMsg
    const LAST_ERROR_MSG_FROM_MANAGER = 'com_apiportal.lastErrorMsg';
    // com_apiportal.applications.view.layout
    const APPLICATION_VIEW_LAYOUT = 'com_apiportal.appViewLayout';
    // com_apiportal.user.create.data
    const USER_CREATE_DATA = 'com_apiportal.userCreateData';
    // com_apiportal.user.edit.data
    const USER_EDIT_DATA = 'com_apiportal.userEditData';
    // com_apiportal.registration.data
    const USER_REGISTRATION_DATA = 'com_apiportal.userRegistrationData';
    // com_apiportal.application.create.data
    const APP_CREATE_DATA = 'com_apiportal.appCreateData';
    // com_apiportal.application.edit.data
    const APP_EDIT_DATA = 'com_apiportal.appEditData';
    // com_apiportal.application.edit.new.users
    const APP_EDIT_NEW_USER = 'com_apiportal.appEditNewUser';
	// For SSO - mandatory header value
	const SSO_HEADER_VALUE = 'com_apiportal.ssoHeaderValue';
	// For SSO - mandatory header name
	const SSO_HEADER_NAME = 'com_apiportal.ssoHeaderName';
	// For SSO - is it enabled/disabled
	const IS_SSO_ON = 'com_apiportal.isSsoOn';
	// For SSO - if the current user is SSO
	const IS_SSO_LOGGED_USER = 'com_apiportal.isSsoLogged';
}