<?php
/**
 * Pest is a REST client for PHP.
 *
 * See http://github.com/educoder/pest for details.
 *
 * This code is licensed for use, modification, and distribution
 * under the terms of the MIT License (see http://en.wikipedia.org/wiki/MIT_License)
 */
class Pest
{
    /**
     * @var array Default CURL options
     */
    public $curl_opts = array(
        CURLOPT_RETURNTRANSFER => true, // return result instead of echoing
        CURLOPT_SSL_VERIFYPEER => false, // stop cURL from verifying the peer's certificate
        CURLOPT_FOLLOWLOCATION => false, // follow redirects, Location: headers
        CURLOPT_MAXREDIRS => 10, // but dont redirect more than 10 times
        CURLOPT_HTTPHEADER => array(),
        CURLOPT_SSLVERSION => 6
    );

    /**
     * @var string Base URL
     */
    public $base_url;

    /**
     * @var array Last response
     */
    public $last_response;

    /**
     * @var array Last request
     */
    public $last_request;

    /**
     * @var array Last headers
     */
    public $last_headers;

    /**
     * @var bool Throw exceptions on HTTP error codes
     */
    public $throw_exceptions = true;

	// If you need to use Joomla Session functionality - pass the session object to the constructor
	protected $session;

    /**
     * Class constructor
     * @param string $base_url
     * @param object|null $session
     * @throws Exception
     */
    public function __construct($base_url, $session = null)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('CURL module not available! Pest requires CURL. See http://php.net/manual/en/book.curl.php');
        }

        /*
         * Only enable CURLOPT_FOLLOWLOCATION if safe_mode and open_base_dir are
         * not in use
         */
        if (ini_get('open_basedir') == '' && strtolower(ini_get('safe_mode')) == 'off') {
            $this->curl_opts['CURLOPT_FOLLOWLOCATION'] = true;
        }

        $this->base_url = $base_url;
	    $this->session = $session;

        // The callback to handle return headers
        // Using PHP 5.2, it cannot be initialised in the static context
        $this->curl_opts[CURLOPT_HEADERFUNCTION] = array($this, 'handle_header');
    }

    /**
     * Setup authentication
     *
     * @param string $user
     * @param string $pass
     * @param string $auth  Can be 'basic' or 'digest'
     */
    public function setupAuth($user, $pass, $auth = 'basic')
    {
        $this->curl_opts[CURLOPT_HTTPAUTH] = constant('CURLAUTH_' . strtoupper($auth));
        $this->curl_opts[CURLOPT_USERPWD] = $user . ":" . $pass;
    }

    /**
     * Setup proxy
     * @param string $host
     * @param int $port
     * @param string $user Optional.
     * @param string $pass Optional.
     */
    public function setupProxy($host, $port, $user = NULL, $pass = NULL)
    {
        $this->curl_opts[CURLOPT_PROXYTYPE] = 'HTTP';
        $this->curl_opts[CURLOPT_PROXY] = $host;
        $this->curl_opts[CURLOPT_PROXYPORT] = $port;
        if ($user && $pass) {
            $this->curl_opts[CURLOPT_PROXYUSERPWD] = $user . ":" . $pass;
        }
    }

    /**
     * Perform HTTP GET request
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    public function get($url, $data = array(), $headers=array())
    {
        if (!empty($data)) {
            $pos = strpos($url, '?');
            if ($pos !== false) {
                $url = substr($url, 0, $pos);
            }
            $url .= '?' . http_build_query($data);
        }

        $curl_opts = $this->curl_opts;
        
        $curl_opts[CURLOPT_HTTPHEADER] = $this->prepHeaders($headers);

        $curl = $this->prepRequest($curl_opts, $url);
        $body = $this->doRequest($curl);
        $body = $this->processBody($body);

        return $body;
    }

    /**
     * Prepare request
     *
     * @param array $opts
     * @param string $url
     * @return resource
     * @throws Pest_Curl_Init
     */
    protected function prepRequest($opts, $url)
    {
        if (strncmp($url, $this->base_url, strlen($this->base_url)) != 0) {
            $url = rtrim($this->base_url, '/') . '/' . ltrim($url, '/');
        }

        $curl = curl_init($url);
        if ($curl === false) {
            throw new Pest_Curl_Init($this->processError(curl_error($curl), 'curl'));
        }

        foreach ($opts as $opt => $val)
            curl_setopt($curl, $opt, $val);

        $this->last_request = array(
            'url' => $url
        );

        if (isset($opts[CURLOPT_CUSTOMREQUEST]))
            $this->last_request['method'] = $opts[CURLOPT_CUSTOMREQUEST];
        else
            $this->last_request['method'] = 'GET';

        if (isset($opts[CURLOPT_POSTFIELDS]))
            $this->last_request['data'] = $opts[CURLOPT_POSTFIELDS];

        return $curl;
    }
    
    /**
     * Determines if a given array is numerically indexed or not
     *
     * @param array $array
     * @return boolean
     */
    protected function _isNumericallyIndexedArray($array)
    {
        return !(bool)count(array_filter(array_keys($array), 'is_string'));
    }
    
    /**
     * Flatten headers from an associative array to a numerically indexed array of "Name: Value"
     * style entries like CURLOPT_HTTPHEADER expects. Numerically indexed arrays are not modified.
     *
     * @param array $headers
     * @return array
     */
    protected function prepHeaders($headers)
    {
	    $flattened = [];

	    // Check if the request requires a mandatory SSO Header
	    // Two type of passing $headers argument and we take care of it
	    if (is_object($this->session) && true === (bool)$this->session->get(ApiPortalSessionVariables::IS_SSO_ON)) {
		    $headers[] = $this->session->get(ApiPortalSessionVariables::SSO_HEADER_NAME).': '.$this->session->get(ApiPortalSessionVariables::SSO_HEADER_VALUE);
		    $flattened[$this->session->get(ApiPortalSessionVariables::SSO_HEADER_NAME)] = $this->session->get(ApiPortalSessionVariables::SSO_HEADER_VALUE);
	    }

        if ($this->_isNumericallyIndexedArray($headers)) {
            $headers[] = 'IsApiPortalRequest: true';
            return $headers;
        }

        foreach ($headers as $name => $value) {
             $flattened[] = $name . ': ' . $value;
        }

        $flattened['IsApiPortalRequest'] = true;
        return $flattened;
    }

    /**
     * Process error
     * @param string $body
     * @return string
     */
    protected function processError($body)
    {
        // Override this in classes that extend Pest.
        // The body of every erroneous (non-2xx/3xx) GET/POST/PUT/DELETE
        // response goes through here prior to being used as the 'message'
        // of the resulting Pest_Exception
        return $body;
    }

    /**
     * Do CURL request
     * @param $curl
     * @param bool $return
     * @return bool
     * @throws Pest_BadRequest
     * @throws Pest_ClientError
     * @throws Pest_Conflict
     * @throws Pest_Curl_Exec
     * @throws Pest_Curl_Meta
     * @throws Pest_Forbidden
     * @throws Pest_Gone
     * @throws Pest_InvalidRecord
     * @throws Pest_MethodNotAllowed
     * @throws Pest_NotFound
     * @throws Pest_ServerError
     * @throws Pest_Unauthorized
     * @throws Pest_UnknownResponse
     */
    private function doRequest($curl, $return = true)
    {
        $this->last_headers = array();
        $this->last_response = array();

        // curl_error() needs to be tested right after function failure
        $this->last_response["body"] = curl_exec($curl);
        if ($this->last_response["body"] === false && $this->throw_exceptions) {
            throw new Pest_Curl_Exec(curl_error($curl));
        }

        $this->last_response["meta"] = curl_getinfo($curl);
        if ($this->last_response["meta"] === false && $this->throw_exceptions) {
            throw new Pest_Curl_Meta(curl_error($curl));
        }

        curl_close($curl);

        $this->checkLastResponseForError();

        if ($return === true) {
            return $this->last_response["body"];
        }

        return true;
    }

    /**
     * Do Curl request with whatever method you want
     * @param $path string - the url
     * @param $httpMethod string GET, POST etc
     * @param $headers array
     * @param $data mixed the body data
     * @param $multipart boolean is multipart/form-data or not
     * @return mixed
     * @throws Pest_Curl_Exec
     * @throws Pest_Curl_Init
     * @throws Pest_Curl_Meta
     */
    public function sendRequest($path, $httpMethod, $headers, $data = null, $multipart = false)
    {
        // Send the request with data (if exist)
        if (!empty($data)) {

            // Prepare the body data
            $this->curl_opts[CURLOPT_POSTFIELDS] = $this->prepData($data, $multipart);
        }
        if (!is_array($data)) {
            // Add header
            $headers['Content-Length'] = strlen($data);
        }
        // Prepare the headers
        $this->curl_opts[CURLOPT_HTTPHEADER] = $this->prepHeaders($headers);
        // Set the HTTP method
        $this->curl_opts[CURLOPT_CUSTOMREQUEST] = strtoupper($httpMethod);

        //Prepare the request and send it.
        $curl = $this->prepRequest($this->curl_opts, $path);
        $this->doRequest($curl, false);
    }

    /**
     * Check last response for error
     *
     * @throws Pest_Conflict
     * @throws Pest_Gone
     * @throws Pest_Unauthorized
     * @throws Pest_ClientError
     * @throws Pest_MethodNotAllowed
     * @throws Pest_NotFound
     * @throws Pest_BadRequest
     * @throws Pest_UnknownResponse
     * @throws Pest_InvalidRecord
     * @throws Pest_ServerError
     * @throws Pest_Forbidden
     */
    protected function checkLastResponseForError()
    {
        if (!$this->throw_exceptions)
            return;

        $meta = $this->last_response['meta'];
        $body = $this->last_response['body'];

        if ($meta === false)
            return;

        $err = null;
        switch ($meta['http_code']) {
            case 400:
                throw new Pest_BadRequest($this->processError($body));
                break;
            case 401:
                throw new Pest_Unauthorized($this->processError($body));
                break;
            case 403:
                throw new Pest_Forbidden($this->processError($body));
                break;
            case 404:
                throw new Pest_NotFound($this->processError($body));
                break;
            case 405:
                throw new Pest_MethodNotAllowed($this->processError($body));
                break;
            case 409:
                throw new Pest_Conflict($this->processError($body));
                break;
            case 410:
                throw new Pest_Gone($this->processError($body));
                break;
            case 422:
                // Unprocessable Entity -- see http://www.iana.org/assignments/http-status-codes
                // This is now commonly used (in Rails, at least) to indicate
                // a response to a request that is syntactically correct,
                // but semantically invalid (for example, when trying to
                // create a resource with some required fields missing)
                throw new Pest_InvalidRecord($this->processError($body));
                break;
            default:
                if ($meta['http_code'] >= 400 && $meta['http_code'] <= 499)
                    throw new Pest_ClientError($this->processError($body));
                elseif ($meta['http_code'] >= 500 && $meta['http_code'] <= 599)
                    throw new Pest_ServerError($this->processError($body)); elseif (!isset($meta['http_code']) || $meta['http_code'] >= 600) {
                    throw new Pest_UnknownResponse($this->processError($body));
                }
        }
    }

    /**
     * Process body
     * @param string $body
     * @return string
     */
    protected function processBody($body)
    {
        // Override this in classes that extend Pest.
        // The body of every GET/POST/PUT/DELETE response goes through
        // here prior to being returned.
        return $body;
    }

    /**
     * Perform HTTP HEAD request
     * @param string $url
     * @return string
     */
    public function head($url)
    {
        $curl_opts = $this->curl_opts;
        $curl_opts[CURLOPT_NOBODY] = true;

        $curl = $this->prepRequest($this->curl_opts, $url);
        $body = $this->doRequest($curl);

        $body = $this->processBody($body);

        return $body;
    }

    /**
     * Perform HTTP POST request
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    public function post($url, $data, $headers = array())
    {
        $data = $this->prepData($data);

        $curl_opts = $this->curl_opts;
        $curl_opts[CURLOPT_CUSTOMREQUEST] = 'POST';
        if (!is_array($data)) $headers[] = 'Content-Length: ' . strlen($data);
        $curl_opts[CURLOPT_HTTPHEADER] = $this->prepHeaders($headers);
        $curl_opts[CURLOPT_POSTFIELDS] = $data;

        $curl = $this->prepRequest($curl_opts, $url);
        $body = $this->doRequest($curl);

        $body = $this->processBody($body);

        return $body;
    }

    /**
     * Prepare data
     * @param array $data
     * @param bool $multipart - whether has to be multipart or not (if we don't have this we can't send multipart data
     * because this method will generate URL-encoded query string from the data and we don't want this. When array is
     * passed to Curl it understands the data like multipart)
     * @return array|string
     */
    public function prepData($data, $multipart = false)
    {
        if (is_array($data)) {

            foreach ($data as $item) {
                // @ for file is deprecated use CURLFile
                if ($item instanceof CURLFile) {
                    $multipart = true;
                    break;
                }
                // keep this if some code use it
                if (is_string($item) && strncmp($item, "@", 1) == 0) {
                    $multipart = true;
                    break;
                }
            }

            return ($multipart) ? $data : http_build_query($data);
        } else {
            return $data;
        }
    }

    /**
     * Perform HTTP PUT request
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    public function put($url, $data, $headers = array())
    {
        $data = $this->prepData($data);

        $curl_opts = $this->curl_opts;
        $curl_opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
        if (!is_array($data)) $headers[] = 'Content-Length: ' . strlen($data);
        $curl_opts[CURLOPT_HTTPHEADER] = $this->prepHeaders($headers);
        $curl_opts[CURLOPT_POSTFIELDS] = $data;

        $curl = $this->prepRequest($curl_opts, $url);
        $body = $this->doRequest($curl);

        $body = $this->processBody($body);

        return $body;
    }

    /**
     * Perform HTTP PATCH request
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    public function patch($url, $data, $headers = array())
    {
        $data = (is_array($data)) ? http_build_query($data) : $data;

        $curl_opts = $this->curl_opts;
        $curl_opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
        $headers[] = 'Content-Length: ' . strlen($data);
        $curl_opts[CURLOPT_HTTPHEADER] = $this->prepHeaders($headers);
        $curl_opts[CURLOPT_POSTFIELDS] = $data;

        $curl = $this->prepRequest($curl_opts, $url);
        $body = $this->doRequest($curl);

        $body = $this->processBody($body);

        return $body;
    }

    /**
     * Perform HTTP DELETE request
     *
     * @param string $url
     * @param array $headers
     * @return string
     */
    public function delete($url, $headers=array())
    {
        $curl_opts = $this->curl_opts;
        $curl_opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        $curl_opts[CURLOPT_HTTPHEADER] = $this->prepHeaders($headers);

        $curl = $this->prepRequest($curl_opts, $url);
        $body = $this->doRequest($curl);

        $body = $this->processBody($body);

        return $body;
    }

    /**
     * Get last response body
     *
     * @return string
     */
    public function lastBody()
    {
        return $this->last_response['body'];
    }

    /**
     * Get last response status
     *
     * @return int
     */
    public function lastStatus()
    {
        return $this->last_response['meta']['http_code'];
    }

    /**
     * Return the last response header (case insensitive) or NULL if not present.
     * HTTP allows empty headers (e.g. RFC 2616, Section 14.23), thus is_null()
     * and not negation or empty() should be used.
     *
     * @param string $header
     * @return string
     */
    public function lastHeader($header)
    {
        foreach ($this->last_headers as $headers) {
            foreach ($headers as $head => $body) {
                if (strtolower($head) == strtolower($header)) {
                    return $body;
                }
            }
        }

        return null;
        /*
        if (empty($this->last_headers[strtolower($header)])) {
            return NULL;
        }
        return $this->last_headers[strtolower($header)];
        */
    }

    /**
     * Handle header
     * @param $ch
     * @param $str
     * @return int
     */
    private function handle_header($ch, $str)
    {
        if (preg_match('/([^:]+):\s(.+)/m', $str, $match)) {
            $this->last_headers[][strtolower($match[1])] = trim($match[2]);
        }
        return strlen($str);
    }

    /**
     * Return array of all founded headers.
     * $headers is an array with all headers
     * $header is specific header you want to get
     * @param $headers
     * @param $header
     * @return array|bool
     */
    public function getHeaders($headers, $header)
    {
        $header = strtolower($header);

        foreach ($headers as $bodyHeaders) {
            foreach ($bodyHeaders as $head => $body) {
                if (strtolower($head) == $header) {
                    if (strpos($body, ",") !== false) {
                        $cookies = explode(",", $body);
                        foreach ($cookies as $cookie) {
                            $arrayHeaders[] = $this->parseCookieString($cookie);
                        }
                    } else {
                        $arrayHeaders[] = $this->parseCookieString($body);
                    }
                }
            }
        }

        if (isset($arrayHeaders)) {
            return $arrayHeaders;
        }

        return false;
    }

    /**
     * Parse cookie string by semicolon and return name=value string
     * @param $cookie
     * @return string
     */
    private function parseCookieString($cookie)
    {
        if (strpos($cookie, ';') !== false) {
            return substr($cookie, 0, strpos($cookie, ';'));
        } else {
            return $cookie;
        }
    }
}

class Pest_Exception extends Exception
{}
class Pest_UnknownResponse extends Pest_Exception
{}

// HTTP Errors
/* 401-499 */
class Pest_ClientError extends Pest_Exception
{}
/* 400 */
class Pest_BadRequest extends Pest_ClientError
{}
/* 401 */
class Pest_Unauthorized extends Pest_ClientError
{}
/* 403 */
class Pest_Forbidden extends Pest_ClientError
{}
/* 404 */
class Pest_NotFound extends Pest_ClientError
{}
/* 405 */
class Pest_MethodNotAllowed extends Pest_ClientError
{}
/* 409 */
class Pest_Conflict extends Pest_ClientError
{}
/* 410 */
class Pest_Gone extends Pest_ClientError
{}
/* 422 */
class Pest_InvalidRecord extends Pest_ClientError
{}
/* 500-599 */
class Pest_ServerError extends Pest_ClientError
{}

// CURL Errors
/* init */
class Pest_Curl_Init extends Pest_Exception
{}
/* meta */
class Pest_Curl_Meta extends Pest_Exception
{}
/* exec */
class Pest_Curl_Exec extends Pest_Exception
{}
