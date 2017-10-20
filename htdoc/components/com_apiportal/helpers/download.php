<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.helper');
require_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'apiconfiguration.php';
require_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_apiportal'.DS.'models'.DS.'apiportal.php';

/**
 * Class ApiPortalDownload
 * For download resource form Api Manager
 * Create instance and pass required params - relative path, the desire chunk in kilobytes and type - if successRequestFirst
 * is set to true a request with Accept header app/json will be send first and if response code is 200 it will
 * continue with another request to the same address with Accept header app/octet-stream and it will force
 * Call method download to start it.
 * download the resource
 * Important! Response form Api Manager must has Content-Disposition header
 */
class ApiPortalDownload
{
    //Store headers from sessions (auth cookie)
    private $headers = [];
    //Chinks int in kilobytes
    private $chunk;
    //Is skd download boolean
    private $successRequestFirst;
    //From api feed
    private $path;
    //Store the session
    private $session;
    //Where to redirect if error occur.
    private $errorRedirect;
    //Last Http Code
    private $lastHttpCode;

    /**
     * @param string $path uri to the resource
     * @param int $chunk in kilobytes
     * @param string $errorRedirect where to redirect if error occur, if not specified it will redirect to /home
     * @param bool|false $successRequestFirst is it required to have a success request before the download
     */
    public function __construct($path, $chunk, $errorRedirect = null, $successRequestFirst=false)
    {
        $path = str_replace('+', '%20', $path);

        $this->path = ApiPortalHelper::getVersionedBaseFolder().$path;
        $this->chunk = $chunk;
        $this->successRequestFirst = $successRequestFirst;
        $this->errorRedirect = $errorRedirect;
        $this->session = JFactory::getSession();
    }

    /**
     * Directly chunk download
     * Setup and execute curl request
     * Force client to download the resource
     * Direct chunk download
     */
    public function download(){
        //Check client session - if expired redirect to login page
        ApiPortalHelper::checkSession(false);

        //Init connection
        $conn = $this->initCurl();
        //Get auth cookies from session
        $headers = ApiPortalHelper::setCookieHeaders($this->session);
        //Add Accept Header
        $headers[] = 'Accept: application/json';
        $headers[] = 'Accept: application/octet-stream';

        //if it's false it's direct download
        if (!$this->successRequestFirst) {
            //Download will be in chunks
            //Set desire chunk in kilobytes
            $conn->curl_opts[CURLOPT_BUFFERSIZE] = $this->chunk;
            //Callback method in which is specified what to do with every chunk
            $conn->curl_opts[CURLOPT_WRITEFUNCTION] = [$this, 'curlChunk'];
        }

        //Try to send the request and log the errors
        $this->sendRequest($conn, $headers);

        //If http code is not 200 don't start the download
        if ($this->lastHttpCode == 200) {
            //What is the type of downloaded element
            if (!$this->successRequestFirst) {
                //Send headers to force download
                $this->sendHeaders($conn);
            } else {
                //Have to have success request first, after that is the request for download
                $headers = ApiPortalHelper::setCookieHeaders($this->session);
                //Add Accept header it's a must
                $headers[] = 'Accept: application/octet-stream';

                //Download will be in chunks
                //Set desire chunk in kilobytes
                $conn->curl_opts[CURLOPT_BUFFERSIZE] = $this->chunk;
                //Callback method in which is specified what to do with every chunk
                $conn->curl_opts[CURLOPT_WRITEFUNCTION] = [$this, 'curlChunk'];

                //Try to send the second request which is the actual download
                //Log if there are errors
                $this->sendRequest($conn, $headers);

                //If it' ok continue with downloading
                if ($this->lastHttpCode == 200) {
                    //Send headers to force the download
                    $this->sendHeaders($conn);
                } else if ($this->lastHttpCode == 401) {
                    $this->loginAndDownload();
                } else {
                    $this->redirect();
                }
            }
        } else if ($this->lastHttpCode == 401) {
            $this->loginAndDownload();
        } else {
            $this->redirect();
        }
    }

    /**
     * Init curl
     * @return resource
     */
    private function initCurl()
    {
        //Get Pest connection and set some curl options
        $conn = ApiPortalHelper::getConnection();
        $conn->curl_opts[CURLOPT_FOLLOWLOCATION] = false;
        $conn->curl_opts[CURLOPT_RETURNTRANSFER] = true;

        return $conn;
    }

    /**
     * Callback function for CURLOPT_WRITEFUNCTION
     * Write to buffer
     * Has to be public - a must
     * @param $ch
     * @param $str
     * @return int
     */
    public function curlChunk($ch, $str)
    {
        echo $str;
        return strlen($str);
    }

    /**
     * Callback for CURLOPT_HEADERFUNCTION
     * Handle the response headers and store them in @this->headers
     * @param $ch
     * @param $str
     * @return int
     */
    private function handleHeader($ch, $str)
    {
        if (preg_match('/([^:]+):\s(.+)/m', $str, $match)) {
            $this->headers[$match[1]] = trim($match[2]);
        }
        return strlen($str);
    }

    /**
     * Prepare uri for downloading swagger definition
     * Also returns main Api Manager configuration - to verify cert or/and host
     * @return object
     */
    private function getApiManagerConfig()
    {
        //Get API Manager address, port and configs
        $config = new ApiportalModelapiportal();
        $apiManagerAdd = $config->getHost();
        $apiManagerPort = $config->getPort();
        $apiManagerCrtFile = $config->getCertFile();
        $verifyCrt = $config->getVefiryCert();
        $verifyHost = $config->getVerifyHost();

        // prepare return statement for Api Manager config
        $apiManagerConfig = new stdClass();
        $apiManagerConfig->apiManagerCrtFile = $apiManagerCrtFile;
        $apiManagerConfig->verifyCrt = $verifyCrt;
        $apiManagerConfig->verifyHost = $verifyHost;
        $apiManagerConfig->uri = 'https://'.$apiManagerAdd.':'.$apiManagerPort;

        //Return ApiManager config
        return $apiManagerConfig;
    }

    /**
     * Login the user in API Manager
     * And try to start download again
     * @throws Exception
     */
    private function loginAndDownload()
    {
        //Get username and password from the session
        $username = $this->session->get(ApiPortalSessionVariables::LOGIN_NAME);
        $password = ApiPortalHelper::getPassword();

        //try to sign in the user
        if (ApiPortalHelper::login($username, $password)) {
            $this->lastHttpCode = null;
            //start download process again
            $this->download();
        } else {
            $this->redirect();
        }
    }

    /**
     * Check if Content-Disposition header exist and add it
     * Send the other headers for forcing client to download
     * @param $conn
     */
    private function sendHeaders($conn)
    {
        //Check if header Content-Disposition exist in response headers if not DON'T continue
        $contentDispositionHeader = $conn->lastHeader('Content-Disposition');
        if ($contentDispositionHeader) {
            $jAppWeb = JApplicationWeb::getInstance();
            //Add headers for forcing the client to download the file
            $jAppWeb->setHeader('Content-Disposition', $contentDispositionHeader);
            $jAppWeb->setHeader('Content-Description', 'File Transfer');
            $jAppWeb->setHeader('Content-Type', 'application/octet-stream');
            $jAppWeb->setHeader('Expires', '0');
            $jAppWeb->setHeader('Cache-Control', 'must-revalidate');
            $jAppWeb->setHeader('Pragma', 'public');
            $jAppWeb->sendHeaders();
        }
    }

    /**
     * Send the request
     * Add some log if there are errors
     * @param $conn
     * @param $headers
     */
    private function sendRequest($conn, $headers)
    {
        try {
            $conn->get($this->path, null, $headers);
        } catch (Pest_ClientError $e) {
            error_log("Caught Pest Client Exception: " . $e->getMessage());
        } catch (Pest_Exception $e) {
            error_log("Caught Pest Exception: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Caught Exception: " . $e->getMessage());
        }

        //Get the http code from the response
        $this->lastHttpCode = $conn->last_response['meta']['http_code'];
    }

    /**
     * Redirect method in case of error during the download
     * If Http code is different than 200 or 401 it will
     * redirect to the specified uri or home page if uri not exist
     * @throws Exception
     */
    private function redirect()
    {
        //Check if uri exist
        if ($this->errorRedirect && is_string($this->errorRedirect)) {
            //Check how to add param for failure (used to display error msg)
            $param = (parse_url($this->errorRedirect, PHP_URL_QUERY) ? '&' : '?') . 'fail=true';
            //Redirect the user to the specified page
            JFactory::getApplication()->redirect($this->errorRedirect.$param);
        } else {
            //Else redirect the user to the home page
            JFactory::getApplication()->redirect('/');
        }
    }

}