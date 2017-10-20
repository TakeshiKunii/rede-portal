<?php
namespace Update\Lib;

use Update\Update;

class UpdateFactory
{
    public static function create($currentVersion, $installedVersion)
    {
        return new Update($currentVersion, $installedVersion);
    }
}