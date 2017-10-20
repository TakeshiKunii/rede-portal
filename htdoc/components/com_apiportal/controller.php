<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class ApiPortalController extends JControllerLegacy
{
    public function display($cachable = false, $urlparams = array()) {
      parent::display($cachable, $urlparams);
    }
  }
