<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modellist');

class ApIPortalModelApplications extends JModelList
{
    protected $items;

    public function getItems() {
        if (!isset($this->items)) {
            $this->items = $this->getApplications();
        }
        return $this->items;
    }

    private function getApplications() {
        $currentUserId = ApiPortalHelper::getCurrentUserPortalId();

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/users";
        $users = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }
        $usersmap = array();
        foreach ($users as $value) {
            $usersmap[$value->id]=$value->name;
        }
        
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications";
        $applications = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        $index = 0;
        foreach ($applications as $application) {
            
            $applicationId = $application->id;

            // Get Application API Access
            $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$applicationId/apis";
            $application->apis = ApiPortalHelper::doGet($path);
;
            if (ApiPortalHelper::isHttpError()) {
                return null;
            }

            // Get Application Permissions
            $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$applicationId/permissions";
            $permissions = ApiPortalHelper::doGet($path);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
            foreach ($permissions as $permission) {
                if ($permission->userId == $currentUserId) {
                    $application->permission = $permission;
                    break;
                }
            }
            //convert the timestamp into date time
            if(!empty($application->createdOn)){
            	$application->createdOn = ApiPortalHelper::convertDateTime($application->createdOn,JText::_('COM_APIPORTAL_LOCAL_DATE_TIME_FORMAT'));
            }
            
            // Extra fields, defined to help application table view
            $application->createdByName = array_key_exists ( $application->createdBy , $usersmap )?$usersmap[$application->createdBy]:($application->createdBy?JText::_('COM_APIPORTAL_USER_ROLE_ADMIN'):"");
            $application->viewUrl = JRoute::_('index.php?option=com_apiportal&view=application&layout=view&applicationId='.$applicationId, false);
            $application->metricsUrl = JRoute::_('index.php?option=com_apiportal&view=application&layout=view&tab=metrics&cn='.$applicationId.'&applicationId='.$applicationId, false);
            $application->createdByUrl = JRoute::_('index.php?option=com_apiportal&view=user&userId='.$application->createdBy.'&task=user.viewUser', false);
            $application->index = $index++;
        }

        return $applications;
    }
    
    public function updateApplicationState($applicationId, $enabled) {
        // Get application and updatre its state
        $app = $this->getApplication($applicationId);
        if ($app==null) {
            return null;
        }
        
        $app->enabled = $enabled;
        $app->id = $app->id;

        // Update application object on API GW Server
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/" . $applicationId;
        ApiPortalHelper::doPut($path, $app);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }
    }

    public function deleteApplication($applicationId) {
        // Delete application object on API GW Server
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/" . $applicationId;
        ApiPortalHelper::doDelete($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }
    }
    
    private function getApplication($applicationId) {
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/" . $applicationId;
        $application = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }
        return $application;
    }
    
}
