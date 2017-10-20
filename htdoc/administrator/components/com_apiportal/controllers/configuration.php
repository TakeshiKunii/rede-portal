<?php
/*------------------------------------------------------------------------
# apiportalconfig.php - API Portal Config Component
# ------------------------------------------------------------------------
# author    Axway
# copyright Copyright (C) 2014. All Rights Reserved
# license   GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
# website   www.axway.com
-------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controlleradmin library
jimport('joomla.application.component.controlleradmin');

class APIPortalControllerConfiguration extends JControllerAdmin
{
    private $errors;
    
    /**
    * Method to get a model object, loading it if required.
    *
    * @param   string  $name    The model name. Optional.
    * @param   string  $prefix  The class prefix. Optional.
    * @param   array   $config  Configuration array for model. Optional.
    *
    * @return  object  The model.
    *
    * @since   12.2
    */
    public function getModel($name = '', $prefix = '', $config = array('ignore_request' => true))
    {
            if (empty($name))
            {
                    $name = $this->context;
            }
            
            $this->errors = array ( 1 => "SSL verification is turned on but no certificate was provided. Please provide a certificate.",
                2 => "Server settings was not saved into the database.");
            
            return parent::getModel($name, $prefix, $config);
    }
    
    public function save(){
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $jinput = JFactory::getApplication()->input;
        $files = $jinput->files->get('jform');
        
        $certFile = $files['cert'];
        $post = $jinput->get('jform','','array');
        $host = "";
        $port = "";
        $verifySSL = "0";
        $verifyHost = "0";
        $oauthPath = "";
        $oauthPort = "";
	    $isSsoOn = 0;
	    $ssoEntityID = "";
	    $ssoPath = "";
	    $publicApi = "0";
	    $publicApiAccountLoginName="";
	    $publicApiAccountPassword="";

        if(isset($post) && is_array($post)){
            $host = array_key_exists('host', $post)?$post["host"]:null;
            $port = array_key_exists('port', $post)?$post["port"]:null;
            $verifySSL = array_key_exists('verifyCrt', $post)?$post["verifyCrt"]:null;
            $verifyHost = array_key_exists('verifySslHost', $post)?$post["verifySslHost"]:null;
            $oauthPath = array_key_exists('oauthPath', $post)?$post["oauthPath"]:null;
            $oauthPort = array_key_exists('oauthPort', $post)?$post["oauthPort"]:null;
	        $ssoEntityID = array_key_exists('ssoEntityID', $post) ? $post['ssoEntityID'] : null;
	        $isSsoOn = array_key_exists('isSsoOn', $post) ? $post['isSsoOn'] : null;
	        $ssoPath = array_key_exists('ssoPath', $post) ? $post['ssoPath'] : null;
	        $publicApi = array_key_exists('publicApi', $post) ? $post['publicApi'] : null;
	        $publicApiAccountLoginName = array_key_exists('publicApiAccountLoginName', $post) ? $post['publicApiAccountLoginName'] : null;
	        $publicApiAccountPassword = array_key_exists('publicApiAccountPassword', $post) ? $post['publicApiAccountPassword'] : null;
        }
        
        $model	= $this->getModel('apiportal', '', array());
	    $errMsg = '';
	    
	    if($post["publicApi"] == 0 && $model->getPublicApi() == 1 ) {
	    	$model->destroyPublicuserSession();
	    }
	    
        $state = $model->saveConfiguration($host, $port, $verifySSL, $certFile, $verifyHost, $oauthPath, $oauthPort, $ssoEntityID, $isSsoOn, $ssoPath, $publicApi,$publicApiAccountLoginName, $publicApiAccountPassword, $errMsg);
		
        // Preset the redirect
        if($state){
            $this->setRedirect(JRoute::_('index.php?option=com_apiportal&view=apiportal', false), JText::_('COM_APIPORTAL_CONFIGURATION_SAVE_SUCCESS_MESSAGE'), 'message');
        } else {
            $this->setRedirect(JRoute::_('index.php?option=com_apiportal&view=apiportal', false), $errMsg, "error");
        }
       
        return true;
    }
}