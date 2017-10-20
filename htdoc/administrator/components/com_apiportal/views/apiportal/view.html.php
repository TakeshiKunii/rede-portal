<?php
/*------------------------------------------------------------------------
# view.html.php - API Portal Component
# ------------------------------------------------------------------------
# author    Axway
# copyright Copyright (C) 2014. All Rights Reserved
# license   GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
# website   www.axway.com
-------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * apiportal View
 */
class ApiportalViewapiportal extends JViewLegacy
{
	/**
	 * Apiportal view display method
	 * @return void
	 */
	function display($tpl = null) 
	{
            // check for errors
            if(count($errors = $this->get("Errors"))){
                Jerror::raiseError(500, implode('<br />', $errors));
                return false;
            } 
            $this->host = $this->get("host");
            $this->port = $this->get("port");
            $form = $this->get('Form');
            $this->form = $form;
            $this->verifySSL = $this->get("vefiryCert");
            $this->verifySslHost = $this->get("verifyHost");
            $cert = $this->get("certFile");
            $this->certFileName = file_exists($cert) ? basename($cert) : "";
            $this->oauthPort = $this->get("oauthPort");
            $this->oauthPath = $this->get("oauthPath");
			$this->ssoEntityID = $this->get("ssoEntityID");
			$this->isSsoOn = $this->get("isSsoOn");
			$this->ssoPath = $this->get("ssoPath");
			$this->publicApi = $this->get("publicApi");
			$this->publicApiAccountLoginName = $this->get("publicApiAccountLoginName");
			$this->publicApiAccountPassword = base64_decode($this->get("publicApiAccountPassword"));

            // Include helper submenu
            ApiportalHelper::addSubmenu(JText::_('COM_APIPORTAL_MENU_SUBMENU_CONFIGURATION'));

            // Set the toolbar
            $this->addToolBar();
            // Show sidebar
            $this->sidebar = JHtmlSidebar::render();

            // Display the template
            parent::display($tpl);

            // Set the document
            $this->setDocument();
	}

	/**
	 * Setting the toolbar
	 */
	protected function addToolBar() 
	{
            JToolBarHelper::title(JText::_('COM_APIPORTAL_MENU_API_MANAGER_TITLE'), 'apiportal');
            JToolBarHelper::preferences('com_apiportal');
            JToolbarHelper::custom('configuration.save', 'save', 'save', 'Save', FALSE);
	}

	/**
	 * Method to set up the document properties
	 *
	 *
	 * @return void
	 */
	protected function setDocument() 
	{
		$document = JFactory::getDocument();
		$document->setTitle(JText::_('COM_APIPORTAL_MENU_API_MANAGER_TITLE'));
	}
        
        protected function hidePassword($input){
            $result = '';
            if(isset($input) && !($input === '')){
                for ($index = 0; $index < strlen($input); $index++) {
                    $result .= '*';
                }
            }
            return $result;
        }
}
?>
