<?php

// Make sure the session is valid before displaying view
ApiPortalHelper::checkSession();

require_once JPATH_COMPONENT.'/helpers/monitoring.php';
require_once JPATH_COMPONENT.'/helpers/utils.php';

$session = JFactory::getSession();

function getMetricsParam($name, $session, $isCommingFromMainMenu = false, $prefix = "") {
    $prefix = "metrics" . ($prefix!=""?"_":"") . $prefix;
    $sess_attr_name = $prefix . "_" . $name;
    // Determine whether it is a direct main menu hit or not
    $minPresentParamsCount = isset($_REQUEST['Itemid']) + isset($_REQUEST['ep']) + isset($_REQUEST['option']) + isset($_REQUEST['usage']) + isset($_REQUEST['view']);
    $directMainMenuHit = $minPresentParamsCount === count($_REQUEST) && (!isset($_REQUEST['ep']) || $_REQUEST['ep']==="mainmenu");

    // Get param from url
    $result = isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;

    // Get param from session it is required and session object is set
    if ((!isset($result) || $directMainMenuHit) && $isCommingFromMainMenu && isset($session)) {
        $result = $session->get($sess_attr_name);
    }

    // put back in session
    if ($isCommingFromMainMenu && isset($session)) {
        // Do not store params in session if it is not comming from the main menu Metrics, but commes from the
        if (isset($result)) {
            $session->set($sess_attr_name, $result);
        }
    }

    return $result;
}

$usageTypeAPP = 'app'; // Overview
$usageTypeAPI = 'api'; // Overview

$usageType1Label = JText::_('COM_APIPORTAL_MONITORING_USAGE_TYPE_APP');
$usageType2Label = JText::_('COM_APIPORTAL_MONITORING_USAGE_TYPE_API');
$infoSecondLabel = JText::_('COM_APIPORTAL_MONITORING_USAGE_TYPE_NONE');

$page1 = 'messages'; // Overview
$page2 = 'successes'; // Using the API
$page3 = 'failures'; // Parameters
$page4 = 'exceptions'; // What it Returns
$page5 = 'proctimeavg'; // What it Returns

$page1Label = JText::_('COM_APIPORTAL_MONITORING_PAGE_LABEL_MSGS');
$page2Label = JText::_('COM_APIPORTAL_MONITORING_PAGE_LABEL_SUCCESS');
$page3Label = JText::_('COM_APIPORTAL_MONITORING_PAGE_LABEL_FAIL');
$page4Label = JText::_('COM_APIPORTAL_MONITORING_PAGE_LABEL_EXCEPTIONS');
$page5Label = JText::_('COM_APIPORTAL_MONITORING_PAGE_LABEL_AVG');

$color1 = '#4572A7';
$color2 = '#3E9340';
$color3 = '#AA6401';
$color4 = '#A34646';
$color5 = '#317565';

//Entry Point
$entryPointParamName="ep";
$entryPointParamValLong="mainmenu";
$entryPointParamValShort="mm";
$entryPoint = JRequest::getString($entryPointParamName, null);  //tabs: mainmenu or not
$entryPoint = ApiPortalHelper::cleanHtml($entryPoint, false, true);
$isCommingFromMainMenu = isset($entryPoint) && (strrpos($entryPoint, $entryPointParamValLong) !== false || $entryPoint === $entryPointParamValShort );

//active view
$module = JRequest::getVar('option');  //view
$module = ApiPortalHelper::cleanHtml($module, false, true);
if (isset($module) && isset($module->module)) {
    $module = $module!==null?$module:$module->module!=null?$module->module:"com_apiportal";
}
//active view
$view = JRequest::getVar('view');  //view
$view = ApiPortalHelper::cleanHtml($view, false, true);
$view = $view !== null ? $view : "monitoring";
//active layout
$layout = JRequest::getVar('layout');  //layout
$layout = ApiPortalHelper::cleanHtml($layout, false, true);
//active usage
$usageType = getMetricsParam('usage', $session, $isCommingFromMainMenu);
$usageType = $usageType!==null && $usageType===$usageTypeAPI?$usageTypeAPI:$usageTypeAPP;

//Application ID /if there such/
$applicationId = getMetricsParam('applicationId', $session, $isCommingFromMainMenu);

// Other params
$from = getMetricsParam('sd', $session, $isCommingFromMainMenu);
$to = getMetricsParam('ed', $session, $isCommingFromMainMenu);
$clientname = getMetricsParam('cn', $session, $isCommingFromMainMenu, $usageType);
$servicename = getMetricsParam('sn', $session, $isCommingFromMainMenu, $usageType);
$methodName = getMetricsParam('md', $session);

//active tab
$tab = getMetricsParam('tab', $session, $isCommingFromMainMenu, $usageType);
$tab = $tab !== null && strlen($tab)!==0 ?$tab:$page1;

$baseLink = addURLParameters("index.php",$_REQUEST);
if ($isCommingFromMainMenu) {
    $baseLink = addURLParameters($baseLink, array($entryPointParamName => $entryPointParamValShort));
    $entryPoint = $entryPointParamValShort;
}

$usageTypelink1 = JUri::base().addURLParameters(stripURLParameter($baseLink, array("sn", "cn", "tab")), array("usage" => $usageTypeAPP), $entryPoint);
$usageTypelink2 = JUri::base().addURLParameters(stripURLParameter($baseLink, array("sn", "cn", "tab")), array("usage" => $usageTypeAPI), $entryPoint);

$pagelink1  = JUri::base().addURLParameters($baseLink, array("usage" => $usageType, "tab" => $page1, "applicationId" => $applicationId), $entryPoint);
$pagelink2  = JUri::base().addURLParameters($baseLink, array("usage" => $usageType, "tab" => $page2, "applicationId" => $applicationId), $entryPoint);
$pagelink3  = JUri::base().addURLParameters($baseLink, array("usage" => $usageType, "tab" => $page3, "applicationId" => $applicationId), $entryPoint);
$pagelink4  = JUri::base().addURLParameters($baseLink, array("usage" => $usageType, "tab" => $page4, "applicationId" => $applicationId), $entryPoint);
$pagelink5  = JUri::base().addURLParameters($baseLink, array("usage" => $usageType, "tab" => $page5, "applicationId" => $applicationId), $entryPoint);

// Metrics
$metricType = $tab!==null ? (
$tab===$page5?"processingTimeAvg":(
$tab===$page4?"exceptions":(
$tab===$page3?"failures":(
$tab===$page2?"successes":"numMessages")))) : "numMessages";

$metricLabel = $tab!==null ? (
$tab===$page5?$page5Label:(
$tab===$page4?$page4Label:(
$tab===$page3?$page3Label:(
$tab===$page2?$page2Label:$page1Label)))) : $page1Label;

$metricColor = $tab!==null ? (
$tab===$page5?$color5:(
$tab===$page4?$color4:(
$tab===$page3?$color3:(
$tab===$page2?$color2:$color1)))) : $color1;

// Graphic series
$metricsTime = null;
$metricsDisabled = false;
try {
    $metricsTime = ApiPortalMetrics::getMetricsTimeline(
        $usageType, //type
        "0", //level
        $from, //from
        $to, //to
        !empty($applicationId) ? $applicationId : $clientname, //client = null
        $servicename, //service = null
        $metricType,  //metricType=null
        $methodName
    );
} catch (RuntimeException $e) {
    $metricsDisabled = true;
}

$series = $metricsTime!==null && isset($metricsTime->series) ? $metricsTime->series : null;

if ($series !== null) {
    foreach ($series as $serie) {
        $serie->name=$metricLabel;
        $serie->type="area";
    }
}

// Show/Hide flags:
//Cleint Name column
$showClientColumn   = $isCommingFromMainMenu && $usageType===$usageTypeAPP;
//Show All items
$showAllItemsButton = (($servicename!==null || $clientname!=null && $isCommingFromMainMenu) && (strlen($servicename)!==0 || strlen($clientname)!==0 && $isCommingFromMainMenu));
//Breadcrumbs
$showBreadcrumbs    = ($isCommingFromMainMenu && (($servicename!==null || $clientname!=null) && (strlen($servicename)!==0 || strlen($clientname)!==0))) || (isset($appUsageTab) && $appUsageTab !== false);
//Tabs
$showTabs           = $isCommingFromMainMenu;

// Summary table data
$summarydata = ApiPortalMetrics::getMetricsSummary(
    $usageType, //type
    $usageType===$usageTypeAPP && $servicename!=null && strlen($servicename)!==0?"1":"0", //level
    $from, //from
    $to, //to
    !empty($applicationId) ? $applicationId : ($isCommingFromMainMenu ? null : $clientname), //client = null
    $isCommingFromMainMenu?null:$servicename
);

$applications = ApiPortalMetrics::getApplications();
$applicationsMap = array();
if ($applications !== null) {
    foreach ($applications as $application) {
        $applicationsMap[$application->id] = $application->name;
    }
}

//For tab data - above the chart
$totalNumMessagesSingle = 0;
$totalSuccessesSingle = 0;
$totalFailuresSingle = 0;
$totalExceptionsSingle = 0;
$totalProcessingTimeAvgSingle = 0;
$singleEmpty = true;

//For table data
$totalNumMessages       = 0;
$totalSuccesses         = 0;
$totalFailures          = 0;
$totalExceptions        = 0;
$totalProcessingTimeAvg = 0;
if ($summarydata !== null) {
    foreach ($summarydata as $rowEntry) {
        $rowEntry->APPNAME = isset($rowEntry->CLIENTNAME) && isset($applicationsMap[$rowEntry->CLIENTNAME]) && $applicationsMap[$rowEntry->CLIENTNAME] !== null ? $applicationsMap[$rowEntry->CLIENTNAME] : (isset($rowEntry->CLIENTNAME) ? $rowEntry->CLIENTNAME : null);

        $totalNumMessages += $rowEntry->totalNumMessages;
        $totalSuccesses += $rowEntry->totalSuccesses;
        $totalFailures += $rowEntry->totalFailures;
        $totalExceptions += $rowEntry->totalExceptions;
        $totalProcessingTimeAvg += $rowEntry->totalProcessingTimeAvg * $rowEntry->totalNumMessages;


        if ((!empty($methodName) && !empty($rowEntry->METHODNAME) && $rowEntry->METHODNAME == $methodName)
            || (!empty($clientname) && !empty($rowEntry->CLIENTNAME) && $rowEntry->CLIENTNAME == $clientname)
        ) {
            $totalNumMessagesSingle = $rowEntry->totalNumMessages;
            $totalSuccessesSingle = $rowEntry->totalSuccesses;
            $totalFailuresSingle = $rowEntry->totalFailures;
            $totalExceptionsSingle = $rowEntry->totalExceptions;
            $totalProcessingTimeAvgSingle = $rowEntry->totalProcessingTimeAvg * $rowEntry->totalNumMessages;
            $singleEmpty = false;
        }

        if (!empty($clientname) && (isset($rowEntry->CLIENTNAME) && $rowEntry->CLIENTNAME == $clientname)) {
            $infoSecondLabel = $this->escape($rowEntry->ORGANIZATIONNAME);
        }
    }

    if ($singleEmpty) {
        $totalNumMessagesSingle = $totalNumMessages;
        $totalSuccessesSingle = $totalSuccesses;
        $totalFailuresSingle = $totalFailures;
        $totalExceptionsSingle = $totalExceptions;
        $totalProcessingTimeAvgSingle = $totalProcessingTimeAvg;
    }

}

// Info label
if ($usageType == $usageTypeAPI) {
    $infoLabel = $this->escape($servicename);
    $infoSecondLabel = $this->escape($methodName);
} else {
    $infoLabel = isset($applicationsMap[$clientname]) ? $this->escape($applicationsMap[$clientname]) : null;
    if ($infoLabel===null || $infoLabel==="") {
        $infoLabel = $clientname!==null && $clientname!=="" ? $this->escape($clientname) : $this->escape($servicename);
    }
    // $infoSecondLabel is set in the above loop
}