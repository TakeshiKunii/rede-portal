<?php

/**
 * Class HandleProxyService
 * Handle the Proxy service root dir (proxy/{index.php, .htaccess})
 * It copies the proxy dir from the com_apiportal package (joomla extension) into teh root dir of Joomla
 */
class HandleProxyService {
    // the proxy dir name
    private $proxyDir = 'proxy';
    // local directory for current temp dir on Joomla package install/update
    private $dir = null;
    // root dir of the project
    private $rootDir = '/opt/axway/apiportal/htdoc';

    /**
     * HandleProxyService constructor.
     * @param string $dir
     * @throws Exception if passed param in not a dir
     */
    public function __construct($dir)
    {
        // Check if it's dir else throw Exception
        // This is the dir for the current package install/update
        // It's something like JoomlaRootDir/tmp/install_{random hash}/packages/install_{another random hash}/{the content of apiportal joomla extension package}
        if (is_dir($dir)) {
            $this->dir = $dir;
        } else {
            return false;
        }

        return true;
    }

    /**
     * Set the proxy dir in the root dir
     * Override if it's an update and add it if it's an install
     * @throws Exception
     * @return boolean
     */
    public function setProxyDir()
    {
        // Check if the proxy dir present in the com_apiportal package dir
        // throw Exception if not
        if (is_dir($this->dir . '/' . $this->proxyDir)) {

            // Create the proxy dir in the Joomla root
            if (!$this->createProxyDir()) {
                throw new Exception('The proxy dir in Joomla root was not created.');
            }

            // Iterate over the files in the proxy dir to be able to manipulate with them
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->dir . '/' . $this->proxyDir)) as $file) {
                // If it's dir go to the next element
                // If we want to support dirs make recursive call here
                if ($file->isDir()) {
                    continue;
                }

                // Get the file in the root dir
                if (!is_file($file->getPathname()) || !rename($file->getPathname(), $this->rootDir .
                        '/' . $this->proxyDir . '/' . $file->getBasename())) {
                    // If coping of the file didn't succeed throw exception we don't want this
                    throw new Exception('The file' . $file->getBasename() . ' from the proxy service wasn\'t moved correctly.');
                }
            }
        } else {
            throw new Exception('Proxy dir not found.');
        }

        return true;
    }

    /**
     * Create the proxy dir if not exist
     * Proxy dir in the Joomla root dir
     */
    private function createProxyDir()
    {
        if (!is_dir($this->rootDir . '/' . $this->proxyDir)) {
            // Create the dir with permissions
            if (!mkdir($this->rootDir . '/' . $this->proxyDir, 0750)){
                return false;
            }
        }

        return true;
    }
}