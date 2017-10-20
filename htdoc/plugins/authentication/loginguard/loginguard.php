<?php
/**
 * @package   LoginGuard
 * @copyright Copyright (c)2017 Joal Technology Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

require_once 'libs/LgModelInterface.php';
require_once 'libs/LoginGuard.php';
require_once 'models/LgUser.php';
require_once 'classes/LgLockUser.php';
require_once 'helpers/LgFileLogger.php';

class PlgAuthenticationLoginGuard extends JPlugin
{
    // Lock object
    private $loginGuard;
	// Logger object
    private $logger;
    // Load language files
    protected $autoloadLanguage = true;
	// DB object
    public $db;
    // LoginGuard plugin type
    const LOGIN_GUARD_TYPE = 'LoginGuard';


    public function __construct($subject, $config = [])
    {
        parent::__construct($subject, $config);

        $this->logger = new LgFileLogger();
    }

    /**
     * This method should handle any authentication and report back to the subject
     *
     * @param   array $credentials Array holding the user credentials
     * @param   array $options Array of extra options
     * @param   object &$response Authentication response object
     *
     * @return  boolean
     *
     */
    public function onUserAuthenticate($credentials, $options, &$response)
    {
        $response->type = self::LOGIN_GUARD_TYPE;
        $userObj = null;

        // If the LoginGuard is active
        if ($this->params->get('lock_by_username', 1)) {

            // If no username is provided - exit with fail
            if (empty($credentials['username']) || empty($credentials['password'])) {
                $response->status = JAuthentication::STATUS_FAILURE;

                return false;
            }

            // Check for the username in DB
            $query = $this->db->getQuery(true)
                ->select('id, password, username')
                ->from('#__users')
                ->where('username=' . $this->db->quote($credentials['username']));

            $this->db->setQuery($query);
            try {
                $userObj = $this->db->loadObject();
            } catch (Exception $e) {
                error_log('Problem Fetching the user by username. User username: ' . $credentials['username'] . ' Message: ' . $e->getMessage());
                $response->status = JAuthentication::STATUS_FAILURE;

                return false;
            }

            /* No valid username - use IP address */
            if ($userObj || !is_null($userObj)) {
                /* There is such user - work with username/id */
                // LockUser identifier object requires entered password by the user
                $userObj->inPassword = $credentials['password'];
                // Create loginGuard for User
                $this->loginGuard = new LgLockUser($this->logger, $this->params, new LgUser($this->db), $userObj);


                // Get wrong attempts for the user
                $userWrongLoginAttempts = $this->loginGuard->getWrongLoginAttempts();

                // Check if the current user is locked
                $isLocked = $this->loginGuard->isLocked($userWrongLoginAttempts);

                // It will increment/create a record with the attempt if it's wrong one
                $isWrongAttempt = $this->loginGuard->isWrongAttempt();

                // If this is not a wrong login attempt and the user is locked
                if (!$isWrongAttempt && $isLocked) {
                    if ((time() - $this->loginGuard->getLastWrongLoginTimestamp($userObj->id)) > $this->params->get('lock_timeout', 1200)) {
                        // Lock timeout has passed, unlock
                        $this->loginGuard->unlock();
                        // Set to false to allow login
                        $isLocked = false;
                        $this->logger->log("The user with ID: " . $userObj->id . " has been unlocked in" . time());
                    }
                }

                // If this is not wrong attempt and the user is not Locked - reset wrong attempts
                if (!$isWrongAttempt && !$isLocked) {
                    $this->loginGuard->resetWrongLoginAttempts($userObj->id);
                }

                // The user is locked - deny the access
                if ($isLocked) {
                    // Are login attempts equal to the configured one for locking
                    if ($this->loginGuard->isAttemptsQuotaExceed($userWrongLoginAttempts)) {
                        // Locked by username
                        $this->loginGuard->lock();
                    }

                    // But pass it as success to the necessary next event
                    $response->status = JAuthentication::STATUS_SUCCESS;
                    $response->error_message = JText::sprintf('PLG_AUTHENTICATION_LOGINGUARD_LOCKED_ACCESS', $this->params->get('allowed_login_attempts', 3));

                    return false;
                }
            }
        }

        $response->status = JAuthentication::STATUS_FAILURE;
        return false;
    }

    /**
     * Executed on triggered Authorisation event
     * If it's from LoginGuard return DENIED status
     * @param object $response
     * @param array $options
     * @return object $response
     */
    public function onUserAuthorisation($response, $options)
    {
        if ($response->type == self::LOGIN_GUARD_TYPE) {
            $response->status = JAuthentication::STATUS_DENIED;
        }

        return $response;
    }

    /**
     * Executed on triggered AuthorisationFailure event
     * And if it's from LoginGuard raise proper msg.
     * The login is DENIED
     * @param array $authorisation
     */
    public function onUserAuthorisationFailure($authorisation)
    {
        if ($authorisation['type'] == self::LOGIN_GUARD_TYPE) {
            JError::raiseWarning('102002', $authorisation['error_message']);
        }
    }
}