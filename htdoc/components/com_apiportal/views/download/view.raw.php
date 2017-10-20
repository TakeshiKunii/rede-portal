<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');

class ApiPortalViewDownload extends JViewLegacy
{
    CONST API_REST = 'rest';
    CONST API_WSDL = 'wsdl';

    public function display($tpl = null) {
        // Called on the 'image' model
        $api = $this->get('Api');

        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors));
        }

        if ($api->type == self::API_REST) {
            $jAppWeb = JApplicationWeb::getInstance();
            $jAppWeb->setHeader('Content-type', $api->mimeType);
            $jAppWeb->setHeader('Content-Disposition', 'attachment; filename="' . $api->apiName . '.json"');
            $jAppWeb->sendHeaders();
        } else if ($api->type == self::API_WSDL) {
            $jAppWeb = JApplicationWeb::getInstance();
            $jAppWeb->setHeader('Content-type', $api->mimeType);
            $jAppWeb->setHeader('Content-Disposition', 'attachment; filename="' . $api->apiName . '.xml"');
            $jAppWeb->sendHeaders();
        }

        echo $api->content;
    }
}
