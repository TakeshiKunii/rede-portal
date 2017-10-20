--
-- Table structure for table `#__loginguard_user`
--

CREATE TABLE IF NOT EXISTS `#__loginguard_user` (
  `id` int(11) NOT NULL,
  `jm_user_id` int(11) NOT NULL,
  `ip` VARBINARY(16) NOT NULL,
  `lock_time` int(11) NOT NULL DEFAULT 0,
  `wrong_login_attempts` int(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for table `#__loginguard_user`
--
ALTER TABLE `#__loginguard_user`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for table `#__loginguard_user`
--
ALTER TABLE `#__loginguard_user`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Add last wrong login attempt timestamp

ALTER TABLE `#__loginguard_user` ADD `last_wrong_login_timestamp` INT NOT NULL AFTER `wrong_login_attempts`;
