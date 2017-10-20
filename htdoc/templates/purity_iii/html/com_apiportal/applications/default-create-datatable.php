<?php 
// Manage hidden tab for public API user
$publicApiAction = ApiPortalHelper::hasHiddenTabforPublicUser();
?>
<script>
function toTitleCase(str)
{
    return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}

// Count selected enabled/disabled rows
function selectionChanged() {

    selected = selectedEnabled() + selectedDisabled();
    if (selected===0) {
        jQuery ("#applications-delete-button").attr('disabled','true');
    } else {
        jQuery ("#applications-delete-button").removeAttr('disabled');
    }
    if (selectedDisabled()===0 || selectedEnabled()!==0) {
        jQuery ("#applications-enable-button").attr('disabled','true');
    } else {
        jQuery ("#applications-enable-button").removeAttr('disabled');
    }
    if (selectedEnabled()===0|| selectedDisabled()!==0) {
        jQuery ("#applications-disable-button").attr('disabled','true');
    } else {
        jQuery ("#applications-disable-button").removeAttr('disabled');
    }
}

function selectedCheckboxes () {
    return jQuery('.checkboxSelector:checkbox:checked').length;
}

function selectedDisabled () {
    return jQuery('.disabled-app:checkbox:checked').length;
}

function selectedEnabled () {
    return selectedCheckboxes() - selectedDisabled ();
}

function createAppDatatable () {
        var servicesArray = <?php echo json_encode($applications); ?>;

        //Convert services array to Datatable records
        servicesData = jQuery.map(servicesArray, function(val, i) {
            return [
                [
                    val.id, //invisible
                    val, // Check boxes
                    val, // Name
                    val, // State
                    val.description, // Description
                    val, // Created By (Name)
                    val.createdOn, // Created On
                    val
                ]
            ];
        });

        function renderRaw(nRow, data, iDataIndex){
            var enabled = data[3].enabled == true;
            if (!enabled) {
                jQuery(nRow).addClass("disabled-text-effect");
                jQuery(".checkboxSelector",nRow).addClass("disabled-app");
            }
            jQuery("td",nRow).addClass("appcontent");
            jQuery("td:eq(3)",nRow).addClass("description");
        }

        var dataTable = jQuery('#dtable').dataTable({
            "bPaginate": false,
            "bLengthChange": true,
            "bFilter": true,
            "oLanguage": {
                "sSearch": "",
                "sInfo": "_TOTAL_ <?= JText::_('COM_APIPORTAL_APPLICATIONS_INFO') ?>",
                "sInfoEmpty": "<?= JText::_('COM_APIPORTAL_APPLICATIONS_EMPTY_INFO') ?>",
                "sEmptyTable": "<?= JText::_('COM_APIPORTAL_APPLICATIONS_EMPTY_TABLE') ?>",
                "sLengthMenu": "_MENU_",
                "oPaginate" : {
                    "sPrevious" : "<?= JText::_('COM_APIPORTAL_MONITORING_PREVIOUS') ?>",
                    "sNext" : "<?= JText::_('COM_APIPORTAL_MONITORING_NEXT') ?>"
                }
            },
            "fnCreatedRow": renderRaw,
            "aoColumns":
                    [
                        {"sTitle": "<?= JText::_('COM_APIPORTAL_APPLICATIONS_ID') ?>", "sWidth": "0%", "bVisible": false},
                        {"sTitle": "<input type='checkbox' id='selectAll' />", "sWidth": "1%", bSortable: false,
                            mRender: function (data, type, full)
                            {
                                var enabled = data.enabled == true;
                                return '<input type="checkbox" class="checkboxSelector" name="app-id-'+ data.index + '" data-id="'+ data.id +'" value="'+ data.id +'" onchange="selectionChanged()">';
                            }
                        },
                        {"sTitle": "<?= JText::_('COM_APIPORTAL_APPLICATIONS_NAME') ?>", "sWidth": "20%", "bSortable": true,
                            mRender: function ( data, type, full )
                            {
                                return  '<a href="' + data.viewUrl + '">' + escapeHTML(data.name) + '</a>';
                            }
                        },
                        {"sTitle": "<?= JText::_('COM_APIPORTAL_APPLICATIONS_STATUS') ?>", "sWidth": "15%", "bSortable": true,
                            mRender: function (data, type, full)
                            {
                               var enabled = data.enabled == true; // normalize bool value (1, true, etc are translated to TRUE)
                                if (data.state == 'pending'){
                                   return '<i class="fa fa-clock-o"></i> <?php echo JText::_('COM_APIPORTAL_APPLICATIONS_APP_PENDING'); ?>'
                               }
                               return enabled ? toTitleCase(data.state) : ('<i class="fa fa-ban"></i>&nbsp;' + toTitleCase('disabled'));
                            }
                        },
                        {"sTitle": "<?= JText::_('COM_APIPORTAL_APPLICATIONS_DESCRIPTION') ?>", "sWidth": "25%", "bSortable": true,
                            mRender: function (data, type, full) {
                                return data !== null ? escapeHTML(data).substr(0, 120) + ' ...' : data;
                            }
                        },
                        {"sTitle": "<?= JText::_('COM_APIPORTAL_APPLICATIONS_CREATED_BY') ?>", "sWidth": "17%", "bSortable": true,
                            mRender: function ( data, type, full )
                            {
                                var enabled = data.enabled == true;
                                return  enabled?'<a href="' + data.createdByUrl + '">' + escapeHTML(data.createdByName) + '</a>':escapeHTML(data.createdByName);
                            }
                        },
                        {"sTitle": "<?= JText::_('COM_APIPORTAL_APPLICATIONS_CREATED_ON') ?>", "sWidth": "16%", "bSortable": true},
                        <?php if(!(JFactory::getSession()->get('PublicAPIMode',0) ==1)) { ?>
                        {"sTitle": "<?= JText::_('COM_APIPORTAL_APPLICATIONS_ACTIONS') ?>", "sWidth": "9%", "bSortable": false,
                            mRender: function ( data, type, full )
                            {
                                var enabled = data.enabled == true;
                                return  enabled?'<a href="' + data.metricsUrl + '"><?php echo JText::_('COM_APIPORTAL_APICATALOG_VIEW_METRICS'); ?></a>':"";
                            }
                        }
                        <?php } ?>
                    ],
           "sDom": '<"#dtableCustomToolbar"frp><"body auto"it>',
           "aaSorting": [[ 2, "asc" ]]
        });

        // Add a custom element to the page length selector
        var pageLengthSelector = jQuery('.dataTables_length select');
        pageLengthSelector.after('<span>per page</span>');

        jQuery("#dtableCustomToolbar").addClass('auto');
        jQuery("#dtableCustomToolbar").wrap('<div class="btn-toolbar" role="toolbar"></div>');
        jQuery("#dtableCustomToolbar").prepend(
          '<div class="btn-group" <?php echo $publicApiAction; ?>>'

          + '<a href="<?php echo $createAppURL; ?>"  class="btn btn-default icon add-circle" data-toggle="tabTODO"><?php echo JText::_('COM_APIPORTAL_APPLICATIONS_CREATE_LABEL'); ?></a>'

          + '</div>'

		  + '<div class="btn-group">'

          + '<button disabled="true" type="button" id="applications-enable-button" class="btn btn-default" data-toggle="tabTODO"><?php echo JText::_('COM_APIPORTAL_APPLICATIONS_ENABLE_LABEL'); ?></button>'

          + '<button disabled="true" type="button" id="applications-disable-button" class="btn btn-default" data-toggle="tabTODO"><?php echo JText::_('COM_APIPORTAL_APPLICATIONS_DISABLE_LABEL'); ?></button>'

		  + '</div>'

          + '<button '
                + 'disabled="true" '
                + 'type="button" '
                + 'id="applications-delete-button" '
                + 'class="btn btn-default icon delete" '
                + 'data-toggle="modal" '
                + 'data-target="#confirm-delete" '
                + 'data-name="application(s)" '
                + 'data-object="<?php echo JText::_('COM_APIPORTAL_APPLICATION_APPLICATIONS_OBJECT'); ?>" '
                + '><?php echo JText::_('COM_APIPORTAL_APPLICATIONS_DELETE_LABEL'); ?></button>'
        );

        // Add a placeholder value and magnifier icon to Search Input Field
        var filterWrapper = jQuery('.dataTables_filter');
        var filterField = jQuery(filterWrapper).find('input');
        filterField.parent().before(
                '<div class="dropdown sort-dropdown">'
                + '  <label><?= JText::_('COM_APIPORTAL_APPLICATIONS_DISPLAY') ?>'
                + '  <button type="button" class="btn btn-default dropdown-toggle icon icons" data-toggle="dropdown"><?php echo ucfirst($viewLayout) ;?></button>'
                + '  &nbsp;'
                + '  <ul class="dropdown-menu" role="menu">'
                + '     <li><a onclick="javascript:onFilterClick()" href="index.php?option=com_apiportal&view=applications&layout=<?php echo APP_VIEW_LAYOUT_TABLE;?>&Itemid=<?php echo $itemId; ?>" class="btn btn-primary"><?= JText::_('COM_APIPORTAL_APPLICATIONS_DISPLAY_TABLE') ?></a></li>'
                + '     <li><a onclick="javascript:onFilterClick()" href="index.php?option=com_apiportal&view=applications&layout=<?php echo APP_VIEW_LAYOUT_CATALOG;?>&Itemid=<?php echo $itemId; ?>" class="btn btn-primary"><?= JText::_('COM_APIPORTAL_APPLICATIONS_DISPLAY_CATALOG') ?></a></li>'
                + '  </ul>'
				+ '  </label>'
                + '</div>'
        );
        filterField.attr("placeholder", "<?= JText::_('COM_APIPORTAL_APPLICATIONS_SEARCH_PLACEHOLDER') ?>");
        filterWrapper.attr('role', 'search');

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

        // Handle Select All checkbox clicks
        jQuery("#selectAll").change(function() {
            var isSelected = jQuery(this).is(':checked');
            jQuery('.checkboxSelector').prop('checked', isSelected);
            selectionChanged();
        });

    }

</script>
