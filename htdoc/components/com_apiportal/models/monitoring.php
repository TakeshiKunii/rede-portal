<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modellist');

require_once JPATH_COMPONENT.'/helpers/monitoring.php';

class APIPortalModelMonitoring extends JModelList
{
    protected $items;

    public function getItems()
    {
        if (!isset($this->items)) {
            //TODO: ttotev: Implement this
            $this->items = "";
        }
        return $this->items;
    }

}
