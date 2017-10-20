<?php
/**
 * @package   LoginGuard
 * @copyright Copyright (c)2017 Joal Technology Ltd
 * @license   GNU General Public License version 3, or later
 */

interface LgLoggerInterface
{
    public function log($message, $code = JLog::WARNING);
}