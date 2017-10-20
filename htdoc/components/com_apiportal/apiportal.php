<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

JHtml::_('jquery.framework');

$document = JFactory::getDocument();

$document->addStyleSheet('components/com_apiportal/assets/css/apiportal.css');

$document->addScript('components/com_apiportal/assets/js/jquery.metadata.js');
$document->addScript('components/com_apiportal/assets/js/tablesorter/jquery.tablesorter.js');
$document->addScript('components/com_apiportal/assets/js/tablesorter/jquery.tablesorter.widgets.js');
$document->addScript('components/com_apiportal/assets/js/jquery.validate.js');

$document->addScript('components/com_apiportal/assets/js/highcharts/js/highcharts.js');
$document->addScript('components/com_apiportal/assets/js/apiportal.js');


//Import das classes usadas nas customizações da rede
JLoader::register('APIPortalModelUsers', dirname(__FILE__) . DS . 'models' . DS . 'users.php');
JLoader::register('APIPortalModelUser', dirname(__FILE__) . DS . 'models' . DS . 'user.php');
JLoader::register('ApIPortalModelApplications', dirname(__FILE__) . DS . 'models' . DS . 'applications.php');


// Autoload helpers on demand
JLoader::register('ApiPortalHelper', dirname(__FILE__) . DS . 'helpers' . DS . 'apiportal.php');
JLoader::register('ApiPortalValidator', dirname(__FILE__) . DS . 'helpers' . DS . 'validator.php');
JLoader::register('ApiPortalDownload', dirname(__FILE__) . DS . 'helpers' . DS . 'download.php');
JLoader::register('ApiPortalSessionVariables', dirname(__FILE__) . DS . 'helpers' . DS . 'SessionVariables.php');


// Get an instance of the controller prefixed by ApiPortal
$controller = JControllerLegacy::getInstance('ApiPortal');

//check for Public API mode
ApiPortalHelper::PublicUserLogin();

// Get the task to perform, if defined. Otherwise this is a view display only.
$task = JRequest::getCmd('task');
if ($task) {
    // Make sure that we are authorized to perform this task. Method redirects if not.
    $controller->authorise($task);
}

// Perform the request task
$controller->execute($task);

// Redirect if set by the controller
$controller->redirect();
