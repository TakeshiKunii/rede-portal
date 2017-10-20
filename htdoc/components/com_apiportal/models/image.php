<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modelitem');

class ApiPortalModelImage extends JModelItem
{
    protected $image;

    public function getImage() {
        if (!isset($this->image)) {
            $this->image = $this->_getImage();
        }
        return $this->image;
    }

    private function _getImage() {
        $applicationId = JRequest::getVar('applicationId', null, 'get', 'STRING');
        $apiId = JRequest::getVar('apiId', null, 'get', 'STRING');
        $userId = JRequest::getVar('userId', null, 'get', 'STRING');

        if ($applicationId == null && $apiId == null && $userId==null) {
            error_log("Task getImage failed: missing image ID");
            return null;
        }

        $image = new stdClass();

        $baseFolder = ApiPortalHelper::getVersionedBaseFolder();
        if ($applicationId) {
            $image->content = ApiPortalHelper::doGet($baseFolder . "/applications/$applicationId/image");
        } else if ($apiId) {
            $image->content = ApiPortalHelper::doGet($baseFolder . "/discovery/swagger/apis/$apiId/image");
        } else {
            $image->content = ApiPortalHelper::doGet($baseFolder . "/users/$userId/image");
        }
        $image->mimeType = ApiPortalHelper::getHeader('Content-Type');

        return $image;
    }
}
