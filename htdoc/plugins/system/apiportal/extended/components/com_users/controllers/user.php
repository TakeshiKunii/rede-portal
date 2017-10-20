<?php
defined('_JEXEC') or die('Restricted access');

JLoader::register('ApiPortalHelper', JPATH_BASE.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'apiportal.php');
JLoader::register('ApiPortalValidator', JPATH_BASE.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'validator.php');

// Extend the Original Core Class 'UsersControllerUser' and override the 'login/logout' methods
class UsersControllerUser extends UsersControllerUserOriginal
{
    /*
     * Mostly the same as the Core Joomla implementation, with the following differences:
     *
     * - Added username/password field validation.
     * - Removed JSession::checkToken() call, we don't need it for login.
     * - Removed 'secretkey', we don't support it.
     * - If 'return' is empty, set to '/' instead of User Profile.
     * - Check that 'return' URL is internal
     */
    public function login() {

	    $app = JFactory::getApplication();

	    if (!JSession::checkToken('post')) {
		    $app->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
		    $this->setRedirect(JRoute::_("sign-in"));
		    $this->redirect();
		    return false;
	    }

        $username = JRequest::getVar('username', '', 'post', 'STRING');
        $password = JRequest::getVar('password', '', 'post', 'STRING', JREQUEST_ALLOWRAW);

        $data = array();
        $data['username'] = $username;
        $data['return'] = base64_decode($app->input->post->get('return', '', 'BASE64'));

        $app->setUserState('users.login.form.data', null);

        // If 'return' is set, make sure that it is an internal URI
        if (!empty($data['return'])) {
            if (!JURI::isInternal($data['return'])) {
                $data['return'] = '';
            }
        }

        // Set the return URL if empty
        if (empty($data['return'])) {
            $landingURL =  JURI::base(false) . 'index.php?option=com_apiportal&task=landing.page';
            $data['return'] = $landingURL;
        }

        // Set the return URL in the user state to allow modification by plugins
        $app->setUserState('users.login.form.return', $data['return']);

        $validationOK = true;

        // Validate any required fields and all field lengths
        $name = JText::_('COM_USERS_SIGN_IN_USERNAME_LABEL');
        $field = $username;
        $length = ApiPortalValidator::MAX_FIELD_LEN;
        if (!ApiPortalValidator::validateRequired($name, $field, null)) {
            $app->enqueueMessage($name . ': ' . strtolower(JText::_('COM_USERS_FIELD_REQUIRED')));
            $validationOK = false;
        } else if (!ApiPortalValidator::validateLength($name, $field, null, $length)) {
            $app->enqueueMessage(sprintf($name . ': ' . strtolower(JText::_('COM_USERS_FIELD_TOO_LONG')), $length));
            $data['username'] = '';
            $validationOK = false;
        }

        $name = JText::_('COM_USERS_SIGN_IN_PASSWORD_LABEL');
        $field = $password;
        $length = ApiPortalValidator::MAX_FIELD_LEN;
        if (!ApiPortalValidator::validateRequired($name, $field, null)) {
            $app->enqueueMessage($name . ': ' . strtolower(JText::_('COM_USERS_FIELD_REQUIRED')));
            $validationOK = false;
        } else if (!ApiPortalValidator::validateLength($name, $field, null, $length)) {
            $app->enqueueMessage(sprintf($name . ': ' . strtolower(JText::_('COM_USERS_FIELD_TOO_LONG')), $length));
            $validationOK = false;
        }
        
        // Get the document object.
	$document	= JFactory::getDocument();

	// Set the default view name and format from the Request.
	$viewname   = $this->input->getCmd('view', 'login');
	$format = $document->getType();
	$layout   = $this->input->getCmd('layout', 'default');
                
        $model = $this->getModel($viewname);
                
        if (!$validationOK) {
            $app->setUserState('users.login.form.data', $data);
            ApiPortalHelper::displayView($viewname, $layout, null, $this, $model, $format, $document);
            return;
        }

        $credentials = array();
        $credentials['username']  = $username;
        $credentials['password']  = $password;

        $options = array();
        $options['return'] = $data['return'];

        if (true === $app->login($credentials, $options)) {
			//Public Mode vs RegularUserMode differencition
        	JFactory::getSession()->set('RegularUserMode', 1);
            // Success
            $app->redirect(JRoute::_($app->getUserState('users.login.form.return'), false));
        } else {
            // Failed
            $app->setUserState('users.login.form.data', $data);
            ApiPortalHelper::displayView($viewname, $layout, null, $this, $model, $format, $document);
        }
    }

    /*
     * Mostly the same as the Core Joomla implementation, with the following differences:
     *
     * - Removed JSession::checkToken() call, we don't need it for logout.
     * - If 'return' is empty, set to '/' instead of leaving it empty.
     */
    public function logout() {
        $app = JFactory::getApplication();

        $error = $app->logout();
        if (!($error instanceof Exception)) {
            $return = base64_decode(JRequest::getVar('return', '', 'method', 'base64')) ;

            // If 'return' is set, make sure that it is an internal URI
            if (!JURI::isInternal($return)) {
                $return = '';
            }

            // Set the return URL if empty
            if (empty($return)) {
                $return = JRoute::_(JURI::base(false), false);
            }
            $app->redirect(JRoute::_($return, false));
        } else {
            $app->redirect(JRoute::_('index.php?option=com_users&view=login', false));
        }
    }
}
