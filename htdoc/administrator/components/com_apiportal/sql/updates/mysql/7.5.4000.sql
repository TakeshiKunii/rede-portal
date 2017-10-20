INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('publicApi', '0');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('publicApiAccountLoginName', '');
INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('publicApiAccountPassword', '');

DELETE FROM `#__apiportal_configuration` WHERE property = 'clientSdk';

ALTER TABLE `#__apiportal_user` ENGINE=InnoDB;
ALTER TABLE `#__apiportal_configuration` ENGINE=InnoDB;