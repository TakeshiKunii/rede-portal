<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class ApiPortalViewReset extends JViewLegacy
{
    public function display($tpl = null) {

	    $isErrorExist = false;

	    // Get the app info data
	    $config = ApiPortalHelper::getAPIManagerAppInfo();

	    // Check if this options is enabled in API Manager
	    if (!property_exists($config, 'resetPasswordEnabled') || !$config->resetPasswordEnabled) {
		    //Prevent double message for disabled registration (this on is for the front, there is a separate check in
		    // the action)
		    $isErrorExist = array_search('error', array_column(JFactory::getApplication()->getMessageQueue(), 'type'));
		    // If there is not an error type in the queue add the warning
		    if ($isErrorExist === false) {
			    JFactory::getApplication()->enqueueMessage(JText::_('COM_APIPORTAL_APIMANAGER_RESET_PASSWORD_DISABLED_ERROR'), 'warning');
		    }
	    }

        parent::display($tpl);
    }
}
