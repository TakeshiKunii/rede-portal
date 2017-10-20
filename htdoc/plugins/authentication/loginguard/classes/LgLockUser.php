<?php
/**
 * @package   LoginGuard
 * @copyright Copyright (c)2017 Joal Technology Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Class LgLockUser
 * Controller for User locking
 */
class LgLockUser extends LoginGuard
{

    /**
     * LockUser constructor.
     * @param LgLoggerInterface $logger
     * @param array $params
     * @param LgModelInterface $model
     * @param null|object $identifier
     */
    public function __construct(LgLoggerInterface $logger, $params, LgModelInterface $model, $identifier)
    {
        parent::__construct($logger, $params, $model, $identifier);
    }

    /**
     * Lock the user
     * Save the time of locking

     * @return bool
     */
    public function lock()
    {
        if ($this->model->lock($this->identifier->id)) {
            $this->logger->log(JText::sprintf('PLG_AUTHENTICATION_LOGINGUARD_USER_LOCK', $this->identifier->username));
            return true;
        }

        return false;
    }
    
    /**
     * Unlock the user
     * @return bool
     */
    public function unlock()
    {
        if ($this->model->unlock($this->identifier->id)) {
            $this->logger->log(JText::sprintf('PLG_AUTHENTICATION_LOGINGUARD_USER_UNLOCK', $this->identifier->username));
            return true;
        }

        return false;
    }

    /**
     * Detect if this is wrong login attempt
     * @return bool
     */
    public function isWrongAttempt()
    {
        // Verify the password
        $match = JUserHelper::verifyPassword($this->identifier->inPassword, $this->identifier->password, $this->identifier->id);

        // If the pass didn't match continue
        if ($match === false) {
            $userWrongLoginAttempts = $this->getWrongLoginAttempts();
            // Add/Increment wrong login attempt
            if ($userWrongLoginAttempts !== null) {
                $userWrongLoginAttempts = ((int)$userWrongLoginAttempts) + 1;
                // Existing record - update
                $this->updateLoginAttempts($userWrongLoginAttempts, $this->identifier);
            } else {
                // No record - insert
                $this->insertLoginAttempts($this->identifier);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param integer $userId
     * @return mixed
     */
    public function getLastWrongLoginTimestamp($userId)
    {
        return $this->model->getLastWrongLoginTimestamp($userId);
    }
    
    /**
     * 
     * @param integer $userId
     */
    public function resetWrongLoginAttempts($userId)
    {
        $this->model->resetWrongLoginAttempts($userId);
    }
}