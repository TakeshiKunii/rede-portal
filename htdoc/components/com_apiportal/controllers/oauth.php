<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.user.component.controller');

class ApiPortalControllerOauth extends JControllerLegacy
{
    public function authorise($task) {
        // Make sure the session is valid before proceeding with tasks
        ApiPortalHelper::checkSession();
    }

    public function requestToken()
    {

	    // Check for CSRF token
	    if (!JSession::checkToken('post'))
	    {
		    $error = [
			    'error' => 'Error',
			    'error_description' => JText::_('JINVALID_TOKEN')
		    ];
		    echo json_encode($error);
		    exit();
	    }

        try {
            //post params
            $jinput = JFactory::getApplication()->input;
            $grantType = $jinput->post->get('grant_type', 'client_credentials', 'WORD');
            $clientID = $jinput->post->get('client_id', '', 'STRING');
            $clientSecret = $jinput->post->get('client_secret', '', 'STRING');
            $scope = $jinput->post->get('scope', '', 'STRING');
            $loginEndPoint = $jinput->post->get('login_end_point', '', 'STRING');

            //check params
            if (empty($clientID) || empty($clientSecret) || empty($scope) || empty($loginEndPoint)) {
                $error = array(
                    'error' => 'Error. Missing params.',
                    'error_description' => 'Error. Missing params.'
                );
                echo json_encode($error);
                exit();
            }

            //config params
            $config = new ApiportalModelapiportal();
            $api_manager_crt_file = $config->getCertFile();
            $verifyCrt = $config->getVefiryCert();
            $verifyHost = $config->getVerifyHost();
            $verbosPath = $config->getCertDir() . DS . 'curl.log';

            $params = [
                'grant_type' => $grantType,
                'client_id' => $clientID,
                'client_secret' => $clientSecret,
                'scope' => $scope
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_STDERR, fopen($verbosPath, "w+"));
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 40);
            curl_setopt($ch, CURLOPT_TIMEOUT, 40);
            curl_setopt($ch, CURLOPT_SSLVERSION, 6);

            if ($verifyCrt == "1") {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
                curl_setopt($ch, CURLOPT_CAINFO, $api_manager_crt_file);
            }

            if ($verifyHost == "1") {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
            }

            curl_setopt($ch, CURLOPT_URL, $loginEndPoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            $output = curl_exec($ch);
            curl_close($ch);

            echo $output;
            exit();
        } catch (Exception $e) {
            $error = array (
                'error_description' => $e->getMessage(),
                'error' => $e->getCode()
            );
            echo json_encode($error);
            exit();
        }
    }
}