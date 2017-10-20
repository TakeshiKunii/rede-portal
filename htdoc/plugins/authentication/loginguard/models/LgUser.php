<?php
/**
 * @package   LoginGuard
 * @copyright Copyright (c)2017 Joal Technology Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Class LgUser
 * Lock by User Model
 */
class LgUser implements LgModelInterface
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Lock the user
     * A timestamp is save
     * @param $userId
     * @return bool
     */
    public function lock($userId)
    {
        $query = $this->db->getQuery()
            ->update('#__loginguard_user')
            ->set('lock_time=' . time())
            ->where('jm_user_id=' . $this->db->quote($userId));
        $this->db->setQuery($query);
        try {
            $this->db->execute();
            return true;
        } catch (Exception $e) {
            error_log('Problem locking user. User ID: ' . $userId . ' Message: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param int $userId
     * @throws Exception
     * @return null
     */
    public function getWrongLoginAttempts($userId)
    {
        $query = $this->db->getQuery(true)
            ->select('wrong_login_attempts')
            ->from('#__loginguard_user')
            ->where('jm_user_id=' . $this->db->quote($userId));

        $this->db->setQuery($query);

        try {
            return $this->db->loadResult();
        } catch (Exception $e) {
            error_log('Problem Fetching user\'s wrong login attempts by ID. User ID: ' . $userId . ' Message: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Increment user wrong login attempts
     * @param integer $wrongLoginAttempts
     * @param integer $userId
     * @return boolean
     */
    public function updateUserLoginAttempts($wrongLoginAttempts, $userId)
    {
        if ($wrongLoginAttempts <= 1000) {
            $query = $this->db->getQuery()
                ->update('#__loginguard_user')
                ->set('wrong_login_attempts=' . $this->db->quote($wrongLoginAttempts))
                ->set('last_wrong_login_timestamp=' . $this->db->quote(time()))
                ->where('jm_user_id=' . $this->db->quote($userId));
            $this->db->setQuery($query);
            try {
                $this->db->execute();
                return true;
            } catch (Exception $e) {
                error_log('Problem updating user\'s login attempts. User ID: ' . $userId . ' Message: ' . $e->getMessage());
                return false;
            }
        }

        return true;
    }
    
    /**
     * Insert first user wrong login attempts
     * @param integer $userId
     * @return boolean
     */
    public function insertUserLoginAttempts($userId)
    {
        $query = $this->db->getQuery()
            ->insert($this->db->quoteName('#__loginguard_user'))
            ->columns($this->db->quoteName(['jm_user_id', 'wrong_login_attempts', 'last_wrong_login_timestamp']))
            ->values(implode(',', [$userId, 1, time()]));
        $this->db->setQuery($query);
        try {
            $this->db->execute();
            return true;
        } catch (Exception $e) {
            error_log('Problem inserting user\'s login attempts. User ID: ' . $userId . ' Message: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset the count of the wrong login attempts of the user
     * Could be used also for unlock
     * 
     * @param integer $userId
     * @return boolean
     */
    public function resetWrongLoginAttempts($userId)
    {
        $wrongLoginAttempts = 0;
        $query = $this->db->getQuery()
            ->update('#__loginguard_user')
            ->set('wrong_login_attempts=' . $this->db->quote($wrongLoginAttempts))
            ->set('lock_time=' . $this->db->quote(0))
            ->where('jm_user_id=' . $this->db->quote($userId));
        $this->db->setQuery($query);
        try {
            $this->db->execute();

            return true;
        } catch (Exception $e) {
            error_log('Problem resetting user\'s login attempts. User ID: ' . $userId . ' Message: ' . $e->getMessage());

            return false;
        }
    }
    
    /**
     * Unlock username
     * @param integer $userId
     */
    public function unlock($userId)
    {
        $this->resetWrongLoginAttempts($userId);
    }
    
    /**
     * @param int $userId
     * @throws Exception
     * @return mixed
     */
    public function getLastWrongLoginTimestamp($userId)
    {
        $query = $this->db->getQuery(true)
            ->select('last_wrong_login_timestamp')
            ->from('#__loginguard_user')
            ->where('jm_user_id=' . $this->db->quote($userId));

        $this->db->setQuery($query);

        try {
            return $this->db->loadResult();
        } catch (Exception $e) {
            error_log('Problem Fetching user\'s wrong login attempts by ID. User ID: ' . $userId . ' Message: ' . $e->getMessage());
            return false;
        }
    }
}