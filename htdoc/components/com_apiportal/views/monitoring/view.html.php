<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class APIPortalViewMonitoring extends JViewLegacy
{
	protected $item;

	public function display($tpl = null)
	{
        $this->item = $this->get('Item');
        
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors));
        }
        
        parent::display($tpl);
	}
}
