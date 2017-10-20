<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class ApiPortalViewApplications extends JViewLegacy
{
    protected $items;

    public function display($tpl = null) {
        // Make sure the session is valid before displaying view
        ApiPortalHelper::checkSession();

        // Called on the 'applications' model
        $this->items = $this->get('Items');

        parent::display($tpl);
    }
}
