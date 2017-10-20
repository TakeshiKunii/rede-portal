<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.user.component.controller');

/*
 * TODO: This class could use a round of refactoring for the controller tasks with very similar
 * structure, particularly those related to API Keys, OAuth Clients, and OAuth Scopes.
 */
class ApiPortalControllerUser extends JControllerLegacy
{
    public function authorise($task) {
        // Make sure the session is valid before proceeding with tasks
        ApiPortalHelper::checkSession();
    }

    public function createUser() {
        $app = JFactory::getApplication();
	    $model = $this->getModel('User');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'save-type'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('user', 'create', null, $this, $model);
			    return false;
		    }

		    $data = JRequest::getVar('userdata', array(), "post", 'ARRAY');

		    $app->setUserState(ApiPortalSessionVariables::USER_CREATE_DATA, null);

		    // Validate input fields
		    if (!$model->validateCreateUser($data, false, false))
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    $app->setUserState(ApiPortalSessionVariables::USER_CREATE_DATA, $data);

			    ApiPortalHelper::displayView('user', 'create', null, $this, $model);
			    return false;
		    }

		    $userId = $model->createUser();

		    if ($userId)
		    {
			    $name    = $data['name'];
			    $type    = JText::_('COM_APIPORTAL_USER_USER_OBJECT');
			    $message = sprintf(JText::_('COM_APIPORTAL_USER_CREATE_CONFIRMATION'), $type, htmlspecialchars($name));
			    $app->enqueueMessage($message, 'message');

			    $this->redirectToList();
			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    $app->setUserState(ApiPortalSessionVariables::USER_CREATE_DATA, $data);
			    ApiPortalHelper::displayView('user', 'create', null, $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('user', 'create', null, $this, $model);
	    return false;
    }

    public function saveUser () {
        $app = JFactory::getApplication();
        $model = $this->getModel('User');

        $data = JRequest::getVar('userdata', array(), "post", 'ARRAY');
        
        $jform = JRequest::getVar('jform', array(), "post", 'ARRAY');
        $isProfileAction = isset($jform) && count ($jform) != 0; // currently we use jform param only for profile update.
        $timezone = array_key_exists('params', $jform) && array_key_exists('timezone', $jform['params'])?$jform['params']['timezone']:null;

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'save-type'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    if ($isProfileAction)
			    {
				    // Go to profile edit screen
				    $this->redirectTo("view=user&layout=edit&ep=profile-menu");

				    return false;
			    }
			    else
			    {
				    // Go to user edit screen
				    $this->redirectTo("view=user&layout=" . $this->getLayout() . "&userId=" . $data['id']);

				    return false;
			    }
		    }

		    $userId    = JRequest::getVar('userId');
		    $saveType  = JRequest::getVar('save-type');
		    $imageInfo = JRequest::getVar('image', null, 'files', 'ARRAY');

		    // Get current object data
		    $tmpItem = isset($model) ? $model->getItem($isProfileAction) : null;
		    $tmpUser = isset($tmpItem) ? $tmpItem->user : null;

		    // Get mandatory field from current profil prior real profile update
		    if (isset($tmpUser)) $data['type'] = $tmpUser->type;// else $data['type']=null;
		    if (isset($tmpUser)) $data['createdOn'] = $tmpUser->createdOn;
		    // If it is profile update or API Manager Admin/Organization Amdin updates an user, then get 'OrganizationId' from current user
		    if ($isProfileAction || (isset($tmpUser) && ApiPortalHelper::hasGroupAdminRole())) $data['organizationId'] = $tmpUser->organizationId;
		    // If it is profile update, then get 'Role' && 'State' from current user
		    if ($isProfileAction && isset($tmpUser)) $data['role'] = $tmpUser->role;
		    if ($isProfileAction && isset($tmpUser)) $data['enabled'] = $tmpUser->enabled;
		    if (isset($tmpUser))
		    {
			    $data['state'] = $tmpUser->state;
		    }
		    /*
			if ($isProfileAction && isset($tmpUser)) $data['dn']=$tmpUser->dn;
			if ($isProfileAction && isset($tmpUser)) $data['image']=$tmpUser->image;
			if ($isProfileAction && isset($tmpUser)) $data['mobile']=$tmpUser->mobile;
			/**/

		    /**
		     * User ID (loginName) is a little bit different.
		     * If it's equal to email and user is internal these two properties must be sync.
		     */
		    $syncEmailID = false;
		    if ($isProfileAction && isset($tmpUser))
		    {
			    if ($tmpUser->loginName == $tmpUser->email && $tmpUser->type == APIPortalModelUser::USER_TYPE_INTERNAL)
			    {
				    if ($tmpUser->email != $data['email'])
				    {
					    $data['loginName'] = $data['email'];
					    $syncEmailID       = true;
				    }
				    else
				    {
					    $data['loginName'] = $tmpUser->loginName;
				    }
			    }
			    else
			    {
				    $data['loginName'] = $tmpUser->loginName;
			    }
		    }

		    $app->setUserState(ApiPortalSessionVariables::USER_EDIT_DATA, null);

		    // Validate input fields
		    if (!$model->validateCreateUser($data, false, $isProfileAction))
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    $app->setUserState(ApiPortalSessionVariables::USER_EDIT_DATA, $data);

			    if ($isProfileAction)
			    {
				    // Go to profile edit screen
				    $this->redirectTo("view=user&layout=edit&ep=profile-menu");
			    }
			    else
			    {
				    ApiPortalHelper::displayView('user', $this->getLayout(), null, $this, $model);
			    }

			    return false;
		    }

		    // Validate Image
		    if (!$model->validateImage($imageInfo))
		    {
			    $app->setUserState(ApiPortalSessionVariables::USER_EDIT_DATA, $data);
			    ApiPortalHelper::displayView('user', $this->getLayout(), null, $this, $model);

			    return false;
		    }

		    $data   = array_merge(array("id" => $userId), $data);
		    $userId = $model->saveUser($saveType, $userId, $data, $imageInfo, $timezone, $isProfileAction);

		    if ($userId)
		    {
			    $name    = $data['name'];
			    $type    = JText::_($isProfileAction ? 'COM_APIPORTAL_USER_PROFILE_OBJECT' : 'COM_APIPORTAL_USER_USER_OBJECT');
			    $message = sprintf(JText::_($isProfileAction ? 'COM_APIPORTAL_USER_PROFILE_UPDATE_CONFIRMATION' : 'COM_APIPORTAL_USER_UPDATE_CONFIRMATION'), $type, htmlspecialchars($name));
			    $app->enqueueMessage($message, 'message');

			    if ($isProfileAction)
			    {
				    /**
				     * Needed for keeping session and further authentication up to date.
				     * User will have to logout and login again without this :)
				     */
				    if ($syncEmailID)
				    {
					    $session = JFactory::getSession();
					    $session->set(ApiPortalSessionVariables::MANAGER_EMAIL, $data['email']);
				    }

				    $this->redirectTo("view=user&layout=view&ep=profile-menu");
			    }
			    else
			    {
				    // redirect to the same user edit screen
				    //$this->redirectTo("view=user&layout=".$this->getLayout()."&userId=".$data['id']);

				    // redirect to users list
				    $this->redirectToList();
			    }

			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    $app->setUserState(ApiPortalSessionVariables::USER_EDIT_DATA, $data);

			    if ($isProfileAction)
			    {
				    // Go to profile edit screen
				    $this->redirectTo("view=user&layout=edit&ep=profile-menu");
			    }
			    else
			    {
				    // Go to user edit screen
				    $this->redirectTo("view=user&layout=" . $this->getLayout() . "&userId=" . $data['id']);
			    }

			    return false;
		    }
	    }

	    if ($isProfileAction)
	    {
		    // Go to profile edit screen
		    $this->redirectTo("view=user&layout=edit&ep=profile-menu");
	    }
	    else
	    {
		    // Go to user edit screen
		    $this->redirectTo("view=user&layout=" . $this->getLayout() . "&userId=" . $data['id']);
	    }

	    return false;

    }
    
    public function deleteUser()
    {
	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'userId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    $this->redirectToList();

			    return false;
		    }

		    $model         = $this->getModel('User');
		    $jinput        = JFactory::getApplication()->input;
		    $userId        = $jinput->post->get('userId', '', 'STRING');
		    $currentUserId = ApiPortalHelper::getCurrentUserPortalId();
		    if ($userId != $currentUserId) {
			    $model->deleteUser($userId);
		    }
	    }

        $this->redirectToList();
        return false;
    }
    
    public function approveUser()
    {
	    if (!JSession::checkToken('get'))
	    {
		    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
		    $this->redirectToList();

		    return false;
	    }

	    $model  = $this->getModel('User');
	    $userId = JRequest::getVar('userId');
	    $result = $model->approveUser($userId);
	    if ($result)
	    {
		    $this->redirectTo("view=user&layout=" . $this->getLayout() . "&userId=" . $userId);
	    }
	    else
	    {
		    $this->redirectToList();
	    }
    }
    
    public function rejectUser() {

	    if (!JSession::checkToken('get'))
	    {
		    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');

		    $this->redirectToList();
		    return false;
	    }

        $model = $this->getModel('User');
        $userId=JRequest::getVar('userId');
        $result = $model->rejectUser($userId);

        $this->redirectToList();
        return false;
    }
    
    public function viewUser() {
        $model = $this->getModel('User');
        ApiPortalHelper::displayView('user', $this->getLayout(), null, $this, $model);
        return false;
    }

    /**
     * This is only for admins and org admins
     * @throws Exception
     */
    public function changePassword() {

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'userdata[id]'))
	    {
		    $userId = JRequest::getVar('userId');

		    // Do you have permission
		    if (ApiPortalHelper::hasGroupAdminRole() || ApiPortalHelper::hasAdminRole())
		    {
			    $data   = JRequest::getVar('userdata', array(), "post", 'ARRAY');
			    $app    = JFactory::getApplication();

			    // Check for CSRF token
			    if (!JSession::checkToken('post'))
			    {
				    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
				    $this->redirectTo("view=user&layout=" . $this->getLayout() . "&userId=" . $userId);

				    return false;
			    }

			    $app->setUserState(ApiPortalSessionVariables::USER_EDIT_DATA, $data);
			    $model = $this->getModel('User');

			    // Validate input fields
			    if (!$model->validateNewPassword($data))
			    {
				    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
				    $this->redirectTo("view=user&layout=" . $this->getLayout() . "&userId=" . $userId);
			    }

			    if ($model->changePassword($userId, $data["password1"]) !== null)
			    {
				    $app->enqueueMessage(JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_PASSWORD_CONFIRMATION'), 'message');
			    };

			    $this->redirectTo("view=user&layout=" . $this->getLayout() . "&userId=" . $userId);
			    return false;
		    }
		    else
		    {
			    $this->redirectTo("view=user&layout=edit&ep=profile-menu");
			    return false;
		    }
	    }

	    $this->redirectTo("view=user&layout=" . $this->getLayout() . "&userId=" . $userId);
	    return false;
    }

    /**
     * Duplicate the changePassword method because we need to be sure there is current password value
     * This is in the case when user wants to chane his password from edit profile page.
     * And the changePassword method is available for admins and org admins only
     * @throws Exception
     */
    public function changeProfilePassword() {

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'userdata[id]'))
	    {
		    // Get the data
		    $data   = JRequest::getVar('userdata', array(), "post", 'ARRAY');
		    $userId = JRequest::getVar('userId');
		    $app    = JFactory::getApplication();

		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    $this->redirectTo("view=user&layout=edit&ep=profile-menu");

			    return false;
		    }

		    $app->setUserState(ApiPortalSessionVariables::USER_EDIT_DATA, $data);
		    $model = $this->getModel('User');

		    // Check current password if exists
		    if (!empty($data['password_current']) && $data['password_current'] != ApiPortalHelper::getPassword())
		    {
			    $app->enqueueMessage(JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_PASSWORD_CURRENT_ERROR'), 'error');
			    $this->redirectTo("view=user&layout=edit&ep=profile-menu");
		    }

		    // Validate input fields
		    if (!$model->validateNewPassword($data))
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    $this->redirectTo("view=user&layout=edit&ep=profile-menu");
		    }

		    // Send the request
		    if ($model->changePassword($userId, $data["password1"]) !== null)
		    {
			    $app->enqueueMessage(JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_PASSWORD_CONFIRMATION'), 'message');
		    };
	    }

        // Redirect to edit profile page
        $this->redirectTo("view=user&layout=edit&ep=profile-menu");
    }
    
    public function resetPassword() {
        $data = JRequest::getVar('userdata', array(), "post", 'ARRAY');
        $userId = JRequest::getVar('userId');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'reset-pass'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    $this->redirectTo("view=user&layout=" . $this->getLayout() . "&userId=" . $userId);

			    return false;
		    }

		    $app = JFactory::getApplication();
		    $app->setUserState(ApiPortalSessionVariables::USER_EDIT_DATA, $data);
		    $model = $this->getModel('User');

		    $result = $model->resetPassword($userId);
		    if ($result !== null)
		    {
			    $app->enqueueMessage(sprintf(JText::_('COM_APIPORTAL_USER_EDIT_RESET_PASSWORD_CONFIRMATION'), $result->email), 'message');
		    };
	    }

        $this->redirectTo("view=user&layout=".$this->getLayout()."&userId=".$userId);
        return false;
    }
    
    public function addSharedApp() {
        $data = JRequest::getVar('userdata', array(), "post", 'ARRAY');
        $userId=JRequest::getVar('userId');
        $apps = JRequest::getVar('apps', array(), "post", 'ARRAY');
        $app = JFactory::getApplication();

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'add-shared-post'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    $this->redirectTo("view=user&layout=" . $this->getLayout() . "&userId=" . $userId);

			    return false;
		    }

		    $app->setUserState(ApiPortalSessionVariables::USER_EDIT_DATA, $data);
		    $model = $this->getModel('User');

		    $result = $model->addSharedApp($userId, $apps);
		    if ($result !== null)
		    {
			    $app->enqueueMessage(sprintf(JText::_('COM_APIPORTAL_USER_EDIT_ADD_APPLICATION_CONFIRMATION'), $result->email), 'message');
		    };
	    }
        $this->redirectTo("view=user&layout=".$this->getLayout()."&userId=".$userId);
        return false;
    }

    public function updateSharedApp() {
        $app = JFactory::getApplication();
	    $userId=JRequest::getVar('userId');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    $this->redirectTo("view=user&layout=" . $this->getLayout() . "&userId=" . $userId);

			    return false;
		    }

		    $model         = $this->getModel('User');
		    $applicationId = JRequest::getVar('applicationId');
		    $permissionId  = JRequest::getVar('permissionId');
		    $permission    = JRequest::getVar('permission');

		    $result = $model->updateSharedApp($userId, $applicationId, $permissionId, $permission);
		    if ($result !== null)
		    {
			    $app->enqueueMessage(sprintf(JText::_('COM_APIPORTAL_USER_EDIT_UPDATE_APPLICATION_PERMISSION_CONFIRMATION'), $result->email), 'message');
		    };
	    }

        $this->redirectTo("view=user&layout=".$this->getLayout()."&userId=".$userId);
        return false;
    }

    public function removeSharedApp() {
        $data = JRequest::getVar('userdata', array(), "post", 'ARRAY');
        $userId=JRequest::getVar('userId');
	    $app = JFactory::getApplication();

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    $this->redirectTo("view=user&layout=" . $this->getLayout() . "&userId=" . $userId);

			    return false;
		    }

		    $applicationId = JRequest::getVar('applicationId');
		    $permissionId  = JRequest::getVar('permissionId');
		    $app->setUserState(ApiPortalSessionVariables::USER_EDIT_DATA, $data);
		    $model = $this->getModel('User');

		    $result = $model->removeSharedApp($userId, $applicationId, $permissionId);
		    if ($result !== null)
		    {
			    $app->enqueueMessage(sprintf(JText::_('COM_APIPORTAL_USER_EDIT_UPDATE_APPLICATION_PERMISSION_CONFIRMATION'), $result->email), 'message');
		    };
	    }

        $this->redirectTo("view=user&layout=".$this->getLayout()."&userId=".$userId);
        return false;
    }

    private function getLayout() {
        $model = $this->getModel('User');
        $item = $model->getItem();
        $user = $item!=null?$item->user:null;
        $pending = $user!=null?$user->state==="pending":false;
        $layout = JRequest::getString('layout', '');
        if ($layout) {
            return $layout;
        } else {
            $config = ApiPortalHelper::getAPIMangerConfig();
            $canEdit = isset($config) && isset($config->delegateUserAdministration) ? $config->delegateUserAdministration : false;
            return $canEdit && !$pending?"edit":"view";
        }
    }

    private function redirectToList() {
        $link = JRoute::_("index.php?option=com_apiportal&view=users", false);
        $this->setRedirect($link);
    }

    /**
     * Redirects to location
     * No need to return value
     * @param $where string
     */
    private function redirectTo($where) {
        $link = JRoute::_("index.php?option=com_apiportal&$where", false);
        $this->setRedirect($link);
        $this->redirect();
    }

}
