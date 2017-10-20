<?php
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.modellist');

class APIPortalModelAjaxRequest extends JModelList
{
    // Parsed host and port
    private $apiHostPort = null;
    // Store last chunk
    private $chunkBuffer = null;

    /**
     * Callback function for CURLOPT_WRITEFUNCTION
     * Write to buffer
     * @param $ch
     * @param $str string the chunk
     * @return int return length of the chunk
     */
    public function curlChunk($ch, $str)
    {
        echo $str;

        // Check if we have the host and port
        if ($this->apiHostPort === null) {
            // Process the chunk
            $this->processChunk($str);
        }

        return strlen($str);
    }

    /**
     * Process chunk
     * Parse the chunk and if there is result save it in session variable
     * Set @apiHostPort and @chunkBuffer
     * @param string $chunk
     */
    private function processChunk($chunk)
    {
        // Concat chunk buffer even if it's empty
        $this->chunkBuffer .= $chunk;
        // Parse the chunk
        $basePath = $this->parseChunk($this->chunkBuffer);
        // If return is false we have to save the current chunk for further use
        if ($basePath === false) {
            $this->chunkBuffer = $chunk;
        } else if (!empty($basePath)) {
            // Else if not empty we have the host and port
            // Set this variable 'cause we need to check if we have to continue with parsing in curlChunk method
            $this->apiHostPort = $basePath;
            // Init session
            $session = JFactory::getSession();
            // Store it in the session for use in Try It proxy service
            $session->set(ApiPortalSessionVariables::PROXY_TRY_IT_BASE_PATH, $this->apiHostPort);
        }
    }

    /**
     * Parse given string (chunk) and return host and port.
     * If the match (basePath"(space or not):(space or not)"(whatever)"(comma or not)) is found
     * parse the match for valid host and port
     * if is found return it else return false to continue.
     * Return false if no match or empty param @chunk is passed.
     * @param null $chunk
     * @return false|string
     */
    private function parseChunk($chunk)
    {
        // If no chunk stop
        if (empty($chunk)) {
            return false;
        }

        // Parse the chunk for 'basePath " : "(whatever)",'
        preg_match('/basePath"[\\s]?:[\\s]?"(.*?)"[,]?/', $chunk, $matches);
        if (!empty($matches) && isset($matches[0])) {
            // Parse the matches for valid host and port
            $regex = '(?xi)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';
            preg_match("#$regex#i", $matches[0], $results);
            // If we have match (host and port) return it
            if (!empty($results) && isset($results[0])) {
                return $results[0];
            } else {
                // If no valid host and port is found
                return false;
            }
        } else {
            // If no matches return null
            return false;
        }
    }
}