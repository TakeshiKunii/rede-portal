<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.helper');

abstract class ApiPortalValidator
{
    const MAX_FIELD_LEN = 100;
    const MAX_TEXTAREA_LEN = 32768;
    const MAX_ARRAY_LEN = 100;

    public static function isNullOrEmpty($value) {
        if (!isset($value) || $value == null || $value == '') {
            return true;
        }
        return false;
    }

    public static function validateRequired($name, $field, $model) {
        if (self::isNullOrEmpty($field)) {
            if ($model) {
                $model->setError($name . ': ' . strtolower(JText::_('JGLOBAL_FIELD_REQUIRED')));
            }
            return false;
        } else {
            return true;
        }
    }

    public static function validateLength($name, $field, $model, $length) {
        if (strlen($field) > $length) {
            if ($model) {
                $model->setError(sprintf($name . ': ' . strtolower(JText::_('JGLOBAL_FIELD_TOO_LONG')), $length));
            }
            return false;
        } else {
            return true;
        }
    }

    public static function validateEmails($name, $email, $email2, $model) {
        if (strlen($email)!=strlen($email2) || $email!==$email2) {
            if ($model) {
                $label = JText::_('COM_APIPORTAL_USER_PROFILE_EMAIL_DONT_MATCH');
                $model->setError($label);
            }
            return false;
        } else {
            return true;
        }
    }

    public static function validatePassword($password, $config, $model) {
        if (property_exists($config, 'minimumPasswordLength')) {
            $minLength = $config->minimumPasswordLength;
            if (strlen($password) < $minLength) {
                $label = JText::_('COM_APIPORTAL_REGISTRATION_PASSWORD_LABEL') . ': ';
                $model->setError(sprintf($label . strtolower(JText::_('JGLOBAL_FIELD_INVALID_PASSWORD_LENGTH')), $minLength));
                return false;
            }
        }
        return true;
    }

    public static function validatePasswords($name, $password, $password2, $config, $model) {
        $result = true;
        $minLength = 0;
        $name = ($name?$name:JText::_('COM_APIPORTAL_REGISTRATION_PASSWORD_LABEL')) . ': ';
        if (property_exists($config, 'minimumPasswordLength')) {
            $minLength = $config->minimumPasswordLength;
            if (strlen($password) < $minLength) {
                $model->setError(sprintf($name . strtolower(JText::_('JGLOBAL_FIELD_INVALID_PASSWORD_LENGTH')), $minLength));
                $result = false;
            }
        }
        if ($result && (strlen($password)!=strlen($password2) || $password!==$password2)) {
                $model->setError(sprintf($name . strtolower(JText::_('JGLOBAL_FIELD_PASSWORDS_DONT_MATCH')), $minLength));
                $result = false;
        }
        return $result;
    }

    /*
     * Validate Custom Fields
     *
     * Since we don't know what type the custom field is, the best we can do is to
     * validate that the field is not larger than MAX_FIELD_LEN.
     *
     * We also can't currently validate that it is a required field.
     *
     * TODO: Consider adding support for:
     *  - checkbox
     *  - email
     *  - hidden
     *  - password
     *  - radio
     *  - tel
     *  - url
     *
     * And possibly:
     *  - textareas
     *  - arrays
     */
    public static function validateCustomFields($fields, $standardFields, &$data, $model) {
        $result = true;
        foreach ($fields as $key => $value) {
            // Look for fields that are not present in the standard set of fields
            if (!in_array($key, $standardFields)) {
                $name = ucfirst($key);
                $field = $value;
                if (!ApiPortalValidator::validateLength($name, $field, $model, self::MAX_FIELD_LEN)) {
                    $data[$key] = '';
                    $result = false;
                }
            }
        }
        return $result;
    }

    public static function isValidGuid($guid) {
        return !empty($guid) &&
          preg_match('/^[a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{12}$/', $guid);
    }

/*
    // NOT USED: Kept for Joomla password policy check reference
    private function validateJoomlaPasswordPolicy($password, $model) {
        $usersConfig = JComponentHelper::getParams('com_users');

        // Password check (params came from Joomla settings -> Users -> Options)
        $password_minimum_length    = $usersConfig->get('minimum_length');
        $password_minimum_integers  = $usersConfig->get('minimum_integers');
        $password_minimum_symbols   = $usersConfig->get('minimum_symbols');
        $password_minimum_uppercase = $usersConfig->get('minimum_uppercase');

        //It's not defined anywhere!
        $minLength = '';

        $label = JText::_('COM_APIPORTAL_REGISTRATION_PASSWORD_LABEL') . ': ';

        // Minimum length
        if (strlen($password) < $password_minimum_length) {
            // Password too short
            $model->setError(sprintf($label . strtolower(JText::_('JGLOBAL_FIELD_INVALID_PASSWORD_LENGTH')), $minLength));
            $result = false;
        }
        // Minimum number of integers
        if (LooklikeHelperUtils::countDigits($password) < $password_minimum_integers) {
            // Not enough digits (integers) in the password
            $model->setError(sprintf($label . strtolower(JText::_('JGLOBAL_FIELD_INVALID_PASSWORD_INTEGERS')), $minLength));
            $result = false;
        }
        // Minimum number of symbols
        if (LooklikeHelperUtils::countSymbols($password) < $password_minimum_symbols) {
            // Not enough symbols in the password
            $model->setError(sprintf($label . strtolower(JText::_('JGLOBAL_FIELD_INVALID_PASSWORD_SYMBOLS')), $minLength));
            $result = false;
        }
        // Minimum number of uppercase charaters
        if (LooklikeHelperUtils::countUppercase($password) < $password_minimum_uppercase) {
            // Not enough uppercase charaters in the password
            $model->setError(sprintf($label . strtolower(JText::_('JGLOBAL_FIELD_INVALID_PASSWORD_UPPER_CASE')), $minLength));
            $result = false;
        }
        return $result;
    }
*/
}
