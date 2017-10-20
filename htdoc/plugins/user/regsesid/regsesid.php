<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.application.component.helper');

/**
* This plugin makes session id regeneration
* to prevent session fixation vulnerability.
* After user is logged change session id but it saves data.
* If Joomla provide functionality for this thread, please disable this plugin
* if you are sure it prevent session hijacking. 
*/
class PlgUserRegSesId extends JPlugin
{
	/**
	* After user logged in change session id to avoid session fixation vulnerability
	*/
	public function onUserLogin($user, $options = array())
    {
        //get session
        $session = JFactory::getSession();

        //save values
        $values = $_SESSION;
        $cookie = session_get_cookie_params();

        //regenerate
        session_regenerate_id(true);
        $id = session_id();

        //get session storage
        $store = JSessionStorage::getInstance($session->storeName, array());

        //grab the session data
        $data = $store->read($id);

        //kill session
        session_destroy();

        //re-register the session store after a session has been destroyed, to avoid PHP bug
        $store->register();

        //restore config
        ini_set('session.use_trans_sid', false);
        session_set_cookie_params($cookie['lifetime'], $cookie['path'], $cookie['domain'], $cookie['secure'], true);

        //restart session with new id
        session_id($id);

        session_start();
        $_SESSION = $values;

        //put the session data back
        $store->write($id, $data);

		return true;
	}
}