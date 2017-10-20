<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.user.component.controller');

/*
 * TODO: This class could use a round of refactoring for the controller tasks with very similar
 * structure, particularly those related to API Keys, OAuth Clients, and OAuth Scopes.
 */
class ApiPortalControllerUsers extends JControllerLegacy
{
    public function authorise($task) {
        // Make sure the session is valid before proceeding with tasks
        ApiPortalHelper::checkSession();
    }

    public function enableUsers()
    {
	    // If it's post do the job
	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    $this->redirectToList();

			    return false;
		    }

		    $this->updateUserStates(true);
	    }

        $this->redirectToList();
        return false;
    }

    public function disableUsers()
    {
	    // If it's post do the job
	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    $this->redirectToList();

			    return false;
		    }

		    $this->updateUserStates(false);
	    }

        $this->redirectToList();
        return false;
    }
    
    public function deleteUsers()
    {
	    // If it's post do the job
	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input))
	    {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    $this->redirectToList();

			    return false;
		    }

		    $model         = $this->getModel('Users');
		    $currentUserId = ApiPortalHelper::getCurrentUserPortalId();
		    $jinput        = JFactory::getApplication()->input;
		    $posts         = $jinput->post->getArray();

		    foreach ($posts as $key => $userId)
		    {
			    if (startsWith('user-id-', $key))
			    {
				    if ($userId != $currentUserId)
				    {
					    $model->deleteUser($userId);
				    }
			    }
		    }
	    }

        $this->redirectToList();
        return false;
    }

    private function updateUserStates($enabled) {
        $model = $this->getModel('Users');
        $posts = JRequest::get( 'post' );
        foreach ($posts as $key => $userId) {
            if (startsWith('user-id-', $key)) {
                $model->updateUserState($userId, $enabled);
            }
        }
    }
    
    private function redirectToList() {
        $link = JRoute::_("index.php?option=com_apiportal&view=users", false);
        $this->setRedirect($link);
    }

}
