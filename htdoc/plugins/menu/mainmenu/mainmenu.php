<?php 

// no direct access
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.plugin.plugin');

/**
 * Categories Mamenuin plugin.
 * Implemented for PUBLIC API
 * Enable/Disable Mainmenu in respect of Public API
 */

class PlgMenuMainmenu extends JPlugin
{
	function onContentMainMenu()
	{
		$result = '';
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			  ->select('*')
			  ->from('#__menu')
			  ->where($db->quoteName('menutype') .'LIKE "%mainmenu%" AND'. $db->quoteName('parent_id').'=1 AND'. $db->quoteName('published') . '=1');
		
		$db->setQuery($query);
		$db->execute();
		try
		{
			$result = $db->loadAssocList();
		}
		catch (Exception $e)
		{
			$result = 'No result found';
		
			
		}
		
		return $result; 
				
	}
}

?>