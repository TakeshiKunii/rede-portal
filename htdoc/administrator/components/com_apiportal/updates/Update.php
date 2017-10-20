<?php

class Update
{
    protected $currentVersion;
    protected $newVersion;
    protected $map = [
        0 => '7.5.1',
        1 => '7.5.2',
        2 => '7.5.3',
        3 => '7.5.4',
    ];

    public function __construct($currentVersion, $newVersion)
    {
        $this->currentVersion = $currentVersion; // the current version
        $this->newVersion = $newVersion; // the new version
    }


    /**
     * Start the upgrade init process
     * @param array $params Description
     * @throws Exception
     * @since 7.5.3
     */
    public function start(array $params)
    {
        if (version_compare($this->newVersion, $this->currentVersion, '>')) {
            // Get the index of the installation version
            $newVersionIndex = array_search($this->newVersion, $this->map);
            // TODO But what if we don't have an update
            if ($newVersionIndex !== false) {
                $lastUpdate = $newVersionIndex;
            } else {
                end($this->map);
                $lastUpdate = key($this->map);
            }

            // Get the index of the current version
            $currentVersionIndex = array_search($this->currentVersion, $this->map);
            if ($currentVersionIndex !== false) {
	            // If we have a map for the current version increase it to the next one
	            // we don't want to trigger current version Update class
                $firstUpdate = $currentVersionIndex + 1;
            } else {
                reset($this->map);
                $firstUpdate = key($this->map);
            }
        
            foreach (range($firstUpdate, $lastUpdate) as $update) {
                $class = 'Update' . str_replace('.', '', $this->map[$update]);
                require_once 'instance/' . $class . '.php';

                $updateObj = new $class;
                $updateObj->init($params);
            }
        }
    }
}

