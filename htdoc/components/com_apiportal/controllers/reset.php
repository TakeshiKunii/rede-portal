<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class ApiPortalControllerReset extends JControllerLegacy
{
    public function submit() {
	    $model = $this->getModel('Reset');

	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input, 'email'))
	    {
		    $app   = JFactory::getApplication();

		    // Check if this options is enabled in API Manager
		    $config = ApiPortalHelper::getAPIManagerAppInfo();
		    if (!property_exists($config, 'resetPasswordEnabled') || !$config->resetPasswordEnabled)
		    {
			    $app->enqueueMessage(JText::_('COM_APIPORTAL_APIMANAGER_RESET_PASSWORD_DISABLED_ERROR'), 'error');
			    ApiPortalHelper::displayView('reset', 'default', null, $this, $model);

			    return false;
		    }

		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('reset', 'default', null, $this, $model);

			    return false;
		    }

		    // Validate input fields
		    if (!$model->validate())
		    {
			    ApiPortalHelper::enqueueErrors($app, $model->getErrors());
			    ApiPortalHelper::displayView('reset', 'default', null, $this, $model);

			    return false;
		    }

		    $result = $model->submit();
		    if ($result == ApiPortalModelReset::RESET_SUCCESS)
		    {
			    $app->enqueueMessage(JText::_('COM_APIPORTAL_RESET_REQUEST_SUCCESS'));
			    $this->setRedirect(JRoute::_('index.php?option=com_users&view=login', false));

			    return true;
		    }
		    else
		    {
			    if ($result == ApiPortalModelReset::RESET_FAILED)
			    {
				    $app->enqueueMessage(JText::_('COM_APIPORTAL_RESET_REQUEST_FAILED'), 'error');
			    }
			    elseif ($result == ApiPortalModelReset::RESET_ERROR)
			    {
				    $app->enqueueMessage(JText::_('COM_APIPORTAL_RESET_REQUEST_ERROR'), 'error');
			    }

			    ApiPortalHelper::displayView('reset', 'default', null, $this, $model);

			    return false;
		    }
	    }

	    ApiPortalHelper::displayView('reset', 'default', null, $this, $model);
	    return false;
    }

    public function validate() {
        $app = JFactory::getApplication();
        $model = $this->getModel('Reset');

        $result = $model->confirm();
        if ($result == ApiPortalModelReset::RESET_SUCCESS) {
            $app->enqueueMessage(JText::_('COM_APIPORTAL_RESET_SUCCESS'));
            $this->setRedirect(JRoute::_('index.php?option=com_users&view=login', false));
            return true;
        } else {
            if ($result == ApiPortalModelReset::RESET_FAILED) {
                $app->enqueueMessage(JText::_('COM_APIPORTAL_RESET_FAILED'), 'error');
            } elseif ($result == ApiPortalModelReset::RESET_ERROR) {
                $app->enqueueMessage(JText::_('COM_APIPORTAL_RESET_ERROR'), 'error');
            }

            ApiPortalHelper::displayView('reset', 'default', null, $this, $model);
            return false;
        }
    }
}
