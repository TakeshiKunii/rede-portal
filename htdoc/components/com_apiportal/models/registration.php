<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modelitem');

class ApiPortalModelRegistration extends JModelItem
{
    const REGISTER_SUCCESS = "success";
    const REGISTER_FAILED = "failed";
    const REGISTER_ERROR = "error";
    const REGISTER_DUPLICATED_EMAIL = "duplicated.mail";
    const REGISTER_DUPLICATED_USERNAME = "duplicated.username";

    private $apidata;
    private $password;
    private $confirmPassword;
    private $confirmPassword_off;

    function __construct() {
        parent::__construct();

        $this->apidata = JRequest::getVar('apidata', array(), 'post', 'ARRAY');
        $this->password = JRequest::getVar('password', null, 'post', 'STRING', JREQUEST_ALLOWRAW);
        $this->confirmPassword = JRequest::getVar('confirm_password', null, 'post', 'STRING', JREQUEST_ALLOWRAW);
        $this->confirmPassword_off = JRequest::getVar('confirm_password_off', 'false', 'post', 'STRING');

    }

    public function submit() {
        $data = array('password' => $this->password);

        foreach ($this->apidata as $key => $value) {
            // Everything in the 'data' array will get posted to the API Manger, including any custom fields
            $data[$key] = $value;
        }
        $data['success'] = '/registration-success';
        $data['failure'] = '/registration-failed';

        //prepare custom property for t&c to be sent to Api Manager
        if (isset($this->apidata['TermsAndConditionsAccepted']) && $this->apidata['TermsAndConditionsAccepted'] == 'on'){
            $data['tcAccepted'] = true;
        }

        ApiPortalHelper::doPost((ApiPortalHelper::getVersionedBaseFolder())."/users/register", $data, CONTENT_TYPE_FORM);
        $status = ApiPortalHelper::getStatus();
        if ($status == 303) {
            $location = ApiPortalHelper::getHeader('Location');

            if (preg_match('/.*registration-success.*/', $location)) {
                return self::REGISTER_SUCCESS;
            } else {
                return self::REGISTER_FAILED;
            }
        } else if (ApiPortalHelper::isHttpError()) {
            return self::REGISTER_ERROR;
        } else {
            return self::REGISTER_ERROR;
        }
    }

    public function confirm() {
        $email = JRequest::getVar('email', null, 'get', 'STRING');
        $token = JRequest::getVar('validator', null, 'get', 'STRING');
        $pais = JRequest::getVar('pais', null, 'get', 'STRING');

        $data = array('email' => $email, 'validator' => $token, 'pais' => $pais);

        ApiPortalHelper::doGet((ApiPortalHelper::getVersionedBaseFolder())."/users/validateuser", $data);
        $status = ApiPortalHelper::getStatus();
        if ($status == 303) {
            $location = ApiPortalHelper::getHeader('Location');

            if (preg_match('/.*validation-success.*/', $location)) {
                try {
                    if ($this->isJoomlaUser($email, 'email')) {//remove exsisting apiportal user
                        $this->_removeUserByEmail($email);
                    }
                }
                catch(Exception $e){
                    echo $e->getMessage();
                    exit;
                }
                return self::REGISTER_SUCCESS;
            } else {
                return self::REGISTER_FAILED;
            }
        } else if (ApiPortalHelper::isHttpError()) {
            return self::REGISTER_ERROR;
        } else {
            return self::REGISTER_ERROR;
        }
    }

    public function validate(&$data) {
        $appInfo = ApiPortalHelper::getAPIManagerAppInfo();
        $result = true;
        $tcAccepted =
            isset($this->apidata['TermsAndConditionsAccepted'])
                ? $this->apidata['TermsAndConditionsAccepted']
                : null;

        // Set confirm password = password if it is visible
        if ($this->confirmPassword_off == 'true') {
           $this->confirmPassword =  $this->password;
        }

        // Validate any required fields and all field lengths
        $name = JText::_('COM_APIPORTAL_REGISTRATION_FULL_NAME_LABEL');
        $field = $this->apidata['name'];
        if (!ApiPortalValidator::validateRequired($name, $field, $this)) {
            $result = false;
        } else if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $data['name'] = '';
            $result = false;
        }

        $name = JText::_('COM_APIPORTAL_REGISTRATION_EMAIL_LABEL');
        $field = $this->apidata['email'];
        if (!ApiPortalValidator::validateRequired($name, $field, $this)) {
            $result = false;
        } else if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $data['email'] = '';
            $result = false;
        }

        $name = JText::_('COM_APIPORTAL_REGISTRATION_PASSWORD_LABEL');
        $field = $this->password;
        if (!ApiPortalValidator::validateRequired($name, $field, $this)) {
            $result = false;
        } else if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $result = false;
        } else if (!ApiPortalValidator::validatePassword($field, $appInfo, $this)) {
            $result = false;
        }

        if ($this->confirmPassword_off == 'false') {
            $name = JText::_('COM_APIPORTAL_REGISTRATION_CONFIRM_PASSWORD_LABEL');
            $field = $this->confirmPassword;
            if (!ApiPortalValidator::validateRequired($name, $field, $this)) {
                $result = false;
            } else if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
                $result = false;
            }
        }

        $name = JText::_('COM_APIPORTAL_REGISTRATION_ORGANIZATION_CODE_LABEL');
        $field = $this->apidata['token'];
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $data['token'] = '';
            $result = false;
        }

        if (!ApiPortalValidator::isNullOrEmpty($this->password) && !ApiPortalValidator::isNullOrEmpty($this->confirmPassword)) {
            if ($this->password != $this->confirmPassword) {
                $this->setError(JText::_('COM_APIPORTAL_PASSWORDS_DONT_MATCH'));
                $result = false;
            }
        }

        if (ApiPortalValidator::isNullOrEmpty($tcAccepted) || !strtolower($tcAccepted) == "on") {
            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::_('COM_APIPORTAL_REGISTRATION_ACCEPT_TERMS'), 'notice');
            $result = false;
        }

        // Validate any custom fields: currently only basic <input type="text" /> fields are supported
        $standardFields = array('name', 'email', 'token', 'TermsAndConditionsAccepted');
        if (!ApiPortalValidator::validateCustomFields($this->apidata, $standardFields, $data, $this)) {
            // Errors have already been taken care of
            $result = false;
        }

        return $result;
    }

    /**
     * This method check if the account is admin or not.
     * Accept username and search it in Joomla users.
     * It will return true only if the user is found by username
     * and his password is not #.
     * @param $username
     * @return bool
     */
    public function checkIsAdminAccount($username)
    {
        $db = JFactory::getDbo();
        $db->getQuery(true);
        $db->setQuery('SELECT username, password FROM #__users WHERE username = '.$db->Quote($username));
        $db->execute();
        $result = $db->loadAssoc();

        if (is_array($result) && !empty($result)) {
            if ($result['password'] != '#') {
                return false;
            }
        }
        
        return true;
    }

    private static function isJoomlaUser($username, $param) {
        $db = JFactory::getDbo();
        if ($param == 'email' ) {
            $db->setQuery('SELECT email FROM #__users WHERE email = '.$db->Quote($username));
            try {
                $db->query();
            } catch (Exception $e) {
                // No exceptions should leak out into the browser
                error_log("Caught Exception: " . $e->getMessage());
                return false;
            }
            if ($db->getNumRows() == 0) {
                return false;
            }
            return true;
        }
        if ($param == 'username' ) {
            $db->setQuery('SELECT email FROM #__users WHERE name = '.$db->Quote($username));
            try {
                $db->query();
            } catch (Exception $e) {
                // No exceptions should leak out into the browser
                error_log("Caught Exception: " . $e->getMessage());
                return false;
            }
            if ($db->getNumRows() == 0) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Remove user from apiportal and joomla db table
     * @param string $email
     */
    protected function _removeUserByEmail($email){
        if ($email){

            $db = JFactory::getDbo();

            //get the joomla user ID
            $db->setQuery('SELECT user_id_jm FROM #__apiportal_user WHERE email = ' . $db->Quote($email));
            $db->execute();
            $result = $db->loadAssoc();

            //delete from apiportal users table
            $db->setQuery('DELETE FROM #__apiportal_user WHERE email = ' . $db->Quote($email));
            $db->query();

            // delete from joomla users table
            $user = new JUser($result['user_id_jm']);
            $user->delete();
        }
    }
}
