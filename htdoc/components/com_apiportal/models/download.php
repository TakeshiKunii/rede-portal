<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modelitem');

class ApiPortalModelDownload extends JModelItem
{
    protected $api;
    /* If change these two const, change them in view class too! */
    CONST API_REST = 'rest';
    CONST API_WSDL = 'wsdl';

    public function getApi() {
        if (!isset($this->api)) {
            $this->api = $this->_getApi();
        }
        $this->api = $this->_getApi();
        return $this->api;
    }

    private function _getApi() {
        $jinput = JFactory::getApplication()->input;
        $apiName = $jinput->get('apiName', null, 'STRING');
        $apiName = ApiPortalHelper::cleanHtml($apiName, false, true);
        $apiType = $jinput->get('apiType', self::API_REST, 'STRING');
        $apiType = ApiPortalHelper::cleanHtml($apiType, false, true);
        $apiID = $jinput->get('apiID', null, 'STRING');
        $apiID = ApiPortalHelper::cleanHtml($apiID, false, true);

        if ($apiName == null) {
            error_log("Task getApi failed: missing apiName");
            return null;
        }

        if ($apiID == null && $apiType == self::API_WSDL) {
            error_log("Task getApi failed: missing apiID");
            return null;
        }

        if ($apiType != self::API_REST && $apiType != self::API_WSDL) {
            error_log("Task getApi failed: missing apiType");
            return null;
        }

        $api = new stdClass();

        switch ($apiType) {
            case self::API_REST :
                $path = ApiPortalHelper::getVersionedBaseFolder()."/discovery/swagger/api".'/'.urlencode($apiName);
                $api->content = ApiPortalHelper::doGet($path, array(), true, false);
                $api->mimeType = ApiPortalHelper::getHeader('Content-Type');
                $api->apiName = $apiName;
                $api->type = $apiType;
                break;
            case self::API_WSDL :
                $path = ApiPortalHelper::getVersionedBaseFolder()."/discovery/swagger/apis".'/'.urlencode($apiID).'/service-definition';
                $api->content = ApiPortalHelper::doGet($path, array(), true, false);
                $api->mimeType = ApiPortalHelper::getHeader('Content-Type');
                $api->apiName = $apiName;
                $api->type = $apiType;
                break;
        }

        return $api;
    }
}
