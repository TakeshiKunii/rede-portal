<?php
defined ( '_JEXEC' ) or die ( 'Restricted access' );
class plgSystemakeebaclirequesthandler extends JPlugin {
	function onAfterRoute() {
		$jinput = JFactory::getApplication ()->input;
		$option = $jinput->get ( 'option' );
		$view = $jinput->get ( 'view' );
		$format = $jinput->get ( 'format' );
		$remoteAddress = $jinput->server->get ( 'REMOTE_ADDR', '', '' );
		
		$defaultAllowedIps = '::1';
		$allowedIPaddress = array ();
		$lookUpFormat = array (
				'raw',
				'html' 
		);
		
		if ($option != 'com_akeeba')
			return;
		
		$allowedIPAddressConfig = $this->params->get ( 'allowed_IP_address' );
		
		if (! empty ( $allowedIPAddressConfig )) {
			$allowedIPaddress = explode ( ',', $allowedIPAddressConfig );
			array_push ( $allowedIPaddress, $defaultAllowedIps );
		} else {
			array_push ( $allowedIPaddress, $defaultAllowedIps );
		}
		
		if ($option == 'com_akeeba' && $view == 'json' && in_array ( $format, $lookUpFormat ) && ! in_array ( $remoteAddress, $allowedIPaddress )) {
			
			//$data = "<br> option : $option , view : $view , format:$format, remoteAddress: $remoteAddress ";
			
			header ( 'HTTP/1.0 403 Forbidden' );
			die ( 'You are not allowed to access' );
		} else {
			return true;
		}
		
		return true;
	}
}

?>