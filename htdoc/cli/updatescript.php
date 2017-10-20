<?php
ob_start();
error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);

const _JEXEC = 1;

if (!defined('JOOMLA_PATH')) {
    define('JOOMLA_PATH', dirname(__DIR__) . '/administrator');
}

// Load system defines
if (file_exists(JOOMLA_PATH . '/defines.php')) {
	require_once JOOMLA_PATH . '/defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', JOOMLA_PATH);
    require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_BASE . '/includes/helper.php';

// Get command line argument
if (isset($argv[1])) {
    $packageNameArgument = $argv[1];
}

class AutomatedUpdate extends JApplicationCli
{
    /**
     * Location of new packages.
     * It should be in one level with this file
     * @var string
     */
    private $updatePackagesPath = 'updates';

    public function doExecute()
    {
        global $packageNameArgument;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = true;
        $app = JFactory::getApplication('administrator');
		$app->execute();
		ob_clean();

/*
// Gets current installed components, plugins, templates and return their versions
        $packages = $this->packagesInfo();
        $this->out("\nCurrent package version: ");
        if (is_array($packages) && !empty($packages)) {
            foreach ($packages as $version) {
                $this->out($version);
            }
        } else {
            $this->out("\n No current packages found");
        }
*/

        if (isset($packageNameArgument)) {
            if (file_exists($this->updatePackagesPath . '/' . $packageNameArgument)) {
                $files[] = $packageNameArgument;
            } else {
                $this->out("Error. The package '" . $packageNameArgument . "' was not found.");
                error_log("Cli apiportal update script error. The package '" . $packageNameArgument . "' was not found.");
                $this->close();
            }
        } else {
            $files = $this->getPackages();
            if (count($files) < 1) {
                $this->out("\nNo update package(s) found");
                $this->close();
            }
        }

/*
        $this->out("\nYou are about to install the following package(s):\n");

        foreach ($files as $key => $pack) {
            $this->out($key+1 . '. '. basename($pack));
        }
*/

        $this->out("\nInstalling...");

		JPluginHelper::importPlugin('system', 't3');
        JPluginHelper::importPlugin('installer');
		
		foreach ($files as $file) {
            $package = null;
            $fileName = basename($file);
			
            $dispatcher = JEventDispatcher::getInstance();
            $dispatcher->trigger('onInstallerBeforeInstaller', array($this, &$package));

            // Get an installer instance.
            $installer = JInstaller::getInstance();

            // Unpack the downloaded package file.
            $this->out("\nPreparing package " . $fileName);
            $this->out("...");

            $config		= JFactory::getConfig();
            $tmpDest	= $config->get('tmp_path') . '/' . $fileName;

            // Move uploaded file.
            JFile::move($file, $tmpDest);

            $package = JInstallerHelper::unpack($tmpDest, true);

            // Install the package.
            $this->out("Installing package " . $fileName);
            $this->out("...");
            /*
             * Try to catch some exceptions but keep the if statement
             * in case of normal flow without exception but with error detected
             * from install method itself. In such a case no error will be thrown and
             * false will be return.
             */
            try {

                if (!$installer->install($package['dir'])) {
                    // There was an error installing the package.
                    $this->out('There was an error installing the package ' . $fileName);
                    $this->out('Type ' . $package['type']);
                    $result = false;
                    $msg = 'error';
                } else {
                    // Package installed successfully.
                    $this->out("Successful installation of package " . $fileName);
                    $result = true;
                    $msg = 'success';
                }
            } catch (Exception $e) {
                $this->out('Error: ' . $e->getCode() . "\n" . $e->getMessage());
                error_log('Error installing package '. $fileName .' from apiportal update script. Error: ' . $e->getCode() . "\n" . $e->getMessage());
                $result = false;
                $msg = 'error';
            }

            // This event allows a custom a post-flight:
            $dispatcher->trigger('onInstallerAfterInstaller', array($this, &$package, $installer, &$result, &$msg));

            // Cleanup the install files.
            $this->out("...");
            $this->out('Cleanup the install files');
            if (!is_file($package['packagefile'])) {
                $config = JFactory::getConfig();
                $package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
            }

            JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

            $this->out("...");
            $this->out("Done with ". $fileName);
        }

        $this->out("...");
        $this->out("All Done");
        $this->close();
    }

    /**
     * Get packages from the directory
     */
    private function getPackages()
    {
        $packages = glob(__DIR__ . '/' .$this->updatePackagesPath . '/*.zip');
        usort($packages, array($this, 'setPackagePriority'));
        return $packages;
    }

    private function setPackagePriority($a, $b)
    {
        $a = strtolower(basename($a));
        $b = strtolower(basename($b));

        if (strpos($a, 'apiportal') !== false) {
            return 1;
        }

        if (strpos($b, 'joomla_3') !== false) {
            return 1;
        }

        return 0;
    }

    /**
     * Get current packages info
     * Needs DB connection
     */
    private function packagesInfo()
    {
        $extns = array();
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select("manifest_cache, element, type, name");
        $query->from("#__extensions");
        $query->where("(element = 'com_apiportal' OR element = 't3' OR element = 'com_easyblog' OR element = 'com_easydiscuss' OR element = 'purity_iii') AND (type = 'plugin' OR type = 'component' OR type = 'template')");
        $db->setQuery($query);
        $extensions = $db->loadAssocList();
		if (is_array($extensions)) {
			foreach ($extensions as $extension) {
				$temp = json_decode($extension['manifest_cache'], true);
				$extns[] = $extension['name'] . ': v' . $temp['version'];
			}
		}

        $extns[] = 'Joomla: v' . JVERSION;

        return $extns;
    }
}

JApplicationCli::getInstance('AutomatedUpdate')->execute();
