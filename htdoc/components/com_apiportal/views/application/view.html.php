<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

$document = JFactory::getDocument();
$document->addScript('components/com_apiportal/assets/js/marked/lib/marked.js');

class ApiPortalViewApplication extends JViewLegacy
{
    protected $item;

    public function display($tpl = null) {
        // Make sure the session is valid before displaying view
        ApiPortalHelper::checkSession();
        $params = json_decode(JFactory::getSession()->get('user')->params);
        $this->userPid = $params->pid;
        $config = ApiPortalHelper::getAPIMangerConfig();
        $this->appAutoApprove = $config->autoApproveApplications;
        $this->delegateApplicationAdministration = $config->delegateApplicationAdministration ;

        // Called on the 'application' model
        $this->item = $this->get('Item');

        parent::display($tpl);
    }
}
