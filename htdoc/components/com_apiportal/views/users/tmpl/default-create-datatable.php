<script>
hasEditPermissions = <?php echo ($hasAdminRole || $delegateUserAdministration?"true":"false");?>

function toTitleCase(str)
{
    return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
};

// Count selected enabled/disabled rows
function selectionChanged() {
    selected = selectedEnabled() + selectedDisabled();
    if (!hasEditPermissions || selected===0) {
        jQuery ("#users-delete-button").attr('disabled','true');
    } else {
        jQuery ("#users-delete-button").removeAttr('disabled');
    }
    if (!hasEditPermissions || selectedDisabled()===0 || selectedEnabled()!==0) {
        jQuery ("#users-enable-button").attr('disabled','true');
    } else {
        jQuery ("#users-enable-button").removeAttr('disabled');
    }
    if (!hasEditPermissions || selectedEnabled()===0|| selectedDisabled()!==0) {
        jQuery ("#users-disable-button").attr('disabled','true');
    } else {
        jQuery ("#users-disable-button").removeAttr('disabled');
    }
}

function selectedCheckboxes () {
    return jQuery('.checkboxSelector:checkbox:checked').length;
}

function selectedDisabled () {
    return jQuery('.disabled-user:checkbox:checked').length;
}

function selectedEnabled () {
    return selectedCheckboxes() - selectedDisabled ();
}

function createUsersDatatable () {
        var servicesArray = <?php echo json_encode($users); ?>;

        //Convert services array to Datatable records
        servicesData = jQuery.map(servicesArray, function(val, i) {
            return [
                [
                    val.id, //invisible
                    val, // Check boxes
                    val, // Name
                    val, // Login Name
                    val, // State
                    val.createdOn// Registered On
                ]
            ];
        });

        function renderRaw(nRow, data, iDataIndex){
            var enabled = data[3].enabled == true;
            if (!enabled) {
                jQuery(nRow).addClass("disabled-text-effect");
                jQuery(".checkboxSelector",nRow).addClass("disabled-user");
            }
            jQuery("td",nRow).addClass("usercontent");
            jQuery("td:eq(3)",nRow).addClass("description");
        }

        var dataTable = jQuery('#dtable').dataTable({
            "bPaginate": false,
            "bLengthChange": true,
            "bFilter": true,
            "oLanguage": {
                "sSearch": "",
                "sInfo": "_TOTAL_ <?= JText::_('COM_APIPORTAL_USER_INFO') ?>",
                "sInfoEmpty": "<?= JText::_('COM_APIPORTAL_USERS_EMPTY_INFO') ?>",
                "sEmptyTable": "<?= JText::_('COM_APIPORTAL_USERS_EMPTY_TABLE') ?>",
                "sLengthMenu": "_MENU_",
                "oPaginate" : {
                    "sPrevious" : "<?= JText::_('COM_APIPORTAL_MONITORING_PREVIOUS') ?>",
                    "sNext" : "<?= JText::_('COM_APIPORTAL_MONITORING_NEXT') ?>"
                }
            },
            "fnCreatedRow": renderRaw,
            "aoColumns":
                    [
                        {"sTitle": "Id", "sWidth": "0%", "bVisible": false},
                        {"sTitle": "<input type='checkbox' id='selectAll' />", "sWidth": "1%", bSortable: false,
                            mRender: function (data, type, full)
                            {
                                return '<input type="checkbox" class="checkboxSelector" name="user-id-'+ data.id + '" data-id="'+ data.id +'" value="'+ data.id +'" onchange="selectionChanged()">';
                            }
                        },
                        {"sTitle": "<?= JText::_('COM_APIPORTAL_USER_VIEW_NAME_LABEL') ?>", "sWidth": "15%", "bSortable": true,
                            mRender: function ( data, type, full )
                            {
                                return  '<a href="' + data.viewUrl + '">' + escapeHTML(data.name) + '</a>';
                            }
                        },
                        {"sTitle": "<?= JText::_('COM_APIPORTAL_USER_VIEW_LOGIN_NAME_LABEL') ?>", "sWidth": "13%", "bSortable": true,
                            mRender: function ( data, type, full )
                            {
                                return  '<a href="' + data.viewUrl + '">' + escapeHTML(data.loginName) + '</a>';
                            }
                        },
                        {"sTitle": "<?= JText::_('COM_APIPORTAL_USER_STATUS') ?>", "sWidth": "10%", "bSortable": true,
                            mRender: function (data, type, full)
                            {
                               var enabled = data.enabled == true; // normalize bool value (1, true, etc are translated to TRUE)
                               var pending = data.state == "pending";
                               return pending?('<i class="fa fa-clock-o"></i>&nbsp;' + toTitleCase('Pending approval')):(enabled ? toTitleCase(data.state) : ('<i class="fa fa-ban"></i>&nbsp;' + toTitleCase('disabled')));
                            }
                        },
                       
                        
                        
                        {"sTitle": "<?= JText::_('COM_APIPORTAL_USER_VIEW_DETAILS_REGISTERED_LABEL') ?>", "sWidth": "17%", "bSortable": true}
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
          '<div class="btn-group">'

          + '<a href="<?php echo $createUserURL; ?>" class="btn btn-default icon add-circle" data-toggle="tabTODO"><?php echo JText::_('COM_APIPORTAL_USERS_CREATE_LABEL'); ?></a>'

          + '</div>'

          + '<div class="btn-group">'
                  + '  <button disabled="true" type="button" id="users-enable-button" class="btn btn-default" data-toggle="tabTODO"><?php echo JText::_('COM_APIPORTAL_USERS_ENABLE_LABEL'); ?></button>'
                  + '  <button disabled="true" type="button" id="users-disable-button" class="btn btn-default" data-toggle="tabTODO"><?php echo JText::_('COM_APIPORTAL_USERS_DISABLE_LABEL'); ?></button>'
          + '</div>'
                  + '<button '
                    + 'disabled="true" '
                    + 'type="button" '
                    + 'id="users-delete-button" '
                    + 'class="btn btn-default icon delete" '
                    + 'data-toggle="modal" '
                    + 'data-target="#confirm-delete" '
                    + 'data-name="user(s)" '
                    + 'data-object="<?php echo JText::_('COM_APIPORTAL_USERS_USERS_OBJECT'); ?>" '
                    + '><?php echo JText::_('COM_APIPORTAL_USERS_DELETE_LABEL'); ?></button>'
                  );

        // Add a placeholder value and magnifier icon to Search Input Field
        var filterWrapper = jQuery('.dataTables_filter');
        var filterField = jQuery(filterWrapper).find('input');
        filterField.attr("placeholder", "<?= JText::_('COM_APIPORTAL_USER_FILTER_USERS') ?>");
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

        if (!hasEditPermissions) {
            jQuery ("#selectAll").attr('disabled','true');
            jQuery('.checkboxSelector').attr('disabled','true');
        } else {
            jQuery ("#selectAll").removeAttr('disabled');
            jQuery('.checkboxSelector').removeAttr('disabled');
        }

    }

</script>
