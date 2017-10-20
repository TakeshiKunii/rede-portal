<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class ApiPortalViewUsers extends JViewLegacy
{
    protected $items;

    public function display($tpl = null) {
        // Make sure the session is valid before displaying view
        ApiPortalHelper::checkSession();

	    /**
	     * Stop access to the page from normal users
	     */
	    if (!JFactory::getUser()->authorise('core.manage', 'com_content')) {
		    $app = &JFactory::getApplication();
		    $app->redirect(JUri::base());
	    }

        // Called on the 'users' model
        $this->items = $this->get('Items');
        $this->config = $this->get('Config');

        parent::display($tpl);
    }
}
