<?php
/*------------------------------------------------------------------------
# apiportal.php - API Portal Component
# ------------------------------------------------------------------------
# author    Axway
# copyright Copyright (C) 2014. All Rights Reserved
# license   GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
# website   www.axway.com
-------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the Joomla modellist library
jimport('joomla.application.component.modellist');
jimport('joomla.filesystem.file');
require_once JPATH_SITE.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'SessionVariables.php';

/**
 * Apiportal Model
 */
class ApiportalModelapiportal extends JModelList
{
    private $config;

    private $_proxyTimeout = 30000; // try it proxy timeout 30s

    private static $crtFileName = 'apigw.crt';

    public function __construct($config = array()){
        parent::__construct($config);
        $this->populateState();
        $this->config = new ApiPortalConfiguration();
    }

    public function getHost(){
        $host = $this->config->getHost();
        return $host;
    }

    public function getPort(){
        $port = $this->config->getPort();
        return $port;
    }

    public function saveConfiguration($host, $port, $verifyCert, $file, $verifyHost, $oauthPath, $oauthPort, $ssoEntityID, $isSsoOn, $ssoPath ,$publicApi, $publicApiAccountLoginName, $publicApiAccountPassword, &$errMsg)
    {
	    // Allowed extensions
	    $extensions = [
		    'cer',
		    'crt',
		    'pem',
		    'der'
	    ];

        if ($file["error"] == 0 && isset($file["tmp_name"])) {
			// Get the file extension
	        $fileExt =  JFile::getExt($file["name"]);
	        // Final file name and extension
	        $destination = $this->getCertFile();

	        // Check the size of the cert - max 2 mb

	        if ($file['size'] > 2000000) {
		        $errMsg = JText::_('COM_APIPORTAL_CONFIGURATION_ERROR_BIG_FILE_MESSAGE');
		        return false;
	        }

	        // Check the file extension of the cert
	        if (!in_array($fileExt, $extensions)) {
		        $errMsg = JText::_('COM_APIPORTAL_CONFIGURATION_ERROR_EXT_NOTALLOWED_MESSAGE');
		        return false;
	        }

	        // Read the file to determine what is the encoding
	        $fileContent = file_get_contents($file["tmp_name"]);

	        // We need BEGIN and END on the cert or to be valid base64
	        if (!$this->isValidBase64($fileContent) && (strpos($fileContent, 'BEGIN') !== false) && strpos($fileContent, 'END') !== false) {
		        // PEM cert with BEGIN and END
		        $fileInArray = file($file["tmp_name"]);
		        // Read it as an array to remove first and last rows
		        if (strpos($fileInArray[0], 'BEGIN') !== false) {
		        	unset($fileInArray[0]);
		        }
				// Remove the last row
		        $fileInArrayCount = sizeof($fileInArray);
		        if (strpos($fileInArray[$fileInArrayCount], 'END') !== false) {
					unset($fileInArray[$fileInArrayCount]);
		        } elseif (strpos($fileInArray[$fileInArrayCount-1], 'END') !== false) {
		        	// The last row maybe just an empty string so chek it
			        unset($fileInArray[$fileInArrayCount-1]);
		        }

		        // If it is not valid base64 stop
		        if (!$this->isValidBase64(implode("", $fileInArray))) {
			        $errMsg = JText::_('COM_APIPORTAL_CONFIGURATION_ERROR_NOT_VALID_PEM_MESSAGE');
			        return false;
		        }

		        // If the file still exist save it
		        if (isset($file["tmp_name"])) {
			        copy($file["tmp_name"], $destination);
		        }
	        } else {
	        	// Or maybe is der encoded
		        // Convert the data
		        $convertedData = $this->convertDerToPem($fileContent);
		        if ($convertedData) {
			        file_put_contents($destination, $this->convertDerToPem($fileContent));
		        } else {
			        $errMsg = JText::_('COM_APIPORTAL_CONFIGURATION_ERROR_NOT_VALID_DER_MESSAGE');
			        return false;
		        }
	        }
        } elseif ($file["error"] == 4 && $file["name"] == "") {
            // no certificate file was provided
            if ($verifyCert == "1" && !file_exists($this->getCertFile())) {
	            $errMsg = JText::_('COM_APIPORTAL_CONFIGURATION_ERROR_NOT_CERT_UPLOADED_MESSAGE');
                return false; // no certificate
            }
        }

        // Input validation
	    $ssoEntityID = htmlentities($ssoEntityID);
	    $isSsoOn = ($isSsoOn != 0 && $isSsoOn != 1) ? 0 : (int) $isSsoOn;
	    // Set session variable with the state of the SSO
	    // should be with a dependency injections but it's not the time for refactoring
	    if ($isSsoOn === 1) {
		    JFactory::getSession()->set(ApiPortalSessionVariables::IS_SSO_ON, true);
	    } else {
		    JFactory::getSession()->set(ApiPortalSessionVariables::IS_SSO_ON, false);
	    }

	    // If SSO is on we need Entity ID and Path
	    if ($isSsoOn && empty($ssoEntityID) && empty($ssoPath)) {
		    $errMsg = JText::_('COM_APIPORTAL_CONFIGURATION_SSO_IS_ON_ERROR');
		    return false;
	    }

        $isSaved = $this->config->saveConfiguration($host, $port, $verifyCert, $verifyHost, $oauthPath, $oauthPort, $ssoEntityID, $isSsoOn, $ssoPath, $publicApi, $publicApiAccountLoginName, $publicApiAccountPassword);
        if (!$isSaved) {
	        $errMsg = JText::_('COM_APIPORTAL_CONFIGURATION_ERROR_DATA_NOT_SAVED_MESSAGE');
            return false;
        }

        return true;
    }

    public function getCertDir(){
        return JPATH_ADMINISTRATOR.DS.'components'.DS.'com_apiportal'.DS.'assets'.DS.'cert';
    }

    public function getCertFile(){
        $file = $this->getCertDir() . DS. self::$crtFileName;
        return $file;
    }

    public function getVefiryCert(){
        $verify = $this->config->verifyCert();
        return $verify;
    }

    public function getVerifyHost(){
        $verifyHost = $this->config->verifyHost();
        return $verifyHost;
    }

    public function getOauthPath(){
        $oAuthPath = $this->config->getOauthPath();
        return $oAuthPath;
    }

    public function getOauthPort(){
        $oAuthPort = $this->config->getOauthPort();
        return $oAuthPort;
    }
    
    public function getPublicApi(){
    	$publicApi = $this->config->getPublicApi();
    	return $publicApi;
    }
    
    public function getPublicApiAccountLoginName(){
    	$publicApiAccountLoginName = $this->config->publicApiAccountLoginName();
    	return $publicApiAccountLoginName;
    }
    
    public function getPublicApiAccountPassword(){
    	$publicApiAccountPassword = $this->config->publicApiAccountPassword();
    	return $publicApiAccountPassword;
    }
    

	/**
	 * @return string
	 * @since 7.5.3
	 */
	public function getSsoEntityID()
	{
		return $this->config->ssoEntityID();
	}

	/**
	 * @return bool
	 * @since 7.5.3
	 */
	public function getIsSsoOn()
	{
		return $this->config->isSsoOn();
	}

	/**
	 * @return string
	 * @since 7.5.3
	 */
	public function getSsoPath()
	{
		return $this->config->ssoPath();
	}


	public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_apiportal.settings', 'settings',
            array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form))
        {
            return false;
        }

        return $form;
    }

    /**
     * Get the value of the proxyTimeout from JConfig
     *
     * @return int
     */
    public function getProxyTimeout(){

        $proxyTimeout = JFactory::getConfig()->get('apiPortalProxyTimeout');

        if ($proxyTimeout && !empty($proxyTimeout)){
            return intval($proxyTimeout);
        }
        return $this->_proxyTimeout;
    }

	/**
	 * Convert DER encoded SSL certificate to PEM (base64)
	 * @param string $derData
	 * @param string $type
	 * @since 7.5.2
	 * @return string
	 */
    private function convertDerToPem($derData, $type='CERTIFICATE')
    {
    	// Encode the data
	    $pem = chunk_split(base64_encode($derData), 64, "\n");
	    if ($this->isValidBase64($pem)) {
		    $pem = "-----BEGIN ".$type."-----\n".$pem."-----END ".$type."-----\n";
		    return $pem;
	    }

	    return false;
    }

	/**
	 * Check is the given string is base64
	 * @param $string
	 * @return bool
	 * @since 7.5.2
	 */
	private function isValidBase64($string)
	{
		// Decode the string in strict mode and send the response
		if(base64_decode($string, true)) {
			return true;
		}

		return false;
	}
	
	/*If the admin disabled Public API user then destroy the session */
	public function destroyPublicuserSession(){
		return $this->config->destroyPublicusers();
	}
	
}