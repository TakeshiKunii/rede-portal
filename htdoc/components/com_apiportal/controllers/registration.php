<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');
jimport('joomla.application.component.helper');

class ApiPortalControllerRegistration extends JControllerLegacy
{
    public function submit() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Registration');
        $data = JRequest::getVar('apidata', array(), "post", 'ARRAY');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'password'))
	    {
		    // Check if this options is enabled in API Manager
		    $config = ApiPortalHelper::getAPIManagerAppInfo();
		    if (!property_exists($config, 'registrationEnabled') || !$config->registrationEnabled)
		    {
			    $app->enqueueMessage(JText::_('COM_APIPORTAL_REGISTRATION_APIMANAGER_DISABLED_ERROR'), 'error');
			    ApiPortalHelper::displayView('registration', 'default', null, $this, $model);

			    return false;
		    }

		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('registration', 'default', null, $this, $model);

			    return false;
		    }

		    $app->setUserState(ApiPortalSessionVariables::USER_REGISTRATION_DATA, null);
		    $validationOK = true;

		    // Validate CAPTCHA
		    if (!self::validateCaptcha(JRequest::get('post')))
		    {
			    $model->setError(JText::_('COM_APIPORTAL_REGISTRATION_INVALID_CAPTCHA'));
			    $validationOK = false;
		    }

		    // Validate input fields
		    if (!$model->validate($data))
		    {
			    // Errors have already been set
			    $validationOK = false;
		    }

		    if (!$validationOK)
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    $app->setUserState(ApiPortalSessionVariables::USER_REGISTRATION_DATA, $data);

			    ApiPortalHelper::displayView('registration', 'default', null, $this, $model);

			    return false;
		    }

		    // We do not allow users that have joomla admin rights to login into API Portal
		    if (!$model->checkIsAdminAccount($data['email']))
		    {

			    $app->setUserState(ApiPortalSessionVariables::USER_REGISTRATION_DATA, $data);
			    $app->enqueueMessage(JText::_('COM_APIPORTAL_REGISTER_ADMIN_CONFLICT'), 'warning');
			    ApiPortalHelper::displayView('registration', 'default', null, $this, $model);

			    // Logs a message
			    ApiPortalHelper::logUserConflictMessage($data['email']);

			    return false;
		    }

		    $result = $model->submit();
		    if ($result == ApiPortalModelRegistration::REGISTER_SUCCESS)
		    {
			    $app->enqueueMessage(JText::_('COM_APIPORTAL_REGISTRATION_SUCCESS'));
			    $this->setRedirect(JRoute::_('index.php?option=com_users&view=login', false));

			    return true;
		    }
		    else
		    {
			    if ($result == ApiPortalModelRegistration::REGISTER_FAILED)
			    {
				    $app->enqueueMessage(JText::_('COM_APIPORTAL_REGISTRATION_FAILED'), 'error');
			    }
			    elseif ($result == ApiPortalModelRegistration::REGISTER_ERROR)
			    {
				    $app->enqueueMessage(JText::_('COM_APIPORTAL_REGISTRATION_ERROR'), 'error');
			    }
			    elseif ($result == ApiPortalModelRegistration::REGISTER_DUPLICATED_EMAIL)
			    {
				    $app->enqueueMessage(JText::_('COM_APIPORTAL_REGISTRATION_DUPLICATED_EMAIL'), 'error');
			    }
			    elseif ($result == ApiPortalModelRegistration::REGISTER_DUPLICATED_USERNAME)
			    {
				    $app->enqueueMessage(JText::_('COM_APIPORTAL_REGISTRATION_DUPLICATED_USERNAME'), 'error');
			    }

			    $app->setUserState(ApiPortalSessionVariables::USER_REGISTRATION_DATA, $data);

			    ApiPortalHelper::displayView('registration', 'default', null, $this, $model);
			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('registration', 'default', null, $this, $model);
	    return false;
    }

    public function validate() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Registration');

        $result = $model->confirm();
        if ($result == ApiPortalModelRegistration::REGISTER_SUCCESS) {
            $app->enqueueMessage(JText::_('COM_APIPORTAL_REGISTRATION_VALIDATION_SUCCESS'));
            $this->setRedirect(JRoute::_('index.php?option=com_users&view=login', false));
            return true;
        } else {
            if ($result == ApiPortalModelRegistration::REGISTER_FAILED) {
                $app->enqueueMessage(JText::_('COM_APIPORTAL_REGISTRATION_VALIDATION_FAILED'), 'error');
            } elseif ($result == ApiPortalModelRegistration::REGISTER_ERROR) {
                $app->enqueueMessage(JText::_('COM_APIPORTAL_REGISTRATION_VALIDATION_ERROR'), 'error');
            }

            ApiPortalHelper::displayView('registration', 'default', null, $this, $model);
            return false;
        }
    }

    // TODO: Make CAPTCHA mandatory if enabled.
    private static function validateCaptcha($post) {
        JPluginHelper::importPlugin('captcha');
        $dispatcher = JEventDispatcher::getInstance();
        $isCaptcha = false;
        try {
            if (isset($post['recaptcha_challenge_field'])) {
                $isCaptcha = true;
            }
        } catch (Exception $ex) {
            //
        }
        if ($isCaptcha) {
            $res = $dispatcher->trigger('onCheckAnswer' , $post['recaptcha_response_field']);
            if (!$res[0]) {
                return false;
            }
        } else if (ApiPortalHelper::isCaptchaRequired()) {
            return false;
        }
        return true;
    }
}
