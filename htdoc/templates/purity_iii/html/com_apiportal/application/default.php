<?php
    defined('_JEXEC') or die('Restricted access');

    $appListURL = JRoute::_('index.php?option=com_apiportal&view=applications', false);

    // No layout specified, redirect back to application list
    $app = JFactory::getApplication();
    $app->redirect($appListURL);
