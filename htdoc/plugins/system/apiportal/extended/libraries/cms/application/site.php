<?php
defined('_JEXEC') or die('Restricted access');

JLoader::import('Sso', JPATH_SITE.'/components/com_apiportal/helpers');

// Extend the Original Core Class 'JApplicationSite' and override the 'authorise' method
class JApplicationSite extends JApplicationSiteOriginal
{
    /*
     * This method performs menu level authorizations. Each component must still check for
     * component level authorizations, which in our case is done via ApiPortalHelper::checkSession()
     *
     * There is actually more comment here than code as I try to document the frequently strange
     * behavior of the Joomla session handling for future maintainers of this code.
     *
     * This override is an attempt to work around some of the strangeness inherent in the Joomla
     * session handling, and is probably more complicated than it needs to be, but unless we figure
     * out a more elegant way to work around these issues, this is the best we can do right now.
     */
    protected function authorise($itemid) {
        if (!$itemid) { return; }

        $loginURL = 'index.php?option=com_users&view=login';
        $requestedURL = JURI::getInstance();

        $menus = $this->getMenu();
        $authorized = $menus->authorise($itemid);

        $user = JFactory::getUser();
        $session = JFactory::getSession();
	    $ssoCookie = (isset($_COOKIE[API_PORTAL_AUTH_TYPE_SSO_COOKIE]) && $_COOKIE[API_PORTAL_AUTH_TYPE_SSO_COOKIE] == API_PORTAL_AUTH_TYPE_SSO_COOKIE_VALUE)
		    ? true : false;
	    $ssoPath = SSOHelper::getSSOPath();

        if ($session->isActive()) {
            if (!$authorized && $user->guest) {
                /*
                 * I've seen this error message pop up after a user has logged out and they click the login link.
                 * Yet more strangeness in the Joomla session handling since this case shouldn't be possible, but
                 * there it is. And then, since the return redirect goes to the 'sign in' page, after they log in
                 * the user gets presented with the 'sign in' page again, even though they are already logged in!
                 *
                 * XXX: Inspect the requested URL for the presence of 'sign-in' or 'login'
                 *
                 * Newsflash: expired sessions are entering the twilight zone and I'm never quite sure how they will
                 * behave once they comes back. Similar to the comment below regarding the user trying to logout, if
                 * the expired session gets converted into an 'active guest' session, then we will end up here and 
                 * the user will be asked to sign in first in order to logout!.
                 * 
                 * XXX: Inspect the requested URL for the presence of 'sign-out' or 'logout'
                 *
                 * TODO: Figure out a less fragile way of handling this!
                 */
                if (
                    !strpos($requestedURL, 'sign-in')  && !strpos($requestedURL, 'login') &&
                    !strpos($requestedURL, 'sign-out') && !strpos($requestedURL, 'logout')
                ) {
                    $this->enqueueMessage(JText::_('JGLOBAL_SIGN_IN_REQUIRED'));
					// Redirect to SSO if the user is SSO authenticated
	                if ($ssoCookie) {
		                $this->redirect(JRoute::_('/'. $ssoPath, true));
	                }


                    $return = urlencode(base64_encode($requestedURL));
                    $this->redirect($loginURL . '&return=' . $return);
                }

            } else if (!$authorized) {
                /*
                 * This else clause in the original implementation has caused me a lot of grief. All it does
                 * is display an error message stating that the user is not authorized to view the resource,
                 * but it still allows whatever was being requested to be accessed! So, until we can 
                 * figure out a better way of handling this case, just comment out the error message.
                 *
                 * TODO: $this->enqueueMessage(JText::_('JGLOBAL_NOT_AUTHORIZED'), 'warning');
                 */
            }
        } else {
            /*
             * So here's the problem: if the session has timed out amd the user tries to logout, then
             * they will get redirected to the login page _without_ actually calling the logout controller.
             * Then, immediately after signing in again, the user would get redirected to the orignal 'logout'
             * action and get logged out! The original Joomla implementation would also display the
             * JERROR_ALERTNOAUTHOR error, both cases are bad and should be avoided.
             * 
             * Additionally, we don't care if the 'guest' user session has expired.
             *
             * XXX: Inspect the requested URL for the presence of 'sign-out' or 'logout'
             *
             * TODO: Figure out a less fragile way of handling this!
             */
            if (!$user->guest && !strpos($requestedURL, 'sign-out') && !strpos($requestedURL, 'logout')) {
                $state = $session->getState();

                $session->destroy();

	            // Redirect to SSO if the user is SSO authenticated
	            if ($ssoCookie) {
		            $this->redirect(JRoute::_('/' . $ssoPath, true));
	            }

                $return = urlencode(base64_encode($requestedURL));
                $this->redirect($loginURL . '&session=' . $state . '&return=' . $return);
            }
        }
    }
}
