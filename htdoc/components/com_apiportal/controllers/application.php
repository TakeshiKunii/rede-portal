<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/*
 * TODO: This class could use a round of refactoring for the controller tasks with very similar
 * structure, particularly those related to API Keys, OAuth Clients, and OAuth Scopes.
 */
class ApiPortalControllerApplication extends JControllerLegacy
{
    public function authorise($task) {
        // Make sure the session is valid before proceeding with tasks
        ApiPortalHelper::checkSession();
    }

    public function createApp() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');
        $data = JRequest::getVar('apidata', array(), "post", 'ARRAY');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'create', null, $this, $model);

			    return false;
		    }

		    $app->setUserState(ApiPortalSessionVariables::APP_CREATE_DATA, null);

		    // Validate input fields
		    if (!$model->validateCreateApp($data))
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    $app->setUserState(ApiPortalSessionVariables::APP_CREATE_DATA, $data);

			    ApiPortalHelper::displayView('application', 'create', null, $this, $model);

			    return false;
		    }

		    $applicationId = $model->createApp();
		    if ($applicationId)
		    {
			    $saveType = JRequest::getVar('save-type', 'save-and-list', "post", 'STRING');
			    if ($saveType == 'save-and-auth')
			    {
				    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$applicationId");
			    }
			    else
			    {
				    $name    = $data['name'];
				    $type    = JText::_('COM_APIPORTAL_APPLICATION_APPLICATION_OBJECT');
				    $message = sprintf(JText::_('COM_APIPORTAL_APPLICATION_CREATE_CONFIRMATION'), $type, htmlspecialchars($name));
				    $app->enqueueMessage($message, 'message');

				  $this->redirectTo("view=application&layout=view&tab=details&applicationId=$applicationId");
			    }

			    return true;
		    }
		    else
		    {
			    // Errors are already queued up - should only be unexpected HTTP errors at this point
			    $app->setUserState(ApiPortalSessionVariables::APP_CREATE_DATA, $data);

			    ApiPortalHelper::displayView('application', 'create', null, $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'create', null, $this, $model);
	    return false;
    }

    public function updateApp() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');
        $data = JRequest::getVar('apidata', array(), "post", 'ARRAY');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'details', null, $this, $model);
			    return false;
		    }

		    $app->setUserState(ApiPortalSessionVariables::APP_EDIT_DATA, null);

		    // Validate input fields
		    if (!$model->validateUpdateApp($data))
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    $app->setUserState(ApiPortalSessionVariables::APP_EDIT_DATA, $data);

			    ApiPortalHelper::displayView('application', 'edit', 'details', $this, $model);
			    return false;
		    }

		    $applicationId = $model->updateApp();
		    if ($applicationId)
		    {
			    $message = JText::_('COM_APIPORTAL_APPLICATION_APPLY_CONFIRMATION');
			    $app->enqueueMessage($message, 'message');

			    $this->redirectTo("view=application&layout=edit&tab=details&applicationId=$applicationId");
			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    $app->setUserState(ApiPortalSessionVariables::APP_EDIT_DATA, $data);

			    ApiPortalHelper::displayView('application', 'edit', 'details', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'details', $this, $model);
	    return false;
    }

    public function deleteApp() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');
        $viewName = JRequest::getVar('viewName', 'view', 'post', 'STRING');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', $viewName, 'details', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateDeleteApp())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', $viewName, 'details', $this, $model);

			    return false;
		    }

		    $applicationId = $model->deleteApp();
		    if ($applicationId)
		    {
			    $name    = $_POST["applicationName"];
			    $type    = JText::_('COM_APIPORTAL_APPLICATION_APPLICATION_OBJECT');
			    $message = sprintf(JText::_('COM_APIPORTAL_APPLICATION_DELETE_CONFIRMATION'), $type, htmlspecialchars($name));
			    $app->enqueueMessage($message, 'message');

			    $this->redirectToList();

			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', $viewName, 'details', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', $viewName, 'details', $this, $model);
	    return false;
    }

    public function createKey() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateCreateKey())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    $applicationId = $model->createKey();
		    if ($applicationId)
		    {
			    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$applicationId");
			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    public function updateKey() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateUpdateKey())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    $applicationId = $model->updateKey();
		    if ($applicationId)
		    {
			    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$applicationId");

			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    public function enableKey() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateToggleKeyState())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    $applicationId = $model->toggleKeyState();
		    if ($applicationId)
		    {
			    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$applicationId");

			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    public function disableKey() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateToggleKeyState())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    $applicationId = $model->toggleKeyState();
		    if ($applicationId)
		    {
			    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$applicationId");

			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    public function deleteKey() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateDeleteKey())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    $applicationId = $model->deleteKey();
		    if ($applicationId)
		    {
			    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$applicationId");

			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    public function createOAuth() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateCreateOAuth())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }

		    $applicationId = $model->createOAuth();
		    if ($applicationId)
		    {
			    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$applicationId");
			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    public function updateOAuth() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateUpdateOAuth())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    $applicationId = $model->updateOAuth();
		    if ($applicationId)
		    {
			    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$applicationId");

			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    public function enableOAuth() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateToggleOAuthState())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    $applicationId = $model->toggleOAuthState();
		    if ($applicationId)
		    {
			    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$applicationId");

			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    public function disableOAuth() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateToggleOAuthState())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    $applicationId = $model->toggleOAuthState();
		    if ($applicationId)
		    {
			    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$applicationId");

			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    public function deleteOAuth() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateDeleteOAuth())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    $applicationId = $model->deleteOAuth();
		    if ($applicationId)
		    {
			    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$applicationId");

			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    /**
     * Create External OAuth record
     * @throws Exception
     */
    public function createExtOAuth() 
    {
        // Get user input data for the record
        // App Id is from hidden field
        $jInput = JFactory::getApplication()->input;
        $appId = $jInput->post->get('applicationId', null, 'STRING');
        $clientId = $jInput->post->get('clientId', null, 'RAW');
        $corsOrigin = $jInput->post->get('corsOrigins', '*', 'RAW');
        
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateExtOAuth($appId, $clientId, $corsOrigin))
		    {
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }

		    // Try to create create the setting
		    if ($model->createExtOAuth($appId, $clientId, $corsOrigin))
		    {
			    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$appId");
			    return false;
		    }
		    else
		    {
			    // Errors are already queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    /**
     * Update External OAuth
     * @throws Exception
     */
    public function updateExtOAuth()
    {
        // Get user input data
        // App Id is from hidden field
        $jInput = JFactory::getApplication()->input;
        $appId = $jInput->post->get('applicationId', null, 'STRING');
        $clientId = $jInput->post->get('clientId', null, 'RAW');
        $objectId = $jInput->post->get('objectId', false, 'STRING');
        $corsOrigin = $jInput->post->get('corsOrigins', '*', 'RAW');
        $objectEnabled = $jInput->post->get('objectEnabled', true, 'BOOLEAN');

        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateExtOAuth($appId, $clientId, $corsOrigin, $objectId))
		    {
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }

		    // Update the record
		    if ($model->updateExtOAuth($appId, $clientId, $corsOrigin, $objectId, $objectEnabled))
		    {
			    $this->redirectTo("view=application&layout=edit&tab=authentication&applicationId=$appId");
			    return false;
		    }
		    else
		    {
			    // Errors are already queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    /**
     * Change Enable/Disable option for External OAuth setting
     * @throws Exception
     */
    public function changeExtOAuthState() 
    {
        // Get the needed data - it's from hidden fileds
        $jInput = JFactory::getApplication()->input;
        $appId = $jInput->post->get('applicationId', null, 'STRING');
        $objectId = $jInput->post->get('objectId', null, 'STRING');
        
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateExtOAuth($appId, $objectId))
		    {
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Save the change
		    if ($model->toggleExtOAuthState($appId, $objectId))
		    {
			    $this->redirectTo('view=application&layout=edit&tab=authentication&applicationId=' . $appId);
			    return false;
		    }
		    else
		    {
			    // Errors are already queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    /**
     * Delete External OAuth setting
     * @throws Exception
     */
    public function deleteExtOAuth()
    {
        // Get needed data
        $jInput = JFactory::getApplication()->input;
        $appId = $jInput->post->get('applicationId', null, 'STRING');
        $objectId = $jInput->post->get('objectId', null, 'STRING');
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateExtOAuth($appId, $objectId))
		    {
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }

		    // Delete the setting
		    if ($model->deleteExtOAuth($appId, $objectId))
		    {
			    $this->redirectTo('view=application&layout=edit&tab=authentication&applicationId=' . $appId);
			    return false;
		    }
		    else
		    {
			    // Errors are alreday queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'authentication', $this, $model);
	    return false;
    }

    public function addSharedUsers() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'sharing', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateAddSharedUsers())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', 'edit', 'sharing', $this, $model);

			    return false;
		    }

		    $applicationId = $model->addSharedUsers();
		    if ($applicationId)
		    {
			    $users = JRequest::getVar('users', array(), 'post', 'ARRAY');
			    $app->setUserState(ApiPortalSessionVariables::APP_EDIT_NEW_USER, $users);

			    $this->redirectTo("view=application&layout=edit&tab=sharing&applicationId=$applicationId");
			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'sharing', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'sharing', $this, $model);
	    return false;
    }

    public function removeSharedUser() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Application');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'applicationId'))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('application', 'edit', 'sharing', $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validateRemoveSharedUser())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('application', 'edit', 'sharing', $this, $model);

			    return false;
		    }

		    $applicationId = $model->removeSharedUser();
		    if ($applicationId)
		    {
			    $name    = JRequest::getVar('userName', 'null', 'post', 'STRING');
			    $type    = JText::_('COM_APIPORTAL_APPLICATION_USER_OBJECT');
			    $message = sprintf(JText::_('COM_APIPORTAL_APPLICATION_REMOVE_CONFIRMATION'), $type, htmlspecialchars($name));
			    $app->enqueueMessage($message, 'message');

			    $this->redirectTo("view=application&layout=edit&tab=sharing&applicationId=$applicationId");

			    return true;
		    }
		    else
		    {
			    // Errors are alredy queued up - should only be unexpected HTTP errors at this point
			    ApiPortalHelper::displayView('application', 'edit', 'sharing', $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('application', 'edit', 'sharing', $this, $model);
	    return false;
    }

    private function redirectToList() {
        $link = JRoute::_("index.php?option=com_apiportal&view=applications", false);
        $this->setRedirect($link);
    }

    private function redirectTo($where) {
        $link = JRoute::_("index.php?option=com_apiportal&$where", false);
        $this->setRedirect($link);
    }
}
