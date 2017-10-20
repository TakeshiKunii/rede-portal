DROP TABLE IF EXISTS `#__apiportal_configuration`;
DROP TABLE IF EXISTS `#__apiportal_user`;

DELETE FROM `#__menu` 
WHERE `link` like "index.php?option=com_apiportal&view=%" and `path` not like 'home';

DELETE FROM `#__menu` 
WHERE `link` like "index.php?option=com_users&view=login%" and `path` = "sign-in" and `alias` = "sign-in";

DELETE FROM `#__menu` 
WHERE `link` like "index.php?option=com_users&view=profile%" and `path` = "profile-menu" and `alias` = "profile-menu";

DELETE FROM `#__menu` 
WHERE `link` like "index.php?option=com_users&view=profile%" and `path` = "profile-menu/profile" and `alias` = "profile";

DELETE FROM `#__menu` 
WHERE `link` like "index.php?option=com_users&view=profile%" and `path` = "profile-menu/edit-profile" and `alias` = "edit-profile";

DELETE FROM `#__menu` 
WHERE `link` like "index.php?option=com_users&task=user.logout%" and `path` = "profile-menu/sign-out" and `alias` = "sign-out";

DELETE FROM `#__menu` 
WHERE `link` like "index.php?option=com_easyblog&view=%" and `path` = "blog" and `alias` = "blog";

DELETE FROM `#__menu` 
WHERE `link` like "index.php?option=com_easydiscuss&view=%" and `path` = "help-center/discussions" and `alias` = "discussions";
