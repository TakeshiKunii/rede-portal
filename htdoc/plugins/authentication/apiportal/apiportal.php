<?php
defined('_JEXEC') or die;

jimport('joomla.application.component.helper');

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once JPATH_SITE.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'apiportal.php';
require_once JPATH_SITE.DS.'components'.DS.'com_apiportal'.DS.'models'.DS.'user.php';
require_once JPATH_SITE.DS.'administrator'.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'apiconfiguration.php';

class PlgAuthenticationApiPortal extends JPlugin
{
    const API_MANAGER_ADMIN_ROLE = 'admin';

    /**
     * This method should handle any authentication and report back to the subject
     *
     * @param   array   $credentials  Array holding the user credentials
     * @param   array   $options      Array of extra options
     * @param   object  &$response    Authentication response object
     *
     * @return  boolean
     */
    public function onUserAuthenticate($credentials, $options, &$response) {

        $session = JFactory::getSession();
        $response->type = 'ApiPortal';
        $response->email = null;
        $response->username = null;
        $response->fullname = null;

        // Authenticate against API Manager
        $username = array_key_exists('username', $credentials)?$credentials['username']:null;
        $password = array_key_exists('password', $credentials)?$credentials['password']:null;

		// Prepare the userDetails for the login
	    $userDetails = isset($options['userDetails']) ? (object) $options['userDetails'] : true;

		if (!ApiPortalHelper::login($username, $password, $userDetails)) {
            $response->status = JAuthentication::STATUS_FAILURE;
            $lastLoginError = $session->get(ApiPortalSessionVariables::LAST_LOGIN_ERROR_FROM_MANAGER);
            $errorObj = null;
            try {
                $errorObj = json_decode($lastLoginError);
                $lastLoginError = isset($errorObj) && isset($errorObj->errors[0]) && isset($errorObj->errors[0]->message)?$errorObj->errors[0]->message:$lastLoginError;
            } catch (Exception $e) { }
            $response->error_message = JText::_('COM_APIPORTAL_AUTHENTICATION_FAILED') . (isset($lastLoginError)?' (' . $lastLoginError . ')':'');
            return false;
        }

        //sync user by username and email as these properties should be unique
        if (!APIPortalModelUser::syncByUsername($userDetails->loginName, $userDetails->id)||
            !APIPortalModelUser::syncByEmail($userDetails->email, $userDetails->id)){
            return false;
        }

        /**
         * Enable/Disable APIManager Admin Login in Front-end (Site)
         * Check if user is APIManager Admin User and if it's true
         * then check config 'allowAPIManagerAdminLogin' to stop or continue login process
         */
        if (JFactory::getApplication()->isSite()) {
            if ($userDetails->role == self::API_MANAGER_ADMIN_ROLE) {
                $config = new ApiPortalConfiguration();
                $allowAPIManagerAdminLogin = $config->getAllowAPIManagerAdminLogin();
                if (!$allowAPIManagerAdminLogin) {
                    $response->status = JAuthentication::STATUS_FAILURE;
                    $response->error_message = JText::_('COM_APIPORTAL_AUTHENTICATION_API_ADMIN');
                    return false;
                }
            }
        }

        /*
         * DONE: Don't use ALWAYS the API Manager userDetails to set the response values below _unless_ the user does
         * not already exist in the Joomla User table. What I saw happen was that if the $userDetails->email, $userDetails->loginName or
         * the $userDetails->name have been changed, then Joomla will try to create a new user and things do not
         * go well after that.
         */
        $portalUserId = $userDetails->id;
        $user_id_jm = null;

        // We do not allow joomla users that have joomla admin rights to login into API Portal
        $targetUserId = ApiPortalHelper::findJoomlaUserByUsername($userDetails->loginName);
        if ($targetUserId){
            $targetUserObj = new JUser(intval($targetUserId));

            if ($targetUserObj->authorise('core.login.admin') === true && $targetUserObj->get('password') != "#"){
                $response->status = JAuthentication::STATUS_FAILURE;
                $response->error_message = JText::_('COM_APIPORTAL_LOGIN_ADMIN_CONFLICT');

                // Logs a message
                ApiPortalHelper::logUserConflictMessage($targetUserObj->get('username'));

                return false;
            }
            // If the target user has no rights to login to joomla admin panel - delete it(we do not support such users)
            elseif ($targetUserObj->authorise('core.login.admin') != true && $targetUserObj->get('password') != "#" ){
                $targetUserObj->set('keepInApiManager',1);// do not try to delete the user form API Manager
                $targetUserObj->delete();
            }
        }
        if (!ApiPortalHelper::laodApiUser($portalUserId, $email, $name, $user_id_jm)) {

            // Try alternative way to find related joomla user
            $user_id_jm = ApiPortalHelper::findJoomlaUserByUsername($userDetails->loginName);
        }

        // Get Joomla User object
        $userObj = new JUser($user_id_jm);

        // Now, let we have JUser attributes remembered just for case
        // If JUser update fails, those Joomla user's name & email to identify correctly user
        $userObjPid = $userObj->getParam("pid");
        $name = $userObj->name;
        $username = $userObj->username;
        $email = $userObj->email;
        $apiportal_role = $userDetails->role;
        $existsInJoomla = false;

        // !!! IMPORTANT !!! Prevent recognizing a wrong Joomla user as API Portal User
        $userObj = ApiPortalHelper::couldBeAPIPortalUser($email, $userDetails->loginName, $username, $portalUserId, $userObjPid)?$userObj:null;

        $isFirstAPIAdminLoginAttempt = false;

        if ( isset($userObj) && isset($user_id_jm) && $user_id_jm!='0' && $userObj->id == $user_id_jm) {
            $existsInJoomla = true;

            // At this point we have recognized the Joomla user and its id

            // Now verify are they different than current email & name retrived from the API Manager and update joomla user if needed.
            if (
                $userObj->name!=$userDetails->name ||
                $userObj->username!=$userDetails->loginName ||
                $userObj->email!=$userDetails->email
            ) {
                // Not try to update the JUser
                $userObj->name = $userDetails->name;
                $userObj->username = $userDetails->loginName;
                $userObj->email = $userDetails->email;
                // Save attempt will fail for admin users
                $saved = $userObj->save();
                if ($saved) {
                    $name = $userDetails->name;
                    $username = $userDetails->loginName;
                    $email = $userDetails->email;
                }
            }
        } else {
            $existsInJoomla = false;
            // Shouldn't be a real case, but its possible to be if there is/are incorrect inherited record(s) in #__apiportal_user table
            $name = $userDetails->name;
            $username = $userDetails->loginName;
            $email = $userDetails->email;

            /*
            if ($userDetails->role==="admin") {
                try {
                    ApiPortalHelper::makeJoomlaAdminAccount($name, $username, $email);
                } catch (Exception $e) {
                    $response->error_message = JText::_($e->getMessage());
                }
            }
             */
        }

        // prepare Terms&Conditions to be stored #__apiportal_user 
        $tcAccepted = 0;
        // the same code exists in USER plugin too.
        // DO NOT forget to update it too if any change is needed
        if (isset($userDetails->tags->tcAccepted) ||
            (isset($userDetails->tags->TermsAndConditionsAccepted) &&
                ($userDetails->tags->TermsAndConditionsAccepted == 'on' ||
                    $userDetails->tags->TermsAndConditionsAccepted == true))){
            $tcAccepted = 1;
        } else if (isset($userDetails->tcAccepted) || (isset($userDetails->TermsAndConditionsAccepted) &&
            ($userDetails->TermsAndConditionsAccepted == 'on' || $userDetails->TermsAndConditionsAccepted == true))) {
            $tcAccepted = 1;
        }

        ApiPortalHelper::saveApiUser($portalUserId, $email, $name, $username, null, intval($tcAccepted));

        $response->email = $email;
        $response->username = $username;
        $response->fullname = $name;
        $response->password_clear = null;
        $response->status = JAuthentication::STATUS_SUCCESS;
        $response->error_message = '';
        $response->pid = ApiPortalHelper::getCurrentUserPortalId();
        $response->apiportal_role = $apiportal_role;
        $response->existsInJoomla = $existsInJoomla;
        $response->tcAccepted = $tcAccepted;
        if ($existsInJoomla) {
            $response->user_id_jm = $user_id_jm;
            $response->user_groups = $userObj->groups;
        }

        return true;
    }

}

