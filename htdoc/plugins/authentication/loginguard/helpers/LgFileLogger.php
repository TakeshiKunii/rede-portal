<?php
/**
 * @package   LoginGuard
 * @copyright Copyright (c)2017 Joal Technology Ltd
 * @license   GNU General Public License version 3, or later
 */

require_once __DIR__.'/../libs/LgLoggerInterface.php';
jimport('joomla.log.log');

class LgFileLogger implements LgLoggerInterface
{
    const PLUGIN_NAME = 'plg_loginguard';

    public function __construct()
    {
        // Configure Joomla Log
        JLog::addLogger(
            array(
                // Sets file name
                'text_file' => 'plg_loginguard.logs.php'
            ),
            // Log Level
            JLog::ALL,
            // Plugin
            [self::PLUGIN_NAME]
        );
    }

	/**
	 * Log the message passed as parameter
	 * @param string $message
	 * @param int| $code = JLog::WARNING
	 *
	 * @since version
	 */
    public function log($message, $code = JLog::WARNING)
    {
        JLog::add($message, $code, self::PLUGIN_NAME);
    }
}