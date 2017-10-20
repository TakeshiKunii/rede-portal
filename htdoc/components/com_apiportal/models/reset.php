<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modelitem');

class ApiPortalModelReset extends JModelItem
{
    const RESET_SUCCESS = "success";
    const RESET_FAILED = "failed";
    const RESET_ERROR = "error";

    private $email;

    function __construct() {
        parent::__construct();

        $this->email = JRequest::getString('email', null, 'post', 'STRING');
    }

    public function submit() {
        $data = array('email' => $this->email);
        $data['success'] = '/request-forgotten-pw-success';
        $data['failure'] = '/request-forgotten-pw-failed';

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/users/forgotpassword";
        ApiPortalHelper::doPost($path, $data, CONTENT_TYPE_FORM);
        $status = ApiPortalHelper::getStatus();
        if ($status == 303) {
            $location = ApiPortalHelper::getHeader('Location');

            if (preg_match('/.*forgotten-pw-success.*/', $location)) {
                return self::RESET_SUCCESS;
            } else {
                return self::RESET_FAILED;
            }
        } else if (ApiPortalHelper::isHttpError()) {
            return self::RESET_ERROR;
        } else {
            return self::RESET_ERROR;
        }
    }

    public function confirm() {
        $email = JRequest::getString('email', null);
        $validator = JRequest::getString('validator', null);

        $data = array("email" => $email, "validator" => $validator);

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/users/resetpassword";
        ApiPortalHelper::doGet($path, $data);
        $status = ApiPortalHelper::getStatus();
        if ($status == 303) {
            $location = ApiPortalHelper::getHeader('Location');

            if (preg_match('/.*forgotten-pw-success.*/', $location)) {
                return self::RESET_SUCCESS;
            } else {
                return self::RESET_FAILED;
            }
        } else if (ApiPortalHelper::isHttpError()) {
            return self::RESET_ERROR;
        } else {
            return self::RESET_ERROR;
        }
    }

    public function validate() {
        $result = true;

        $name = JText::_('COM_APIPORTAL_RESET_EMAIL_LABEL');
        $field = $this->email;
        if (!ApiPortalValidator::validateRequired($name, $field, $this)) {
            $result = false;
        } else if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $result = false;
        }

        return $result;
    }
}
