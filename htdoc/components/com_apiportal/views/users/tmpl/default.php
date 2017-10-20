<?php
    defined('_JEXEC') or die('Restricted access');

    $document = JFactory::getDocument();
    $document->addStyleSheet('components/com_apiportal/assets/css/jquery.dataTables.css');
    $document->addStyleSheet('components/com_apiportal/assets/css/dataTables.bootstrap.css');
    $document->addScript('components/com_apiportal/assets/js/jquery.dataTables.min.js');
    $document->addScript('components/com_apiportal/assets/js/dataTables.bootstrap.js');
    $document->addScript('components/com_apiportal/assets/js/fnDisplayRow.js');
    $document->addScript('components/com_apiportal/assets/js/moment.min.js');
    // For sprintf utility
    $document->addScript('components/com_apiportal/assets/js/util/sprintf.js');

    //
    $users = isset($this->items) ? $this->items : array();
    $hasAdminRole = ApiPortalHelper::hasAdminRole();
    $delegateUserAdministration =  (isset($this->config->delegateUserAdministration) ? $this->config->delegateUserAdministration : false);

    // We only need to use JRoute on URLs that are visible in the browser address bar
    $createUserURL = JRoute::_('index.php?option=com_apiportal&view=user&layout=create', false);
    $enableUsersURL = JRoute::_('index.php?option=com_apiportal&task=users.enableUsers', false);
    $disableUsersURL = JRoute::_('index.php?option=com_apiportal&task=users.disableUsers', false);
    $deleteUsersURL = JRoute::_('index.php?option=com_apiportal&task=users.deleteUsers', false);

    $session = JFactory::getSession();
    require JPATH_COMPONENT.'/views/users/tmpl/default-create-datatable.php';
    
    $itemId = JRequest::getString('Itemid', '');
    $itemId = ApiPortalHelper::cleanHtml($itemId, false, true);
    
    // Check Menu Id to get Menu Params Mastheadtitle, MastheadSlogan
    $result = ApiPortalHelper::getMenuParamsValue($itemId);
    if(!empty($result['masthead-title'])){
    	$title =  $result['masthead-title'];
    }else{
    	$title =  JText::_('COM_APIPORTAL_USERS_TITLE');;
    }
    if(!empty($result['masthead-slogan'])){
    	$slogan =  $result['masthead-slogan'];
    }else{
    	$slogan =  JText::_('COM_APIPORTAL_APICATALOG_GENERAL_SECTION');
    }
?>

<div class="head">
  <h1 class="auto"><?php echo  $title; ?></h1>
  <p class="auto"><em><?php echo $slogan; ?><!-- placeholder --></em></p>
</div>
<?php
if (count($users)>0) {
?>
<form
    id="users-form"
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

  <div class="btn-toolbar auto">
    <a href="<?php echo $createUserURL; ?>" class="btn btn-default icon add-circle"><?php echo JText::_('COM_APIPORTAL_USERS_CREATE_LABEL'); ?></a>
    <div class="action-group">
    </div>
  </div>
  <p class="no-users-defined auto">
    <em><?php echo JText::_('COM_APIPORTAL_USERS_NO_USERS'); ?></em>
  </p>
<?php
}
?>

<!-- Confirm Delete Dialog -->
<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="confirm-delete-dialog-title" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h3 class="modal-title" id="confirm-delete-dialog-title"></h3>
      </div><!-- .modal-header -->

      <div class="modal-body">
        <p id="confirm-delete-dialog-text"></p>
      </div><!-- .modal-body -->

      <div class="modal-footer">
        <div class="dialog-actions">
          <button type="submit" class="btn btn-primary" id="confirm-delete-button"></button>
          <button type="button" class="btn btn-default" data-dismiss="modal">
            <?php echo JText::_('COM_APIPORTAL_USERS_CONFIRM_DELETE_DIALOG_SECONDARY_ACTION_LABEL'); ?>
          </button>
        </div>
      </div><!-- /modal-footer -->

    </div><!-- .modal-content -->
  </div><!-- .modal-dialog -->
</div><!-- .modal -->


<script type="text/javascript">
    // jQuery is loaded in 'noconflict' mode.
    jQuery(document).ready(function($) {

        createUsersDatatable();

        // Submit enable users action form data
        jQuery('#users-enable-button').on('click', function(e) {
            jQuery('#users-form').attr('action', '<?php echo $enableUsersURL; ?>');
            jQuery('#users-form').submit();
            return false;
        });

        // Submit disable users action form data
        jQuery('#users-disable-button').on('click', function(e) {
            jQuery('#users-form').attr('action', '<?php echo $disableUsersURL; ?>');
            jQuery('#users-form').submit();
            return false;
        });

        // Submit delete users action form data
        // Set delete confirmation text and form.
        jQuery('#confirm-delete').on('show.bs.modal', function(e) {
            var relatedTarget = e.relatedTarget ? e.relatedTarget : e.fromElement;

            var deleteTitle =  "<?php echo JText::_('COM_APIPORTAL_USERS_CONFIRM_DELETE_DIALOG_TITLE'); ?>";
            var deleteHtml =   "<?php echo JText::_('COM_APIPORTAL_USERS_CONFIRM_DELETE_DIALOG_TEXT'); ?>";
            var deleteButton = "<?php echo JText::_('COM_APIPORTAL_USERS_CONFIRM_DELETE_DIALOG_PRIMARY_ACTION_LABEL'); ?>";

            jQuery('#confirm-delete-dialog-title').text(sprintf(deleteTitle, jQuery(relatedTarget).data('object')));
            jQuery('#confirm-delete-dialog-text').html(sprintf(deleteHtml, escapeHTML("selected " + selectedCheckboxes() + " " + jQuery(relatedTarget).data('name'))));
            jQuery('#confirm-delete-button').text(sprintf(deleteButton, jQuery(relatedTarget).data('object')));
            jQuery('#confirm-delete-button').data('form', jQuery('#users-form'));

        });

        // Submit confirm delete form.
        jQuery('#confirm-delete-button').on('click', function(e) {
            jQuery('#users-form').attr('action', '<?php echo $deleteUsersURL; ?>');
            jQuery('#users-form').submit();
            return false;
        });
    });

</script>
