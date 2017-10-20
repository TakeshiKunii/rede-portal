DROP TABLE IF EXISTS `#__apiportal_configuration`;
 
CREATE TABLE `#__apiportal_configuration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property` varchar(50) NOT NULL,
  `value` varchar(250) NOT NULL,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('host', '');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('port', '8075');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('verifySSL', '0');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('verifyHost', '0');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('oauthPath', '/api/oauth/token');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('oauthPort', '8089');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('allowAPIManagerAdminLogin', '0');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('ssoEntityID', '');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('isSsoOn', '0');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('ssoPath', 'sso');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('clientSdk', '0');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('publicApi', '0');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('publicApiAccountLoginName', '');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('publicApiAccountPassword', '');

DROP TABLE IF EXISTS `#__apiportal_user`;
 
CREATE TABLE `#__apiportal_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id_jm` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `email` varchar(250) NOT NULL,
  `name` varchar(250) NOT NULL,
  `loginname` varchar(250) NOT NULL,
  `termsAndCond` tinyint(1) NULL,
   PRIMARY KEY  (`id`),
   UNIQUE KEY `user_id` (`user_id`),
   INDEX `user_id_jm` (`user_id_jm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;