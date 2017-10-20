<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.user.component.modellist');

class APIPortalModelUsers extends JModelList
{
    protected $items;
    protected $config;

    public function getItems() {
        if (!isset($this->items)) {
            $this->items = $this->getUsers();
        }
        return $this->items;
    }

    public function getConfig() {
        if (!isset($this->config)) {
            $this->config = ApiPortalHelper::getAPIMangerConfig();;
        }
        return $this->config;
    }

    private function getUsers() {
        $currentUserId = ApiPortalHelper::getCurrentUserPortalId();

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/users";
        $users = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }
        
        foreach ($users as $key => $user) {
            $user->viewUrl = JRoute::_('index.php?option=com_apiportal&view=user&task=user.viewUser&userId='.$user->id, false);

            //remove current user from the list
            if ($user->id == $currentUserId) {
                unset($users[$key]);
            }
            
            if(!empty($user->createdOn)){
            	$user->createdOn = ApiPortalHelper::convertDateTime($user->createdOn,JText::_('COM_APIPORTAL_LOCAL_DATE_TIME_FORMAT')); 
            }
        }
        return $users;
    }

    public function updateUserState($userId, $enabled) {
        // Get user and updatre its state
        $user = $this->getUser($userId);
        if ($user==null) {
            return null;
        }
        
        $user->enabled = $enabled;

        // Update user object on API GW Server
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/users/" . $userId;
        ApiPortalHelper::doPut($path, $user);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }
    }

    public function deleteUser($id) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array('user_id', 'user_id_jm')));
        $query->from($db->quoteName('#__apiportal_user'));
        $query->where($db->quoteName('user_id') . ' = '. $db->quote($id));

        $db->setQuery($query, 0, 1);
        $result = $db->loadObject();

        if ($result) {
            try {
                $instance = JUser::getInstance($result->user_id_jm);
                if ($instance) {
                    $instance->delete();
                }
            } catch (Exception $e) {
                error_log('Error deleting Joomla account! Error: '.$e->getMessage());
            }
        } else {
            // Delete user object on API GW Server
            $path = ApiPortalHelper::getVersionedBaseFolder() . "/users/" . $id;
            ApiPortalHelper::doDelete($path);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        }

        return null;
    }
    
    private function getUser($userId) {
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/users/" . $userId;
        $user = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }
        return $user;
    }
    
}