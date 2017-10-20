<?php
/**
 * @package   LoginGuard
 * @copyright Copyright (c)2017 Joal Technology Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Class LoginGuard
 */
abstract class LoginGuard
{
    // Config params
    protected $params;
    // Injected model
    protected $model;
    // Logger object
    protected $logger;
    // Identifier object
    protected $identifier;

    abstract protected function lock();
    abstract protected function unlock();
    abstract protected function isWrongAttempt();

    /**
     * LoginGuard constructor.
     * @param LgLoggerInterface $logger
     * @param array $params
     * @param LgModelInterface $model
     * @param object $identifier
     */
    public function __construct(LgLoggerInterface $logger, $params, LgModelInterface $model, $identifier = null)
    {
        $this->logger = $logger;
        $this->params = $params;
        $this->model = $model;
        $this->identifier = $identifier;
    }

    /**
     * Check if the attempts quota is fulfilled
     * @param $attempts
     * @return bool
     */
    public function isAttemptsQuotaExceed($attempts)
    {
        if ($attempts == (int)$this->params->get('allowed_login_attempts', 3)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user is locked
     * @param int $attempts
     * @return boolean
     */
    public function isLocked($attempts)
    {
        if ($attempts >= (int)$this->params->get('allowed_login_attempts', 3)) {
            return true;
        }

        return false;
    }

    /**
     * Returns wrong login attempts
     * @return mixed
     */
    public function getWrongLoginAttempts()
    {
        return $this->model->getWrongLoginAttempts($this->identifier->id);
    }

    /**
     * Increment wrong login attempts
     * @param $wrongLoginAttempts
     * @return mixed
     */
    public function updateLoginAttempts($wrongLoginAttempts)
    {
        return $this->model->updateUserLoginAttempts($wrongLoginAttempts, $this->identifier->id);
    }

    /**
     * Entry point for wrong login attempts
     * @return mixed
     */
    public function insertLoginAttempts()
    {
        return $this->model->insertUserLoginAttempts($this->identifier->id);
    }
}