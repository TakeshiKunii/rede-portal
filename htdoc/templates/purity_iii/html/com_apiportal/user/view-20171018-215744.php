<?php
defined('_JEXEC') or die('Restricted access');

$userListURL = JRoute::_('index.php?option=com_apiportal&view=users', false);
$isProfileAction = false;
$error = false;
if (!isset($this->item)) {
    $error = true;
} else {
    $user = isset($this->item->user) ? $this->item->user : null;
    $isProfileAction = $this->item->isProfileAction;
    if (!$user) {
         $error = true;
         $app = JFactory::getApplication();
         $app->enqueueMessage(JText::_('COM_APIPORTAL_USER_VIEW_NOT_FOUND_ALERT'), 'notice');
    }
}

if (!$error) {
    $config = isset($this->item->config) ? $this->item->config : new stdClass();

    $pending = $user->state==="pending";
    $hasAdminRole = ApiPortalHelper::hasAdminRole();
    $delegateUserAdministration =  isset($config->delegateUserAdministration) ? $config->delegateUserAdministration : false;
    $showPendingLayout = ($hasAdminRole || $delegateUserAdministration) && $pending;

    $maxFieldLen = ApiPortalValidator::MAX_FIELD_LEN;
    $maxTextAreaLen = ApiPortalValidator::MAX_TEXTAREA_LEN;

    $requiredMsg = JText::_('JGLOBAL_FIELD_REQUIRED');
    $invalidEmailMsg = JText::_('JGLOBAL_FIELD_INVALID_EMAIL');
    $minLengthMsg = str_replace('%s', '{0}', JText::_('JGLOBAL_FIELD_TOO_SHORT'));
    $maxLengthMsg = str_replace('%s', '{0}', JText::_('JGLOBAL_FIELD_TOO_LONG'));

    $app = JFactory::getApplication();

    $data = array(
        'enabled' => $user->enabled,
        'name' => $this->escape($user->name),
        'loginName' => $this->escape($user->loginName),
        'email' => $this->escape($user->email),
        'phone' => $this->escape($user->phone),
        'role' => $this->escape($user->role),
        'organizationId' => $this->escape($user->organizationId),
        'description' => $this->escape($user->description),
        'createdOn' => $this->escape($user->createdOn)
    );
    $formData =  $app->getUserState(ApiPortalSessionVariables::USER_EDIT_DATA, null);
    if ($formData) {
        $data = array_merge($data, $formData);
    }
    $app->setUserState(ApiPortalSessionVariables::USER_EDIT_DATA, null);

    $selectedApps = isset($this->item->applications) ? $this->item->applications : array();
    $remainingOrgApps = isset($this->item->orgApplications) ? $this->item->orgApplications : array();
    $userApplications = isset($this->item->applications) ? $this->item->applications : array();

        
    $organizations = isset($this->item->organizations) ? $this->item->organizations : array();

    if (ApiPortalHelper::hasAdminRole() && count($organizations)  > 1) {
        /*
         * If we have more than 1 organization, then this can only be the Super Admin ('apiadmin') and we
         * will need to provide a dropdown list to select which organization to create the user in.
         */
        $organizationId = $data["organizationId"];
    } else {
        /*
         * Otherwise just display the organization name. The user will
         * be created in the current user's organization by the createApp task.
         */
        $session = JFactory::getSession();
        $organizationId = ApiPortalHelper::getActiveOrganizationId();
    }
    $organizationName = ApiPortalHelper::getOrganizationName($organizationId, $organizations);

    // These are all actions (tasks), don't use JRoute, but include Itemid
    $itemId                 = JRequest::getVar('Itemid', 0, 'INT');
    $deleteUserURL          = JRoute::_('index.php?option=com_apiportal&view=user&userId='.$user->id.'&task=user.deleteUser', false);
    $approveUserURL         = JRoute::_('index.php?option=com_apiportal&view=user&userId='.$user->id.'&task=user.approveUser&' . JSession::getFormToken() .'=1', false);
    $rejectUserURL          = JRoute::_('index.php?option=com_apiportal&view=user&userId='.$user->id.'&task=user.rejectUser&' . JSession::getFormToken() .'=1', false);

    $editProfileURL         = JRoute::_('index.php?option=com_apiportal&view=user&layout=edit&ep=profile-menu', false);
    
    $addImageURL            = JRoute::_('components/com_apiportal/assets/img/add_image.png', false);
    $userImageURL =          JURI::base(false) . 'index.php?option=com_apiportal&view=image&format=raw&userId=' . $user->id;

    $document               = JFactory::getDocument();

    // For filtering & sorting on checkbox state
    $document->addScript('components/com_apiportal/assets/js/tablesorter/parsers/parser-input-select.js');
    $document->addScript('components/com_apiportal/assets/js/tablesorter/widgets/widget-grouping.js');

    // For image thumbnails preview/upload
    $document->addStyleSheet('components/com_apiportal/assets/css/jasny-bootstrap.css');
    $document->addScript('components/com_apiportal/assets/js/jasny-bootstrap.js');
    $document->addScript('components/com_apiportal/assets/js/additional-methods.js');

    // For sprintf utility
    $document->addScript('components/com_apiportal/assets/js/util/sprintf.js');

    // For date/time formatting
    $document->addScript('components/com_apiportal/assets/js/moment.js');
}
?>


<div class="head">
<?php if (!$isProfileAction) { ?>
  <div class="btn-toolbar auto">
    <a href="<?php echo $userListURL; ?>" class="btn btn-default icon arrow-left">
      <?php echo JText::_('COM_APIPORTAL_USERS_TITLE'); ?>
    </a>
  </div>
<?php }?>
  <h1 class="auto"><?php echo sprintf(JText::_($isProfileAction?'COM_APIPORTAL_USER_PROFILE_VIEW_TITLE':'COM_APIPORTAL_USER_VIEW_TITLE'), $this->escape($user->name)); ?></h1>
  <p class="auto"><em></em></p>
</div>

<?php if ($error) { return; } ?>

<?php if ($isProfileAction) { ?>
  <div class="btn-toolbar">
    <div class="auto">
    <a href="<?php echo $editProfileURL; ?>" class="btn btn-default icon pencil">
      <?php echo JText::_('COM_APIPORTAL_USER_PROFILE_VIEW_EDIT_BUTTON'); ?>
    </a>
  </div>
  </div>
<?php }?>

<div class="body auto">
    <?php if ($showPendingLayout) { ?>
    <div id="t3-content" class="t3-content col-xs-12">
        <div id="system-message-container">
            <div id="system-message">
                <div class="alert alert-message auto">
                    <h4 class="alert-heading"><?php echo JText::_('COM_APIPORTAL_USER_VIEW_PENDING_APPROVAL_LABEL'); ?></h4>
                    <div>
                        <a href="<?php echo $approveUserURL; ?>"><?php echo JText::_('COM_APIPORTAL_USER_VIEW_PENDING_APPROVAL_APPROVE_ACTION_LABEL'); ?></a>
                        &nbsp;&nbsp;&nbsp;
                        <a href="<?php echo $rejectUserURL; ?>"><?php echo JText::_('COM_APIPORTAL_USER_VIEW_PENDING_APPROVAL_REJECT_ACTION_LABEL'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<?php if (!$isProfileAction) { ?>
    <h2><?php echo JText::_('COM_APIPORTAL_USER_VIEW_DETAILS_SECTION'); ?></h2>
<?php } ?> 

<div id="user-data" class="form-horizontal">
  <div id="general">
<?php if (!$showPendingLayout) { ?>
      <div class="form-group">
        <label class="col-sm-2 control-label" for="image-wrapper">
          <?php echo JText::_('COM_APIPORTAL_USER_VIEW_GENERAL_IMAGE_LABEL'); ?>:
        </label>
        <div class="col-sm-2 form-control-static" id="image-wrapper">
          <div class="fileinput" data-provides="fileinput">
            <div class="fileinput-new">
              <?php if ($user->image) { ?>
                <?php if (strpos($user->image, 'base64')) { ?>
                  <div class="application logo" style="background-image: url('<?php echo $user->image; ?>')"></div>
                <?php } else { ?>
                  <div class="application logo" data-trigger="fileinput" style="background-image: url('<?php echo $userImageURL; ?>')"></div>
                <?php } ?>
              <?php } else { ?>
                  <div class="application logo" data-trigger="fileinput" style="background-image: url('<?php echo $addImageURL; ?>')"></div>
              <?php } ?>
            </div>
          </div>
        </div>
      </div><!-- .form-group -->
<?php } ?>

      <div class="form-group">
          <label class="col-sm-2 control-label" for="name">
              <?php echo JText::_('COM_APIPORTAL_USER_VIEW_LOGIN_NAME_LABEL'); ?>:
          </label>
          <div class="col-sm-4 form-control-static"><?php echo $data['loginName']; ?></div>
      </div><!-- .form-group -->

      <div class="form-group">
        <label class="col-sm-2 control-label" for="name">
          <?php echo JText::_('COM_APIPORTAL_USER_VIEW_NAME_LABEL'); ?>:
        </label>
        <div class="col-sm-4 form-control-static"><?php echo $this->escape($data['name']); ?></div>
      </div><!-- .form-group -->

      <div class="form-group">
        <label class="col-sm-2 control-label" for="organization">
          <?php echo JText::_('COM_APIPORTAL_USER_VIEW_ORGANIZATION_LABEL'); ?>:
        </label>
        <div class="col-sm-4 form-control-static"><?php echo $this->escape($organizationName) ?></div>
      </div><!-- .form-group -->

      <div class="form-group">
        <label class="col-sm-2 control-label" for="role">
          <?php echo JText::_('COM_APIPORTAL_USER_VIEW_ROLE_LABEL'); ?>:
        </label>
        <div class="col-sm-4 form-control-static">
            <?php 
                if ($data['role'] === 'admin') {
                    echo JText::_('COM_APIPORTAL_USER_ROLE_ADMIN'); 
                } else if ($data['role'] === 'oadmin') {
                    echo JText::_('COM_APIPORTAL_USER_ROLE_ORGADMIN'); 
                } else {
                    echo JText::_('COM_APIPORTAL_USER_ROLE_APPDEVELOPER'); 
                }
             ?>
        </div>
      </div><!-- .form-group -->

      <div class="form-group">
        <label class="col-sm-2 control-label" for="email">
          <?php echo JText::_('COM_APIPORTAL_USER_VIEW_EMAIL_LABEL'); ?>:
        </label>
        <div class="col-sm-4 form-control-static"><?php echo $this->escape($data['email']); ?></div>
      </div><!-- .form-group -->

<?php if (!$showPendingLayout) { ?>
      <div class="form-group">
        <label class="col-sm-2 control-label" for="phone">
          <?php echo JText::_('COM_APIPORTAL_USER_VIEW_DETAILS_PHONE_LABEL'); ?>:
        </label>
        <div class="col-sm-4 form-control-static"><?php echo $this->escape($data['phone']); ?></div>
      </div><!-- .form-group -->

      <div class="form-group">
        <label class="col-sm-2 control-label" for="description">
          <?php echo JText::_('COM_APIPORTAL_USER_VIEW_DESCRIPTION_LABEL'); ?>:
        </label>
        <div class="col-sm-4 form-control-static"><?php echo $this->escape($data['description']); ?></div>
      </div><!-- .form-group -->
<?php } ?>

<?php if ($isProfileAction) { ?>
      <div class="form-group">
        <label class="col-sm-2 control-label">
          <?php echo JText::_('COM_APIPORTAL_USER_PROFILE_VIEW_TIMEZONE_LABEL'); ?>:
        </label>
        <div class="col-sm-4 form-control-static">
            <?= isset($user->timezone) ? $user->timezone : null; ?>
        </div>
      </div><!-- .form-group -->
<?php } ?>
      
      <div class="form-group">
        <label class="col-sm-2 control-label">
          <?php echo JText::_('COM_APIPORTAL_USER_VIEW_DETAILS_REGISTERED_LABEL'); ?>:
        </label>
        <div class="col-sm-4 form-control-static">
            <?php
            /*
                $secs = $data['createdOn'] / 1000;
                $registered = new DateTime();
                $registered->setTimestamp($secs);
                $registered->setTimeZone(new DateTimeZone($user->timezone));
                if ($registered->getLastErrors()['warning_count'] === 0 && $registered->getLastErrors()['error_count'] === 0) {
                    echo $registered->format('j M Y, H:i');
                }
                */
            
            echo ApiPortalHelper::convertDateTime($data['createdOn'],JText::_('COM_APIPORTAL_LOCAL_DATE_TIME_FORMAT'));
            ?>
        </div>
      </div><!-- .form-group -->

<?php if ($isProfileAction) { ?>
      <div class="form-group">
        <label class="col-sm-2 control-label">
          <?php echo JText::_('COM_APIPORTAL_USER_PROFILE_VIEW_LASTVISITED_LABEL'); ?>:
        </label>
        <div class="col-sm-4 form-control-static">
            <?php
            /*
            $date = new DateTime(JFactory::getUser()->get('lastvisitDate'));
            $date->setTimeZone(new DateTimeZone($user->timezone));
            if($date->getLastErrors()['warning_count'] > 0 || $date->getLastErrors()['error_count'] > 0) {
                echo JText::_('COM_APIPORTAL_USER_PROFILE_VIEW_LASTVISITED_NEVER');
            } else {
                echo $date->format('j M Y, H:i');
            }
            */
            $lastvisitDate = JFactory::getUser()->get('lastvisitDate');
 
            if(empty($lastvisitDate)){
            	echo JText::_('COM_APIPORTAL_USER_PROFILE_VIEW_LASTVISITED_NEVER');
            }else{
            	$lastvisitDate = (string) strtotime($lastvisitDate);
            	echo ApiPortalHelper::convertDateTime($lastvisitDate,JText::_('COM_APIPORTAL_LOCAL_DATE_TIME_FORMAT'));
            }
            
            ?>
        </div>
      </div><!-- .form-group -->
<?php } ?>
      
  </div>
</div> <!-- user-data -->

<?php if (!$showPendingLayout && !$isProfileAction) { ?>
<h2><?php echo JText::_('COM_APIPORTAL_USER_VIEW_APPPERMISSIONS_SECTION'); ?></h2>

  <!-- Sharing Area -->
  <div class="tab-pane fade in active" id="sharing">

  <?php if (!$userApplications) { ?>
    <p class="application-not-shared">
      <em><?php echo JText::_('COM_APIPORTAL_USER_VIEW_SHARING_NO_SHARED_APPS'); ?></em>
    </p>
  <?php } else { 
    $viewAppDetailsURL = JRoute::_('index.php?option=com_apiportal&view=application&layout=view&applicationId=%s', false);
  ?>
    <div id="shared-app-edit">
    <?php foreach ($userApplications as $userApplication): ?>
      <div class="col-sm-12 control-group row shared-app-edit">
        <div class="col-sm-2">
          <?php
              if (in_array($userApplication->id, $newSharedApps)) {
                  $sharedAppName = '<strong>' . $this->escape($userApplication->name) . '</strong>';
              } else {
                  $sharedAppName = $this->escape($userApplication->name);
              }
          ?>
          <span class="shared-app-name">
              <a href="<?php echo sprintf($viewAppDetailsURL, $userApplication->id); ?>">
                <?php echo $sharedAppName; ?>
              </a>
          </span>
        </div>
        <?php
            $disabled = '';
            if ($user->id === $userApplication->createdBy) {
                $disabled = 'disabled';
            }
            $canEdit = $userApplication->permission->permission == 'manage';
        ?>
        <div class="col-sm-2 <?php echo $disabled; ?>">
            <label for="view-<?php echo $userApplication->id; ?>">
              <?php
                  $viewText = $canEdit?JText::_('COM_APIPORTAL_USER_VIEW_SHARING_SHARED_MANAGE'):JText::_('COM_APIPORTAL_USER_VIEW_SHARING_SHARED_VIEW_ONLY');
                  if (!$disabled) {
                      $viewText = ucfirst($viewText);
                  }
              ?>
              <?php echo $viewText; ?>
            </label>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php } ?>
  </div><!-- .area-pane -->
<?php } ?>
</div>
<script type="text/javascript">
    // jQuery is loaded in 'noconflict' mode.
    jQuery(document).ready(function($) {
        // Set delete confirmation text and form.
        $('#confirm-delete').on('show.bs.modal', function(e) {
            var relatedTarget = e.relatedTarget ? e.relatedTarget : e.fromElement;

            var deleteTitle =  "<?php echo JText::_('COM_APIPORTAL_USER_VIEW_CONFIRM_DELETE_DIALOG_TITLE'); ?>";
            var deleteHtml =   "<?php echo JText::_('COM_APIPORTAL_USER_VIEW_CONFIRM_DELETE_DIALOG_TEXT'); ?>";
            var deleteButton = "<?php echo JText::_('COM_APIPORTAL_USER_VIEW_CONFIRM_DELETE_DIALOG_PRIMARY_ACTION_LABEL'); ?>";

            $('#confirm-delete-dialog-title').text(sprintf(deleteTitle, $(relatedTarget).data('object')));
            $('#confirm-delete-dialog-text').html(sprintf(deleteHtml, escapeHTML($(relatedTarget).data('name'))));
            $('#confirm-delete-button').text(sprintf(deleteButton, $(relatedTarget).data('object')));
            $('#confirm-delete-button').data('form', $(relatedTarget).closest('form'));
        });

        // Submit confirm delete form.
        $('#confirm-delete-button').on('click', function(e) {
            $(this).data('form').submit();
        });
        
    });
</script>
