<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');

class ApiPortalViewDefinition extends JViewLegacy
{
    public function display($tpl = null) {
        $jInput = JFactory::getApplication()->input;
        //Get params from the request
        //Path for download
        $path = $jInput->get('path', null, 'STRING');
        //Type of download - direct or with success request first
        $successRequestFirst = $jInput->get('successRequestFirst', false, 'BOOLEAN');
        //Where to redirect on error
        $errorRedirect = base64_decode($jInput->get('stateReturn', null, 'STRING'));

        //Check if we have $path and $apiName
        if ($path == null) {
            error_log("Task getApi failed: missing path");
            JFactory::getApplication()->redirect('/');
        }

        //Start download
        $apiPortalDownload = new ApiPortalDownload($path, 500, $errorRedirect, (bool)$successRequestFirst);
        $apiPortalDownload->download();
    }
}
