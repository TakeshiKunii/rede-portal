<script>
    function createDatatable () {
        var allDataRequest =
                (getCurrentServiceName()==null || getCurrentServiceName()=="undefined" || getCurrentServiceName()=="")
                &&
                (getCurrentClientName()==null || getCurrentClientName()=="undefined" || getCurrentClientName()=="")
            ;
        var isTableRowsClickable = true;//allDataRequest;
        servicesArray = <?php echo json_encode($summarydata); ?>;

        //Convert services array to Datatable records
        servicesData = jQuery.map(servicesArray, function(val, i) {
            return [
                [
                    val.ORGANIZATIONNAME = (val.ORGANIZATIONNAME == null || typeof val.ORGANIZATIONNAME === 'undefined') ? '-' : escapeHTML(val.ORGANIZATIONNAME),
                    val.APPNAME = (val.APPNAME && val.APPNAME != '') ? escapeHTML(val.APPNAME) : '',
                    val.SERVICENAME = (val.SERVICENAME == null || typeof val.SERVICENAME === 'undefined') ? '-' : escapeHTML(val.SERVICENAME),
                    val.METHODNAME = (val.METHODNAME == null || typeof val.METHODNAME === 'undefined') ? '-' : escapeHTML(val.METHODNAME),
                    val.totalNumMessages + " ("+(<?= $totalNumMessages;?>!=0?(val.totalNumMessages  / <?= $totalNumMessages;?>*100):0).toFixed(0)+"%)",
                    val.totalSuccesses + " ("+(<?php echo $totalSuccesses;?>!=0?(val.totalSuccesses / <?php echo $totalSuccesses;?>*100):0).toFixed(0)+"%)",
                    val.totalFailures + " ("+(<?php echo $totalFailures;?>!=0?(val.totalFailures / <?php echo $totalFailures;?>*100):0).toFixed(0)+"%)",
                    val.totalExceptions + " ("+(<?php echo $totalExceptions;?>!=0?(val.totalExceptions / <?php echo $totalExceptions;?>*100):0).toFixed(0)+"%)",
                    val.totalProcessingTimeAvg + "&nbsp;<label class='details'><?= JText::_('COM_APIPORTAL_MONITORING_MILLISECONDS') ?></label>",
                    val.CLIENTNAME = (val.CLIENTNAME && val.CLIENTNAME != '') ? escapeHTML(val.CLIENTNAME) : ''
                ]
            ]
        });

        var highlightedRow = null;
        function renderRaw(nRow, aData, iDataIndex){
            var sn = getCurrentServiceName();
            var cn = getCurrentClientName();
            var mn = getMethodName();
            if (((cn!=null && cn!="") && aData[9]==cn) || ((mn!=null && mn!="") && aData[3]==mn && ((sn!=null && sn!="") && sn==aData[2]))) {
                jQuery(nRow).addClass("highlight");
                highlightedRow = nRow;
            }

            // Set cursor
            if (isTableRowsClickable && !jQuery(nRow).hasClass("highlight")) {
                jQuery(nRow).css('cursor', 'pointer');
            }
        }

        var dataTable = jQuery('#dtable').dataTable({
            "bPaginate": true,
            "bLengthChange": true,
            "bFilter": true,
            "oLanguage": {
                "sSearch": "",
                "sInfo": "_START_-_END_ of _TOTAL_ <?= JText::_('COM_APIPORTAL_MONITORING_INFO') ?>",
                "sInfoEmpty": "<?= JText::_('COM_APIPORTAL_MONITORING_EMPTY_INFO') ?>",
                "sLengthMenu": "_MENU_",
                "oPaginate" : {
                    "sPrevious" : "<?= JText::_('COM_APIPORTAL_MONITORING_PREVIOUS') ?>",
                    "sNext" : "<?= JText::_('COM_APIPORTAL_MONITORING_NEXT') ?>"
                }
            },
            "fnCreatedRow": renderRaw,
            "aoColumns":
                [
                    {"sTitle": "<?= JText::_('COM_APIPORTAL_MONITORING_ORG_NAME') ?>", "sWidth": "23%", "bSortable": <?php echo $showClientColumn?"true":"false"; ?>, "bVisible" : <?php echo $showClientColumn?"true":(isset($applicationId)?'true':"false"); ?>},
                    {"sTitle": "<?= JText::_('COM_APIPORTAL_MONITORING_APP_NAME') ?>", "sWidth": "23%", "bSortable": <?php echo $showClientColumn?"true":"false"; ?>, "bVisible" : <?php echo $showClientColumn?"true":(isset($applicationId)?'true':"false"); ?>},
                    {"sTitle": "<?= JText::_('COM_APIPORTAL_MONITORING_API_NAME') ?>", "sWidth": "23%", "bSortable": true, bVisible: <?= !$showClientColumn?(isset($applicationId)?'false':'true'):'false' ?>},
                    {"sTitle": "<?= JText::_('COM_APIPORTAL_MONITORING_METHOD_NAME') ?>", "sWidth": "23%", "bSortable": true, bVisible: <?= !$showClientColumn?(isset($applicationId)?'false':'true'):'false' ?>},
                    {"sTitle": "<?= JText::_('COM_APIPORTAL_MONITORING_PAGE_LABEL_MSGS') ?>", "sWidth": "9%", "bSortable": true},
                    {"sTitle": "<?= JText::_('COM_APIPORTAL_MONITORING_PAGE_LABEL_SUCCESS') ?>", "sWidth": "9%", "bSortable": true},
                    {"sTitle": "<?= JText::_('COM_APIPORTAL_MONITORING_PAGE_LABEL_FAIL') ?>", "sWidth": "9%", "bSortable": true},
                    {"sTitle": "<?= JText::_('COM_APIPORTAL_MONITORING_PAGE_LABEL_EXCEPTIONS') ?>", "sWidth": "9%", "bSortable": true},
                    {"sTitle": "<?= JText::_('COM_APIPORTAL_MONITORING_PAGE_LABEL_AVG') ?>", "sWidth": "18%", "bSortable": true},
                    {"sTitle": "<?= JText::_('COM_APIPORTAL_MONITORING_CLIENT_ID') ?>", "sWidth": "0px", "bSortable": false, "bVisible" : false}
                ],
            "<?php echo $showAllItemsButton?'sDom':'_sDom_Fake'; ?>": '<?php echo $showAllItemsButton?'<"#dtableCustomButtons">frtip':''; ?>'
        });

        // Add a placeholder value and magnifier icon to Search Input Field
        var filterWrapper = jQuery('.dataTables_filter');
        var filterField = jQuery(filterWrapper).find('input');
        filterField.attr("placeholder", "<?= JText::_('COM_APIPORTAL_MONITORING_FILTER_APIS') ?>");
        filterWrapper.attr('role', 'search');

        // Add a custom element to the page length selector
        var pageLengthSelector = jQuery('.dataTables_length select');
        pageLengthSelector.after('<span><?= JText::_('COM_APIPORTAL_MONITORING_PER_PAGE') ?></span>');

        <?php if ($showAllItemsButton) { ?>
        jQuery("#dtableCustomButtons").html('<a onclick="javascript: doFilter(\'<?php echo isset($applicationId)?$applicationId:"" ;?>\',\'\', null, null, null);" class="btn btn-default" data-toggle="tabTODO"><i class="fa fa-caret-left"></i>&nbsp; <?= JText::_('COM_APIPORTAL_MONITORING_ALL') ?> <?php echo $usageType == $usageTypeAPP ? JText::_('COM_APIPORTAL_MONITORING_USAGE_TYPE_APPS') : JText::_('COM_APIPORTAL_MONITORING_USAGE_TYPE_APIS') ;?></a>');
        <?php } ?>

        // Fill data in table
        dataTable.fnClearTable();
        for (var i = 0; i < servicesData.length; i++) {
            if (servicesData[i][servicesData[i].length-1] === undefined) {
                servicesData[i][servicesData[i].length-1] = "";
            }
            dataTable.fnAddData(servicesData[i]);
        }
        jQuery(dataTable.fnSettings().aoData).each(function() {
            jQuery(this.nTr).height = 15;
        });

        // On datatable row click event handler
        jQuery("#dtable tbody").delegate("tr", "click", function() {
            if (isTableRowsClickable && !jQuery(this).hasClass("highlight")) {
                var firstCellText = escapeHTML(jQuery("td:first", this).text());
                var secondCellText = escapeHTML(jQuery("td:eq(1)", this).text());
                var lastCellText = escapeHTML(dataTable.fnGetData(this,9));
                var serviceName = "true"==="<?= !$showClientColumn?(isset($applicationId)?'false':'true'):'false' ?>"?firstCellText:null;
                var methodName = "true"==="<?= !$showClientColumn?'true':'false' ?>"?secondCellText:null;
                var clientName  = (lastCellText !== null && lastCellText.length !== 0) ? lastCellText:null;
                doFilter(clientName, serviceName, null, null, methodName);
            }
        });

        // Switch to the proper datatable page
        dataTable.fnDisplayRow(highlightedRow);

    }
</script>
