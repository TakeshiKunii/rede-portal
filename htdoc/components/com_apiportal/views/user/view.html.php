<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class ApiPortalViewUser extends JViewLegacy
{
    protected $item;

    public function display($tpl = null) {
        // Make sure the session is valid before displaying view
        ApiPortalHelper::checkSession();

        // Called on the 'user' model
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');

        // Get the app info data
        $config = ApiPortalHelper::getAPIManagerAppInfo();
        // If there is a loginNameRegex assign it and pass it to the view for further use
        // Currently is needed by user edit page
        $this->loginNameRegex = isset($config->loginNameRegex) ? $config->loginNameRegex : null;

        parent::display($tpl);
    }
}
