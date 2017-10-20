<?php

/**
 * Class PreserveCustomFiles
 *
 * This class is used in API Portal component install/update script.
 * When we have an update we have to store some customizable files and restore them after that.
 * Now it uses hard coded path and files but this can be easily changed if needed for some reason.
 * Logs errors in /opt/axway/apiportal/htdoc/log/com_apiportal.txt
 */
class BackupCustomFiles
{
    // Files for backup
    // Use multi arrays because of duplications of names and paths
    private $_files = [
        ['default.php', 'components/com_apiportal/views/documentation/tmpl/'],
        ['faq.php', 'components/com_apiportal/views/help/tmpl/'],
        ['contact.php', 'components/com_apiportal/views/help/tmpl/'],
        ['default.php', 'components/com_apiportal/views/pricing/tmpl/'],
        ['default.php', 'components/com_apiportal/views/terms/tmpl/'],
        ['default.php', 'components/com_apiportal/views/started/tmpl/'],
        ['default.php', 'components/com_apiportal/views/home/tmpl/'],
        ['default.php', 'components/com_apiportal/views/help/tmpl/']
    ];
    // Root dir / Joomla dir
    private $_rootDir = '/opt/axway/apiportal/htdoc/';
    // For backup dir
    private $_backupDir;

    /**
     * PreserveCustomFiles constructor.
     */
    public function __construct()
    {
        // Get date object for backup folder unique name
        $now = new DateTime();

        // Prepare backup dir
        // Set Backup folder unique name - will be created in backUpFiles() method
        $this->_backupDir = '/tmp/apiportal/backpus/' . $now->format('Y-m-d_H-i-s_') . uniqid() . '/';
    }

    /**
     * This method start file backup process
     * @return void because it logs info
     */
    public function backUpFiles()
    {
        // Log info msg
        JLog::add("preflight: BackUp files: started", JLog::INFO, COMPONENT_NAME);

        // Store every file
        foreach ($this->_files as $file) {
            // try and catch if some error occur
            try {
                // Because of duplication of files names and paths we have a multidimensional array for files
                // So $file[0] is the filename and $file[1] is the path to the file
                // And if we don't have created path for current file we created it.
                if (!is_dir($this->_backupDir . $file[1]) && !mkdir($this->_backupDir . $file[1], 0770, true)) {
                    // If path was not created log msg
                    JLog::add("preflight: BackUp files: Directory: " . $file[1] . ' was not created.', JLog::ERROR, COMPONENT_NAME);
                } else {
                    // If we have path for backup try to copy real file to the backup directory
                    if (copy($this->_rootDir . $file[1] . $file[0], $this->_backupDir . $file[1] . $file[0])) {
                        // Backup OK
                        JLog::add("preflight: BackUp files: File: " . $file[1] . $file[0] . ' backup was successful.', JLog::INFO, COMPONENT_NAME);
                    } else {
                        // Backup error
                        JLog::add("preflight: BackUp files: File: " . $file[1] . $file[0] . ' backup was unsuccessful.', JLog::ERROR, COMPONENT_NAME);
                    }
                }
            } catch (Exception $e) {
                JLog::add('preflight: BackUp files: File: ' . $file[0] . ' failed. Error: ' . $e->getMessage(), JLog::ERROR, COMPONENT_NAME);
            }
        }
    }

    /**
     * Restore backup files
     */
    public function restoreFiles()
    {
        // Info log msg
        JLog::add("postflight: Restore files: BackUp directory: " . $this->_backupDir, JLog::INFO, COMPONENT_NAME);

        // Check for error in restore process
        $error = false;

        // Restore every file
        foreach ($this->_files as $file) {
            // Try to restore log if error occur
            try {
                // Because of duplication of files names and paths we have a multidimensional array for files
                // So $file[0] is the filename and $file[1] is the path to the file
                // Try to restore backup file to the original destination
                if (copy($this->_backupDir . $file[1] . $file[0], $this->_rootDir . $file[1] . $file[0])) {
                    // Restore ok
                    JLog::add("postflight: Restore files: File: " . $this->_backupDir . $file[1] . $file[0] . ' was successfully restored.', JLog::INFO, COMPONENT_NAME);
                } else {
                    // Restore fail
                    JLog::add("postflight: Restore files: File: " . $this->_backupDir . $file[1] . $file[0] . ' was not restored.', JLog::ERROR, COMPONENT_NAME);
                    // Mark the fail no matter how many times - one is enough
                    $error = true;
                }
            } catch (Exception $e) {
                JLog::add("postflight: Restore files: File: " . $file[0] . ' was not restored. Error: '. $e->getMessage(), JLog::ERROR, COMPONENT_NAME);
            }
        }

        // If no file restoring fail
        if (!$error) {
            // Delete temporary backup data
            $this->removeTmpData($this->_backupDir);
        }
    }

    /**
     * Delete the given directory recursively
     * @param $path string - directory to delete
     */
    private function removeTmpData($path)
    {
        // Try to delete backup direcotry
        try {
            // Open the source directory to read in files
            $i = new DirectoryIterator($path);
            foreach ($i as $f) {
                // If is file - delete
                // else continue recursively
                if ($f->isFile()) {
                    if (unlink($f->getRealPath())) {
                        // Delete ok
                        JLog::add("postflight: Delete backup files: File: " . $f->getRealPath() . ' was successfully deleted.', JLog::INFO, COMPONENT_NAME);
                    } else {
                        // Delete fail
                        JLog::add("postflight: Delete backup files: File: " . $f->getRealPath() . ' was not deleted.', JLog::ERROR, COMPONENT_NAME);
                    }
                } else if (!$f->isDot() && $f->isDir()) {
                    // continue recursively
                    $this->removeTmpData($f->getRealPath());
                }
            }
            // At the end delete the main path
            if (rmdir($path)) {
                JLog::add("postflight: Delete backup dir: " . $path . ' was successful.', JLog::INFO, COMPONENT_NAME);
            } else {
                JLog::add("postflight: Delete backup dir: " . $path . ' was not successful.', JLog::ERROR, COMPONENT_NAME);
            }
        } catch (Exception $e) {
            JLog::add("postflight: Delete backup dir: " . $path . ' was not successful. Error: '. $e->getMessage(), JLog::ERROR, COMPONENT_NAME);
        }
    }

}