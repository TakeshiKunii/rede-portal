<?php
/*------------------------------------------------------------------------
# controller.php - API Portal Config Component
# ------------------------------------------------------------------------
# author    Axway
# copyright Copyright (C) 2014. All Rights Reserved
# license   GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
# website   www.axway.com
-------------------------------------------------------------------------*/
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

class ApiPortalConfiguration {

    private $host;

    private $port;

    private $verifySSL;

    private $verifySslHost;

    private $oauthPath;

    private $oauthPort;

    private $allowAPIManagerAdminLogin;

    private $ssoEntityID;

	private $isSsoOn;

	private $ssoPath;
	
	private $publicApi;
	
	private $publicApiAccountLoginName;
	
	private $publicApiAccountPassword;

    public function __construct() {
        $this->loadProperties();
    }

    private function loadProperties(){
        $db = JFactory::getDbo();
        $query = $db->getQuery(TRUE);
        $query->select('property, value');
        $query->from('#__apiportal_configuration');
        $db->setQuery((string)$query);
        $configuration = $db->loadObjectList();
        if(isset($configuration)){
            foreach ($configuration as $conf){
                if($conf->property == "host"){
                    $this->host = $conf->value;
                    continue;
                } else if ($conf->property == "port"){
                    $this->port = $conf->value;
                    continue;
                } else if ($conf->property == "verifyHost"){
                    $this->verifySslHost = $conf->value;
                    continue;
                } else if ($conf->property == "verifySSL"){
                    $this->verifySSL = $conf->value;
                    continue;
                } else if ($conf->property == "oauthPath"){
                    $this->oauthPath = $conf->value;
                    continue;
                } else if ($conf->property == "oauthPort"){
                    $this->oauthPort = $conf->value;
                } else if ($conf->property == "allowAPIManagerAdminLogin") {
                    $this->allowAPIManagerAdminLogin = $conf->value;
                } else if ($conf->property == 'ssoEntityID') {
	                $this->ssoEntityID = $conf->value;
                } else if ($conf->property == 'isSsoOn') {
	                $this->isSsoOn = $conf->value;
                } else if ($conf->property == 'ssoPath') {
	                $this->ssoPath = $conf->value;
                } else if ($conf->property == 'publicApi') {
	                $this->publicApi = $conf->value;
                } else if ($conf->property == 'publicApiAccountLoginName') {
	                $this->publicApiAccountLoginName = $conf->value;
                } else if ($conf->property == 'publicApiAccountPassword') {
	                $this->publicApiAccountPassword = $conf->value;
                }
            }
        }
    }

    public function saveConfiguration($host = '', $port = 0, $verifySSL = 0, $verifyHost = 0, $oauthPath = "", $oauthPort = "", $ssoEntityID='', $isSsoOn = 0, $ssoPath = 'sso', $publicApi = 0, $publicApiAccountLoginName="",$publicApiAccountPassword=""){
        $result = true;
        $result = $result && $this->updateProperty("host", $host);
        $result = $result && $this->updateProperty("port", $port);
        $result = $result && $this->updateProperty("verifySSL", $verifySSL);
        $result = $result && $this->updateProperty("verifyHost", $verifyHost);
        $result = $result && $this->updateProperty("oauthPath", $oauthPath);
        $result = $result && $this->updateProperty("oauthPort", $oauthPort);
        $result = $result && $this->updateProperty("ssoEntityID", $ssoEntityID);
        $result = $result && $this->updateProperty("isSsoOn", $isSsoOn);
        $result = $result && $this->updateProperty("ssoPath", $ssoPath);
        $result = $result && $this->updateProperty("publicApi", $publicApi);
        $result = $result && $this->updateProperty("publicApiAccountLoginName", $publicApiAccountLoginName);
        $result = $result && $this->updateProperty("publicApiAccountPassword", base64_encode($publicApiAccountPassword));

        return $result;
    }

    private function updateProperty($name, $value){
        $db = JFactory::getDbo();
        $field = array(
            $db->quoteName('value') . ' = ' . $db->quote($value)
        );

        $condition = array(
            $db->quoteName('property') . ' = ' . $db->quote($name)
        );

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__apiportal_configuration'))->set($field)->where($condition);
        $db->setQuery($query);
        $result = $db->query();
        return $result;
    }
    
    public function destroyPublicusers() {
    	$db = JFactory::getDbo();
    	$query = $db->getQuery(true);
    	$condition = array(
    			$db->quoteName('username') . ' = ' . $db->quote($this->publicApiAccountLoginName())
    	);
    	$query->delete($db->quoteName('#__session'))->where($condition);
    	$db->setQuery($query);
    	$result = $db->query();
    	return $result;
    }

    public function getHost(){
        return $this->host;
    }

    public function getPort(){
        return $this->port;
    }

    public function verifyCert(){
        return $this->verifySSL;
    }

    public function verifyHost(){
        return $this->verifySslHost;
    }

    public function getOauthPort(){
        return $this->oauthPort;
    }

    public function getOauthPath(){
        return $this->oauthPath;
    }
    
	/**
	 *
	 * @return string
	 * @since 7.5.3
	 */
	public function ssoEntityID()
	{
		return $this->ssoEntityID;
	}

	/**
	 *
	 * @return boolean 1|0
	 * @since 7.5.3
	 */
	public function isSsoOn()
	{
		return $this->isSsoOn;
	}

	/**
	 * @return string
	 * @since 7.5.3
	 */
	public function ssoPath()
	{
		return $this->ssoPath;
	}
	
	/** 
	 * @return string
	 * @since 7.5.4
	 */
	public function getPublicApi()
	{
		return $this->publicApi;
	}
	
	public function publicApiAccountLoginName()
	{
		return $this->publicApiAccountLoginName;
	}
	
	public function publicApiAccountPassword()
	{
		return $this->publicApiAccountPassword;
	}

    public function getAllowAPIManagerAdminLogin(){
        return $this->allowAPIManagerAdminLogin;
    }
    
}