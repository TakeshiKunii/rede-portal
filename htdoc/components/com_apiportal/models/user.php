<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.user.component.modellist');
jimport('joomla.application.component.modelitem');

class APIPortalModelUser extends JModelList
{
    private $userdata;
    protected $item;
    const USER_TYPE_INTERNAL = 'internal';
    const USER_TYPE_EXTERNAL = 'external';
    const MANAGER_USER_ROLE_ADMIN = 'admin';
    const MANAGER_USER_ROLE_OADMIN = 'oadmin';

    private $resizeWidth = '300';
    private $resizeHeight = '300';

    public function getItem($isProfileAction = false) {
        if (!isset($this->item)) {
            $this->item = $this->getUser($isProfileAction);
        }
        return $this->item;
    }

    private function getUser($isProfileAction) {

        $item = new stdClass();
        $item->user = new stdClass();
        $item->isProfileAction = null;
        $editing = false;
        $creating = false;

        $layout = JRequest::getString('layout', '');
        $layout = ApiPortalHelper::cleanHtml($layout);
        $userId = JRequest::getString('userId', null);
        $userId = ApiPortalHelper::cleanHtml($userId);
        $entypoint = JRequest::getString('ep', null);
        $entypoint = ApiPortalHelper::cleanHtml($entypoint);
        $item->isProfileAction = $isProfileAction || ($entypoint==="profile-menu" && empty($userId));
        if ($item->isProfileAction) {
            $userId = ApiPortalHelper::getCurrentUserPortalId();
        }
        if ( empty($userId)) {
            // Check if we are creating a new user
            if ($layout != 'create') {
                error_log("Task getUser failed: missing User ID");
                $app = JFactory::getApplication();
                if ($layout == 'edit') {
                    $app->enqueueMessage(JText::_('COM_APIPORTAL_USER_EDIT_GENERAL_INVALID_USER', 'error'));
                } else {
                    $app->enqueueMessage(JText::_('COM_APIPORTAL_USER_VIEW_GENERAL_INVALID_USER', 'error'));
                }
                return null;
            }
            $creating = true;
        } else if ($layout == 'edit') {
            $editing = true;
        } else {
            $viewing = true;
        }

        // Get the API Manager configuration
        $item->config = ApiPortalHelper::getAPIMangerConfig();

        // Get the list of organizations visible to the current user: most likely just one unless Super User ('apiadmin')
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/organizations";
        $item->organizations = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        // Get User
        if (!$creating) {
            $path = ApiPortalHelper::getVersionedBaseFolder() . ($item->isProfileAction?"/currentuser":"/users/$userId");
            $item->user = ApiPortalHelper::doGet($path);

            if (ApiPortalHelper::isHttpError()) {
                // If we can't get the application, then there is no point in continuing...
                return null;
            } else {
                // Convert 0/1 to true/false
                if ($item->user->enabled) {
                    $item->user->enabled = 'true';
                } else {
                    $item->user->enabled = 'false';
                }

                //set user's timezone
                $item->user->timezone = JFactory::getUser()->getParam('timezone');
                if (empty($item->user->timezone)) {
                    $item->user->timezone = 'UTC';
                }

                //set user type
                $item->user->internal = $item->user->type == self::USER_TYPE_INTERNAL || $item->user->type === null ? true : false;
            }
        } else {
            $item->user->enabled        = "true";
            $item->user->name           = "";
            $item->user->email          = "";
            $item->user->phone          = "";
            $item->user->role           = "user";
            $item->user->organizationId = ApiPortalHelper::getActiveOrganizationId();
        }

        // Add 'Organization Name' to user
        $item->user->organizationName = ApiPortalHelper::getOrganizationName($item->user->organizationId, $item->organizations);

        // Get the list of shared apps for this user if the Organization is not 'Community'
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications?field=userid&op=eq&value=$userId";
        $item->applications =  ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        foreach ($item->applications as $application) {
            // Get Application Permissions
            $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$application->id/permissions";
            $permissions = ApiPortalHelper::doGet($path);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }

            if (!ApiPortalHelper::isCommunity($application)) {
                foreach ($permissions as $permission) {
                    if ($permission->userId == $userId) {
                        $application->permission = $permission;
                        break;
                    }
                }
            }
        }
        if (count($item->applications)>0) {
            usort($item->applications, array($this, "sortAppNames"));
        }

        // Get list of applications in this organization if 'editing' only, and the Organization is not 'Community'
        $orgApplications = array();
        if ($editing && !ApiPortalHelper::isCommunity($item->user)) {
            $organizationId = $item->user->organizationId;

            $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications?field=orgid&op=eq&value=$organizationId";
            $orgApplications = ApiPortalHelper::doGet($path);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }

            $count = count($orgApplications);
            for ($i = 0; $i < $count; $i++) {
                $orgApp = $orgApplications[$i];

                // Filter out any applications that the application is already shared with
                foreach ($item->applications as $sharedApplication) {
                    if ($orgApp->id == $sharedApplication->id) {
                        unset($orgApplications[$i]);
                        break;
                    }
                }
            }

            $orgApplications = array_values($orgApplications);
            if (count($orgApplications) > 0) {
                usort($orgApplications, array($this, "sortUserNames"));
            }
        }
        $item->orgApplications = $orgApplications;

        return $item;
    }

    public function createUser() {
        // Everything in the 'data' array will get posted to the API Manger, including any custom fields
        $data = $this->userdata;
        $pass = null;

        if (array_key_exists("passwordapproach", $data) && $data["passwordapproach"]==="manually") {
            if (array_key_exists("password1", $data)) {
                $pass = $data["password1"];
            }
        }

        // Phone muset be presented
        $data["phone"]="";

        unset($data["password1"]);
        unset($data["password2"]);
        $data['type'] = self::USER_TYPE_INTERNAL;

        $path = ApiPortalHelper::getVersionedBaseFolder() ."/users";
        $app = JFactory::getApplication();
        try {
            $user = ApiPortalHelper::doPost($path, $data, CONTENT_TYPE_JSON, false, true);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }

            if ($user) {
                //set manually pass
                if ($pass !== null) {
                    if ($this->changePassword($user->id, $pass) === null) {
                        $app->enqueueMessage(JText::_('COM_APIPORTAL_USER_CREATE_PASSWORD'), 'error');
                        return $user->id;
                    }
                } else {
                    //random pass - reset
                    if (array_key_exists("passwordapproach", $data) && $data["passwordapproach"] === "random") {
                        if ($this->resetPassword($user->id) === null) {
                            $app->enqueueMessage(JText::_('COM_APIPORTAL_USER_CREATE_PASSWORD'), 'error');
                            return $user->id;
                        }
                    }
                }
            }
            unset($data["passwordapproach"]);
        } catch (Exception $e) {

            //if (ApiPortalHelper::isHttpError()) {
            $app->enqueueMessage($e->getMessage(), 'error');
            return null;
            //}
        }
        return isset($user) ? $user->id : null;
    }

    public function saveUser($saveType, $userId, $data, $imageInfo, $timezone, $isProfileAction) {

        //first sync user by username and email to prevent conflicts, as these properties should be unique
        if (!self::syncByEmail($data['email'], $data['id']) ||
            !self::syncByUsername($data['loginName'], $data['id'])){
            return null;
        }

        $path = ApiPortalHelper::getVersionedBaseFolder() . ($isProfileAction?"/currentuser":"/users/".$userId);

        $data = array_merge(array("id" => $userId), $data);
        try {
            $user = ApiPortalHelper::doPut($path, $data, CONTENT_TYPE_JSON, false, true);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        } catch (Exception $e) {

            //if (ApiPortalHelper::isHttpError()) {
            $app = JFactory::getApplication();
            $app->enqueueMessage($e->getMessage(), 'error');
            return null;
            //}
        }

        if ($isProfileAction) {
            // Update Timezone
            $userObj = JFactory::getUser();
            if (isset($data['name']))       $userObj->set('name', $data['name']);
            if (isset($data['loginName']))  $userObj->set('username', $data['loginName']);
            if (isset($data['email']))      $userObj->set('email', $data['email']);
            if (isset($timezone))           $userObj->setParam("timezone", $timezone);

            // Save Joomla User object
            if (!$userObj->save()) {
                // if save failes, add a log entry
                error_log("Caught Exception: " . $userObj->getError());
                $app = JFactory::getApplication();
                $app->enqueueMessage($userObj->getError(), 'error');
                return null;
            }
        }

        // Send image to API Manager if present
        if ($imageInfo) {
            $filename = $imageInfo['name'];
            $filepath = $imageInfo['tmp_name'];
            $type = $imageInfo['type'];
            try {
                ApiPortalHelper::resizeImage($filepath, $filename, $this->resizeWidth, $this->resizeHeight);
            } catch (Exception $e) {
                $app = JFactory::getApplication();
                $app->enqueueMessage($e->getMessage(), 'error');
                return null;
            }

            $imageData = array('file' => "@$filepath;type=$type");

            $imagePath = ApiPortalHelper::getVersionedBaseFolder() . "/users/".$userId."/image";
            ApiPortalHelper::doPost($imagePath, $imageData, CONTENT_TYPE_MULTI);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        }

        return $user->id;
    }

    /**
     * Delete User From APi Portal and from API Manager
     *
     * @param integer $userId
     * @return void
     */
    public function deleteUser($userId) {

        $db = JFactory::getDbo();
        $db->setQuery('SELECT user_id_jm from #__apiportal_user WHERE user_id=' . $db->quote($userId));
        $db->execute();
        $result = $db->loadAssoc();
        if (is_array($result) && !empty($result)) {
            $jUser = new JUser($result['user_id_jm']);

            //delete from Joomla users table
            if ($jUser instanceof JUser) {
                $jUser->delete();// this will trigger deleting from API Manager
            } else {
                error_log('No Joomla user with id:' . $userId);
            }

            //delete from apiportal users table
            $db->setQuery('DELETE FROM  #__apiportal_user WHERE user_id_jm =' . $db->quote($userId));
            $db->execute;
        }
        else{
            //user may have never logged in so there should not be a record in Apiportal DB
            //so try to delete it directly from API Manager
            $path = ApiPortalHelper::getVersionedBaseFolder() ."/users/".$userId;
            $result = null;
            try {
                ApiPortalHelper::doDelete($path, null, CONTENT_TYPE_JSON, false, true);

                if (ApiPortalHelper::isHttpError()) {
                    return null;
                }
            } catch (Exception $e) {

                if (ApiPortalHelper::isHttpError()) {
                    $app = JFactory::getApplication();
                    $app->enqueueMessage($e->getMessage(), 'error');
                }
            }
        }
    }

    public function approveUser($userId) {
        $result = null;
        $path = ApiPortalHelper::getVersionedBaseFolder() ."/users/".$userId.'/approve';
        try {
            $result = ApiPortalHelper::doPost($path, null, CONTENT_TYPE_JSON, false, true);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        } catch (Exception $e) {

            if (ApiPortalHelper::isHttpError()) {
                $app = JFactory::getApplication();
                $app->enqueueMessage($e->getMessage(), 'error');
                return null;
            }
        }
        return $result;
    }

    public function rejectUser($userId) {
        $path = ApiPortalHelper::getVersionedBaseFolder() ."/users/".$userId;
        $result = null;
        try {
            $result = ApiPortalHelper::doDelete($path, null, CONTENT_TYPE_JSON, false, true);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        } catch (Exception $e) {

            if (ApiPortalHelper::isHttpError()) {
                $app = JFactory::getApplication();
                $app->enqueueMessage($e->getMessage(), 'error');
                return null;
            }
        }
        return $result;
    }

    public function changePassword($userId, $newPassword) {
        $result = null;
        //check type
        if (!$this->checkUserType($userId)) {
            return null;
        }

        // Everything in the 'data' array will get posted to the API Manger, including any custom fields
        $data["newPassword"]=$newPassword;
        $path = ApiPortalHelper::getVersionedBaseFolder() ."/users/".$userId."/changepassword/";

        try {
            // !!!! THIS IS NOT A REST !!! This is a common post request.
            $result = ApiPortalHelper::doPost($path, $data, CONTENT_TYPE_FORM, false, true);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        } catch (Exception $e) {

            if (ApiPortalHelper::isHttpError()) {
                $app = JFactory::getApplication();
                $app->enqueueMessage($e->getMessage(), 'error');
                return null;
            }
        }
        return $result;
    }

    public function resetPassword($userId) {
        $result = null;

        //check type
        if (!$this->checkUserType($userId)) {
            return null;
        }

        // Everything in the 'data' array will get posted to the API Manger, including any custom fields
        $data = array();
        $path = ApiPortalHelper::getVersionedBaseFolder() ."/users/".$userId."/resetpassword/";

        try {
            // !!!! THIS IS NOT A REST !!! This is a common post request.
            $result = ApiPortalHelper::doPut($path, $data, CONTENT_TYPE_FORM, false, true);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        } catch (Exception $e) {

            if (ApiPortalHelper::isHttpError()) {
                $app = JFactory::getApplication();
                $app->enqueueMessage($e->getMessage(), 'error');
                return null;
            }
        }
        return $result;
    }

    /**
     * Check user's type
     * @param null $userId
     * @return mixed|null
     */
    private function checkUserType($userId = null)
    {
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/currentuser";
        $user = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        if ($user->role == self::MANAGER_USER_ROLE_ADMIN || $user == self::MANAGER_USER_ROLE_OADMIN) {
            $path = ApiPortalHelper::getVersionedBaseFolder() . "/users/" . $userId;
            $user = ApiPortalHelper::doGet($path);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        }

        if ($user->type != self::USER_TYPE_INTERNAL && $user->type !== null) {
            return null;
        }

        return true;
    }

    public function addSharedApp($userId, $apps) {
        $result = null;
        $data=array();
        $data["userId"] = $userId;
        $data["permission"] = "view";
        foreach ($apps as $appId) {
            $path = ApiPortalHelper::getVersionedBaseFolder() ."/applications/".$appId."/permissions/";
            try {
                $result = ApiPortalHelper::doPost($path, $data, CONTENT_TYPE_JSON, false, true);

                if (ApiPortalHelper::isHttpError()) {
                    return null;
                }
            } catch (Exception $e) {

                if (ApiPortalHelper::isHttpError()) {
                    $app = JFactory::getApplication();
                    $app->enqueueMessage($e->getMessage(), 'error');
                    return null;
                }
            }
        }
        return $result;
    }

    // AJAX: Skip Error Messages/HTTP Status Verification - results will propagate directly
    public function updateSharedApp($userId, $appId, $permissionId, $permission) {
        $data = array();
        $result = null;
        $data["id"] = $permissionId;
        $data["userId"] = $userId;
        $data["permission"] = $permission==="manage"?"manage":"view";
        $path = ApiPortalHelper::getVersionedBaseFolder() ."/applications/".$appId."/permissions/".$permissionId;
        try {
            $result = ApiPortalHelper::doPut($path, $data, CONTENT_TYPE_JSON, false, true);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        } catch (Exception $e) {

            if (ApiPortalHelper::isHttpError()) {
                $app = JFactory::getApplication();
                $app->enqueueMessage($e->getMessage(), 'error');
                return null;
            }
        }
        return $result;
    }

    public function removeSharedApp($userId, $appId, $permissionId) {
        $result = null;
        $path = ApiPortalHelper::getVersionedBaseFolder() ."/applications/".$appId."/permissions/".$permissionId;
        try {
            $result = ApiPortalHelper::doDelete($path, null, CONTENT_TYPE_JSON, false, true);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        } catch (Exception $e) {

            if (ApiPortalHelper::isHttpError()) {
                $app = JFactory::getApplication();
                $app->enqueueMessage($e->getMessage(), 'error');
                return null;
            }
        }
        return $result;
    }

    public function validateCreateUser(&$data, $email2, $isProfileAction) {
        $result = true;
        $this->userdata = $data;
        if (count($this->userdata) > ApiPortalValidator::MAX_ARRAY_LEN) {
            $this->setError(sprintf(JText::_('COM_APIPORTAL_ARRAY_TOO_LONG'),  ApiPortalValidator::MAX_ARRAY_LEN));
            return false;
        }

        // Validate Organization ID if present
        if (ApiPortalHelper::hasAdminRole()) {
            $organizationId = isset($this->userdata['organizationId']) ? $this->userdata['organizationId'] : 0;
            if ($organizationId) {
                if (!ApiPortalValidator::isValidGuid($organizationId)) {
                    $this->setError(JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_INVALID_ORGANIZATION'));
                    $result = false;
                }
            }
        }

        // Validate any required fields and all field lengths
        $name = JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_NAME_LABEL');
        $field = isset($this->userdata['name']) ? $this->userdata['name'] : null;
        if (!ApiPortalValidator::validateRequired($name, $field, $this)) {
            $result = false;
        } else if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $data['name'] = '';
            $result = false;
        }

        $name = JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_EMAIL_LABEL');
        $field = isset($this->userdata['email']) ? $this->userdata['email'] : null;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $data['email'] = '';
            $result = false;
        }

        if (!empty($email2) && !ApiPortalValidator::validateEmails($name, $field, $email2, $this)) {
            $result = false;
        }

        $name = JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_ROLE_LABEL');
        $field = isset($this->userdata['role']) ? $this->userdata['role'] : null;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_TEXTAREA_LEN)) {
            $data['role'] = '';
            $result = false;
        }

        // Validate checkbox value length
        if (isset($this->userdata['enabled'])) {
            /*
             * For the truly paranoid: the checkbox value is simply a string,
             * and can be manipulated for evil by any competent hacker.
             */
            $name = JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_ENABLE_LABEL');
            $field = $this->userdata['enabled'];
            if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
                $data['enabled'] = 'false';
                $result = false;
            }
        } else {
            /*
             * If the checkbox was not checked, there will be nothing sent in the POST, so set it to 'false'
             * in case we have any validation errors, it will then remain unchecked when we get back to the page.
             */
            if (!$isProfileAction) {
                $data['enabled'] = 'false';
            }
        }

        $passwordapproach = isset($this->userdata['passwordapproach']) ? $this->userdata['passwordapproach'] : null;
        if ($passwordapproach==="manually") {
            $name = JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_SET_PASSWORD_LABEL');
            $result = $this->validatePasswords($name, $data);
        }

        if (!$result) {
            $data['password1'] = '';
            $data['password2'] = '';
        }

        return $result;
    }

    public function validateNewPassword($data) {
        $name = JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_PASSWORD_DIALOG_NEW_PASSWORD_LABEL');
        return $this->validatePasswords($name, $data);
    }

    public function validateImage(&$imageInfo) {
        $result = true;
        if ($imageInfo) {
            $error = $imageInfo['error'];
            switch ($error) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $this->setError(JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_IMAGE_TOO_LARGE'));
                    $result = false;
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $imageInfo = null;
                    break;
                default:
                    error_log('Image: error $error occurred uploading image');
                    $this->setError(JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_IMAGE_ERROR'));
                    $result = false;
                    break;
            }
        }
        return $result;
    }

    public function validatePasswords($name, $data) {
        $result = true;

        $config = ApiPortalHelper::getAPIMangerConfig();

        // Need to validate password & password confirmation inputs
        $field1 = isset($data['password1']) ? $data['password1'] : null;
        $field2 = isset($data['password2']) ? $data['password2'] : null;
        if (!ApiPortalValidator::validatePasswords ($name, $field1, $field2, $config, $this )) {
            $result = false;
        }
        return $result;
    }

    protected function sortAppNames($a, $b) {
        $name1 = strtolower($a->name);
        $name2 = strtolower($b->name);

        if ($name1 == $name2) {
            return 0;
        }
        return ($name1 < $name2) ? -1 : 1;
    }


    protected function sortUserNames($a, $b) {
        $name1 = strtolower($a->name);
        $name2 = strtolower($b->name);

        if ($name1 == $name2) {
            return 0;
        }
        return ($name1 < $name2) ? -1 : 1;
    }

    public function getForm($data = array(), $loadData = false)
    {
        // Get the form.
        $form = $this->loadForm('com_apiportal.user', 'user',
            array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form))
        {
            return false;
        }

        $set = $form->getFieldset();
        return $form;
    }

    /**
     * @param string $userName
     * @return boolean <code>true</code> if sync was successful (i.e. no exeception was thrown),
     * <code>false</code> otherwise.
     */
    public static function syncByUsername($userName, $userId)
    {
        return self::_sync(array('key' => 'loginname', 'value' => $userName), $userId);
    }

    /**
     * @param string $email
     * @params string $userId
     * @return boolean <code>true</code> if sync was successful (i.e. no exeception was thrown),
     * <code>false</code> otherwise.
     */
    public static function syncByEmail($email, $userId)
    {
        return self::_sync(array('key' => 'email', 'value' => $email), $userId);
    }

    /**
     * Update conflict records with temporary username and email
     *
     * @param array $users
     * @param array $params
     * e.g. stucture :
     * array('key' => columnName (email/loginname),
     *        'value' => email/loginname value
     *      )
     * @return bool
     */
    public static function updateUsersWithTempData($users, $params) {
        $key = ($params["key"] == 'loginname') ? 'username' : 'email';
        foreach ($users as $user) {
            // This is workaround for conflicts with duplicated usernames and emails if a user has been deleted from API Manager
            // We update all duplicated records with random hashes
            // On the next login of the updated user these random hashes will be replaces with his real useranme and email
            try {
                $db = JFactory::getDbo();
                $db->transactionStart();
                $randomString = 'UNSYNC_' . md5(microtime() . rand(1, 99999999) . uniqid());
                $db->setQuery('UPDATE #__users set ' . $db->escape($key) . '=' . $db->quote($randomString) . ' WHERE id =' . $db->quote($user["user_id_jm"]));
                $db->execute();

                $db->setQuery('UPDATE #__apiportal_user set ' . $db->escape($params["key"]) . '=' . $db->quote($randomString) . ' WHERE user_id_jm =' . $db->quote($user["user_id_jm"]) . ' AND ' . $db->escape($params["key"]) . '=' . $db->quote($params["value"]));
                $db->execute();
                $db->transactionCommit();
            } catch (Execption $e) {
                $db->transactionRollback();
                error_log($e->getMessage());
                return false;
            }
        }
        return true;
    }

    /**
     * Common logic for sync db tables either by email or by username
     *
     * @param array $params
     * e.g. stucture :
     * array('key' => columnName (email/loginname),
     *        'value' => email/loginname value
     *      )
     * @param $userId
     * @return bool
     * @throws Exception
     */
    private static function _sync($params, $userId) {
        // $params contains the actual user name and e-mail of the current user and we'll need to update the DB record.
        // However, check first if there are other (stale) records with the same username/email and move them out
        $db = JFactory::getDbo();
        $db->getQuery(true);
        $db->setQuery('SELECT * FROM #__apiportal_user WHERE ' . $db->escape($params["key"]) . '=' . $db->quote($params["value"]) . ' AND user_id !=' . $db->quote($userId));
        $db->execute();

        $result = $db->loadAssocList();
        if (!empty($result)){// conflict detected

            // Since only one user can have this username/email in API Manager,
            // the other users are out of sync. Rename them in the database before updating the current record
            return self::updateUsersWithTempData($result, $params);
        }
        return true;
    }
}