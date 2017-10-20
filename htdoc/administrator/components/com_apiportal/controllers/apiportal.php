<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controlleradmin library
jimport('joomla.application.component.controlleradmin');

class APIPortalControllerAPIPortal extends JControllerAdmin
{
	public function getModel($name = 'apiserver', $prefix = 'APIPortalModel')
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		
		return $model;
	}
}