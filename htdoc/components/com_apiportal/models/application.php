<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modelitem');

/*
 * TODO: This class needs a serious round of refactoring for the validation functions.
 * There is a lot of common code that would benefit from a set of validation helpers in
 * the ApiPortalValidator class.
 */
class ApiPortalModelApplication extends JModelItem
{
    protected $item;

    private $apidata;
    private $imageInfo;
    private $apis;
    private $users;
    private $apiKey;
    private $oauthClient;

    private $applicationId;
    private $organizationId;
    private $apiKeyId;
    private $oauthClientId;
    private $permissionId;
    private $userId;

    private $resizeWidth = '300';
    private $resizeHeight = '300';

    public function createApp() {
    
foreach ($this->apidata as $key => $value) {
/*
 $strCPF = $this->apidata['cpf'];
 $strCPF = preg_replace('/[^A-Za-z0-9\-]/', '', $strCPF);
 $strCPF = str_replace("-","",$strCPF);
*/
 $params = array(
 "abbreviatedName"=>$this->apidata['name'],
  "callbackRefund"=> "http://localhost:8083/api/portal/e",
  "cpfCnpj"=>'11111111111',
  "email"=>$this->apidata['email'],
  "fantasyName"=>$this->apidata['name'],
  "phoneNumber"=>'(00)00000-0000',
  "site"=> "http://www.userede.com.br.com.br/desenvolvedores",
  "socialReasonName"=>$this->apidata['name']);
        }

$json_data = json_encode($params);  
$ch = curl_init("https://LB-API-Gateway-Externo-2068934300.sa-east-1.elb.amazonaws.com/api/portal/1.0/store/"); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$myjson = json_decode($result);
curl_close($ch);
    
        foreach ($this->apidata as $key => $value) {
            // Everything in the 'data' array will get posted to the API Manger, including any custom fields
            $data['pv'] = $myjson->pv;
            $data['token'] =$myjson->token;
            $data['authBase64'] =$myjson->authBase64;
            $data[$key] = $value;
        }

        if (isset($this->apidata['enabled'])) {
            $data['enabled'] = 1;
        } else {
            // Enabled not checked: no checkbox parameter sent
            $data['enabled'] = 0;
        }

        if ($this->organizationId == null) {
            $session = JFactory::getSession();
            $this->organizationId = $session->get(ApiPortalSessionVariables::MANAGER_ORG_ID);
        }
        $data['organizationId'] = $this->organizationId;
        $data['apis'] = $this->apis;

        $image = null;
        if ($this->imageInfo) {
            $filename = $this->imageInfo['name'];
            $filepath = $this->imageInfo['tmp_name'];
            $type = $this->imageInfo['type'];
            try {
                ApiPortalHelper::resizeImage($filepath, $filename, $this->resizeWidth, $this->resizeHeight);
            } catch (Exception $e) {
                $app = JFactory::getApplication();
                $app->enqueueMessage($e->getMessage(), 'error');
                return null;
            }
            /*
             * Images need to be handled differently if 'autoApproveApplications' is on or off.
             * If on, handle the image the same way as we do for updateApp() by POSTing it separately.
             * If off, we need to base64 encode it and submit it with the Application Request.
             */
            $config = ApiPortalHelper::getAPIMangerConfig();
            if (property_exists($config, 'autoApproveApplications') && $config->autoApproveApplications) {
                $image = array('file' => "@$filepath;type=$type");
            } else {
                $imageData = base64_encode(file_get_contents($filepath));
                $inline = 'data:' . $type . ';base64,' . $imageData;
                $data['image'] = $inline;
            }
        }

        $path = ApiPortalHelper::getVersionedBaseFolder() ."/applications";
        $application = ApiPortalHelper::doPost($path, $data, CONTENT_TYPE_JSON);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        if ($image) {
            $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$application->id/image";
            ApiPortalHelper::doPost($path, $image, CONTENT_TYPE_MULTI);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        }

        return $application->id;
    }

    public function validateCreateApp(&$data) {
        $result = true;

        $this->apidata = JRequest::getVar('apidata', array(), 'post', 'ARRAY');
        if (count($this->apidata) > ApiPortalValidator::MAX_ARRAY_LEN) {
            $this->setError(sprintf(JText::_('COM_APIPORTAL_ARRAY_TOO_LONG'),  ApiPortalValidator::MAX_ARRAY_LEN));
            return false;
        }

        // Validate image if present
        if (!$this->validateImage()) {
            $result = false;
        }

        // Validate Organization ID if present
        if (ApiPortalHelper::hasAdminRole()) {
            $this->organizationId = JRequest::getVar('organizationId', null, 'post', 'STRING');
            if ($this->organizationId) {
                if (!ApiPortalValidator::isValidGuid($this->organizationId)) {
                    $this->setError(JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_INVALID_ORGANIZATION'));
                    $result = false;
                }
            }
        }

        // Validate any required fields and all field lengths
        $name = JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_NAME_LABEL');
        $field = isset($this->apidata['name']) ? $this->apidata['name'] : null;
        if (!ApiPortalValidator::validateRequired($name, $field, $this)) {
            $result = false;
        } else if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $data['name'] = '';
            $result = false;
        }

        $name = JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_DESCRIPTION_LABEL');
        $field = isset($this->apidata['description']) ? $this->apidata['description'] : null;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_TEXTAREA_LEN)) {
            $data['description'] = '';
            $result = false;
        }

        $name = JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_PHONE_LABEL');
        $field = isset($this->apidata['phone']) ? $this->apidata['phone'] : null;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $data['phone'] = '';
            $result = false;
        }

        $name = JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_EMAIL_LABEL');
        $field = isset($this->apidata['email']) ? $this->apidata['email'] : null;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $data['email'] = '';
            $result = false;
        }

        // Validate checkbox value length
        if (isset($this->apidata['enabled'])) {
            /*
             * For the truly paranoid: the checkbox value is simply a string,
             * and can be manipulated for evil by any competent hacker.
             */
            $name = JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_ENABLE_LABEL');
            $field = $this->apidata['enabled'];
            if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
                $data['enabled'] = 'false';
                $result = false;
            }
        } else {
            /*
             * If the checkbox was not checked, there will be nothing sent in the POST, so set it to 'false'
             * in case we have any validation errors, it will then remain unchecked when we get back to the page.
             */
            $data['enabled'] = 'false';
        }

        // Validate APIs
        $this->apis = isset($this->apidata['apis']) ? $this->apidata['apis'] : array();
        if (count($this->apis) > ApiPortalValidator::MAX_ARRAY_LEN) {
            $this->setError(sprintf(JText::_('COM_APIPORTAL_ARRAY_TOO_LONG'),  ApiPortalValidator::MAX_ARRAY_LEN));
            $result = false;
        } else {
            foreach ($this->apis as $api) {
                /*
                 * Again for the truly paranoid: verify that the APIs selected are
                 * actually represented by a well-formed guid string.
                 */
                if (!ApiPortalValidator::isValidGuid($api)) {
                    $this->setError(JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_INVALID_API_SELECTED'));
                    $result = false;
                }
            }
        }

        // Validate any custom fields: currently only basic <input type="text" /> fields are supported
        $standardFields = array('name', 'description', 'phone', 'email', 'enabled', 'apis');
        if (!ApiPortalValidator::validateCustomFields($this->apidata, $standardFields, $data, $this)) {
            // Errors have already been taken care of
            $result = false;
        }

        return $result;
    }

    private function validateImage() {
        $result = true;
        $this->imageInfo = JRequest::getVar('image', null, 'files', 'ARRAY');
        if ($this->imageInfo) {
            $error = $this->imageInfo['error'];
            switch ($error) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $this->setError(JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_IMAGE_TOO_LARGE'));
                    $result = false;
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->imageInfo = null;
                    break;
                default:
                    error_log('Image: error $error occurred uploading image');
                    $this->setError(JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_IMAGE_ERROR'));
                    $result = false;
                    break;
            }
        }
        return $result;
    }

    public function updateApp() {
        foreach ($this->apidata as $key => $value) {
            // Everything in the 'data' array will get posted to the API Manger, including any custom fields
            $data[$key] = $value;
        }

        if (isset($this->apidata['enabled'])) {
            $data['enabled'] = 1;
        } else {
            // Enabled not checked: no checkbox parameter sent
            $data['enabled'] = 0;
        }

        $data['id'] = $this->applicationId;
        $data['organizationId'] = $this->organizationId;

        // WORKAROUND: API Manager stomps CreatedBy/CreatedOn if not provided here.
        $data['createdBy'] = JRequest::getVar('createdBy', '', 'post', 'STRING');
        $data['createdOn'] = JRequest::getVar('createdOn', '', 'post', 'STRING');

	    // From 7.5.3 this param is required - pass it in the request - currently there is no security issue
	    // because the state can't be changed from this request - no matter who you are - so it's safe now - it's
	    // a regression in the API but it is what it is.
	    $data['state'] = 'approved';

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId";
        ApiPortalHelper::doPut($path, $data, CONTENT_TYPE_JSON);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        $path = ApiPortalHelper::getVersionedBaseFolder(). "/applications/$this->applicationId/apis";
        $oldApis = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }
        $newApis = $this->apis;

        /*
         * For each API in the 'new' array, if it exists in 'old' - do nothing.
         * For each API in the 'new' array, if it does not exist in 'old' - add API.
         *
         * For each API in the 'old' array, if it exists in 'new' - do nothing.
         * For each API in the 'old' array, if it does not exist in 'new' - delete API.
         */
        foreach ($newApis as $newApi) {
            $found = false;
            foreach ($oldApis as $oldApi) {
                if ($newApi == $oldApi->apiId) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                continue;
            }

            $data = array(
                'apiId' => $newApi,
                'enabled' => true
            );

            $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/apis";
            ApiPortalHelper::doPost($path, $data, CONTENT_TYPE_JSON);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        }

        foreach ($oldApis as $oldApi) {
            if (!in_array($oldApi->apiId, $newApis)) {
                $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/apis/$oldApi->id";
                ApiPortalHelper::doDelete($path);

                if (ApiPortalHelper::isHttpError()) {
                    return null;
                }
            }
        }

        // Send image to API Manager if present
        $image = null;
        if ($this->imageInfo) {
            $filename = $this->imageInfo['name'];
            $filepath = $this->imageInfo['tmp_name'];
            $type = $this->imageInfo['type'];
            try {
                ApiPortalHelper::resizeImage($filepath, $filename, $this->resizeWidth, $this->resizeHeight);
            } catch (Exception $e) {
                $app = JFactory::getApplication();
                $app->enqueueMessage($e->getMessage(), 'error');
                return null;
            }

            $data = array('file' => "@$filepath;type=$type");

            $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/image";
            ApiPortalHelper::doPost($path, $data, CONTENT_TYPE_MULTI);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        }

        return $this->applicationId;
    }

    public function validateUpdateApp(&$data) {
        $result = true;

        $this->apidata = JRequest::getVar('apidata', array(), 'post', 'ARRAY');
        if (count($this->apidata) > ApiPortalValidator::MAX_ARRAY_LEN) {
            $this->setError(sprintf(JText::_('COM_APIPORTAL_ARRAY_TOO_LONG'),  ApiPortalValidator::MAX_ARRAY_LEN));
            return false;
        }

        // Validate App/Org Id's
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }

        $this->organizationId = JRequest::getVar('organizationId', null, 'post', 'STRING');
        if ($this->organizationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_ORGANIZATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_ORGANIZATION'));
            $result = false;
        }

        // Validate image if present
        if (!$this->validateImage()) {
            $result = false;
        }

        // Validate any required fields and all field lengths
        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_NAME_LABEL');
        $field = isset($this->apidata['name']) ? $this->apidata['name'] : null;
        if (!ApiPortalValidator::validateRequired($name, $field, $this)) {
            $result = false;
        } else if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $data['name'] = '';
            $result = false;
        }

        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_DESCRIPTION_LABEL');
        $field = isset($this->apidata['description']) ? $this->apidata['description'] : null;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_TEXTAREA_LEN)) {
            $data['description'] = '';
            $result = false;
        }

        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_PHONE_LABEL');
        $field = isset($this->apidata['phone']) ? $this->apidata['phone'] : null;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $data['phone'] = '';
            $result = false;
        }

        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_EMAIL_LABEL');
        $field = isset($this->apidata['email']) ? $this->apidata['email'] : null;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $data['email'] = '';
            $result = false;
        }

        // Validate checkbox value length
        if (isset($this->apidata['enabled'])) {
            /*
             * For the truly paranoid: the checkbox value is simply a string,
             * and can be manipulated for evil by any competent hacker.
             */
            $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_ENABLE_LABEL');
            $field = $this->apidata['enabled'];
            if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
                $data['enabled'] = 'false';
                $result = false;
            }
        } else {
            /*
             * If the checkbox was not checked, there will be nothing sent in the POST, so set it to 'false'
             * in case we have any validation errors, it will then remain unchecked when we get back to the page.
             */
            $data['enabled'] = 'false';
        }

        // Validate APIs
        $this->apis = JRequest::getVar('apis', array(), 'post', 'ARRAY');
        if (count($this->apis) > ApiPortalValidator::MAX_ARRAY_LEN) {
            $this->setError(sprintf(JText::_('COM_APIPORTAL_ARRAY_TOO_LONG'),  ApiPortalValidator::MAX_ARRAY_LEN));
            $result = false;
        } else {
            foreach ($this->apis as $api) {
                /*
                 * Again for the truly paranoid: verify that the APIs selected are
                 * actually represented by a well-formed guid string.
                 */
                if (!ApiPortalValidator::isValidGuid($api)) {
                    $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_API_SELECTED'));
                    $result = false;
                }
            }
        }

        // Validate any custom fields: currently only basic <input type="text" /> fields are supported
        $standardFields = array('name', 'description', 'phone', 'email', 'enabled', 'apis');
        if (!ApiPortalValidator::validateCustomFields($this->apidata, $standardFields, $data, $this)) {
            // Errors have already been taken care of
            $result = false;
        }

        return $result;
    }

    public function deleteApp() {
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId";
        ApiPortalHelper::doDelete($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        return $this->applicationId;
    }

    public function validateDeleteApp() {
        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }
        return $result;
    }

    private function updateCreatedKeyDefaultOrigins($appId) {

        // Get the API Keys
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/".$appId."/apikeys";  //get latest key
        $apiKeys = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }


        // Find the one we are interested in
        $latest_key_data = null;
        $createdstamp = 0;
        $keyId = null;

        //get key with max create date (latest) and update corsOrigins '' to '*'  
        foreach ($apiKeys as $apiKey) {
            if($apiKey->createdOn > $createdstamp){
                $createdstamp = $apiKey->createdOn;
                $latest_key_data = $apiKey;
                $keyId = $apiKey->id;
            }
        }

        if($latest_key_data == null){
            return $appId;
        }

        $latest_key_data->corsOrigins = preg_split('/\s+/', '*' ); //corsOrigins is array, preg_split gets array from string

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/".$appId."/apikeys/".$keyId;
        ApiPortalHelper::doPut($path, $latest_key_data, CONTENT_TYPE_JSON);
        ;
        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        return $appId;
    }

    public function createKey() {
        $data = array(
            'applicationId' => $this->applicationId,
            'enabled' => true
        );

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/apikeys";
        ApiPortalHelper::doPost($path, $data, CONTENT_TYPE_JSON);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        //originally: return $this->applicationId;

        return ApiPortalModelApplication::updateCreatedKeyDefaultOrigins($this->applicationId);

    }

    public function validateCreateKey() {
        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }
        return $result;
    }

    public function updateKey() {
        // Get the API Keys
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/apikeys";
        $apiKeys = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        // Find the one we are interested in
        $data = null;
        foreach ($apiKeys as $apiKey) {
            if ($apiKey->id == $this->apiKeyId) {
                $data = $apiKey;
                $data->corsOrigins = preg_split('/\s+/', $this->apiKey->corsOrigins);
                break;
            }
        }

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/apikeys/$this->apiKeyId";
        ApiPortalHelper::doPut($path, $data, CONTENT_TYPE_JSON);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        return $this->applicationId;
    }

    public function validateUpdateKey() {
        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }

        $this->apiKeyId = JRequest::getVar('apiKeyId', null, 'post', 'STRING');
        if ($this->apiKeyId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_API_KEY'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->apiKeyId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_API_KEY'));
            $result = false;
        }

        $this->apiKey = new stdClass();

        $this->apiKey->corsOrigins = JRequest::getString('corsOrigins', '', 'post', 'STRING');
        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEY_DIALOG_CORS_LABEL');
        $field = $this->apiKey->corsOrigins;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_TEXTAREA_LEN)) {
            $result = false;
        }

        return $result;
    }

    public function toggleKeyState() {
        // Get the API Keys
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/apikeys";
        $apiKeys = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        // Find the one we are interested in
        $data = null;
        foreach ($apiKeys as $apiKey) {
            if ($apiKey->id == $this->apiKeyId) {
                $data = $apiKey;
                $apiKey->enabled = !$apiKey->enabled;
                break;
            }
        }

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/apikeys/$this->apiKeyId";
        ApiPortalHelper::doPut($path, $data, CONTENT_TYPE_JSON);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        return $this->applicationId;
    }

    public function validateToggleKeyState() {
        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }

        $this->apiKeyId = JRequest::getVar('apiKeyId', null, 'post', 'STRING');
        if ($this->apiKeyId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_API_KEY'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->apiKeyId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_API_KEY'));
            $result = false;
        }
        return $result;
    }

    public function deleteKey() {
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/apikeys/$this->apiKeyId";
        ApiPortalHelper::doDelete($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        return $this->applicationId;
    }

    public function validateDeleteKey() {
        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }

        $this->apiKeyId = JRequest::getVar('apiKeyId', null, 'post', 'STRING');
        if ($this->apiKeyId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_API_KEY'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->apiKeyId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_API_KEY'));
            $result = false;
        }

        return $result;
    }


    private function updateOAuthDefaultOrigins($appId) {

        // Get the API Keys
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/".$appId."/oauth";  //get latest oauth
        $oauthClients = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        // Find the one we are interested in
        $latest_oauth_data = null;
        $createdstamp = 0;
        $oauthClientId= null;

        //get key with max create date (latest) and update corsOrigins '' to '*'  
        foreach ($oauthClients as $oauthClient) {
            if($oauthClient->createdOn > $createdstamp){
                $createdstamp = $oauthClient->createdOn;
                $latest_oauth_data = $oauthClient;
                $oauthClientId= $oauthClient->id;
            }
        }

        if($latest_oauth_data == null){
            return null;; //$appId;
        }

        $latest_oauth_data->corsOrigins = preg_split('/\s+/', '*' ); //corsOrigins is array, preg_split gets array from string

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/".$appId."/oauth/".$oauthClientId;
        ApiPortalHelper::doPut($path, $latest_oauth_data, CONTENT_TYPE_JSON);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        return $appId;
    }

    public function createOAuth() {
        $data = array(
            'applicationId' => $this->applicationId,
            'enabled' => true,
            'type' => $this->oauthClient->clientType,
            'corsOrigins' => preg_split('/\s+/', $this->oauthClient->corsOrigins),
            'redirectUrls' => preg_split('/\s+/', $this->oauthClient->redirectUrls),
            'cert' => $this->oauthClient->x509Certificate
        );

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/oauth";
        ApiPortalHelper::doPost($path, $data, CONTENT_TYPE_JSON);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        //originally return $this->applicationId;
        return ApiPortalModelApplication::updateOAuthDefaultOrigins($this->applicationId);
    }

    public function validateCreateOAuth() {
        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }

        $this->oauthClient = new stdClass();

        $this->oauthClient->clientType = JRequest::getString('client-type', '', 'post', 'STRING');
        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_TYPE_LABEL');
        $field = $this->oauthClient->clientType;
        if (!ApiPortalValidator::validateRequired($name, $field, $this)) {
            $result = false;
        } else if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $result = false;
        }

        $this->oauthClient->corsOrigins = JRequest::getString('corsOrigins', '', 'post', 'STRING');
        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_CORS_LABEL');
        $field = $this->oauthClient->corsOrigins;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_TEXTAREA_LEN)) {
            $result = false;
        }

        $this->oauthClient->redirectUrls = JRequest::getString('redirect-urls', '', 'post', 'STRING');
        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_URLS_LABEL');
        $field = $this->oauthClient->redirectUrls;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_TEXTAREA_LEN)) {
            $result = false;
        }

        $this->oauthClient->x509Certificate = JRequest::getString('x509-certificate', '', 'post', 'STRING');
        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_X509_LABEL');
        $field = $this->oauthClient->x509Certificate;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_TEXTAREA_LEN)) {
            $result = false;
        }

        return $result;
    }

    public function updateOAuth() {
        // Get the OAuth Clients
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/oauth";
        $oauthClients = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        // Find the one we are interested in
        $data = null;
        foreach ($oauthClients as $oauthClient) {
            if ($oauthClient->id == $this->oauthClientId) {
                $data = $oauthClient;
                $data->type = $this->oauthClient->clientType;
                $data->corsOrigins = preg_split('/\s+/', $this->oauthClient->corsOrigins);
                $data->redirectUrls = preg_split('/\s+/', $this->oauthClient->redirectUrls);
                $data->cert = $this->oauthClient->x509Certificate;
                break;
            }
        }

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/oauth/$this->oauthClientId";
        ApiPortalHelper::doPut($path, $data, CONTENT_TYPE_JSON);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        return $this->applicationId;
    }

    public function validateUpdateOAuth() {
        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }

        $this->oauthClientId = JRequest::getVar('oauthClientId', null, 'post', 'STRING');
        if ($this->oauthClientId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_CLIENT'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->oauthClientId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_CLIENT'));
            $result = false;
        }

        $this->oauthClient = new stdClass();

        $this->oauthClient->clientType = JRequest::getString('client-type', '', 'post', 'STRING');
        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_TYPE_LABEL');
        $field = $this->oauthClient->clientType;
        if (!ApiPortalValidator::validateRequired($name, $field, $this)) {
            $result = false;
        } else if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $result = false;
        }

        $this->oauthClient->corsOrigins = JRequest::getString('corsOrigins', '', 'post', 'STRING');
        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_CORS_LABEL');
        $field = $this->oauthClient->corsOrigins;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_TEXTAREA_LEN)) {
            $result = false;
        }

        $this->oauthClient->redirectUrls = JFactory::getApplication()->input->post->get('redirect-urls', '', 'RAW');
        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_URLS_LABEL');
        $field = $this->oauthClient->redirectUrls;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_TEXTAREA_LEN)) {
            $result = false;
        }

        $this->oauthClient->x509Certificate = JRequest::getString('x509-certificate', '', 'post', 'STRING');
        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_X509_LABEL');
        $field = $this->oauthClient->x509Certificate;
        if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_TEXTAREA_LEN)) {
            $result = false;
        }

        return $result;
    }

    public function toggleOAuthState() {
        // Get the OAuth Clients
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/oauth";
        $oauthClients = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        // Find the one we are interested in
        $data = null;
        foreach ($oauthClients as $oauthClient) {
            if ($oauthClient->id == $this->oauthClientId) {
                $data = $oauthClient;
                $oauthClient->enabled = !$oauthClient->enabled;
                break;
            }
        }

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/oauth/$this->oauthClientId";
        ApiPortalHelper::doPut($path, $data, CONTENT_TYPE_JSON);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        return $this->applicationId;
    }

    public function validateToggleOAuthState() {
        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }

        $this->oauthClientId = JRequest::getVar('oauthClientId', null, 'post', 'STRING');
        if ($this->oauthClientId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_CLIENT'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->oauthClientId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_CLIENT'));
            $result = false;
        }
        return $result;
    }

    public function deleteOAuth() {
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/oauth/$this->oauthClientId";
        ApiPortalHelper::doDelete($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        return $this->applicationId;
    }

    public function validateDeleteOAuth() {
        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }

        $this->oauthClientId = JRequest::getVar('oauthClientId', null, 'post', 'STRING');
        if ($this->oauthClientId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_CLIENT'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->oauthClientId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_CLIENT'));
            $result = false;
        }
        return $result;
    }

    /**
     * Create External OAuth credentials for an application
     * @param $appId - application id
     * @param $clientId - user input
     * @param $corsOrigin - user input
     * @return bool
     */
    public function createExtOAuth($appId, $clientId, $corsOrigin)
    {
        // Prepare the data
        $data = array(
            'clientId' => $clientId,
            'enabled' => true,
            'corsOrigins' => preg_split('/\s+/', $corsOrigin),
        );

        // Send the request
        $path = ApiPortalHelper::getVersionedBaseFolder() . '/applications/' . $appId . '/extclients';
        ApiPortalHelper::doPost($path, $data, CONTENT_TYPE_JSON);

        // Check for errors
        if (ApiPortalHelper::isHttpError()) {
            return false;
        }

        return true;
    }

    /**
     * Update External OAuth
     * @param $appId - application id
     * @param $clientId - user input
     * @param $corsOrigin - user input
     * @param $objectId - id of the external oauth record
     * @param $objectEnabled - enable/disable the external OAuth
     * @return bool
     */
    public function updateExtOAuth($appId, $clientId, $corsOrigin, $objectId, $objectEnabled)
    {
        // Prepare the data
        $data = array(
            'id' => $objectId,
            'clientId' => $clientId,
            'corsOrigins' => preg_split('/\s+/', $corsOrigin),
            'enabled' => $objectEnabled
        );

        // Send the request
        $path = ApiPortalHelper::getVersionedBaseFolder() . '/applications/' . $appId . '/extclients/' . $objectId;
        ApiPortalHelper::doPut($path, $data, CONTENT_TYPE_JSON);

        // Check for errors
        if (ApiPortalHelper::isHttpError()) {
            return false;
        }

        return true;
    }

    /**
     * Change the state for External OAuth setting
     * Enable/Disable
     * @param $appId - application id
     * @param $objectId - setting id
     * @return bool
     */
    public function toggleExtOAuthState($appId, $objectId)
    {
        // Get current external OAuth setting
        // We need this because there is no request for change only the state
        // so we need the rest of the data and just change the state
        $path = ApiPortalHelper::getVersionedBaseFolder() . '/applications/' . $appId . '/extclients';
        $extOAuthClients = ApiPortalHelper::doGet($path);

        // Check for the errors
        if (ApiPortalHelper::isHttpError()) {
            return false;
        }

        // Find the one we are interested in
        $data = null;
        foreach ($extOAuthClients as $client) {
            if ($client->id == $objectId) {
                $data = $client;
                // Change the state
                $data->enabled = !$client->enabled;
                break;
            }
        }

        // Put the data right back
        $path = ApiPortalHelper::getVersionedBaseFolder() . '/applications/' . $appId . '/extclients/' . $objectId;
        ApiPortalHelper::doPut($path, $data, CONTENT_TYPE_JSON);

        // Check for errors
        if (ApiPortalHelper::isHttpError()) {
            return false;
        }

        return true;
    }

    /**
     * Delete External OAuth setting
     * @param $appId
     * @param $objectId
     * @return bool
     */
    public function deleteExtOAuth($appId, $objectId)
    {
        // Send the request for deletion with needed params
        $path = ApiPortalHelper::getVersionedBaseFolder() . '/applications/' . $appId . '/extclients/' . $objectId;
        ApiPortalHelper::doDelete($path);

        // Check for errors
        if (ApiPortalHelper::isHttpError()) {
            return false;
        }

        return true;
    }

    /**
     * Validate External OAuth setting
     * @param $appId - application id
     * @param $clientId - user input
     * @param null $corsOrigin - user input
     * @param null $objectId - id of the setting record
     * @return bool
     * @throws Exception
     */
    public function validateExtOAuth($appId, $clientId, $corsOrigin = null, $objectId = null)
    {
        // Validate application id
        if (empty($appId)) {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'), 'error');
            return false;
        } else if (!ApiPortalValidator::isValidGuid($appId)) {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'), 'error');
            return false;
        }

        // Validate client ID - user input
        if (empty($clientId)) {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_EXT_OAUTH_CLIENT_EMPTY_CLIENT_ID'), 'error');
            return false;
        } else if (strlen($clientId) > ApiPortalValidator::MAX_FIELD_LEN) {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENTS_ID_HEADER') . ' ' . JText::_('JGLOBAL_FIELD_TOO_LONG'), 'error');
            return false;
        }

        // Validate CorsOrigins
        if ($corsOrigin && strlen($corsOrigin) > ApiPortalValidator::MAX_TEXTAREA_LEN) {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENT_DIALOG_CORS_LABEL') . ' ' . JText::_('JGLOBAL_FIELD_TOO_LONG'), 'error');
            return false;
        }

        // Validate id of the setting - used on update
        // It's null when not passed as method argument - on create
        // It's false when is passed and empty but needed
        if ($objectId === false) {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_EXT_OAUTH_CLIENT_EMPTY_OBJECT_ID'), 'error');
            return false;
        } else if ($objectId && strlen($objectId) > ApiPortalValidator::MAX_FIELD_LEN) {
            JFactory::getApplication()->enqueueMessage('Object ID ' . JText::_('JGLOBAL_FIELD_TOO_LONG'), 'error');
            return false;
        } else if ($objectId && !ApiPortalValidator::isValidGuid($appId)) {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENT_INVALID_OBJECY_ID'), 'error');
            return false;
        }

        return true;
    }

    public function addSharedUsers() {
        // Add shared user with default permission set to 'view'
        foreach ($this->users as $userId) {
            $data = array(
                'userId' => $userId,
                'permission' => 'view'
            );

            $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/permissions";
            ApiPortalHelper::doPost($path, $data, CONTENT_TYPE_JSON);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }
        }

        return $this->applicationId;
    }

    public function validateAddSharedUsers() {
        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }

        $this->users =  JRequest::getVar('users', array(), 'post', 'ARRAY');
        if (count($this->users) > ApiPortalValidator::MAX_ARRAY_LEN) {
            $this->setError(sprintf(JText::_('COM_APIPORTAL_ARRAY_TOO_LONG'),  ApiPortalValidator::MAX_ARRAY_LEN));
            $result = false;
        } else {
            foreach ($this->users as $userId) {
                if (!ApiPortalValidator::isValidGuid($userId)) {
                    $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_INVALID_USER'));
                    $result = false;
                }
            }
        }
        return $result;
    }

    public function removeSharedUser() {
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/permissions/$this->permissionId";
        ApiPortalHelper::doDelete($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        return $this->applicationId;
    }

    public function validateRemoveSharedUser() {
        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }

        $this->permissionId = JRequest::getVar('permissionId', null, 'post', 'STRING');
        if ($this->permissionId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_INVALID_PERMISSION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->permissionId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_INVALID_PERMISSION'));
            $result = false;
        }
        return $result;
    }

    // AJAX: Skip Error Messages/HTTP Status Verification - results will propagate directly
    public function newOAuthSecret() {
        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }

	    // Check for CSRF token
	    if (!JSession::checkToken('post'))
	    {
		    return false;
	    }

        $this->oauthClientId = JRequest::getVar('oauthClientId', null, 'post', 'STRING');
        if ($this->oauthClientId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_CLIENT'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->oauthClientId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_INVALID_CLIENT'));
            $result = false;
        }

        if (!$result) {
            return null;
        }

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/oauth/$this->oauthClientId/newsecret";
        return ApiPortalHelper::doPut($path, null);
    }

    // AJAX: Skip Error Messages/HTTP Status Verification - results will propagate directly
    public function updateSharedUser() {

        $result = true;
        $this->applicationId = JRequest::getVar('applicationId', null, 'post', 'STRING');
        if ($this->applicationId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->applicationId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION'));
            $result = false;
        }

	    // Check for CSRF token
	    if (!JSession::checkToken('post')) {
		    JFactory::getApplication()->enqueueMessage(JText::_('JINVALID_TOKEN'), 'error');
		    return false;
	    }

        $this->userId = JRequest::getVar('userId', null, 'post', 'STRING');
        if ($this->userId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_INVALID_USER'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->userId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_INVALID_USER'));
            $result = false;
        }

        $this->permissionId = JRequest::getVar('permissionId', null, 'post', 'STRING');
        if ($this->permissionId == null) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_INVALID_PERMISSION'));
            $result = false;
        } else if (!ApiPortalValidator::isValidGuid($this->permissionId)) {
            $this->setError(JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_INVALID_PERMISSION'));
            $result = false;
        }

        $permission = JRequest::getVar('permission', null, 'post', 'STRING');
        $name = JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_SHARED_PERMISSION');
        $field = $permission;
        if (!ApiPortalValidator::validateRequired($name, $field, $this)) {
            $result = false;
        } else if (!ApiPortalValidator::validateLength($name, $field, $this, ApiPortalValidator::MAX_FIELD_LEN)) {
            $result = false;
        }

        if (!$result) {
            return null;
        }

        $data = array(
            'id' => $this->permissionId,
            'userId' => $this->userId,
            'permission' => $permission,
        );

        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$this->applicationId/permissions/$this->permissionId";
        return ApiPortalHelper::doPut($path, $data, CONTENT_TYPE_JSON);
    }

    public function getItem() {
        if (!isset($this->item))
        {
            $this->item = $this->getApplication();
        }
        return $this->item;
    }

    private function getApplication() {
        $item = new stdClass();
        $item->application = null;
        $creating = false;
        $editing = false;
        $viewing = false;

        $layout = JRequest::getString('layout', '');
        $applicationId = JRequest::getString('applicationId', null);
        if ($applicationId == null) {
            // Check if we are creating a new application
            if ($layout != 'create') {
                error_log("Task getApplication failed: missing Application ID");
                $app = JFactory::getApplication();
                if ($layout == 'edit') {
                    $app->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_INVALID_APPLICATION', 'error'));
                } else {
                    $app->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_INVALID_APPLICATION', 'error'));
                }
                return null;
            }
            $creating = true;
        } else if ($layout == 'edit') {
            $editing = true;
        } else {
            $viewing = true;
        }

        // Discover all APIs visible to the current user
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/discovery/apis";
        $discovered = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        $methods = array();
        $apiSecurityProfiles = array();
        $apitext = array();
        foreach ($discovered as $api) {
            // Discovery API does not return 'summary' field, only the Swagger Discovery does as 'description'
            $discovery = ApiPortalHelper::doGet(ApiPortalHelper::getVersionedBaseFolder() . "/discovery/swagger/api/" . rawurlencode($api->name));

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }

            $apitext[$api->id] = new stdClass();
            $apitext[$api->id]->name = $discovery->name;
            $apitext[$api->id]->description = isset($discovery->description) ? $discovery->description : '';
            $apitext[$api->id]->documentationUrl = isset($discovery->documentationUrl) ? $discovery->documentationUrl : '';

            if ($viewing) {
                // Get API method list (for quotas) from swagger if 'viewing' only
                $operations = array();
                foreach ($discovery->apis as $discovered) {
                    $apiSecurityProfiles[$api->id] = $discovery->securityProfile;
                    foreach ($discovered->operations as $operation) {
                        $operations[$operation->id] = $operation;
                    }
                }
                $methods[$api->id] = $operations;
            }
        }

        // Get the list of organizations visible to the current user: most likely just one unless Super User ('apiadmin')
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/organizations";
        $item->organizations = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        // Get list of approved APIs for the organization
        foreach ($item->organizations as $organization) {
            $organizationId = $organization->id;
            $path = ApiPortalHelper::getVersionedBaseFolder() . "/organizations/$organizationId/apis";
            $organization->apis = ApiPortalHelper::doGet($path);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }

            foreach ($organization->apis as $api) {
                $api->name = $apitext[$api->apiId]->name;
                $api->description = $apitext[$api->apiId]->description;
                $api->documentationUrl = $apitext[$api->apiId]->documentationUrl;
            }
        }

        $item->apis = array();
        if (count($item->organizations) == 1) {
            // This is the normal case unless Super User ('apiadmin')
            $item->apis = $item->organizations[0]->apis;
        } else if (ApiPortalHelper::hasAdminRole() && count($item->organizations) > 1) {
            $organizationId = JRequest::getVar('organizationId', null, 'STRING');
            if ($organizationId) {
                foreach ($item->organizations as $organization) {
                    if ($organization->id == $organizationId) {
                        $item->apis = $organization->apis;
                        break;
                    }
                }
            }
        }

        // Get the API Manager configuration
        $item->config = ApiPortalHelper::getAPIMangerConfig();

        if ($creating) {
            // If 'creating' we now have all the information we need
            return $item;
        }

        $currentUserId = ApiPortalHelper::getCurrentUserPortalId();

        // Get Application
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$applicationId";
        $item->application = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            // If we can't get the application, then there is no point in continuing...
            return null;
        } else {
            // Convert 0/1 to true/false
            if ($item->application->enabled) {
                $item->application->enabled = 'true';
            } else {
                $item->application->enabled = 'false';
            }
        }

        // Final case from above where we are not creating and there should be no organizationId
        if (ApiPortalHelper::hasAdminRole() && count($item->organizations) > 1) {
            foreach ($item->organizations as $organization) {
                if ($organization->id == $item->application->organizationId) {
                    $item->apis = $organization->apis;
                    break;
                }
            }
        }

        // Add 'Organization Name' to application
        $item->application->organizationName = ApiPortalHelper::getOrganizationName($item->application->organizationId, $item->organizations);

        // Get the list of users visible to the current user
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/users";
        $item->users = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        // Add 'Created By Name' to application
        $item->application->createdByName = ApiPortalHelper::getCreatedByName($item->application->createdBy, $item->users);

        // Get Application APIs Access
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$applicationId/apis";
        $item->application->apis = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        // Setup security profile oauth scopes
        foreach ($item->application->apis as $selectedApi) {
            if(array_key_exists($selectedApi->apiId, $apiSecurityProfiles)){
                $selectedApi->securityProfile = $apiSecurityProfiles[$selectedApi->apiId];
                $selectedApi->methods = array();
                $selectedApiMethods = $methods[$selectedApi->apiId];
                if($selectedApiMethods != null && array_key_exists($selectedApi->apiId, $methods)){
                    foreach ($selectedApiMethods as $method) {
                        $tmpMethod = new stdClass();
                        $tmpMethod->name = $method->nickname;
                        $tmpMethod->oauthscopes = array();
                        foreach($method->securityProfile->devices as $device){
                            if($device->type = "oauth"){
                                if(isset($device->scopes)){
                                    $tmpMethod->oauthscopes = $device->scopes;
                                }
                            }
                        }
                        array_push($selectedApi->methods, $tmpMethod);
                    }
                }
            }
        }

        // Get Application API Keys
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$applicationId/apikeys";
        $item->application->apikeys = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        // Get Application Oauth Credentials
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$applicationId/oauth";
        $item->application->oauth = ApiPortalHelper::doGet($path);

        if (ApiPortalHelper::isHttpError()) {
            return null;
        }

        // Get Application external oauth
        $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$applicationId/extclients";
        $item->application->extClients = ApiPortalHelper::doGet($path);

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
                $item->application->permission = $permission;
                break;
            }
        }

        // Get the list of users this application is shared with if the Organization is not 'Community'
        if (!ApiPortalHelper::isCommunity($item->application)) {
            $path = ApiPortalHelper::getVersionedBaseFolder() . "/users?field=appid&op=eq&value=$applicationId";
            $users =  ApiPortalHelper::doGet($path);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }

            $count = count($users);
            for ($i = 0; $i < $count; $i++) {
                $user = $users[$i];
                foreach ($permissions as $permission) {
                    if ($permission->userId == $user->id) {
                        $user->permission = $permission;
                        break;
                    }
                }
            }
            $users = array_values($users);
            if (count($users) > 0) {
                usort($users, array($this, "sortUserNames"));
            }
            $item->application->users = $users;
        }

        // Get list of users in this organization if 'editing' only, and the Organization is not 'Community'
        $orgUsers = array();
        if ($editing && !ApiPortalHelper::isCommunity($item->application)) {
            $organizationId = $item->application->organizationId;

            $path = ApiPortalHelper::getVersionedBaseFolder() . "/users?field=orgid&op=eq&value=$organizationId";
            $orgUsers = ApiPortalHelper::doGet($path);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            }

            $count = count($orgUsers);
            for ($i = 0; $i < $count; $i++) {
                $orgUser = $orgUsers[$i];

                // Filter out any users that the application is already shared with
                foreach ($item->application->users as $sharedUser) {
                    if ($orgUser->id == $sharedUser->id) {
                        unset($orgUsers[$i]);
                        break;
                    }
                }
            }

            $orgUsers = array_values($orgUsers);
            if (count($orgUsers) > 0) {
                usort($orgUsers, array($this, "sortUserNames"));
            }
        }
        $item->orgUsers = $orgUsers;

        // Get Application Quotas if 'viewing' only
        if ($viewing) {
            $item->application->quotas = array();

            $path = ApiPortalHelper::getVersionedBaseFolder() . "/applications/$applicationId/quota";
            $quota = ApiPortalHelper::doGet($path);

            if (ApiPortalHelper::isHttpError()) {
                return null;
            } else {
                foreach ($methods as $apiId => $operations) {
                    $item->application->quotas[$apiId] = array();

                    // Pre-fill the list of method names with an empty restriction array.
                    foreach ($operations as $operation) {
                        $item->application->quotas[$apiId][$operation->nickname] = array();
                    }

                    // Now figure out which methods have restrictions
                    foreach ($quota->restrictions as $restriction) {
                        if ($restriction->api == $apiId) {
                            foreach ($operations as $operation) {
                                $formatted = $this->formatQuotaFromConfig($restriction->type, $restriction->config);
                                if ($restriction->method == '*') {
                                    // Quota applies to all methods
                                    $item->application->quotas[$apiId][$operation->nickname][] = $formatted;
                                } else if ($restriction->method == $operation->id) {
                                    // Quota applies to this method only
                                    $item->application->quotas[$apiId][$operation->nickname][] = $formatted;
                                    continue;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $item;
    }

    private function sortUserNames($a, $b) {
        $name1 = strtolower($a->name);
        $name2 = strtolower($b->name);

        if ($name1 == $name2) {
            return 0;
        }
        return ($name1 < $name2) ? -1 : 1;
    }

    private function formatQuotaFromConfig($type, $config) {
        if ($type == 'throttle') {
            $formattedQuota = "Throttle $config->messages message(s) every $config->per $config->period(s)";
        } else {
            $formattedQuota = "Throttle $config->mb MB every $config->per $config->period(s)";
        }

        return $formattedQuota;
    }
}
