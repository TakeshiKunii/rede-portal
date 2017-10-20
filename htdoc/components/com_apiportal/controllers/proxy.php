<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.user.component.controller');

/**
 * Class ApiPortalControllerProxy
 *
 * This is a proxy for API Try It functionality.
 * Used to avoid CORS problems
 */
class ApiPortalControllerProxy extends JControllerLegacy
{
    protected $conn;
    protected $path;
    protected $contentTypeHeader;
    
    const CONTENT_TYPE_MULTIPART = 'multipart/form-data';
    
    const CONTENT_TYPE_URLENCODED = 'application/x-www-form-urlencoded';

    /**
     * Check the user
     * Only authorised users allowed
     * @param string $task
     * @return true/false
     */
    public function authorise($task)
    {
        // Make sure the session is valid before proceeding with tasks
        $jInput = JFactory::getApplication()->input;
        // search for ajax param to avoid direct output
        $isAjax = $jInput->get('ajax', null, 'INT');
        if ($isAjax) {
            $redirect = $this->checkSession();
            if ($redirect) {
                $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
                header($protocol . ' 308 Login Redirect', true);
                // custom header to recognize the request
                header('X-Apiportal-Auto: redirect');
                exit();
            }
        } else {
            ApiPortalHelper::checkSession();
        }

        return true;
    }

    /**
     * Main entry for the proxy.
     * Called form SwaggerUI - Try It button
     */
    public function tryIt()
    {
        $jInput = JFactory::getApplication()->input;

	    // Check for CSRF token
	    if (!JSession::checkToken('get'))
	    {
		    http_response_code(500);
		    echo json_encode(['error' => ['message' => JText::_('JINVALID_TOKEN'), 'code' => 500]]);
		    exit();
	    }

        // get and prepare the headers form the request
        $headers = getallheaders();
        $this->contentTypeHeader = $this->getHeader($headers, 'Content-Type');

        // get the url

        $base64 = $jInput->get('base64',  null, 'STRING'); 
        $this->path = $jInput->get('path', null, 'STRING');
        $this->path = $this->validatePath($this->path);


        if (!$this->path) {
            http_response_code(404);
            echo json_encode(['error' => ['message' => 'Error. Path is not valid.', 'code' => 404]]);
            exit();
        }

        // get and validate the method
        $method = $jInput->getMethod();

        // if there are not presented stop and return error message
        if ($method == null) {
            http_response_code(404);
            echo json_encode(['error' => ['message' => 'Error. No host and method specified.', 'code' => 404]]);
            exit();
        }

        // get config params
        $config = new ApiportalModelapiportal();
        $verbosePath = $config->getCertDir() . DS . 'curl.log';

        //get the session
        $session = JFactory::getSession();
        //get the base path
        $basePath = $session->get(ApiPortalSessionVariables::PROXY_TRY_IT_BASE_PATH);
        $parsedBasePath = parse_url($basePath);
        $apiManagerHost = $parsedBasePath['host'];

        if (gethostbynamel($apiManagerHost) === false){
            http_response_code(500);
            echo json_encode(['error' => ['message' => 'Connection refused!Host name can not be resolved to a valid IP address!', 'code' => 500]]);
            exit();
        }
        // in no base path - return an error and stop
        if (empty($basePath)) {
            http_response_code(500);
            echo json_encode(['error' => ['message' => 'Error. Host basePath is not specified.', 'code' => 500]]);
            exit();
        }

        // init connection - API Manager is always https
        $this->conn = new Pest($basePath);

        // set default opts
        $this->conn->curl_opts[CURLOPT_SSL_VERIFYPEER] = false;
        $this->conn->curl_opts[CURLOPT_SSL_VERIFYHOST] = 0;
        $this->conn->curl_opts[CURLOPT_STDERR] = fopen($verbosePath, "w+");
        $this->conn->curl_opts[CURLOPT_VERBOSE] = TRUE;
        $this->conn->curl_opts[CURLOPT_CONNECTTIMEOUT] = 60;
        $this->conn->curl_opts[CURLOPT_TIMEOUT] = 60;
        $this->conn->curl_opts[CURLOPT_RETURNTRANSFER] = false;
        $this->conn->curl_opts[CURLOPT_BUFFERSIZE] = 1024;
        $this->conn->curl_opts[CURLOPT_WRITEFUNCTION] = [$this, 'curlChunk'];

        // There is an error in Gateway instance if we don't pass this header
        // Parse the base uri
        $url = parse_url(JUri::base());
        // Get the scheme
        $scheme = isset($url['scheme']) ? $url['scheme'] : 'https';
        // Get the host
        $host = isset($url['host']) ? $url['host'] : null;
        // Get the port
        $port = isset($url['port']) ? $url['port'] : null;

        // If for some reasons the host was not handled correctly return error message and log an error notification
        // After that stop execution of the rest of the code.
        if (!$host) {
            // Display error message and set proper status code.
            http_response_code(500);
            echo 'Error. Header Origin was not set correctly. The request will not be send.';
            // Log more info for debug
            error_log('Error. Header Origin was not set correctly. The request will not be send. Problem in Proxy Service. Base URI: '. JUri::base());
            exit();
        } else {
            // Assemble the origin header
            $headers['Origin'] = $scheme . '://' . $host . ($port ? ':' . $port : '');
	        // Remove the host header
	        // Curl will set it correctly
	        if (isset($headers['Host'])) {
	        	unset($headers['Host']);
	        } else if (isset($headers['host'])) {
		        unset($headers['host']);
	        }
        }

        //alterar
          $headers['Authorization'] = 'basic'.' '.$base64;
        // Executing $this->conn->get/post/put/delete may throw exceptions
        try {
            $this->doRequest($headers, $method);
        } catch (Exception $e) {
            // set headers from response
            $this->setHeaders();
            // set the http code
            http_response_code($e->getCode());

            // no need to echo the error message
            // it will be returned directly
            // we use CURLOPT_WRITEFUNCTION

            // exit the app
            exit();
        }

        // set headers from response
        $this->setHeaders();

        // exit the app
        exit();
    }

    /**
     * Send a dynamic request to the target
     * @param $headers array with headers
     * @param $httpMethod string POST, PUT GET etc
     * @return mixed
     */
    protected function doRequest($headers, $httpMethod)
    { 
        // Empty data if there are no data passed
        $data = [];
        $multipart = false;
        // according to content type header, we search data with different approaches
        if (strpos($this->contentTypeHeader, self::CONTENT_TYPE_MULTIPART) !== false) {
            
            // make sure the content type header is correct
            $headers['Content-Type'] = self::CONTENT_TYPE_MULTIPART;
            
            // If we don't have file we have to set that this is multipart data, otherwise it will be transformed
            // into URL-encoded query string
            $multipart = true;

            // check global files array and if there are items add them to $data
            foreach ($_FILES as $fileName => $fileInfo) {
                // Add file param and don't use @ because it's deprecated
                $data[$fileName] = new CURLFile($fileInfo['tmp_name'], $fileInfo['type'], $fileInfo['name']);

                // make sure that in the $_POST array there is no item like the parameters in $_FILES array
                if (isset($_POST[$fileName])) {
                    unset($_POST[$fileName]);
                }
            }

            // merge files and other post fields
            $data = array_merge($data, $_POST);
        } else if (strpos($this->contentTypeHeader, self::CONTENT_TYPE_URLENCODED) !== false) {
            // make sure the content type header is correct
            $headers['Content-Type'] = self::CONTENT_TYPE_URLENCODED;

            // if content type is this one, we get data form post array only
            $data = $_POST;
        } else {
            // in other cases the data is in the input stream
            $data = file_get_contents('php://input');
        }

        // remove headers if exist, we don't need them
        $this->removeHeaders($headers, ['X-Requested-With', 'Referer', 'DNT', 'Accept-Encoding', 'Content-Length']);

        // send the request
        $this->conn->sendRequest($this->path, $httpMethod, $headers, $data, $multipart);

    }

    /**
     * Get specific header form current ones
     * @param $headers
     * @param $header
     * @return bool | $header value
     */
    protected function getHeader($headers, $header)
    {
        if (is_array($headers)) {
            foreach ($headers as $head => $value) {
                if (strtolower($header) == strtolower($head)) {
                    return $value;
                }
            }

            return false;
        } else {
            error_log('ERROR. Try It Proxy: Prepare headers not an array.');
            return false;
        }
    }

    /**
     * Set response headers
     */
    protected function setHeaders()
    {
        // set the last http status
        $code = @$this->conn->lastStatus() ? $this->conn->lastStatus() : 500;
	    // Headers to remove from the response
	    $toRemove = ['Location' => 'location', 'Transfer-Encoding' => 'transfer-encoding'];

        http_response_code($code);

        // set other headers
        foreach ($this->conn->last_headers as $lastHeader) {
	        // Remove broken headers
	        $this->removeResponseHeaders($lastHeader, $toRemove);
            foreach ($lastHeader as $head => $value) {
                header($head . ': ' . $value);
            }
        }
    }

    /**
     * Validate the given path.
     * Allowed only necessary characters for a valid path
     * @param $path
     * @return bool
     */
    protected function validatePath($path)
    {
        if ($path != null) {
            // Decode the path because some characters are encoded and regex will not match
            $path = rawurldecode($path);
            $pattern = '/^\/[\/\.\w0-9-=&\s?]+$/u';
            preg_match($pattern, $path, $matches);
            if (!empty($matches) && isset($matches[0])) {
                if (substr_count($matches[0], '?') > 1) {
                    return false;
                }

                // Parse the given path and encode every element from it
                // We can have path and query - treat them differently
                $uri = parse_url($matches[0]);
                if (!empty($uri['path'])) {
                    // Explode with '/' and encode every element
                    $uri['path'] = join('/', array_map('rawurlencode', explode('/', $uri['path'])));
                }
                if (!empty($uri['query'])) {
                    // More complicated with the query
                    // Explode with '&' and apply the callback for every element
                    // After that implode to have a valid query
                    $uri['query'] = join('&', array_map([$this, 'queryUrlEncodeCallback'], explode('&', $uri['query'])));
                }

                // Assemble the encoded part and return them
                return $uri['path'] . (isset($uri['query']) ? '?' . $uri['query'] : null);
            }
        }

        return false;
    }

    /**
     * Remove given headers from prepared headers for next the request
     * @param $headers array - the headers to be send in the request
     * @param $toBeRemoved array with the headers for removing
     */
    protected function removeHeaders(&$headers, $toBeRemoved)
    {
        foreach ($toBeRemoved as $toRemove) {
            $tempHeader = $this->getHeader($headers, $toRemove);
            if ($tempHeader) {
                unset($headers[$toRemove]);
            }
        }
    }

    /**
     * Callback function for CURLOPT_WRITEFUNCTION
     * Write to buffer
     * Has to be public - must
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
     * Check if the user session is expired.
     * Returns true if session is expired.
     * The method is similar to ApiPortalHelper::checkSession. Check it for more details.
     * @return bool|string
     * @throws Exception
     */
    private function checkSession()
    {
        $session = JFactory::getSession();
        if ($session->isActive()) {
            if (JFactory::getUser()->guest) {
                // User is not logged in
                return true;
            } else if (!ApiPortalHelper::isApiManagerUser()) {
                // Non API Manager logged in user
                return true;
            }
        } else {
            $session->destroy();
            return true;
        }

        return false;
    }

    /**
     * Callback used in path validation
     * Accept one param - an array element from array_map
     * The $element is from exploded url query with & delimiter
     * @param $element
     * @return string
     */
    private function queryUrlEncodeCallback($element)
    {
        // The $element is from exploded query
        // And we need to url encode only the parts without '=' sign
        // TODO if there are '=' signs in key or value. Thing about it.
        $queryParams = explode('=', $element);

        // If we have the two parts from the element
        // encode them
        if (isset($queryParams[0])) {
            $queryParams[0] = rawurlencode($queryParams[0]);
        }
        if (isset($queryParams[1])) {
            $queryParams[1] = rawurlencode($queryParams[1]);
        }

        // return the assembled $element with encoded parts
        return implode('=', $queryParams);
    }

	/**
	 * Remove row from an array of header
	 * Ex:['Location' => 'Value']
	 * @param array $header
	 * @param array $headerToRemove
	 *
	 * @since 7.5.3
	 */
    protected function removeResponseHeaders(&$header, &$headerToRemove)
    {
	    // Go throw unwanted headers and remove them
	    foreach ($headerToRemove as $key => $lowerName) {
		    // Two for different case sensitivity
		    unset($header[$key]);
		    unset($header[$lowerName]);
		    // It is perfectly fine to do it like this - no isset() is needed
        }
    }
}
