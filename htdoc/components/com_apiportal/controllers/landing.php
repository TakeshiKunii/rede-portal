<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class ApiPortalControllerLanding extends JControllerLegacy
{
    public function authorise($task) {
        // Make sure the session is valid before proceeding with tasks
        ApiPortalHelper::checkSession();
    }

    /*
     * Redirect after sign in to either the Applications List view if there
     * are any applications available, or to the API Catalog if not.
     */
    public function page() {

        $applications = ApiPortalHelper::doGet((ApiPortalHelper::getVersionedBaseFolder())."/applications");
        if ($applications &&  is_array($applications) && count($applications) > 0) {
            $link = 'index.php?option=com_apiportal&view=applications';
        } else {
            $link = 'index.php?option=com_apiportal&view=apicatalog';
        }

        // Get the associated menu item
        $menus = JFactory::getApplication()->getMenu()->getMenu();
        foreach ($menus as $menu) {
            if ($menu->link == $link) {
                $link = $menu->alias;
                break;
            }
        }

        $this->setRedirect(JURI::base(false) . $link);
    }
}
