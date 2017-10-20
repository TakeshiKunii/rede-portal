<?php
/**
 * @package   LoginGuard
 * @copyright Copyright (c)2017 Joal Technology Ltd
 * @license   GNU General Public License version 3, or later
 */

interface LgModelInterface
{
    public function lock($identifierId);
    public function getWrongLoginAttempts($identifierId);
    public function updateUserLoginAttempts($wrongLoginAttempts, $identifierId);
    public function insertUserLoginAttempts($identifierId);
}