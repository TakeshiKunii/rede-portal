<?php
defined('_JEXEC') or die('Restricted access');

$document = JFactory::getDocument();
$document->addStyleSheet('components/com_apiportal/assets/css/jquery.dataTables.css');
$document->addStyleSheet('components/com_apiportal/assets/css/dataTables.bootstrap.css');
$document->addScript('components/com_apiportal/assets/js/jquery.dataTables.min.js');
$document->addScript('components/com_apiportal/assets/js/dataTables.bootstrap.js');
$document->addScript('components/com_apiportal/assets/js/fnDisplayRow.js');
$document->addScript('components/com_apiportal/assets/js/moment.min.js');

// For ellipsis truncation of 'description' field
$document->addScript('components/com_apiportal/assets/js/jquery.dotdotdot.js');

// For sprintf utility
$document->addScript('components/com_apiportal/assets/js/util/sprintf.js');

$session = JFactory::getSession();
$applications = isset($this->items) ? $this->items : array();

// We only need to use JRoute on URLs that are visible in the browser address bar
$createAppURL = JRoute::_('index.php?option=com_apiportal&view=application&layout=create', false);
$enableAppsURL = JRoute::_('index.php?option=com_apiportal&task=applications.enableApps', false);
$disableAppsURL = JRoute::_('index.php?option=com_apiportal&task=applications.disableApps', false);
$deleteAppsURL = JRoute::_('index.php?option=com_apiportal&task=applications.deleteApps', false);
$viewAppDetailsURL = JRoute::_('index.php?option=com_apiportal&view=application&layout=view&applicationId=%s', false);
$viewAppMetricsURL = JRoute::_('index.php?option=com_apiportal&view=application&layout=view&tab=metrics&applicationId=%s', false);

$appImageURL = JURI::base(false) . 'index.php?option=com_apiportal&view=image&format=raw&applicationId=%s';

$itemId = JRequest::getString('Itemid', '');
$itemId = ApiPortalHelper::cleanHtml($itemId, false, true);
// Manage views for organization admins: Table | Catalog.
$isOrgAdmin = ApiPortalHelper::hasGroupAdminRole() || ApiPortalHelper::hasAdminRole();

// Manage hidden tab for open API user
$publicApiAction = ApiPortalHelper::hasHiddenTabforPublicUser();

const APP_VIEW_LAYOUT_CATALOG = 'catalog';
const APP_VIEW_LAYOUT_TABLE = 'table';

$layout = JRequest::getVar('layout');
$layout = ApiPortalHelper::cleanHtml($layout, false, true);
if (!empty($layout)) {
    $session->set(ApiPortalSessionVariables::APPLICATION_VIEW_LAYOUT, $layout);
}

$viewLayout = $isOrgAdmin ? APP_VIEW_LAYOUT_TABLE : APP_VIEW_LAYOUT_CATALOG;
if ($isOrgAdmin) {
    $session = JFactory::getSession();
    $sessionViewLayout = $session->get(ApiPortalSessionVariables::APPLICATION_VIEW_LAYOUT);
    $viewLayout = empty($sessionViewLayout) ? $viewLayout : $sessionViewLayout;
}
$isTableLayout = !empty($viewLayout) && $viewLayout === APP_VIEW_LAYOUT_TABLE;

// Check Menu Id to get Menu Params Mastheadtitle, MastheadSlogan
$result = ApiPortalHelper::getMenuParamsValue($itemId);
if(!empty($result['masthead-title'])){
	$title =  $result['masthead-title'];
}else{
	$title =  JText::_('COM_APIPORTAL_APPLICATIONS_TITLE');
}
if(!empty($result['masthead-slogan'])){
	$slogan =  $result['masthead-slogan'];
}

?>

<?php
if ($isTableLayout) {
    require JPATH_COMPONENT . '/views/applications/tmpl/default-create-datatable.php';
}
?>

<div class="head">
  <h1 class="auto"><?php echo $title; ?></h1>
  <p class="auto"><em><?php echo $slogan;?><!-- placeholder --></em></p>
</div>
    <?php
    if (!$isTableLayout) {
    ?>
<div class="btn-toolbar" role="toolbar">
    <div class="auto">
   	
        <div class="btn-group" role="group" <?php echo $publicApiAction; ?>>
   			 <a href="<?php echo $createAppURL; ?>"
       class="btn btn-default icon add-circle"><?php echo JText::_('COM_APIPORTAL_APPLICATIONS_CREATE_LABEL'); ?></a>
        </div>
       
        <div class="action-group" role="group">
        <?php
        if ($isOrgAdmin) {
            ?>
            <div class="dropdown sort-dropdown">
                Display:
                <button type="button" class="btn btn-default dropdown-toggle icon list" data-toggle="dropdown">
                    <?php echo ucfirst($viewLayout); ?>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <li><a onclick="javascript:onFilterClick()"
                           href="index.php?option=com_apiportal&view=applications&layout=<?php echo APP_VIEW_LAYOUT_TABLE; ?>&Itemid=<?php echo $itemId; ?>"
                           class="btn btn-primary"><?php echo ucfirst(APP_VIEW_LAYOUT_TABLE); ?></a></li>
                    <li><a onclick="javascript:onFilterClick()"
                           href="index.php?option=com_apiportal&view=applications&layout=<?php echo APP_VIEW_LAYOUT_CATALOG; ?>&Itemid=<?php echo $itemId; ?>"
                           class="btn btn-primary"><?php echo ucfirst(APP_VIEW_LAYOUT_CATALOG); ?></a></li>
                </ul>
            </div>
        <?php
        }
        ?>
        <?php
        if (count($applications) > 1) {
            ?>
            <div class="dropdown sort-dropdown">
                <button id="sort-button" class="btn btn-default dropdown-toggle icon chevron-down" data-toggle="dropdown" type="button">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATIONS_SORT_NAME_ASCENDING'); ?>
                </button>
                <ul role="menu" class="dropdown-menu">
                    <li>
                        <a class="btn btn-primary" onClick='sortApplications("ascending")'>
                            <?php echo JText::_('COM_APIPORTAL_APPLICATIONS_SORT_NAME_ASCENDING'); ?>
                        </a>
                    </li>
                    <li>
                        <a class="btn btn-primary" onClick='sortApplications("descending")'>
                            <?php echo JText::_('COM_APIPORTAL_APPLICATIONS_SORT_NAME_DESCENDING'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        <?php
        }
        ?>
    </div>
</div>
</div>
<?php
}
?>
<?php
if ($isTableLayout) {
    ?>
    <form
        id="applications-form"
        class="form-horizontal"
        method="post"
        action=""
        novalidate
        >
        <table id="dtable" class="table table-hover table-bordered"></table>
        <input type="hidden" name="submitted" value="1" />
        <?= JHtml::_( 'form.token' ); ?>
    </form>
<?php
} else {
    ?>
<div class="body auto">
    <?php if (!$applications) { ?>
        <p class="no-applications-defined">
            <em><?php echo JText::_('COM_APIPORTAL_APPLICATIONS_NO_APPLICATIONS'); ?></em>
        </p>
    <?php } ?>

    <table class="table" id="applications-table">
        <!-- Tablesorter requires a thead and a th tag to enable sorting -->
        <thead style="display: none;">
        <th></th>
        </thead>

        <tbody aria-live="polite" aria-relevant="all">
        <?php foreach ($applications as $application): ?>
            <tr>
                <td>
                    <ul id="appsList" class="apis" data-view="list">
                        <li>
                          <h2>
                            <a href="<?php echo sprintf($viewAppDetailsURL, $application->id); ?>">
                                <?= ApiPortalHelper::cleanHtml($application->name, false, true); ?>
                            </a>
                          </h2>
                          <div class="api-icon">
                              <?php
                                $imageUrl = "components/com_apiportal/assets/img/no_image.png";

                                if ($application->image) {
                                  if (strpos($application->image, 'base64')) {
                                    $imageUrl = $application->image;
                                  } else {
                                    $imageUrl = sprintf($appImageURL, $application->id);
                                  }
                                }
                              ?>

                              <img src="<?php echo $imageUrl; ?>"
                                 onclick="<?php echo sprintf($viewAppDetailsURL, $application->id); ?>"
                                 alt="api icon"
                                 role="presentation">
                          </div>
                          <p><?= APIPortalHelper::subStrCustom(ApiPortalHelper::cleanHtml($application->description), 390); ?></p>
                          <dl>
                            <dt><!-- Status --></dt>
                                <?php if (!ApiPortalHelper::isPending($application)) { ?>
                                    <?php if (ApiPortalHelper::hasPendingAPIs($application)) { ?>
                            <dd><i class="fa fa-clock-o"></i> <?php echo JText::_('COM_APIPORTAL_APPLICATIONS_API_PENDING'); ?></dd>
                                    <?php } else if (!ApiPortalHelper::isEnabled($application)) { ?>
                            <dd><i class="fa fa-ban"></i> <?php echo JText::_('COM_APIPORTAL_APPLICATIONS_APP_NOT_ENABLED'); ?> </dd>
                                    <?php } ?>
                                <?php } else { ?>
                            <dd><i class="fa fa-clock-o"></i> <?php echo JText::_('COM_APIPORTAL_APPLICATIONS_APP_PENDING'); ?></dd>
                          <?php } ?>
                          </dl>
                          <div role="toolbar" <?php echo $publicApiAction;?>>
                            <?php if (!ApiPortalHelper::isPending($application)) { ?>
                            <a class="btn btn-default icon metrics" href="<?php echo sprintf($viewAppMetricsURL, $application->id); ?>">
                                <?php echo JText::_('COM_APIPORTAL_APPLICATIONS_APP_VIEW_METRICS'); ?>
                            </a>
                                <?php } ?>
                            </div>
                        </li>
                    </ul>
                </td>
            </tr>
        <?php endforeach; ?>
        <tbody>
    </table>
</div>
<?php
}
?>


<!-- Confirm Delete Dialog -->
<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="confirm-delete-dialog-title"
     aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title" id="confirm-delete-dialog-title"></h3>
            </div>
            <!-- .modal-header -->

            <div class="modal-body">
                <p id="confirm-delete-dialog-text"></p>
            </div>
            <!-- .modal-body -->

            <div class="modal-footer">
                <div class="dialog-actions">
                    <button type="submit" class="btn btn-primary" id="confirm-delete-button"></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_CONFIRM_DELETE_DIALOG_SECONDARY_ACTION_LABEL'); ?>
                    </button>
                </div>
            </div>
            <!-- /modal-footer -->

        </div>
        <!-- .modal-content -->
    </div>
    <!-- .modal-dialog -->
</div><!-- .modal -->


<script type="text/javascript">
    // jQuery is loaded in 'noconflict' mode.
    jQuery(document).ready(function ($) {

        <?php if ($isTableLayout) { ?>
        createAppDatatable();
        <?php } else {?>
        // Initialize tablesorter
        jQuery('#applications-table').tablesorter({
            textExtraction: function (node, table, cellIndex) {
                return jQuery(node).find('h2 a').text();
            },
            sortList: [[0, 0]]
        });
        // Add ellipsis to 'description' field
        jQuery('.content .description').dotdotdot({
            watch: true
        });
        <?php } ?>

        // Submit enable applications action form data
        jQuery('#applications-enable-button').on('click', function (e) {
            jQuery('#applications-form').attr('action', '<?php echo $enableAppsURL; ?>');
            jQuery('#applications-form').submit();
            return false;
        });

        // Submit disable applications action form data
        jQuery('#applications-disable-button').on('click', function (e) {
            jQuery('#applications-form').attr('action', '<?php echo $disableAppsURL; ?>');
            jQuery('#applications-form').submit();
            return false;
        });

        // Submit delete applications action form data
        // Set delete confirmation text and form.
        jQuery('#confirm-delete').on('show.bs.modal', function (e) {
            var relatedTarget = e.relatedTarget ? e.relatedTarget : e.fromElement;

            var deleteTitle = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_CONFIRM_DELETE_DIALOG_TITLE'); ?>";
            var deleteHtml = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_CONFIRM_DELETE_DIALOG_TEXT'); ?>";
            var deleteButton = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_CONFIRM_DELETE_DIALOG_PRIMARY_ACTION_LABEL'); ?>";

            jQuery('#confirm-delete-dialog-title').text(sprintf(deleteTitle, jQuery(relatedTarget).data('object')));
            jQuery('#confirm-delete-dialog-text').html(sprintf(deleteHtml, escapeHTML("selected " + selectedCheckboxes() + " " + jQuery(relatedTarget).data('name'))));
            jQuery('#confirm-delete-button').text(sprintf(deleteButton, jQuery(relatedTarget).data('object')));
            jQuery('#confirm-delete-button').data('form', jQuery('#applications-form'));

        });

        // Submit confirm delete form.
        jQuery('#confirm-delete-button').on('click', function (e) {
            jQuery('#applications-form').attr('action', '<?php echo $deleteAppsURL; ?>');
            jQuery('#applications-form').submit();
            return false;
        });
    });

    function sortApplications(direction) {
        (function ($) {
            var sorting;
            var ascending = "<?php echo JText::_('COM_APIPORTAL_APPLICATIONS_SORT_NAME_ASCENDING'); ?>";
            var descending = "<?php echo JText::_('COM_APIPORTAL_APPLICATIONS_SORT_NAME_DESCENDING'); ?>";

            if (direction == 'descending') {
                sorting = [[0, 1]];
                jQuery('#sort-button').html(descending);
            } else {
                sorting = [[0, 0]];
                jQuery('#sort-button').html(ascending);
            }
            jQuery("#applications-table").trigger("sorton", [sorting]);
        })(jQuery);
    }
</script>
