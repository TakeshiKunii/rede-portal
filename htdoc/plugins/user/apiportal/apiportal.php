<?php
defined('_JEXEC') or die;

jimport('joomla.application.component.helper');

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once JPATH_SITE.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'apiportal.php';

class PlgUserApiPortal extends JPlugin
{
    const prevent_apiadmin_name_change = true;
    const apiadmin_name = 'apiadmin';
    const apiadmin_role = 'admin';

    /**
     * Method to handle the "onUserBeforeSave" event. Data can not be changed, only validated.
     *
     * @param   array  $previousData - The currently saved data for the user.
     * @param   bool   $isNew - True if the user to be saved is new.
     * @param   array  $futureData - The new data to save for the user.
     *
     * @return  bool   True to allow the save process to continue, false to stop it.
     */
    function onUserBeforeSave($previousData, $isNew, $futureData) {
        /*
         * DONE: Check if the Joomla 'Name', 'Username' or 'Email' has changed, and synchronize changes back
         * to the API Manager. Also, change the entries in the 'apiportal_user' table to the new value(s).
         *
         *
         * If the update fails, set an error message and return false.
         */
        if (! $isNew) {  
            // Get application instance
            $app = JFactory::getApplication();
            if ($this->isApiPortalUser($futureData, $portalUserId, $joomlaUserId)) {
                $userdetails = ApiPortalHelper::getUser($portalUserId);
                if (isset($futureData) &&  isset($userdetails)) {
                    if (  
                        (!is_null($futureData['name']) && isset($userdetails->name) && ($futureData['name']!==$userdetails->name))
                        ||
                        (!is_null($futureData['email']) && isset($userdetails->email) && ($futureData['email']!==$userdetails->email))
                        ||
                        (!is_null($futureData['username']) && isset($userdetails->loginName) && ($futureData['email']!==$userdetails->loginName))
                    ) {
                        // Do the update request
                        try {
                            $oldname = $userdetails->name;
                            // Check if user is not the API Manager/Portal super user 'apiadmin'. We must prevent his username update
                            if (self::prevent_apiadmin_name_change && strtolower ($userdetails->name)===self::apiadmin_name && strtolower ($userdetails->role===self::apiadmin_role)) {
                                if ($userdetails->name!==$futureData['name']) {
                                    throw new Exception (JText::_('COM_APIPORTAL_CHANGE_APIADMIN_NAME_FAILED'));
                                }
                            } else {
                            // Not a API Portal super user.. so go ahead
                                $userdetails->name = $futureData['name'];
                            }
                            $userdetails->email = $futureData['email'];
                            // Line below will throw a Runtime expeption if update request failes. 
                            // It's message will be shown to the user as warning
                            ApiPortalHelper::updateUser($userdetails, $oldname!==$userdetails->name && !$app->isAdmin() /* If it is not an admin session and name is changed - do relogin.*/);
                        } catch (Exception $e) {
                            // Set wanring message
                            $app->enqueueMessage(JText::_('COM_APIPORTAL_ADMIN_USER_UPDATE_FAILURE') . " (".$e->getMessage().")", 'error');
                            // Execute following to revert back the old values.
                            $app->setUserState('com_users.edit.profile.data', false);
                            // Redirect back to the edit profile
                            if(!$app->isAdmin()) {
                                $app->redirect(self::getRedirectURL());
                            }
                            // Return failure state
                            return false;
                        }
                    }
                }
            }
        }
      
        return true;
    }

    /**
     * Method to handle the "onUserBeforeSave" event.
     *
     * @param   array   $user - An associative array of the columns in the user table.
     * @param   boolean $isnew - Boolean to identify if this is a new user.
     * @param   boolean $success - Boolean to identify if the store was successful.
     * @param   string  $msg - Error message if store failed.
     *
     * @return  bool   True to allow the save process to continue, false to stop it.
     * 
     *
     */
    
    function onUserAfterSave ($user, $isnew, $success, $msg)    
    {
        $app    = JFactory::getApplication();
        if (!$success) {
            // Redirect back to the edit screen.
            if(!$app->isAdmin()) {
                $app->redirect(self::getRedirectURL());
            }
            return false;
        }
        //If update succeeds, then update the custom table
        if (!$isnew) {
            // Do update of some attributes/params once we already have object persisted
            
            // Try to get pid as User Object parameter. 
            // If user is ralted to the API Manager User, pid shoudl contain API Manager User Id
            $pid = null;
            $userparams = $user['params'];
            if (isset($userparams)) {
                try {
                    $paramsObj = json_decode($userparams);
                    $pid = isset($paramsObj->pid) ? $paramsObj->pid : null;
                } catch (Exception $ex) {}
            }
            
            // Now get fresh user attributes from API Manager Portal
            $userDetails = ApiPortalHelper::getUser($pid);
            
            // prepare Terms&Conditions to be stored #__apiportal_user 
            $tcAccepted = 0;

            if (isset($userDetails->tags->tcAccepted) ||
                (isset($userDetails->tags->TermsAndConditionsAccepted) &&
                    ($userDetails->tags->TermsAndConditionsAccepted == 'on' ||
                        $userDetails->tags->TermsAndConditionsAccepted == true))){
                $tcAccepted = 1;
            } else if (isset($userDetails->tcAccepted) || (isset($userDetails->TermsAndConditionsAccepted) &&
                    ($userDetails->TermsAndConditionsAccepted == 'on' || $userDetails->TermsAndConditionsAccepted == true))) {
                $tcAccepted = 1;
            }
            // So, get Joomla user attributes as well
            $userObj = new JUser(JArrayHelper::getValue($user, 'id'));

            // Check if we realy got retrieved the API Manager user attributes
            if (isset($userDetails) && $userDetails!=null) {
                // WE GOT THEM.. update table which contains API Manager User details in joomla
                ApiPortalHelper::saveApiUser($userDetails->id, $userDetails->email, $userDetails->name, $userDetails->loginName, $userObj->id, $tcAccepted);
                // Now decide should we update some of the Joomla User Object attribs
                if (
                        // if 'username' is not same as the 'name'
                        JArrayHelper::getValue($user, 'username') !== $userDetails->loginName
                        || 
                        // If password is not cleaned out
                        $this->isApiPortalUser($userObj, $portalUserId, $joomlaUserId) && !($userObj->get("password","")==="" || $userObj->get("password","")==="#")
                ) {
                    // Make 'username' same as the 'name' in JUser object
                    $userObj->set('username', JArrayHelper::getValue($user, 'email'));
                    // Not sure if it is needed, but update the array passed as param here
                    $user['username'] = JArrayHelper::getValue($user, 'email');
                    /*
                     * Done: Null out the password - required by design to not store password ot its hash
                     *
                     */
                    $userObj->set("password","#");
                    // Save Joomla User object
                    if (!$userObj->save()) {
                        // if save failes, add a log entry
                        error_log("Caught Exception: " . $userObj->getError());    
                    }
                }
            }  
        }

        // Now, decide should we update the password of the API Manager User
        $password = JArrayHelper::getValue($user, 'password_clear');
        // Check if user has provided a new password
        if ($password) {
            // New password is provided
            // Change password via API Manager only if this is an 'API Portal' user.
            if ($this->isApiPortalUser($user, $portaluserId, $joomlaUserId)) {
                try {
                    ApiPortalHelper::changePassword($portaluserId, $password);
                } catch (Exception $e) {
                    // If the update fails, set an error message and return false.
                    // Set wanring message
                    $app->enqueueMessage(JText::_($e->getMessage()), 'error');

                    // Execute following to revert back the old values.
                    $app->setUserState('com_users.edit.profile.data', false);

                    // Redirect back to the edit profile
                    if(!$app->isAdmin()) {
                        $app->redirect(self::getRedirectURL());
                    }

                    // Return failure state
                    return false;
                }
            }
        }
        // All is right. Return true for successful update
        return true;
    }

    /**
      * This method should handle any login logic and report back to the subject
      *
      * @param   array  $user     Holds the user data
      * @param   array  $options  Array holding options (remember, autoregister, group)
      *
      * @return  boolean  True on success
      *
      * @since   1.5
      */
    public function onUserLogin($user, $options = array())
    {
        $user_groups = null; //just in case
        // If it is a first attempt to login with API Portal Manager/Admin user and it is a Joommla Admin Login attempt, create this user directly in SQL DN
        $existsInJoomla = array_key_exists("existsInJoomla" , $user)?$user["existsInJoomla"]:null;
        $action = array_key_exists("action" , $options)?$options["action"]:null;
        $isAdminLoginAction = isset($action) && endsWith("login.admin", $action);
        if (ApiPortalHelper::hasAdminRole() ) {
            if (!$existsInJoomla) {
                $name = array_key_exists ( "name" , $user )?$user["name"]:null;
                $username = array_key_exists ( "username" , $user )?$user["username"]:null;
                $email = array_key_exists ( "email" , $user )?$user["email"]:null;
                $user_id = ApiPortalHelper::makeJoomlaAccount($name, $username,  $email);
            } else /*if()*/ {
               $user_id = array_key_exists ( "user_id_jm" , $user )?$user["user_id_jm"]:null;
               $user_groups = array_key_exists ( "user_groups" , $user )?$user["user_groups"]:null;
            }
            ApiPortalHelper::assignJoomlaUserToAdministratorGroup($user_id, $user_groups);
        } if (ApiPortalHelper::hasGroupAdminRole() && !$isAdminLoginAction) {
            if (!$existsInJoomla) {
                if(array_key_exists ("name" , $user)){
                    $name = $user["name"];
                }
                elseif(array_key_exists ("fullname" , $user)){
                    $name = $user["fullname"];
                }
                else{
                    $name = null;
                }
                $username = array_key_exists ( "username" , $user )?$user["username"]:null;
                $email = array_key_exists ( "email" , $user )?$user["email"]:null;
                $user_id = ApiPortalHelper::makeJoomlaAccount($name, $username,  $email);
            } else /*if()*/ {
               $user_id = array_key_exists ( "user_id_jm" , $user )?$user["user_id_jm"]:null;
               $user_groups = array_key_exists ( "user_groups" , $user )?$user["user_groups"]:null;
            }
            ApiPortalHelper::assignJoomlaUserToManagerGroup($user_id, $user_groups);
            
        } else {
            if ($existsInJoomla && !ApiPortalHelper::hasAdminRole() && (!ApiPortalHelper::hasGroupAdminRole() || ApiPortalHelper::hasGroupAdminRole() && $isAdminLoginAction)) {
                $user_id = array_key_exists ( "user_id_jm" , $user )?$user["user_id_jm"]:null;
                $user_groups = array_key_exists ( "user_groups" , $user )?$user["user_groups"]:null;
                ApiPortalHelper::assignJoomlaUserToRegisteredGroupOnly($user_id, $user_groups);
            }
        }
    }

    /**
     * Method to handle the "onUserAfterLogin" event.
     *
     * @param   array  $options - Contains login request details, includin Joomla User Objecgt (JUser).
     */
    public function onUserAfterLogin($options = array())
    {
        // Once user is logged in, update the pid (API Manager User ID) parameter of the Joomla user.
        $session    = JFactory::getSession();
        // Get logged user poral id or null for non-portal users from the session
        $pid = $session->get(ApiPortalSessionVariables::MANAGER_USER_ID);

        $userObj    = $options["user"];
        $userObjPid = $userObj->getParam("pid");

        if (isset($pid)) {
            // We logged in successfully with the API Manager/Portal user, so update Joomla user param pid = API Manager/Portal user's ID
            if($pid!==$userObjPid) {
                $userObj->setParam("pid", $pid);
                $userObj->set("password", "#");
                $userObj->save();
            }
        } else {
            if (isset($userObjPid)) {
                // We logged in successfully with NON API Manager/Portal user, so update Joomla user param pid = null in order to break any wrong relations with API Manager/Portal users 
                $userObj->setParam("pid", null);
                $userObj->save();
            }
            $action = array_key_exists("action" , $options)?$options["action"]:null;
            $isAdminLoginAction = isset($action) && endsWith("login.admin", $action); 
            if ($isAdminLoginAction) {
                // Get application instance
                $app = JFactory::getApplication();
                // Set wanring message
                $app->enqueueMessage(JText::_('COM_APIPORTAL_ADMIN_IS_NOT_A_PORTAL_MANAGER'), "warning");
            }
        }
    }

    /**
     * Remove all user information for the given user ID
     * Method is called after user data is deleted from the database
     *
     * @param $user
     * @param $success
     * @param $msg
     * @return bool
     * @throws Exception
     */
    function onUserAfterDelete($user, $success, $msg) {
        $result = true;
        $paramsObj = json_decode($user['params']);
        //These vars have to be declared
        $email = null;
        $name = null;
        $user_id_jm = null;

        if (!$success) {
            $result = false;
        } else {
            //If update succeeds, then update the custom table
            $pid = null;
            $userparams = $user['params'];
            if (isset($userparams)) {
                try {
                    $paramsObj = json_decode($userparams);
                    $pid = isset($paramsObj->pid) ? $paramsObj->pid : null;
                } catch (Exception $ex) {}
            }

            if (isset($pid) && ApiPortalHelper::laodApiUser($pid, $email, $name, $user_id_jm)) {
                // Don't delete the 'apiadmin' user on the API Manager
                if ($name == 'apiadmin' || $email == 'apiadmin@localhost') {
                    // Just delete the local api_portal user entry
                    ApiPortalHelper::deleteApiPortalUserEntry($pid);
                } else {
                    // DONE: Verify that delete was successful: If it fails, set an error message and return false.
                    try {
                        // Line below will throw a Runtime expeption if delete request failes.
                        // It's message will be shown to the user as warning
                        if ($paramsObj && !isset($paramsObj->keepInApiManager)) {//this is a flag for not deleting from api manager
                            ApiPortalHelper::deleteUser($pid, true);
                        }
                        ApiPortalHelper::deleteApiPortalUserEntry($pid);
                    } catch (Exception $e) {
                        // Get application instance
                        $app = JFactory::getApplication();
                        // Set wanring message
                        $app->enqueueMessage(JText::_('COM_APIPORTAL_ADMIN_USER_DELETE_FAILURE') . " (" . $e->getMessage() . ")", 'warning');
                        // Return failure state
                        $result = false;
                    }
                }
            }
            
            ApiPortalHelper::purgeUnassignedApiPortalUserEntries();
            
        }
        return $result;
    }

	/**
	 * Triggered on Joomla! logout
	 * It's used for normal logout and with SSO
	 *
	 * @param $credentials
	 * @param $options
	 * @since 7.5.3
	 * @return boolean
	 */
    public function onUserLogout($credentials, $options)
    {
	    // This is specific for SSO logout
	    // When the user click out logout button this event is triggered
	    // And ones again on SSO logout is complete because we stop the chain in SSO logout to redirect the user to the IDP
	    if (isset($options['bypassAPIManager']) && $options['bypassAPIManager'] == true) {
		    return true;
	    }

	    // Send a logout request to the API Manager
	    ApiPortalHelper::doDelete(ApiPortalHelper::getVersionedBaseFolder() . '/login');

	    // If we have enabled SSO send a required get request to the API Manager
	    // It will return a response with html and javascript - to redirect the user to the IDP
	    // There is no Location header so we have to render this response to the user (similar for the SSO login)
	    if (true === (bool)JFactory::getSession()->get(ApiPortalSessionVariables::IS_SSO_ON)) {
		    // Send the request
		    $response = ApiPortalHelper::doGet(ApiPortalHelper::getVersionedBaseFolder() . '/sso/externallogout');
		    // Print the response
		    // This will redirect the user to the IDP and it will send us a POST request which is handled by the
		    // SSO controller. The controller do what it has to do and then triggers Joomla logout again because here
		    // we stop the logout chain and the user is still logged in the API Portal. The second logout has a parameter
		    // bypassAPIManager set to true - that's why we check for it in the beginning and return true - to continue
		    // with the Joomla! logout chain and to finish the hole logout process.
		    print_r($response);
		    // Exit the application
		    jexit();
	    }

	    return true;
    }

    /* 
     * Helper method - compose a URL to be hit in case of user/profile editing failure
     */
    private static function getRedirectURL() {
        $app    = JFactory::getApplication();
        $input = JFactory::getApplication()->input;

        // get current option, view, layout and id...
        $option = $input->get('option'); 
        $view = "profile";//"user"; 
        $layout = "edit";//$input->get('layout'); 
        $id = $input->get('id'); 
        $user_id = $input->get('user_id'); 

        //should be article, categories, featured, blog...
        $redirectUrl = JRoute::_(
                'index.php?option='.$option.
                '&view='.$view.
                (isset($layout) && $layout!=""?"&layout=".$layout:"").
                (isset($id) && $layout!=""?"&id=".$id:"").
                (isset($user_id) && $layout!=""?"&user_id=".$user_id:""), 
                false
        );
        return $redirectUrl;
    }
    
    private function isApiPortalUser($user, &$portalUserId , &$joomlaUserId ) {
        $portalUserId = null;
        $joomlaUserId = null;
        
        $username = is_string($user)?$user:null;
        $userarray = is_array($user)?$user:null;
        $userobj = $user instanceof JUser?$user:null;
        
        if ($username!=null) {
            $db = JFactory::getDbo();
            $db->setQuery('SELECT user_id FROM #__apiportal_user WHERE email = '.$db->Quote($username));
            try {
                $db->query();
            } catch (Exception $e) {
                // No exceptions should leak out into the browser
                error_log("Caught Exception: " . $e->getMessage());
                return false;
            }
            if ($db->getNumRows() == 1) {
                $row = $db->loadRow();
                $portalUserId = $row[0];
                $joomlaUserId = $row[1];
                return isset($portalUserId);
            } 

            $db->setQuery('SELECT user_id, user_id_jm FROM #__apiportal_user WHERE name = '.$db->Quote($username));
            try {
                $db->query();
            } catch (Exception $e) {
                // No exceptions should leak out into the browser
                error_log("Caught Exception: " . $e->getMessage());
                return false;
            }
            if ($db->getNumRows() == 1) {
                $row = $db->loadRow();
                $portalUserId = $row[0];
                $joomlaUserId = $row[1];
                return isset($portalUserId);
            }

            return false;
        } else if (isset($userobj)) {
                $portalUserId = $userobj->getParam("pid");
                $joomlaUserId = $userobj->id;
                
                // !!! IMPORTANT !!! Prevent recognizing a wrong Joomla user as API Portal User
                $couldBeAPIPortalUser = ApiPortalHelper::couldBeAPIPortalUser($userobj->email, $userobj->name, $userobj->username);

                return $couldBeAPIPortalUser && isset($portalUserId);
        } else if (isset($userarray)) {
            $pid = null;
            $userparams = $userarray['params'];
            if (isset($userparams)) {
                try {
                    $paramsObj = json_decode($userparams);
                    $pid = isset($paramsObj) && isset($paramsObj->pid)?$paramsObj->pid:null;
                } catch (Exception $ex) {}
            }
            $portalUserId = $pid;
            $joomlaUserId = $userarray['id'];
            
            // !!! IMPORTANT !!! Prevent recognizing a wrong Joomla user as API Portal User
            $couldBeAPIPortalUser = ApiPortalHelper::couldBeAPIPortalUser($userarray['email'], $userarray['name'], $userarray['username']);
        
            return $couldBeAPIPortalUser && isset($portalUserId);
        } else {
            return false;
        }
    }

}
