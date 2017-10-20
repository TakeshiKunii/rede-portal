<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class ApiPortalViewRegistration extends JViewLegacy
{
    public function display($tpl = null)
    {
	    $isErrorExist = false;

        // Get the app info data
        $config = ApiPortalHelper::getAPIManagerAppInfo();
        // If there is a loginNameRegex assign it and pass it to the view for further use
        $this->loginNameRegex = isset($config->loginNameRegex) ? $config->loginNameRegex : null;

	    // Check if this options is enabled in API Manager
	    if (!property_exists($config, 'registrationEnabled') || !$config->registrationEnabled) {
		    //Prevent double message for disabled registration (this on is for the front, there is a separate check in
		    // the action)
		    $isErrorExist = array_search('error', array_column(JFactory::getApplication()->getMessageQueue(), 'type'));
		    // If there is not an error type in the queue add the warning
		    if ($isErrorExist === false) {
			    JFactory::getApplication()->enqueueMessage(JText::_('COM_APIPORTAL_REGISTRATION_APIMANAGER_DISABLED_ERROR'), 'warning');
		    }
	    }

        parent::display($tpl);
    }
}
