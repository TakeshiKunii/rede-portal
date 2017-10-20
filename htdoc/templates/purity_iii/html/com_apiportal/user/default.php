<?php
    defined('_JEXEC') or die('Restricted access');

    $userListURL = JRoute::_('index.php?option=com_apiportal&view=users', false);

    // No layout specified, redirect back to application list
    $app = JFactory::getApplication();
    $app->redirect($userListURL);
?>
