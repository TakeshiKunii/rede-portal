<?php
/*------------------------------------------------------------------------
# apiportal.php - API Portal Component
# ------------------------------------------------------------------------
# author    Axway
# copyright Copyright (C) 2014. All Rights Reserved
# license   GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
# website   www.axway.com
-------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// Added for Joomla 3.0
if(!defined('DS')){
	define('DS',DIRECTORY_SEPARATOR);
};

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_apiportal')){
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
};

// Load cms libraries
JLoader::registerPrefix('J', JPATH_PLATFORM . '/cms');
// Load joomla libraries without overwrite
JLoader::registerPrefix('J', JPATH_PLATFORM . '/joomla',false);

// require helper files
JLoader::register('ApiportalHelper', dirname(__FILE__) . DS . 'helpers' . DS . 'apiportal.php');
JLoader::register('ApiPortalConfiguration', dirname(__FILE__) . DS . 'helpers' . DS . 'apiconfiguration.php');


// import joomla controller library
jimport('joomla.application.component.controller');

// Add CSS file for all pages
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_apiportal/assets/css/apiportal.css');
$document->addScript('components/com_apiportal/assets/js/apiportal.js');

// Get an instance of the controller prefixed by Apiportal
$controller = JControllerLegacy::getInstance('Apiportal');

// Perform the Request task
$controller->execute(JFactory::getApplication()->input->get('task'));

// Redirect if set by the controller
$controller->redirect();

?>