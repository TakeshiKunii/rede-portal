<?php
defined('_JEXEC') or die('Restricted access');

abstract class PlgSystemApiPortalExtend
{
    // Would be better to just scan for files, but this is fine for now...
    static $originalNames = array(
        'JApplicationSite' => 'libraries/cms/application/site.php',
        'UsersControllerUser' => 'components/com_users/controllers/user.php'
    );

    // A little bit of runtime class-loading fun and games...
    public static function extend($baseDir) {
        foreach (PlgSystemApiPortalExtend::$originalNames as $originalClassName => $originalFileName) {
            $replacedFileName = $baseDir . '/plugins/system/apiportal/replaced/' . $originalFileName;
            $extendedFileName = $baseDir . '/plugins/system/apiportal/extended/' . $originalFileName;
            $replacedClassName = $originalClassName . 'Original';

            if (!is_dir(dirname($replacedFileName))) {
                mkdir(dirname($replacedFileName), 0755, true);
            }

            // Only do the replacement if the file doesn't exist or the original has changed
            if (!file_exists($replacedFileName) || filemtime($baseDir.'/'.$originalFileName) > filemtime($replacedFileName)) {
                $bufferFile = file_get_contents($baseDir.'/'.$originalFileName);

                // Make sure that the class can be extended
                $bufferFile = preg_replace('/(final)\s+(class)/', '$2', $bufferFile);

                // Replace original class name
                $bufferContent = str_replace($originalClassName, $replacedClassName, $bufferFile);
                file_put_contents($replacedFileName, $bufferContent);
            }

            JLoader::register($replacedClassName, $replacedFileName, true);
            JLoader::register($originalClassName, $extendedFileName, true);
        }
    }
}
