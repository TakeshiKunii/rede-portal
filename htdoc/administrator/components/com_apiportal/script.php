<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.installer.installer');
jimport('joomla.installer.helper');
jimport('joomla.log.log');

// Load the RAD layer
if (!defined('FOF_INCLUDED')) {
    require_once JPATH_LIBRARIES . '/fof/include.php';
}

define('COMPONENT_NAME', 'com_apiportal');

class com_APIPortalInstallerScript {

    // Holds the API portal component id
    const CLIENT_ID = 0;
    const LANGUAGE = "*";
    // Backup object
    private $_backupObj;
	// Current Release Version
	private $currentRelease;

    function __construct() {
        $config = new JConfig();
        JLog::addLogger(
                array(
            // Sets file name
            'text_file' => 'com_apiportal.txt',
            'text_entry_format' => '{DATETIME} {PRIORITY} {MESSAGE}',
            'text_file_path' => $config->log_path
                ),
                // Sets messages of all log levels to be sent to the file
                JLog::ALL,
                // The log category/categories which should be recorded in this file
                // In this case, it's just the one category from our extension, still
                // we need to put it inside an array
                array(COMPONENT_NAME)
        );

	    $this->currentRelease = self::getInstalledVersion();

    }

    /**
     * Method to install the component
     * @param object $parent
     * @since $version
     * @return void
     */
    function install($parent) {

    }

    /**
     * Method to uninstall the component
     * @param object $parent
     * @since version
     * @return void
     */
    function uninstall($parent) {
        // restore main menu properties
        self::restoreMainMenuProperties($parent);
    }

    /**
     * method to update the component
     * @param object $parent
     * @since version
     * @return void
     */
    function update($parent) {
        try {
            require_once 'administrator/components/com_apiportal/updates/Update.php';
            $update = new Update($this->currentRelease, $parent->get("manifest")->version);
	        JLog::add("update: Upgrade: Start upgrade from: " . $this->currentRelease . ' to '. $parent->get("manifest")->version . ' ' . __FILE__, JLog::INFO, COMPONENT_NAME);
	        $update->start(array('dir' => dirname(__FILE__) ));
        } catch (Exception $e) {
                error_log($e->getMessage());
        }
    }

    /**
     * Method to run before an install/update/uninstall method
     * @param $type
     * @param $parent
     * @return bool
     * @since version
     */
    function preflight($type, $parent)
    {
        $release = $parent->get("manifest")->version;
	    $meetPrerequisites = self::checkPrerequisites();

        if ($type === 'install' || empty($this->currentRelease)) {
            JLog::add("preflight: Install: Installing release version: " . $release . '; ' . __FILE__, JLog::INFO, COMPONENT_NAME);
            // Check Prerequisites
	        if (!$meetPrerequisites) {
                return false;
            }
        } else if ($type === 'update') {
            JLog::add('preflight: Updating ' . COMPONENT_NAME . ' to version: ' . $release . '; Current release version: ' . $this->currentRelease . '; ' . __FILE__, JLog::INFO, COMPONENT_NAME);
            // Check Prerequisites
            if (!$meetPrerequisites) {
                return false;
            }

	        // Joomla import doesn't work for some reason so do it the old way
	        include_once 'components/com_apiportal/helpers/BackupCustomFiles.php';
	        // Init backup object
	        $this->_backupObj = new BackupCustomFiles();
	        // On update backup some files
	        $this->_backupObj->backUpFiles();

	        $configuration = self::getApiConfigurationV1_0();
	        if (isset($configuration)) {
		        // Upgrade #__apiportal_configuration table
		        $isNewConfigTableCreated = self::createNewConfigTable();
		        $isConfigValuesMigrated = $isNewConfigTableCreated && self::migrateConfiguration($configuration);

		        // return result
		        if ($isConfigValuesMigrated) {
			        self::dropTable('#__apiportal_configuration_old');
		        } else {
			        self::rollBackConfigTable();
		        }
	        } else {
		        JError::raiseWarning(null, 'Could not read API Portal component configuration. Could not upgrade. ');
		        return false;
	        }
        } else if ($type === 'uninstall') {
            JLog::add("uninstall: Unstalling release version: " . $this->currentRelease . '; ' . __FILE__, JLog::INFO, COMPONENT_NAME);
        } else {
            JLog::add('preflight: Unsupported action type: ' . $type, JLog::INFO, COMPONENT_NAME);
        }
    }

    /**
     * Method to run after an install/update/uninstall method
     * @param $type
     * @param $parent

     * @since version
     */
    function postflight($type, $parent) {
        JLog::add('postflight: Starting post installation/update setup.', JLog::INFO, COMPONENT_NAME);

        if ($type === 'update') {
            //repair user tables inconsistnecies
            $this->_repairInconsistenciesInUserTables();
        }

        if ($type === 'install' || ($type === 'update' && !empty($this->currentRelease))) {
            // add site menus
            self::configureArticles();

            if (($type === 'update' && !empty($this->currentRelease))) {
                self::updateMenu($type);
            }

            if ($type == 'install') {
	            self::configMenus($type);

                // Don't change the template and theme on update
                // The client may have change them
                self::setPurityDefaultSiteTemplate();
                // Also we don't need to update Home menu and theme
                self::updateHome();
            }

            // Enable apiportal plugins
            self::enablePlugins();
			self::disablePlugins();
            $this->reorderAuthPlugin();
            self::moveOverrides();
            self::compileLessFiles();

            self::cleanupTmp();

            // Stop user registration in com_users
            $this->setUserOptions();

        } else if ($type == 'uninstall') {
            JLog::add('postflight: Uninstall...', JLog::INFO, COMPONENT_NAME);
        } else {
            JLog::add('postflight: Unsupported action type: ' . $type, JLog::INFO, COMPONENT_NAME);
        }

        if (($type == 'install' || $type == 'update') && !function_exists('mcrypt_create_iv')) {
            JLog::add(JText::_('API PORTAL LOCAL ENCRYPTION IS NOT ENABLED. Install \'mcrypt\' php extension to enable it'), JLog::WARNING, COMPONENT_NAME);
            JLog::add(JText::_('API PORTAL LOCAL ENCRYPTION IS NOT ENABLED. Install \'mcrypt\' php extension to enable it'), JLog::WARNING, 'jerror');
        }

        if ($type == 'update') {
            // The object is defined in preflight method on update
            // After update restore backup files
            $this->_backupObj->restoreFiles();
        }
    }

    static function restoreMainMenuProperties($parent) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $params = '\'{"featured_categories":[""],'
                . '"layout_type":"blog",'
                . '"num_leading_articles":"1",'
                . '"num_intro_articles":"3",'
                . '"num_columns":"3",'
                . '"num_links":"0",'
                . '"multi_column_order":"1",'
                . '"orderby_pri":"",'
                . '"orderby_sec":"front",'
                . '"order_date":"",'
                . '"show_pagination":"2",'
                . '"show_pagination_results":"1",'
                . '"show_title":"",'
                . '"link_titles":"",'
                . '"show_intro":"",'
                . '"info_block_position":"",'
                . '"show_category":"",'
                . '"link_category":"",'
                . '"show_parent_category":"",'
                . '"link_parent_category":"",'
                . '"show_author":"",'
                . '"link_author":"",'
                . '"show_create_date":"",'
                . '"show_modify_date":"",'
                . '"show_publish_date":"",'
                . '"show_item_navigation":"",'
                . '"show_vote":"",'
                . '"show_readmore":"",'
                . '"show_readmore_title":"",'
                . '"show_icons":"",'
                . '"show_print_icon":"",'
                . '"show_email_icon":"",'
                . '"show_hits":"",'
                . '"show_noauth":"",'
                . '"show_feed_link":"1",'
                . '"feed_summary":"",'
                . '"menu-anchor_title":"",'
                . '"menu-anchor_css":"",'
                . '"menu_image":"",'
                . '"menu_text":1,'
                . '"page_title":"",'
                . '"show_page_heading":1,'
                . '"page_heading":"",'
                . '"pageclass_sfx":"",'
                . '"menu-meta_description":"",'
                . '"menu-meta_keywords":"",'
                . '"robots":"",'
                . '"secure":0}\'';
        $params = preg_replace('/\n\s+|\r\n\s+|\r\s+/', '', $params);
        $fields = array(
            $db->quoteName('link') . "=" . "'index.php?option=com_content&view=featured'",
            $db->quoteName('params') . "=" . $params,
            $db->quoteName('component_id') . "=" . self::getComponentId('com_content'),
            $db->quoteName('template_style_id') . '= 0'
        );
        $condition = array(
            'home = 1',
            $db->quoteName('path') . ' = ' . $db->quote('home'),
            $db->quoteName('alias') . ' = ' . $db->quote('home'),
            $db->quoteName('menutype') . ' = ' . $db->quote('mainmenu')
        );

        $query->update($db->quoteName('#__menu'))->set($fields)->where($condition);
        $db->setQuery($query);
        $result = $db->query();
    }

    static function updateMenu ($type) {

        // Remove all Blog menu items. Will be added cleanly
        $easyBlogId = self::getMenuId('Blog', 'easyblog', 'easyblog');
        if ($easyBlogId) self::removeMenu($easyBlogId);
        $blogId = self::getMenuId('Blog', 'blog', 'blog');
        if ($blogId)  self::removeMenu($blogId);

        // Add Menu Users just before APICatalog
        $apicatalogMenuId = self::getMenuId('APIs', 'api-catalog', 'api-catalog');
        self::addMenu(COMPONENT_NAME, 'users', 'Users', 'users', 'users', "", "", 1, 1, 1, 'component', $apicatalogMenuId);

    }

    static function configMenus($type) {
        self::addMenu('com_users', 'login', 'Sign In', 'sign-in', 'sign-in'); // Sign In

        if ($type==="install") {
            // Add Menu Users just before APICatalog
            self::addMenu(COMPONENT_NAME, 'users', 'Users', 'users', 'users');
            try {
                $easyBlog2Id = self::getMenuId('EasyBlog', 'easyblog', 'easyblog');
                if ($easyBlog2Id) self::removeMenu($easyBlog2Id);
            } catch (Exception $e) {
                //
            }
        }

        self::addMenu(COMPONENT_NAME, 'apicatalog', 'APIs', 'api-catalog', 'api-catalog');
        self::addMenu(COMPONENT_NAME, 'applications', 'Applications', 'apps', 'apps');
	    self::addMenu(COMPONENT_NAME, 'monitoring', 'Monitoring', 'monitoring', 'monitoring', '', "&usage=app&ep=mainmenu"); // API Monitoring
        self::addMenu(COMPONENT_NAME, 'help', 'Help Center', 'help-center', 'help-center');

	    // Add EasyDiscuss if component is installed and enabled
        if (self::isComponentEnabled('com_easydiscuss')) {
	        $helpCenterMenuId = self::getMenuId('Help Center', 'help-center', 'help-center');
            self::addMenu('com_easydiscuss', 'index', 'Discussions', 'discussions', 'help-center/discussions', '', '', $helpCenterMenuId, 2);
            $easyDiscussDefaultMenuId = self::getMenuId("Discussions", "discussions", "discussions");
            JLog::add('Easy discuss Default Menu Id is [' . $easyDiscussDefaultMenuId . ']', JLog::INFO, COMPONENT_NAME);
            if ($easyDiscussDefaultMenuId) self::removeMenu($easyDiscussDefaultMenuId);
        }

        self::addMenu(COMPONENT_NAME, 'pricing', 'Pricing', 'pricing', 'pricing'); // Pricing

        // Add EasyBlog just before 'My Profile' if component is installed and enabled
        $myProfileMenuId = self::getMenuId('My Profile', 'profile-menu', 'profile-menu');
        if (self::isComponentEnabled('com_easyblog')) {
            self::addMenu('com_easyblog', 'latest', 'Blog', 'blog', 'blog', "", "", 1, 1, 1, 'component', $myProfileMenuId);
        }

        // Add View Profile/Edit Profile
        self::addMenu(COMPONENT_NAME, 'user', 'My Profile', 'profile-menu', 'profile-menu', '{
                "menu-anchor_title":"",
                "menu-anchor_css":"menu-profile",
                "menu_image":"images\\/' . COMPONENT_NAME . '\\/menu\\/user.svg",
                "menu_text":0,"page_title":"",
                "show_page_heading":0,
                "page_heading":"",
                "pageclass_sfx":"",
                "menu-meta_description":"",
                "menu-meta_keywords":"",
                "robots":"",
                "secure":0,
                "masthead-title":"",
                "masthead-slogan":""
            }', '&layout=view&ep=profile-menu');
        // --> 'My Profile' sub-menu

        $profileMenuId = self::getMenuId('My Profile', 'profile-menu', 'profile-menu');
        self::addMenu(COMPONENT_NAME, 'user', 'My Profile', 'profile', 'profile-menu/profile', '{
                    "menu-anchor_title":"",
                    "menu-anchor_css":"",
                    "menu_image":"",
                    "menu_text":1,
                    "page_title":"",
                    "show_page_heading":0,
                    "page_heading":"",
                    "pageclass_sfx":"",
                    "menu-meta_description":"",
                    "menu-meta_keywords":"",
                    "robots":"",
                    "secure":0,
                    "masthead-title":"",
                    "masthead-slogan":""
                }', '&layout=view&ep=profile-menu', $profileMenuId, 2);
        self::addMenu(COMPONENT_NAME, 'user', 'Edit Profile', 'edit-profile', 'profile-menu/edit-profile', '{
                    "menu-anchor_title":"",
                    "menu-anchor_css":"",
                    "menu_image":"",
                    "menu_text":1,
                    "page_title":"",
                    "show_page_heading":0,
                    "page_heading":"",
                    "pageclass_sfx":"",
                    "menu-meta_description":"",
                    "menu-meta_keywords":"",
                    "robots":"","secure":0,
                    "masthead-title":"",
                    "masthead-slogan":""
                }', '&layout=edit&ep=profile-menu', $profileMenuId, 2, 0);

        // Add Sing-out
        self::addMenu('system', 'logout', 'Sign Out', 'sign-out', 'profile-menu/sign-out', '\'{'
                . '"menu-anchor_title":"",'
                . '"menu-anchor_css":"",'
                . '"menu_image":"",'
                . '"menu_text":1,'
                . '"masthead-title":"",'
                . '"masthead-slogan":""}\'', '', $profileMenuId, 2, 1, 'url');
    }

    static function backupFile($file, $dest) {
        JLog::add('Copying file ' . $file . ' to ' . $dest, JLog::INFO, COMPONENT_NAME);
        if (!copy($file, $dest)) {
            JLog::add('File ' . $dest . ' is not backed up.', JLog::ERROR, COMPONENT_NAME);
            return false;
        }
        JLog::add('File ' . $dest . ' backed up.', JLog::INFO, COMPONENT_NAME);
        return true;
    }

    static function moveOverrides() {
        $currentDir = JPATH_ADMINISTRATOR; // administrator
        JLog::add('Moving overrides dir', JLog::INFO, COMPONENT_NAME);
        JLog::add('Current dir ' . $currentDir, JLog::INFO, COMPONENT_NAME);

        $dest = dirname($currentDir); // portal
        $source = $currentDir . '/components/' . COMPONENT_NAME . '/tmp/overrides';

        date_default_timezone_set('UTC');
        $timestamp = date("YmdHis", time());
        $backupFolder = $dest . DIRECTORY_SEPARATOR . "Backups" . DIRECTORY_SEPARATOR . $timestamp;
        mkdir($backupFolder, 0755, true);
        // copy overrides content
        foreach (
        $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
            $file = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            $parentRelative = $iterator->getSubPath();
            $filename = $iterator->getFilename();
            JLog::add($file, JLog::INFO, COMPONENT_NAME);
            if ($item->isDir() && !file_exists($file)) {
                JLog::add('Creating directory ' . $file, JLog::INFO, COMPONENT_NAME);
                mkdir($file);
            } else if (!$item->isDir()) {
                if (file_exists($file)) {
                    $destFile = $backupFolder . DIRECTORY_SEPARATOR . $parentRelative . DIRECTORY_SEPARATOR . $filename;
                    if (!file_exists($backupFolder . DIRECTORY_SEPARATOR . $parentRelative)) {
                        mkdir($backupFolder . DIRECTORY_SEPARATOR . $parentRelative, 0755, true);
                    }
                    $success = self::backupFile($file, $destFile);
                    if ($success) {
                        copy($item, $file);
                        unlink($item);
                    } else {
                        JLog::add("Failed to backup file: " . $file, JLog::ERROR, COMPONENT_NAME);
                    }
                } else {
                    copy($item, $file);
                    unlink($item);
                }
            }
        }

        // clean up
        self::preventBackupListing($backupFolder);
    }

    static public function preventBackupListing($backupFolder) {
        $content = "<html><body></body></html>";
        $fileName = "index.html";
        foreach (
        $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($backupFolder, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item
        ) {
            if ($item->isDir()) {
                file_put_contents($backupFolder . DIRECTORY_SEPARATOR . $iterator->getSubPath() . DIRECTORY_SEPARATOR . $fileName, $content);
            }
        }
        file_put_contents($backupFolder . DIRECTORY_SEPARATOR . $fileName, $content);
        file_put_contents(dirname($backupFolder) . DIRECTORY_SEPARATOR . $fileName, $content);
    }

    static public function compileLessFiles() {
        $tplname = "purity_iii";
        $tplsubname = "axway";

        define('T3_PATH', JPATH_ROOT . '/plugins/system/t3');
        define('T3_REL', 'plugins/system/t3');
        define('T3_DEV_FOLDER', JPATH_ROOT . '/t3-assets/dev');

        //define('T3_BASE_LESS_COMPILER',       'legacy.less');
        define('T3_BASE_LESS_COMPILER', 'less');

        define('T3_TEMPLATE', $tplname);
        define('T3_TEMPLATE_URL', JURI::root(true) . '/templates/' . T3_TEMPLATE);
        define('T3_TEMPLATE_PATH', JPATH_ROOT . '/templates/' . T3_TEMPLATE);
        define('T3_TEMPLATE_REL', 'templates/' . T3_TEMPLATE);

        define('T3_LOCAL_URL', T3_TEMPLATE_URL . '/' . T3_LOCAL_DIR);
        define('T3_LOCAL_PATH', T3_TEMPLATE_PATH . '/' . T3_LOCAL_DIR);
        define('T3_LOCAL_REL', T3_TEMPLATE_REL . '/' . T3_LOCAL_DIR);

        T3::import('core/less');

        $result = array();

        try {
            T3Less::compileAll($tplsubname);
            JLog::add(JText::_('LESS files has been compiled successfully.'), JLog::INFO, 'jerror');
            $result['successful'] = JText::_('T3_MSG_COMPILE_SUCCESS');
        } catch (Exception $e) {
            JLog::add(JText::_('LESS files compilation has been failed: ' . $e->__toString()), JLog::WARNING, 'jerror');
            $result['error'] = JText::sprintf('T3_MSG_COMPILE_FAILURE', $e->__toString());
        }
    }

    static function cleanupTmp() {
        $source = JPATH_ADMINISTRATOR . '/components/' . COMPONENT_NAME . '/tmp';
        if (file_exists($source) && is_dir($source)) {
            foreach (
            $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item
            ) {
                if ($item->isDir()) {
                    rmdir($item);
                } else {
                    unlink($item);
                }
            }
            rmdir($source);
        }
    }

    static function getMenuId($title, $alias, $path, $component_id = null, $client_id = null, $parent_id = null, $baselink = null) {
        JLog::add('Searching for menu item with title [' . $title . '] alias [' . $alias . '] path [' . $path . '] component_id [' . $component_id . '] client_id [' . $client_id . '] parent_id [' . $parent_id . '] baselink [' . $baselink . ']', JLog::INFO, COMPONENT_NAME);
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select("id");
        $query->from("#__menu");
        $query->where("menutype = " . $db->quote('mainmenu'));
        if ($title) $query->where("title = " . $db->quote($title));
        if ($alias) $query->where("alias = " . $db->quote($alias));
        if ($path) $query->where("path = " . $db->quote($path));
        if ($component_id) $query->where("component_id = " . $db->quote($component_id));
        if ($client_id) $query->where("client_id = " . $db->quote($client_id));
        if ($parent_id) $query->where("parent_id =  " . $db->quote($parent_id));
        if ($baselink) $query->where("link like " . $db->quote($baselink."%"));
        $db->setQuery($query);
        $result = $db->loadObject();
        if (isset($result) && $result != "") {
            JLog::add('Found menu item with id ' . $result->id, JLog::INFO, COMPONENT_NAME);
        } else {
            JLog::add('Not found menu item in joomla db.', JLog::INFO, COMPONENT_NAME);
        }
        return !empty($result) && isset($result->id) ? $result->id : null;
    }

    static function addMenu($component, $viewName, $title, $alias, $path, $params = "", $urlParams = "", $parentid = 1, $level = 1, $published = 1, $type = 'component', $beforeItem=null) {
        $menutype = 'mainmenu';
        $db = JFactory::getDbo();

        if ($viewName == 'login') {
            $baselink = 'index.php?option=com_users&view=' . $viewName;
            $access = 5; //guest
        } else if ($viewName == 'profile') {
            $baselink = 'index.php?option=com_users&view='. $viewName;
            $access = 2; //registered
        } else if ($viewName == 'logout') {
            $baselink = 'index.php?option=com_users&task=user.logout';
            $access = 2; //registered
        } else if ($viewName == 'users') {
            $baselink = 'index.php?option=' . $component . '&view=' . $viewName;
            $access = 3; //special
        } else {
            $baselink = 'index.php?option=' . $component . '&view=' . $viewName;
            $access = 2; //registered
        }
        $link = $baselink . $urlParams;

        $params = empty($params)? '{
                "menu-anchor_title":"",
                "menu-anchor_css":"",
                "menu_image":"",
                "menu_text":1,
                "page_title":"",
                "show_page_heading":0,
                "page_heading":"",
                "pageclass_sfx":"",
                "menu-meta_description":"",
                "menu-meta_keywords":"",
                "robots":"",
                "secure":0,
                "masthead-title":"",
                "masthead-slogan":""
            }' : $params;

        $itemExists = self::getMenuId($title, null, null, null, self::CLIENT_ID, $parentid, null);
        if ($itemExists)  {
            JLog::add('AddMenu: Item exists: ' . $itemExists . '; ' . $title, JLog::INFO, COMPONENT_NAME);
            $fields = array(
                $db->quoteName('level') . "=" . $db->quote($level),
                $db->quoteName('parent_id') . "=" . $db->quote($parentid),
                $db->quoteName('menutype') . "=" . $db->quote('mainmenu'),
                $db->quoteName('title') . "=" . $db->quote($title),
                $db->quoteName('alias') . "=" . $db->quote($alias),
                $db->quoteName('note') . "=" . $db->quote(''),
                $db->quoteName('path') . "=" . $db->quote($path),

                $db->quoteName('link') . "=" . $db->quote($link),
                $db->quoteName('access') . "=" . $db->quote($access),

                $db->quoteName('type') . "=" . $db->quote($type),
                $db->quoteName('published') . "=" . $db->quote($published),
                $db->quoteName('component_id') . "=" . $db->quote(self::getComponentId($component)),
                $db->quoteName('checked_out') . "=" . 0,
                $db->quoteName('browserNav') . "=" . 0,

                $db->quoteName('img') . "=" . $db->quote(''),
                $db->quoteName('template_style_id') . "=" . 0,

                // clean up newlines and whitespaces
                $db->quoteName('params') . "=" . $db->quote(preg_replace('/\n\s+|\r\n\s+|\r\s+/', '', $params)),
                $db->quoteName('home') . "=" . 0,
                $db->quoteName('language') . "=" . $db->quote(self::LANGUAGE),
                $db->quoteName('client_id') . '= '. $db->quote(self::CLIENT_ID)
            );
            $condition = array(
                $db->quoteName('id') . ' = ' . $db->quote($itemExists)
            );

            $query = $db->getQuery(true);
            $query->update($db->quoteName('#__menu'))->set($fields)->where($condition);
            $db->setQuery($query);
            try {
                $result = $db->query();
                JLog::add('Update item [' . $title . ']: ' . ($result === true ? "success" : "error"), JLog::INFO, COMPONENT_NAME);
            } catch (Exception $e) {
                JLog::add('Update item [' . $title . ']: ' . $e->getMessage(), JLog::ERROR, COMPONENT_NAME);
            }
        } else {

            $query = $db->getQuery(true);
            $query->select("max(lft) AS max_lft, max(rgt) AS max_rgt");
            $query->from("#__menu");
            if (empty($beforeItem)) {
                $query->where("menutype = " . $db->quote($menutype));
            } else {
                $query->where("id = " . $db->quote($beforeItem));
            }
            $db->setQuery($query);
            $result = $db->loadObject();

            $query = $db->getQuery(true);
            $query->select("id, lft, rgt, level");
            $query->from("#__menu");
            $query->where("rgt = " . $db->quote($result->max_rgt));
            $db->setQuery($query);
            $result = $db->loadObject();

            // Create space in the tree at the new location for the new node in left ids
            $query = $db->getQuery(true);
            $query->update("#__menu");
            $query->set("lft = lft + 2");
            $query->set("rgt = rgt + 2");
            $query->where("lft " . (empty($beforeItem)?">":">=") . $result->lft);
            $db->setQuery($query);
            $success = $db->query();

            $new_menu_item = new stdClass();
            $new_menu_item->id = 0;
            $new_menu_item->level = $level;
            $new_menu_item->parent_id = $parentid;
            $new_menu_item->menutype = 'mainmenu';
            $new_menu_item->title = $title;
            $new_menu_item->alias = $alias;
            $new_menu_item->note = '';
            $new_menu_item->path = $path;

            $new_menu_item->link = $link;
            $new_menu_item->access = $access;

            $new_menu_item->type = $type;
            $new_menu_item->published = $published;
            $new_menu_item->component_id = self::getComponentId($component);
            $new_menu_item->checked_out = 0;
            $new_menu_item->browserNav = 0;

            $new_menu_item->img = '';
            $new_menu_item->template_style_id = 0;

            $new_menu_item->params = $params;

            // clean up newlines and whitespaces
            $new_menu_item->params = preg_replace('/\n\s+|\r\n\s+|\r\s+/', '', $new_menu_item->params);
            JLog::add($new_menu_item->params, JLog::INFO, COMPONENT_NAME);
            $new_menu_item->home = 0;
            $new_menu_item->language = self::LANGUAGE;
            $new_menu_item->client_id = self::CLIENT_ID;

            $new_menu_item->lft = $result->lft + (empty($beforeItem)?2:0);
            $new_menu_item->rgt = $result->lft + (empty($beforeItem)?2:0) + 1;

            $success = $db->insertObject("#__menu", $new_menu_item, "id");
            JLog::add('Menu item [' . $title . '] inserted into DB: ' . ($success == 1 ? "success" : "error"), JLog::INFO, COMPONENT_NAME);
        }
    }

    static function enablePlugins() {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->update("#__extensions");
        $query->set("enabled = 1");
        $query->where("element = 'apiportal' OR element = 'regsesid' OR element = 'jw_allvideos' OR element = 'mainmenu'");
        $query->where("type = 'plugin'");
        $db->setQuery($query);
        $success = $db->query();
    }

	static function disablePlugins() {
		$db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->update("#__extensions");
        $query->set("enabled = 0");
        $query->where("element = 'joomlaupdate' OR element = 'extensionupdate'");
        $query->where("type = 'plugin'");
        $db->setQuery($query);
        $success = $db->query();
	}

    /**
     * Change the order of the API Portal Authentication plugin
     * Has to be after the Joomla one so our login error messages be shown
     * It changes the filed for ordering in the DB for our plugin
     */
    private function reorderAuthPlugin()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->update("#__extensions");
        $query->set("ordering = 2");
        $query->where("element = 'apiportal' AND name = 'PLG_AUTHENTICATION_APIPORTAL'");
        $query->where("type = 'plugin'");
        $db->setQuery($query);
        $db->execute();
    }

    static function configureUserManager() {
        $userManagerParams = '\'{
            "allowUserRegistration":"1",
            "new_usertype":"2",
            "guest_usergroup":"9",
            "sendpassword":"1",
            "useractivation":"1",
            "mail_to_admin":"0",
            "captcha":"",
            "frontend_userparams":"1",
            "site_language":"0",
            "change_login_name":"1",
            "reset_count":"10",
            "reset_time":"1",
            "minimum_length":"4",
            "minimum_integers":"0",
            "minimum_symbols":"0",
            "minimum_uppercase":"0",
            "save_history":"1",
            "history_limit":5,
            "mailSubjectPrefix":"",
            "mailBodySuffix":""
        }\'';
        // clean up newline characters and extra white spaces
        $userManagerParams = preg_replace('/\n\s+|\r\n\s+|\r\s+/', '', $userManagerParams);
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->update("#__extensions");
        $query->set("params = " . $userManagerParams);
        $query->where('name = ' . $db->quote('com_users'));
        $db->setQuery($query);
        $success = $db->query();
        JLog::add('User Manager configured: ' . ($success === true ? "success" : "error"), JLog::INFO, COMPONENT_NAME);
    }

    static function configureArticles() {
        JLog::add('Configuring articles...', JLog::INFO, COMPONENT_NAME);
        $articleParams = '\'{
            "article_layout":"_:default",
            "show_title":"0",
            "link_titles":"1",
            "show_intro":"1",
            "info_block_position":"0",
            "show_category":"0",
            "link_category":"1",
            "show_parent_category":"0",
            "link_parent_category":"0",
            "show_author":"0",
            "link_author":"0",
            "show_create_date":"0",
            "show_modify_date":"0",
            "show_publish_date":"0",
            "show_item_navigation":"0",
            "show_vote":"0",
            "show_readmore":"1",
            "show_readmore_title":"1",
            "readmore_limit":"100",
            "show_tags":"0",
            "show_icons":"1",
            "show_print_icon":"0",
            "show_email_icon":"0",
            "show_hits":"0",
            "show_noauth":"0",
            "urls_position":"0",
            "show_publishing_options":"1",
            "show_article_options":"1",
            "save_history":"1",
            "history_limit":10,
            "show_urls_images_frontend":"0",
            "show_urls_images_backend":"1",
            "targeta":0,
            "targetb":0,
            "targetc":0,
            "float_intro":"left",
            "float_fulltext":"left",
            "category_layout":"_:blog",
            "show_category_heading_title_text":"1",
            "show_category_title":"0",
            "show_description":"0",
            "show_description_image":"0",
            "maxLevel":"1",
            "show_empty_categories":"0",
            "show_no_articles":"1",
            "show_subcat_desc":"1",
            "show_cat_num_articles":"0",
            "show_base_description":"1",
            "maxLevelcat":"-1",
            "show_empty_categories_cat":"0",
            "show_subcat_desc_cat":"1",
            "show_cat_num_articles_cat":"1",
            "num_leading_articles":"1",
            "num_intro_articles":"4",
            "num_columns":"2",
            "num_links":"4",
            "multi_column_order":"0",
            "show_subcategory_content":"0",
            "show_pagination_limit":"1",
            "filter_field":"hide",
            "show_headings":"1",
            "list_show_date":"0",
            "date_format":"",
            "list_show_hits":"1",
            "list_show_author":"1",
            "orderby_pri":"order",
            "orderby_sec":"rdate",
            "order_date":"published",
            "show_pagination":"2",
            "show_pagination_results":"1",
            "show_feed_link":"1",
            "feed_summary":"0",
            "feed_show_readmore":"0"
        }\'';
        $articleParams = preg_replace('/\n\s+|\r\n\s+|\r\s+/', '', $articleParams);
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->update("#__extensions");
        $query->set("params = " . $articleParams);
        $query->where('name = ' . $db->quote('com_content'));
        $db->setQuery($query);
        $success = $db->query();
        JLog::add('Articles configured: ' . ($success === true ? "success" : "error"), JLog::INFO, COMPONENT_NAME);
    }

    static function setPurityDefaultSiteTemplate() {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true);
        $query->update("#__template_styles");
        $query->set("home = 0");
        $query->where("client_id = " . self::CLIENT_ID);
        $db->setQuery($query);
        $success = $db->query();

        $profileMenuId = self::getMenuId("My Profile", "profile-menu", "profile-menu");
        $profileSubMenuId = self::getMenuId("My Profile", "profile", "profile-menu/profile");
        $helpCenterParams = "";

        if (self::isComponentEnabled('com_easydiscuss')) {
            $helpCenterMenuId = self::getMenuId('Help Center', 'help-center', 'help-center');
            $helpCenterParams = '\\\\"item-' . $helpCenterMenuId . '\\\\":{\\\\"hidesub\\\\":1},';
        }

        $params = '\'{
            "tpl_article_info_datetime_format":"d M Y",
            "t3_template":"1",
            "devmode":"0",
            "themermode":"1",
            "legacy_css":"0","
            responsive":"1",
            "non_responsive_width":"970px",
            "build_rtl":"0",
            "t3-assets":"t3-assets",
            "t3-rmvlogo":"0",
            "minify":"0",
            "minify_js":"0",
            "minify_js_tool":"jsmin",
            "minify_exclude":"",
            "link_titles":"",
            "theme":"axway",
            "logotype":"image",
            "sitename":"",
            "slogan":"",
            "logoimage":"",
            "enable_logoimage_sm":"0",
            "logoimage_sm":"",
            "mainlayout":"blog",
            "mm_type":"mainmenu",
            "navigation_trigger":"hover",
            "navigation_type":"megamenu",
            "navigation_animation":"fading",
            "navigation_animation_duration":"200",
            "mm_config":"{\\\\"mainmenu-2\\\\":{'.$helpCenterParams . '\\\\"item-' . $profileMenuId . '\\\\":{\\\\"sub\\\\":{\\\\"rows\\\\":[[{\\\\"item\\\\":' . $profileSubMenuId . ',\\\\"width\\\\":12}]]}}}}",
            "navigation_collapse_enable":"1",
            "addon_offcanvas_enable":"0",
            "addon_offcanvas_effect":"off-canvas-effect-4",
            
            "snippet_close_head":"",
            "snippet_open_body":"",
            "snippet_close_body":"",
            "snippet_debug":"0"
        }\'';
        $params = preg_replace('/\n\s+|\r\n\s+|\r\s+/', '', $params);
        $fields = array(
            "home = 1",
            "params = " . $params
        );
        $query = $db->getQuery(true);
        $query->update("#__template_styles");
        $query->set($fields);
        $query->where("template = " . $db->quote('purity_iii'));
        $query->where("client_id = " . self::CLIENT_ID);
        $db->setQuery($query);
        $success = $db->query();
        JLog::add('Purity iii set as default site template: ' . ($success === true ? "success" : "error"), JLog::INFO, COMPONENT_NAME);
        JLog::add('Purity iii configured: ' . ($success === true ? "success" : "error"), JLog::INFO, COMPONENT_NAME);
    }

    static function updateHome() {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $params = '\'{
                "menu-anchor_title":"",
                "menu-anchor_css":"",
                "menu_image":"components\\/' . COMPONENT_NAME . '\\/assets\\/img\\/menu\\/axway-logo-top.svg",
                "menu_text":0,
                "page_title":"",
                "show_page_heading":1,
                "page_heading":"",
                "pageclass_sfx":"",
                "menu-meta_description":"",
                "menu-meta_keywords":"",
                "robots":"","secure":0,
                "masthead-title":"",
                "masthead-slogan":""
            }\'';
        $params = preg_replace('/\n\s+|\r\n\s+|\r\s+/', '', $params);
        $fields = array(
            $db->quoteName('link') . "=" . "'index.php?option=" . COMPONENT_NAME . "&view=home'",
            $db->quoteName('params') . "=" . $params,
            $db->quoteName('component_id') . "=" . self::getComponentId(),
            $db->quoteName('template_style_id') . '=' . self::getPurityTemplateId()
        );
        $condition = array(
            'home = 1'
        );

        $query->update($db->quoteName('#__menu'))->set($fields)->where($condition);
        $db->setQuery($query);
        $result = $db->query();
        JLog::add('Home menu configured: ' . ($result === true ? "success" : "error"), JLog::INFO, COMPONENT_NAME);
    }

    static function getComponentId($component = COMPONENT_NAME) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select("extension_id");
        $query->from("#__extensions");
        $query->where("type = " . $db->quote('component'));
        $query->where("element = " . $db->quote($component));
        $db->setQuery($query);
        $_extension = $db->loadObject();
        if (isset($_extension->extension_id)) {
            JLog::add('Found component: ' . $component . ' with id ' . $_extension->extension_id, JLog::INFO, COMPONENT_NAME);
            return $_extension->extension_id;
        } else {
            JLog::add('Not found component: ' . $component . ' in joomla DB.', JLog::ERROR, COMPONENT_NAME);
            return 0;
        }
    }

    static function getInstalledVersion() {
        $db = JFactory::getDbo();
        $db->setQuery('SELECT manifest_cache FROM #__extensions WHERE name = ' . $db->quote(COMPONENT_NAME));
        $params = json_decode($db->loadResult(), true);

        // Add the new variable(s) to the existing one(s)
	    if (is_array($params)) {
		    foreach ($params as $name => $value) {
			    if ($name == "version") {
				    return $value;
			    }
		    }
	    }
        return false;
    }

    static function getApiConfigurationV1_0() {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select("*");
        $query->from("#__apiportal_configuration");
        $db->setQuery($query);
        $configuration = $db->loadObjectList();
        return $configuration;
    }

    static function upgradeApiPortalUsersTable($configuration) {
        $db = JFactory::getDbo();
        $config = JFactory::getConfig();
        $databaseName=$config->get('db');
        // Have to get db prefix in this case because the joomla parser doesn't do the trick itself
        $dbPrefix = $config->get('dbprefix');
        $query = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = " . $db->quote($databaseName) . " AND TABLE_NAME = '" . $dbPrefix . "apiportal_user' AND COLUMN_NAME = " . $db->quote('loginname');
        $db->setQuery($query);
        $result = $db->execute();
        // If we don't have loginname - add it
        if ($result->num_rows == 0) {
            $alterTableQuery = "ALTER TABLE " . $db->quoteName('#__apiportal_user') . " ADD loginname VARCHAR(250)";
            $db->setQuery($alterTableQuery);
            if ($db->execute()) {
                $updateColumn = "UPDATE " . $db->quoteName('#__apiportal_user') . " set loginname=email where loginname is NULL";
                $db->setQuery($updateColumn);

                // Make column NOT NULL
                $alterTableQuery2 = "ALTER TABLE " . $db->quoteName('#__apiportal_user') . " MODIFY loginname VARCHAR(250) NOT NULL";
                $db->setQuery($alterTableQuery2);
                if (!$db->execute()) {
                    return false;
                }
            }
        }
        return true;
    }

    static function createNewConfigTable() {
        $db = JFactory::getDbo();
        $query = "RENAME TABLE #__apiportal_configuration TO #__apiportal_configuration_old";
        $db->setQuery($query);
        $result = $db->execute();
        if ($result) {
            $createTableQuery = "CREATE TABLE `#__apiportal_configuration` ("
                    . "`id` int(11) NOT NULL AUTO_INCREMENT,"
                    . "`property` varchar(50) NOT NULL,"
                    . "`value` varchar(250) NOT NULL,"
                    . "PRIMARY KEY  (`id`)"
                    . ") ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;";
            $db->setQuery($createTableQuery);
            $result = $db->execute();
            if ($result) {
                return true;
            }
        }
        return false;
    }

    static function migrateConfiguration($configuration) {

        $db = JFactory::getDbo();
        foreach ($configuration as $config) {
            // add def values if value is empty
            switch ($config->property) {
                case 'host':
                    // Nothing to do
                    break;
                case 'port':
                    $config->value = !empty($config->value) ? $config->value : '8075';
                    break;
                case 'verifySSL':
                    $config->value = !empty($config->value) ? $config->value : '0';
                    break;
                case 'verifyHost':
                    $config->value = !empty($config->value) ? $config->value : '0';
                    break;
                case 'oauthPath':
                    $config->value = !empty($config->value) ? $config->value : '/api/oauth/token';
                    break;
                case 'oauthPort':
                    $config->value = !empty($config->value) ? $config->value : '8089';
                    break;
                case 'allowAPIManagerAdminLogin':
                    $config->value = !empty($config->value) ? $config->value : '0';
                    break;
	            case 'ssoEntityID':
		            $config->value = !empty($config->value) ? $config->value : '';
		            break;
	            case 'isSsoOn':
		            $config->value = !empty($config->value) ? $config->value : '0';
		            break;
	            case 'ssoPath':
		            $config->value = !empty($config->value) ? $config->value : 'sso';
		            break;
		        case 'publicApi':
		            $config->value = !empty($config->value) ? $config->value : '0';
		            break;
		        case 'publicApiAccountLoginName':
		            $config->value = !empty($config->value) ? $config->value : '';
		            break;
		        case 'publicApiAccountPassword':
		            $config->value = !empty($config->value) ? $config->value : '';
		            break;
            }

            $query = "INSERT INTO `#__apiportal_configuration` (`property`, `value`) VALUES ('" . $config->property . "', '" . $config->value . "');";
            $db->setQuery($query);
            $result = $db->execute();
            if (!$result) {
                Jerror::raiseError(null, 'Unable to upgrade API portal configuration.');
                JLog::add("Unable to insert property " . $config->property . " with value " . $config->value . ' into apiportal_configuration table.', JLog::ERROR, COMPONENT_NAME);
                return FALSE;
            }
        }

        return TRUE;
    }

    static function dropTable($table) {
        $db = JFactory::getDbo();
        $query = "DROP TABLE IF EXISTS " . $table . ";";
        $db->setQuery($query);
        $result = $db->query();
    }

    static function rollBackConfigTable() {
        $db = JFactory::getDbo();
        self::dropTable('#__apiportal_configuration');
        $query = "RENAME TABLE #__apiportal_configuration_old TO #__apiportal_configuration";
        $db->setQuery($query);
        $result = $db->query();
    }

    static function rollBackUsersTable() {
        $db = JFactory::getDbo();
        $query = "ALTER TABLE #__apiportal_user DROP COLUMN loginname;";
        $db->setQuery($query);
        $result = $db->query();
    }

    static function getPurityTemplateId() {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select("id");
        $query->from("#__template_styles");
        $query->where("template = " . $db->quote('purity_iii'));
        $query->where('home = 1'); // set as default template
        $db->setQuery($query);
        $id = $db->loadObject();
        if (isset($id)) {
            JLog::add('Found Purity iii template with id ' . $id->id . ' in the DB.', JLog::INFO, COMPONENT_NAME);
        } else {
            JLog::add('Not found Purity iii template in the DB.', JLog::ERROR, COMPONENT_NAME);
        }
        return $id->id;
    }

    static function isComponentEnabled($componentName) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select("enabled");
        $query->from("#__extensions");
        $query->where("name = " . $db->quote($componentName));
        $db->setQuery($query);
        $result = $db->loadResult();
        JLog::add('Is component [' . $componentName . '] enabled? ' . (isset($result) ? 'true' : 'false'), JLog::INFO, COMPONENT_NAME);
        return $result;
    }

    static function removeMenu($id) {
        if (isset($id) && trim($id) != '') {
            $db = JFactory::getDbo();

            $query = $db->getQuery(true);
            $query->select("title, lft, rgt")->from("#__menu")->where("id = " . $db->quote($id));
            $db->setQuery($query);
            $result = $db->loadObject();
            $title = $result->title;
            $lft = $result->lft;
            $rgt = $result->rgt;

            $query = $db->getQuery(true);
            $query->delete($db->quoteName('#__menu'))->where('id = ' . $id);
            $db->setQuery($query);
            $result = $db->query();
            if ($result) {
                JLog::add('Menu  "' . $title . '" (' . $id . ') has been removed.', JLog::INFO, COMPONENT_NAME);

                // Remove space in the tree at the new location for the removed node in left & rigtht ids
                $query = $db->getQuery(true);
                $query->update("#__menu")->set("lft = lft - 2")->set("rgt = rgt - 2")->where("lft > " . $lft);
                $db->setQuery($query);
                $success = $db->query();

            } else {
                JLog::add('Menu with "' . $title . '" (' . $id . ') has not been removed.' . $result, JLog::ERROR, COMPONENT_NAME);
            }

            return $result;
        }
    }

    static function checkPrerequisites() {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select("id");
        $query->from("#__template_styles");
        $query->where("template = " . $db->quote('purity_iii'));
        $db->setQuery($query);
        $purityIII = $db->loadObject();

        if (!isset($purityIII) || $purityIII->id == NULL) {
            Jerror::raiseError(null, 'Purity III is not installed. Please install Purity III theme before proceeding with this installation.');
            return false;
        }

        return true;
    }

    /**
     * Set com_users params in database.
     * One use for now for setting allowUserRegistration to false.
     * By default we prevent user registration in Joomla.
     */
    private function setUserOptions() {
        // Get DB instance
        $db = JFactory::getDbo();
        // Create query object.
        $query = $db->getQuery(true);
        $query->select($db->quoteName(['params', 'extension_id']));
        $query->from('#__extensions');
        $query->where("name = 'com_users' AND type = 'component' AND element = 'com_users'");
        $query->order('ordering ASC');
        // Prepare the insert query.
        $db->setQuery($query);
        // Execute the request
        $results = $db->loadObjectList();

        // If we have result
        if (!empty($results) && is_array($results)) {
            // Catch if some error occur
            try {
                // Result is in json format - Joomla specification
                $params = json_decode($results[0]->params, true);

                // Check if json is decode properly
                if ($this->validJsonDecode()) {
                    // Disable the registration option
                    $params['allowUserRegistration'] = 0;

                    // Create a new query object.
                    $query = $db->getQuery(true);
                    // Prepare the insert query.
                    $query
                        ->update('#__extensions')
                        ->set("params = '".json_encode($params)."'")
                        ->where('extension_id = '.$results[0]->extension_id);

                    // Set the query using our newly populated query object and execute it.
                    $db->setQuery($query);
                    $db->execute();
                }
            } catch (Exception $e) {
                JLog::add('Error. Can not parse params from extension table. ' . $e->getMessage(), JLog::ERROR, COMPONENT_NAME);
            }
        } else {
            JLog::add('Warning. Could not turn off the user registration in Joomla system.', JLog::WARNING, COMPONENT_NAME);
        }
    }

    /**
     * Check last json error.
     * Return false in case of error.
     * Logs into info in log file.
     * @return bool
     */
    private function validJsonDecode() {
        switch (json_last_error()) {
            case JSON_ERROR_DEPTH:
                JLog::add('Error. Can not parse params from extension table - Maximum stack depth exceeded.', JLog::ERROR, COMPONENT_NAME);
                return false;
            case JSON_ERROR_CTRL_CHAR:
                JLog::add('Error. Can not parse params from extension table - Unexpected control character found.', JLog::ERROR, COMPONENT_NAME);
                return false;
            case JSON_ERROR_SYNTAX:
                JLog::add('Error. Can not parse params from extension table - Syntax error, malformed JSON.', JLog::ERROR, COMPONENT_NAME);
                return false;
            case JSON_ERROR_NONE:
                return true;
            default:
                return false;
        }
    }
    /**
     * Remove all inconsistencies between
     * both users tables in the DB
     * Inconsistencies may occur because of the incompatibility between Joomla! 3.4.7 and API Portal 7.3.1-7.4.2.
     */
    private function _repairInconsistenciesInUserTables() {
        try {
            //first find all records in api portal users table that are missing in joomla users table and remove them
            $db = JFactory::getDbo();
            $db->setQuery('SELECT id FROM #__apiportal_user WHERE user_id_jm NOT IN (SELECT id FROM #__users )');
            $db->execute();
            $recordsToRemove = $db->loadAssocList();
            if (!empty($recordsToRemove)) {
                JLog::add('Found ' . count($recordsToRemove) . ' records to be removed from #__apiportal_user because of inconsistency with #__users', JLog::INFO, COMPONENT_NAME);
                foreach ($recordsToRemove as $record) {
                    $db->setQuery('DELETE FROM #__apiportal_user WHERE id=' . $record['id']);
                    $db->execute();
                    JLog::add('Record with id ' . $record['id'] . ' removed from #__apiportal_user', JLog::INFO, COMPONENT_NAME);
                }
            }
            // now remove all records from joomla users table that are missing in api portal users table
            $db = JFactory::getDbo();
            //select only apimanger users . They have password=#. Password maybe empty, so delete those records too if they don't exsist in #__apiportal_user
            $db->setQuery('SELECT id FROM #__users WHERE id NOT IN (SELECT user_id_jm FROM #__apiportal_user) AND (password="#" OR password="")');
            $db->execute();
            $recordsToRemove = $db->loadAssocList();

            if (!empty($recordsToRemove)) {
                JLog::add('Found ' . count($recordsToRemove) . ' records to be removed from #__users because of inconsistency with #__apiportal_user', JLog::INFO, COMPONENT_NAME);
                foreach ($recordsToRemove as $record) {
                    $userToDelete = new JUser($record['id']);
                    $userToDelete->setParam('keepInApiManager', true);// this param is set to prevent the logic for deletion from API Manager to be triggered
                    $userToDelete->save();
                    $userToDelete->delete();
                    JLog::add('Record with id ' . $record['id'] . ' removed from #__users', JLog::INFO, COMPONENT_NAME);
                }
            }
        }
        catch (Exception $e){
            error_log($e->getMessage());
        }
    }
}
