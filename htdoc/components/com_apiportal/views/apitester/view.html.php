<?php

defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'apiconfiguration.php';
require_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_apiportal'.DS.'models'.DS.'apiportal.php';
jimport('joomla.application.component.view');

$document = JFactory::getDocument();

//Optimized CSS/JS jquery, removing: components/com_apiportal/assets/js/jquery.min.js
foreach ($document->_scripts as $key => $value) {
    if (strpos($key, 'jquery-noconflict') !== FALSE) {
        //On the test page only the second element should be removed: ['/Joomla3f/media/jui/js/jquery-noconflict.js']
        unset($document->_scripts[$key]);
    }
}

$document->addScript('components/com_apiportal/assets/js/moment.min.js');
$document->addScript('components/com_apiportal/assets/js/marked/lib/marked.js');

$document->addScript('components/com_apiportal/assets/js/swagger/ui/jquery.slideto.min.js');
$document->addScript('components/com_apiportal/assets/js/swagger/ui/jquery.wiggle.min.js');
$document->addScript('components/com_apiportal/assets/js/swagger/ui/jquery.ba-bbq.min.js');
$document->addScript('components/com_apiportal/assets/js/swagger/ui/handlebars-1.0.0.js');
$document->addScript('components/com_apiportal/assets/js/swagger/ui/underscore-min.js');
$document->addScript('components/com_apiportal/assets/js/swagger/ui/backbone-min.js');

$document->addScript('components/com_apiportal/assets/js/swagger/ui/btoa.js');
$document->addScript('components/com_apiportal/assets/js/swagger/ui/highlight.7.3.pack.js');

$document->addScript('components/com_apiportal/assets/js/swagger/shred.bundle.js');
$document->addScript('components/com_apiportal/assets/js/swagger/swagger-oauth.js');
$document->addScript('components/com_apiportal/assets/js/swagger/authorizations.js');
$document->addScript('components/com_apiportal/assets/js/swagger/swagger.js');
$document->addScript('components/com_apiportal/assets/js/swagger/swagger-ext.js');
$document->addScript('components/com_apiportal/assets/js/swagger/ui/swagger-ui.js');
$document->addScript('components/com_apiportal/assets/js/swagger/oauth-implicit.js');
$document->addScript('components/com_apiportal/assets/js/swagger/oauth-clientcredentials.js');
$document->addStyleSheet('components/com_apiportal/assets/css/swagger-screens.css');
$document->addScript('components/com_apiportal/assets/js/swagger/swagger-load.js');

//pso select application key text field
$document->addScript('media/jui/js/chosen.jquery.min.js');
$document->addStyleSheet('media/jui/css/chosen.css');
$document->addStyleSheet('components/com_apiportal/assets/css/chosen-overrides.css');


class APIPortalViewApiTester extends JViewLegacy
{
    protected $item;
    protected $itemJson;

    public function display($tpl = null)
    {
        //Check for download error
        $jInput = JFactory::getApplication()->input;
        $downloadError = $jInput->get('fail', false, 'BOOLEAN');
        if ($downloadError) {
            JError::raiseWarning(null, JText::_('COM_APIPORTAL_APITEST_DOWNLOAD_ERROR'));
        }

        // check for errors
        if (count($errors = $this->get("Errors"))) {
            Jerror::raiseError(500, implode('<br />', $errors));
            return false;
        }

        $modelApiportal = new ApiportalModelapiportal();
        $this->proxyTimeout = $modelApiportal->getProxyTimeout();
        
        //cross check for  public mode user
        $currentSessionId = JFactory::getSession()->get('user')->get('id');
        $appUserId = JFactory::getSession()->get('appUserId');

        if($appUserId != $currentSessionId){
        	$app = JFactory::getApplication();
        	$url = JRoute::_('index.php?option=com_users&view=login');
        	$app->redirect($url, JText::_('JGLOBAL_SIGN_IN_REQUIRED'));
        }

        parent::display($tpl);
    }

    /**
     * Generate base64 encoded uri string for current page
     * It's used for SDK and swagger download as return uri on error
     * Also checks for fail param and removes it (this param is added from Sdk, swagger
     * download on error)
     * @return string
     */
    public function generateReturnUri()
    {
        //Get query from the uri
        $urlQuery = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        //Get path from the uri
        $urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        //Parse the uri query string
        parse_str($urlQuery, $urlQueryArray);
        //Remove if fail param exist
        if (array_key_exists('fail', $urlQueryArray)) {
            unset($urlQueryArray['fail']);
        }
        //Add the rest of the params to the path
        $urlPath = $urlPath . (!empty($urlQueryArray) ? '?' . http_build_query($urlQueryArray) : null);

        //return path as base64 string
        return base64_encode($urlPath);
    }
}
