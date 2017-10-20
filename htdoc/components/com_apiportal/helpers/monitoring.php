<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.helper');

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once JPATH_COMPONENT.DS.'lib'.DS.'Pest.php';
require_once JPATH_COMPONENT.DS.'helpers'.DS.'apiportal.php';

abstract class ApiPortalMetrics
{
    public static function IsNullOrEmptyString($value){
        return (!isset($value) || trim($value)==='');
    }

    public static function getMetricsFields()
    {
        $path=ApiPortalHelper::getVersionedBaseFolder()."/metrics/fields";
        return ApiPortalHelper::doGet($path);
    }

    public static function getMetricsSummary($type, $level, $from = null, $to = null, $client = null, $service = null)
    {
        // Manage null values
        $type       = !self::IsNullOrEmptyString($type) ? $type : "app";
        $level      = !self::IsNullOrEmptyString($level) ? $level : "0";

        if (self::IsNullOrEmptyString($from)) {
            $from = !self::IsNullOrEmptyString($from) ? $from : new DateTime();
            $from->setTime(0, 0, 0);
            $from->setTimezone(new DateTimeZone("GMT"));
        }

        if (self::IsNullOrEmptyString($to)) {
            $to = !self::IsNullOrEmptyString($to) ? $to : new DateTime();
            $to->setTimezone(new DateTimeZone("GMT"));
        }

        $from       = ($from instanceof DateTime) ? $from->format("Y-m-d\TH:i:s\Z") : $from;
        $to         = ($to instanceof DateTime) ? $to->format("Y-m-d\TH:i:s\Z") : $to;

        // Query Data
        $data = array();
        $data["from"] = $from;
        $data["to"] = $to;
        $data['reportsubtype'] = 'trafficAll';
        if (!self::IsNullOrEmptyString($client)) { $data["client"] = $client; }
        if (!self::IsNullOrEmptyString($service)) { $data["service"] = $service; }

        $path=ApiPortalHelper::getVersionedBaseFolder()."/metrics/reports/".$type."/summary/".$level;

        return ApiPortalHelper::doGet($path, $data);
    }

    public static function getMetricsTimeline($type, $level, $from = null, $to = null, $client = null, $service = null, $metricType=null, $method=null)
    {
        // Manage null values
        $type       = !self::IsNullOrEmptyString($type) ? $type : "app";
        $level      = !self::IsNullOrEmptyString($level) ? $level : "0";
        $metricType = !self::IsNullOrEmptyString($metricType) ? $metricType : "numMessages";

        if (self::IsNullOrEmptyString($from)) {
            $from = !self::IsNullOrEmptyString($from) ? $from : new DateTime();
            $from->setTime(0, 0, 0);
            $from->setTimezone(new DateTimeZone("GMT"));
        }

        if (self::IsNullOrEmptyString($to)) {
            $to = !self::IsNullOrEmptyString($to) ? $to : new DateTime();
            $to->setTimezone(new DateTimeZone("GMT"));
        }

        $from       = ($from instanceof DateTime) ? $from->format("Y-m-d\TH:i:s\Z") : $from;
        $to         = ($to instanceof DateTime) ? $to->format("Y-m-d\TH:i:s\Z") : $to;
        // Query Data
        $data = array();
        $data["from"] = $from;
        $data["to"] = $to;
        // Add preventCache item 
        $data["request.preventCache"] = time();

        if ($method) {
            $data['method'] = $method;
        }

        if (!self::IsNullOrEmptyString($client)) { $data["client"] = $client; }
        if (!self::IsNullOrEmptyString($service)) { $data["service"] = $service; }

        $path=ApiPortalHelper::getVersionedBaseFolder()."/metrics/reports/".$type."/timeline/".$level."/".$metricType;

        return ApiPortalHelper::doGet($path, $data, false, true, true);
    }

    public static function getApplications()
    {
        // Query Data
        $data = array();
        // Add preventCache item 
        $data["request.preventCache"] = time();
        $path=ApiPortalHelper::getVersionedBaseFolder()."/applications";
        return ApiPortalHelper::doGet($path, $data);
    }
}
