<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/*
 * TODO: This class could use a round of refactoring for the controller tasks with very similar
 * structure, particularly those related to API Keys, OAuth Clients, and OAuth Scopes.
 */
class ApiPortalControllerApplications extends JControllerLegacy
{
    public function authorise($task) {
        // Make sure the session is valid before proceeding with tasks
        ApiPortalHelper::checkSession();
    }

    public function enableApps()
    {
	    $model = $this->getModel('Applications');

	    // Need Post here
	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input)) {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('applications', null, null, $this, $model);

			    return false;
		    }

		    $this->updateApplicationState(true);
	    }

        ApiPortalHelper::displayView('applications', null, null, $this, $model);
        return false;
    }

    public function disableApps()
    {
	    $model = $this->getModel('Applications');

	    // It should be post
	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input)) {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('applications', null, null, $this, $model);

			    return false;
		    }

		    $this->updateApplicationState(false);
	    }
        ApiPortalHelper::displayView('applications', null, null, $this, $model);
        return false;
    }
    
    public function deleteApps() {
        $model = $this->getModel('Applications');

	    // Only in a post
	    if (ApiPortalHelper::isPost(JFactory::getApplication()->input)) {
		    // Check for CSRF token
		    if (!JSession::checkToken('post'))
		    {
			    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
			    ApiPortalHelper::displayView('applications', null, null, $this, $model);

			    return false;
		    }

		    $posts = JRequest::get('post');
		    foreach ($posts as $key => $applicationId)
		    {
			    if (startsWith('app-id-', $key))
			    {
				    $model->deleteApplication($applicationId);
			    }
		    }
	    }

        ApiPortalHelper::displayView('applications', null, null, $this, $model);
        return false;
    }

    private function updateApplicationState($enabled) {
        $model = $this->getModel('Applications');
        $posts = JRequest::get( 'post' );
        foreach ($posts as $key => $applicationId) {
            if (startsWith('app-id-', $key)) {
                $model->updateApplicationState($applicationId, $enabled);
            }
        }
    }

}
