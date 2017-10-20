<?php
defined('_JEXEC') or die('Restricted access');

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('JPATH_PLATFORM')) {
    define('JPATH_PLATFORM', __DIR__ . DS . 'libraries');
}

require_once JPATH_PLATFORM.DS.'loader.php';

/*
 * It's too early in the loading process to have JPluginHelper loaded,
 * so simply check if the API Portal system plugin directory exists.
 */
$apiPortalSystemPluginDir = __DIR__ . DS.'plugins'.DS.'system'.DS.'apiportal';
if (is_dir($apiPortalSystemPluginDir)) {
    require_once $apiPortalSystemPluginDir.DS.'extend.php';
    PlgSystemApiPortalExtend::extend(__DIR__);
}
