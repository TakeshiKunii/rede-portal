<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class ApiPortalControllerApplication extends JControllerLegacy
{
    public function authorise($task) {
        // Make sure the session is valid before proceeding with tasks
        ApiPortalHelper::checkSession();
    }

    // AJAX only task - returns JSON.
    public function newOAuthSecret() {
        $model = $this->getModel('Application');
        echo new JResponseJson($model->newOAuthSecret());
    }

    // AJAX only task - returns JSON.
    public function updateSharedUser() {
        $model = $this->getModel('Application');
        echo new JResponseJson($model->updateSharedUser());
    }
}
