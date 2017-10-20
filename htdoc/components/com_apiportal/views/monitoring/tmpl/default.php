<?php
//---include details and calculations---
require JPATH_COMPONENT.'/views/monitoring/tmpl/default-include.php';


$itemId = JRequest::getString('Itemid', '');
$itemId = ApiPortalHelper::cleanHtml($itemId, false, true);

$document = JFactory::getDocument();
$document->addStyleSheet('components/com_apiportal/assets/css/daterangepicker-bs3-axw.css');
//$document->addStyleSheet('components/com_apiportal/assets/css/font-awesome.min.css');

//$document->addScript('components/com_apiportal/assets/js/jquery.min.js');
//$document->addScript('components/com_apiportal/assets/js/bootstrap.min.js');
$document->addScript('components/com_apiportal/assets/js/moment.min.js');
$document->addScript('components/com_apiportal/assets/js/daterangepicker.js');

$document->addStyleSheet('components/com_apiportal/assets/css/jquery.dataTables.css');
$document->addStyleSheet('components/com_apiportal/assets/css/dataTables.bootstrap.css');
$document->addScript('components/com_apiportal/assets/js/jquery.dataTables.min.js');
$document->addScript('components/com_apiportal/assets/js/dataTables.bootstrap.js');
$document->addScript('components/com_apiportal/assets/js/fnDisplayRow.js');

$createAppURL = JRoute::_('index.php?option=com_apiportal&view=application&layout=create', false);
//---

// Check Menu Id to get Menu Params Mastheadtitle, MastheadSlogan
$result = ApiPortalHelper::getMenuParamsValue($itemId);
if(!empty($result['masthead-title'])){
	$title =  $result['masthead-title'];
}else{
	$title =  JText::_('COM_APIPORTAL_MONITORING_TITLE');
}
if(!empty($result['masthead-slogan'])){
	$slogan =  $result['masthead-slogan'];
}

?>

<script type="text/javascript">
    // jQuery is loaded in 'noconflict' mode.
    jQuery(document).ready(function($) {
        createAllMetricsElements();
    });

    // jQuery is loaded in 'noconflict' mode.
    var createAllMetricsElementsCalled = false;
    function createAllMetricsElements () {
        if (createAllMetricsElementsCalled) {
            return;
        }
        createAllMetricsElementsCalled = true;

        <?php if (isset($series) && sizeof($series)>0) { ?>
        createChart();
        <?php } ?>
        var now = moment();
        createDateRangePicker(now);
        <?php if (($isCommingFromMainMenu || $usageType == $usageTypeAPP) && $summarydata) { ?>
        createDatatable();
        <?php } ?>
        setCurrentStartDate (getCurrentStartDate(now.startOf('day')));
        setCurrentEndDate (getCurrentEndDate(now.endOf('day')));
    }

    function createChart() {
        // using UTC false will force highcharts to render the chart in the local time zone.
        Highcharts.setOptions({global: {useUTC: false}});

        //  the chart for the first time
        this.chart = new Highcharts.Chart({
            credits: {
                enabled: false
            },
            chart: {
                renderTo: 'container',
                zoomType: (true) ? 'x' : '',
                spacingRight: 20,
                animation: false,
                //height: this.height,
                //width: this.width,
                borderColor: '#fff',
                borderWidth: 0,
                backgroundColor: null,
                events: {
                    selection: function(event) {
                        console.log('zooming: ', event.xAxis[0]);
                        widget._onZoom(event.xAxis[0].min, event.xAxis[0].max);//notify
                        event.preventDefault();
                    }
                    //click: lang.hitch(this, function(event) {
                    //this.emit('click', {bubbles: false});
                    //this.onClick();
                    //event.preventDefault();
                    //})
                }
            },
            tooltip: {
                xDateFormat: "%A, %b %e, %Y %H:%M:%S"
            },
            colors: ["<?php echo ($metricColor); ?>"],
            title: {
                text: (false) ? "Title..." : ''
            },
            plotOptions: {
                area: {
                    cursor: 'pointer',
                    marker: {enabled: true}
                },
                line: {
                    animation: false,
                    enableMouseTracking: false ? false : true,
                    shadow: false ? false : true
                },
                series: {
                    animation: false,
                    enableMouseTracking: true, // tooltips
                    shadow: true
                    //events: {
                    //    click: lang.hitch(this, function(event) {
                    //        this.emit('click', {bubbles: false});
                    //        this.onClick();
                    //        event.preventDefault();
                    //    })
                    //}
                }
            },
            yAxis: {
                title: {text: ''},
                min: 0,
                startOnTick: false,
                showFirstLabel: false,
                allowDecimals: false
            },
            legend: {
                enabled: false
            },
            xAxis: {
                type: 'datetime',
                // This is to prevent a chart rendering problem where a) the chart has already
                // been rendered b) new data is applied using a different pointInterval.  For
                // some reason, highcharts auto-calcualtes the minRange, and even though new
                // data is set, it does not reset the minRange.  As a result, '10 minute' data
                // was zooming incorrectly.  Setting this value to something small seems to fix
                // the issue, even though it does not make sense.  Highcharts defines it as:
                // "The entire axis will not be allowed to span over a smaller interval than this"
                minRange: 5000,
                title: {
                    text: null
                }
            },
            series: <?php echo json_encode($series); ?>
        });
    }

    function createDateRangePicker(now) {

        var cb = function(start, end, label) {
            //console.log(start.toISOString(), end.toISOString(), label);
            jQuery('#daterange span').html(formatDate(start) + ' - ' + formatDate(end));
            doFilter( null, null, start, (moment(now)<end?moment(now):end));
        };

        //var ed = "<?php echo $to ?>";
        //var todayEnd = ed == "" ? moment(now.endOf('day')) : ed;
        var optionSet1 = {
            //startDate: moment().subtract('days', 29),
            //endDate: moment(),
            startDate: getCurrentStartDate(now.startOf('day')),
            endDate: getCurrentEndDate(now.endOf('day')),
            minDate: '01/01/2012',
            maxDate: moment().endOf('day'),
            //dateLimit: {days: 60}, - contradicts minDate, may be removed or set to 2000 or so ....
            showDropdowns: true,
            showWeekNumbers: true,
            timePicker: true,
            timePickerIncrement: 1,
            timePicker12Hour: true,
            ranges: {
                '<?= JText::_('COM_APIPORTAL_MONITORING_DATEPICKER_TODAY') ?>': [moment().startOf('day'), moment(now)],
                '<?= JText::_('COM_APIPORTAL_MONITORING_DATEPICKER_YESTERDAY') ?>': [moment().subtract('days', 1).startOf('day'), moment().subtract('days', 1).endOf('day')],
                '<?= JText::_('COM_APIPORTAL_MONITORING_DATEPICKER_LAST_SEVEN_DAYS') ?>': [moment().subtract('days', 6).startOf('day'), now],
                '<?= JText::_('COM_APIPORTAL_MONITORING_DATEPICKER_LAST_THIRTY_DAYS') ?>': [moment().subtract('days', 29).startOf('day'), now],
                '<?= JText::_('COM_APIPORTAL_MONITORING_DATEPICKER_THIS_MONTH') ?>': [moment().startOf('month'), now ],
                '<?= JText::_('COM_APIPORTAL_MONITORING_DATEPICKER_LAST_MONTH') ?>': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
            },
            opens: 'left',
            buttonClasses: ['btn btn-default'],
            applyClass: 'btn-small btn-primary',
            cancelClass: 'btn-small',
            //format: 'MM/DD/YYYY',
            format: 'MMMM D, YYYY',
            separator: ' to ',
            locale: {
                applyLabel: '<?= JText::_('COM_APIPORTAL_MONITORING_DATEPICKER_APPLAY_LABEL') ?>',
                cancelLabel: '<?= JText::_('COM_APIPORTAL_MONITORING_DATEPICKER_CANCEL_LABEL') ?>',
                fromLabel: '<?= JText::_('COM_APIPORTAL_MONITORING_DATEPICKER_FROM_LABEL') ?>',
                toLabel: '<?= JText::_('COM_APIPORTAL_MONITORING_DATEPICKER_TO_LABEL') ?>',
                customRangeLabel: '<?= JText::_('COM_APIPORTAL_MONITORING_CUSTOM_RANGE_LABEL') ?>',
                daysOfWeek: ['<?= JText::_('COM_APIPORTAL_MONITORING_WEEKDAY_SU') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_WEEKDAY_MO') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_WEEKDAY_TU') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_WEEKDAY_WE') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_WEEKDAY_TH') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_WEEKDAY_FR') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_WEEKDAY_SA') ?>'],
                monthNames: ['<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_JANUARY') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_FEBRUARY') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_MARCH') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_APRIL') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_MAY') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_JUNE') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_JULY') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_AUGUST') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_SEPTEMBER') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_OCTOBER') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_NOVEMBER') ?>',
                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_DECEMBER') ?>'],
                firstDay: 1
            }
        };

        jQuery('#daterange span').html(formatDate(moment(getCurrentStartDate(now.startOf('day')))) + ' - ' + formatDate( moment(getCurrentEndDate(moment()))));

        jQuery('#daterange').daterangepicker(optionSet1, cb);
/*
        jQuery('#daterange').on('show.daterangepicker', function() {
            console.log("show event fired");
        });
        jQuery('#daterange').on('hide.daterangepicker', function() {
            console.log("hide event fired");
        });
        jQuery('#daterange').on('apply.daterangepicker', function(ev, picker) {
            console.log("apply event fired, start/end dates are "
                + picker.startDate.format('MMMM D, YYYY')
                + " to "
                + picker.endDate.format('MMMM D, YYYY')
            );
        });
        jQuery('#daterange').on('cancel.daterangepicker', function(ev, picker) {
            console.log("cancel event fired");
        });
*/
        jQuery('#destroy').click(function() {
            jQuery('#daterange').data('daterangepicker').remove();
        });
    }
//this function is used to convert date months names into local language month names.
    function formatDate(date) {
    	  var monthNames = ['<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_JANUARY') ?>',
    	                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_FEBRUARY') ?>',
    	                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_MARCH') ?>',
    	                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_APRIL') ?>',
    	                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_MAY') ?>',
    	                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_JUNE') ?>',
    	                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_JULY') ?>',
    	                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_AUGUST') ?>',
    	                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_SEPTEMBER') ?>',
    	                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_OCTOBER') ?>',
    	                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_NOVEMBER') ?>',
    	                    '<?= JText::_('COM_APIPORTAL_MONITORING_MONTH_DECEMBER') ?>'];
          
    	  var d = new Date(date);
    	  var day = d.getDate();
    	  var monthIndex = d.getMonth();
    	  var year = d.getFullYear();

    	  return monthNames[monthIndex] + ' ' + day + ', ' + year;
    }
	

    function doFilter(clientName, serviceName, startdate, enddate, method) {
        // Service name - get it from QueryString if it is not presented here
        if (serviceName===null || serviceName===undefined) { serviceName = getCurrentServiceName(); }
        // Start & End dates - get them from QueryString if they are not presented here
        if (startdate===null || startdate===undefined) { startdate = getCurrentStartDate(); }
        startdate = startdate!==null && startdate!==undefined?startdate.toISOString():startdate;
        if (enddate===null || enddate===undefined) { enddate = getCurrentEndDate(); }
        enddate = enddate!==null && enddate!==undefined?enddate.toISOString():enddate;
        if (method===null || method===undefined) { method = ''; }

        var uri = jQuery(location).attr('href');

        if (serviceName!==null && serviceName!==undefined) { uri = updateQueryStringParameter (uri, "sn", encodeURI(serviceName)); }
        if (clientName!==null && clientName!==undefined) { uri = updateQueryStringParameter (uri, "cn", encodeURI(clientName)); }
        if (startdate!==null && startdate!==undefined) { uri = updateQueryStringParameter (uri, "sd", encodeURI(startdate)); }
        if (enddate!==null && enddate!==undefined) { uri = updateQueryStringParameter (uri, "ed", encodeURI(enddate)); }
        if (method!==null && method!==undefined) { uri = updateQueryStringParameter (uri, "md", encodeURI(method)); }

        jQuery(location).attr('href',uri);
    }

    function getMethodName () {
        return "<?= addslashes($methodName) ?>";
    }

    function getCurrentServiceName () {
        return "<?= addslashes($servicename) ?>";
    }

    function setCurrentServiceName (sn) {
    }

    function getCurrentClientName () {
        return "<?= addslashes($clientname) ?>";
    }

    function setCurrentClientName (cn) {
    }

    function getCurrentStartDate(defval) {
        var sd = "<?= addslashes($from) ?>";
        return sd!==null && sd.length!==0?new Date(sd):defval;
    }

    function setCurrentStartDate(sd) {
        jQuery('#daterange').data('daterangepicker').setStartDate(sd);
    }

    function getCurrentEndDate(defval) {
        var ed = "<?= addslashes($to) ?>";
        return ed!==null && ed.length!==0?new Date(ed):defval;
    }

    function setCurrentEndDate(ed) {
        jQuery('#daterange').data('daterangepicker').setEndDate(ed);
    }

    function getParameterByName(name) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

    function strToDate (dateString) {
        //var reggie = /(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/;
        var reggie = /(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})\.(\d{3})Z/;
        var dateArray = reggie.exec(dateString);
        var dateObject = new Date(
            (+dateArray[1]),
            (+dateArray[2])-1, // Careful, month starts at 0!
            (+dateArray[3]),
            (+dateArray[4]),
            (+dateArray[5]),
            (+dateArray[6])
        );
        return dateObject;
    }
</script>
<?php if ($isCommingFromMainMenu || $usageType == $usageTypeAPP) {
    require JPATH_COMPONENT.'/views/monitoring/tmpl/default-create-datatable.php';
} ?>


<?php if ($showTabs) { ?>
<div class="head">
  <h1 class="auto"><?php echo $title; ?></h1>
  <p class="auto"><em><?php echo $slogan; ?><!-- placeholder --></em></p>
</div>

<div class="body auto">
    <ul class="nav nav-tabs">
        <li <?php echo ($usageType == $usageTypeAPP ? 'class="active"' : ''); ?>><a href="<?php echo $usageTypelink1; ?>" data-toggle="tabTODO"><?php echo $usageType1Label ; ?> <?= JText::_('COM_APIPORTAL_MONITORING_USAGE') ?></a></li>
        <li <?php echo ($usageType == $usageTypeAPI ? 'class="active"' : ''); ?>><a href="<?php echo $usageTypelink2; ?>" data-toggle="tabTODO"><?php echo $usageType2Label ; ?> <?= JText::_('COM_APIPORTAL_MONITORING_USAGE') ?></a></li>
    </ul>
<?php }?>

<div class="row-fluid pull-left">
    <!-- Breadcrumbs -->
    <?php if ($showBreadcrumbs) { ?>
        <?php if ($usageType == $usageTypeAPP) { ?>
            <div style="line-height: 35px;"><?= JText::sprintf('COM_APIPORTAL_MONITORING_DRILL_ORG_LABEL', "<span class='monitoring-info'>$infoSecondLabel</span>", "<span class='monitoring-info'>$infoLabel</span>") ?></div>
        <?php } else { ?>
            <div style="line-height: 35px;"><?= JText::sprintf('COM_APIPORTAL_MONITORING_DRILL_API_LABEL', "<span class='monitoring-info'>$infoLabel</span>", "<span class='monitoring-info'>$infoSecondLabel</span>") ?></div>
        <?php } ?>
    <?php } ?>
</div>
<div class="row-fluid action-group">
    <div id="daterange" class="btn btn-default icon calendar">
        <span></span> <b class="caret"></b>
    </div>
</div>
<!-- character to prevent the action-group from collapsing in Firefox -->
&nbsp;
<!--div class="row-fluid" style="background: #ddd; padding: 5px 10px; ">
    <b><font size="3"><?php echo ($infoLabel); ?></font></b>
</div-->
<table class="table table-striped table-bordered table-hover table-monitoring" border="1" cellspacing="0" cellpadding="0">
    <thead>
    <tr>
        <th class="tab <?php echo ($tab == $page1 ? 'active table-monitoring-arrow-box' : 'tab'); ?>" <?php echo ($tab == $page1 ? 'bgcolor="#eeeeee"' : ''); ?> onclick="location.href = '<?php echo $pagelink1; ?>';" style="cursor: pointer;">
            <div class="table-monitoring-name"><?php echo $page1Label; ?></div>
            <div class="table-monitoring-value"><?php echo $totalNumMessagesSingle; ?></div>
            <div class="table-monitoring-details"><?php echo number_format(100*($totalNumMessagesSingle!=0?$totalNumMessagesSingle/$totalNumMessagesSingle:0),0)."%";?></div>
        </th>
        <th class="tab <?php echo ($tab == $page2 ? 'active table-monitoring-arrow-box' : 'tab'); ?>" <?php echo ($tab == $page2 ? 'bgcolor="#eeeeee"' : ''); ?> onclick="location.href = '<?php echo $pagelink2; ?>';" style="cursor: pointer;">
            <div class="table-monitoring-name"><?php echo $page2Label; ?></div>
            <div class="table-monitoring-value"><?php echo $totalSuccessesSingle; ?></div>
            <div class="table-monitoring-details"><?php echo number_format(100*($totalNumMessagesSingle!=0?$totalSuccessesSingle/$totalNumMessagesSingle:0),0)."%";?></div>
        </th>
        <th class="tab <?php echo ($tab == $page3 ? 'active table-monitoring-arrow-box' : 'tab'); ?>" <?php echo ($tab == $page3 ? 'bgcolor="#eeeeee"' : ''); ?> onclick="location.href = '<?php echo $pagelink3; ?>';" style="cursor: pointer;">
            <div class="table-monitoring-name"><?php echo $page3Label; ?></div>
            <div class="table-monitoring-value"><?php echo $totalFailuresSingle; ?></div>
            <div class="table-monitoring-details"><?php echo number_format(100*($totalNumMessagesSingle!=0?$totalFailuresSingle/$totalNumMessagesSingle:0),0)."%";?></div>
        </th>
        <th class="tab <?php echo ($tab == $page4 ? 'active table-monitoring-arrow-box' : 'tab'); ?>" <?php echo ($tab == $page4 ? 'bgcolor="#eeeeee"' : ''); ?> onclick="location.href = '<?php echo $pagelink4; ?>';" style="cursor: pointer;">
            <div class="table-monitoring-name"><?php echo $page4Label; ?></div>
            <div class="table-monitoring-value"><?php echo $totalExceptionsSingle; ?></div>
            <div class="table-monitoring-details"><?php echo number_format(100*($totalNumMessagesSingle!=0?$totalExceptionsSingle/$totalNumMessagesSingle:0),0)."%";?></div>
        </th>
        <th class="tab <?php echo ($tab == $page5 ? 'active table-monitoring-arrow-box' : 'tab'); ?>" <?php echo ($tab == $page5 ? 'bgcolor="#eeeeee"' : ''); ?> onclick="location.href = '<?php echo $pagelink5; ?>';" style="cursor: pointer;">
            <div class="table-monitoring-name"><?php echo $page5Label; ?></div>
            <div class="table-monitoring-value"><?php echo number_format($totalNumMessagesSingle!=0?$totalProcessingTimeAvgSingle/$totalNumMessagesSingle:0,1);?></div>
            <div class="table-monitoring-details"><?= JText::_('COM_APIPORTAL_MONITORING_MILLISECONDS') ?></div>
        </th>
    </tr>
    </thead>
</table>
<div id="container" style="width:100%; height:200px;">
    <?php
    if ($metricsDisabled) {
        echo "&nbsp;&nbsp;" . JText::_('COM_APIPORTAL_MONITORING_DISABLED');
    } else {
        echo (isset($series) && sizeof($series) > 0) ? "" : "&nbsp;&nbsp;" . JText::sprintf('COM_APIPORTAL_MONITORING_CREATE_APP_LABEL', $createAppURL);
    }
    ?>
</div>
<?php if ($isCommingFromMainMenu || $usageType == $usageTypeAPP) { ?>
    <table id="dtable" class="table table-hover table-bordered"></table>
<?php } ?>
</div>
