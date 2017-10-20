    <?php
    defined('_JEXEC') or die('Restricted access');


    $userListURL = JRoute::_('index.php?option=com_apiportal&view=users', false);
    $isProfileAction = false;
    $userResetPasswordURL = '';
    $userChangePasswordURL = '';
    $addSharedAppsURL = '';
    $updateSharedAppURL = '';
    $removeSharedAppURL = '';
    $userImageURL = '';
    $error = false;
    if (!isset($this->item)) {
        $error = true;
    } else {
        $user = isset($this->item->user) ? $this->item->user : null;
        $isProfileAction = $this->item->isProfileAction;
        if (!$user) {
            $error = true;
            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::_('COM_APIPORTAL_USER_EDIT_NOT_FOUND_ALERT'), 'notice');
        }
    }

    if (!$error) {
        $maxFieldLen = ApiPortalValidator::MAX_FIELD_LEN;
        $maxTextAreaLen = ApiPortalValidator::MAX_TEXTAREA_LEN;

        $requiredMsg = JText::_('JGLOBAL_FIELD_REQUIRED');
        $invalidEmailMsg = JText::_('JGLOBAL_FIELD_INVALID_EMAIL');
        $minLengthMsg = str_replace('%s', '{0}', JText::_('JGLOBAL_FIELD_TOO_SHORT'));
        $maxLengthMsg = str_replace('%s', '{0}', JText::_('JGLOBAL_FIELD_TOO_LONG'));

        $app = JFactory::getApplication();

        $data = array(
            'enabled' => ApiPortalHelper::cleanHtml($user->enabled, false, true),
            'loginName' => $user->loginName,    
            'name' => $user->name,
            'cpf' => $user ->cpf,
            'telefone' => $user ->telefone,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => ApiPortalHelper::cleanHtml($user->role, false, true),
            'organizationId' => ApiPortalHelper::cleanHtml($user->organizationId, false, true),
            'description' => $user->description,
            'createdOn' => $this->escape($user->createdOn)
        );
        $formData = $app->getUserState(ApiPortalSessionVariables::USER_EDIT_DATA, null);
        if ($formData) {
            $data = array_merge($data, $formData);
        }
        $app->setUserState(ApiPortalSessionVariables::USER_EDIT_DATA, null);

        $selectedApps = isset($this->item->applications) ? $this->item->applications : array();
        $remainingOrgApps = isset($this->item->orgApplications) ? $this->item->orgApplications : array();
        $userApplications = isset($this->item->applications) ? $this->item->applications : array();


        $organizations = isset($this->item->organizations) ? $this->item->organizations : array();
        $config = isset($this->item->config) ? $this->item->config : new stdClass();

        if (ApiPortalHelper::hasAdminRole() && count($organizations) > 0) {
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
        $itemId = JRequest::getVar('Itemid', 0, 'INT');
        $itemId = ApiPortalHelper::cleanHtml($itemId);
        $saveUserURL = JRoute::_('index.php?option=com_apiportal&view=user&userId=' . $user->id . '&task=user.saveUser', false);
        $deleteUserURL = JRoute::_('index.php?option=com_apiportal&view=user&userId=' . $user->id . '&task=user.deleteUser', false);
        $userProfileURL = JRoute::_("index.php?option=com_apiportal&view=user&layout=view&ep=profile-menu", false);

        $userResetPasswordURL = JRoute::_('index.php?option=com_apiportal&view=user&userId=' . $user->id . '&task=user.resetPassword', false);
        // if it's a profile entry (user edit his profile - change password)
        // we use one action
        if ($isProfileAction) {
            $userChangePasswordURL = JRoute::_('index.php?option=com_apiportal&view=user&userId=' . $user->id . '&task=user.changeProfilePassword', false);
        } else {
            // and another when org admin edit some users password
            $userChangePasswordURL = JRoute::_('index.php?option=com_apiportal&view=user&userId=' . $user->id . '&task=user.changePassword', false);
        }

        $addSharedAppsURL = JRoute::_('index.php?option=com_apiportal&view=user&userId=' . $user->id . '&task=user.addSharedApp', false);
        $updateSharedAppURL = JRoute::_('index.php?option=com_apiportal&view=user&userId=' . $user->id . '&task=user.updateSharedApp', false);
        $removeSharedAppURL = JRoute::_('index.php?option=com_apiportal&view=user&userId=' . $user->id . '&task=user.removeSharedApp', false);

        $addImageURL = JRoute::_('components/com_apiportal/assets/img/add_image.png', false);
        //$userImageURL           = JRoute::_('index.php?option=com_apiportal&view=image&format=raw&userId=' . $user->id, false);
        $userImageURL = JURI::base(false) . 'index.php?option=com_apiportal&view=image&format=raw&userId=' . $user->id;

        $document = JFactory::getDocument();

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

        // Add timezone selector support
        $document->addStyleSheet('media/jui/css/chosen.css');
        $document->addStyleSheet('components/com_apiportal/assets/css/chosen-overrides.css');
        $document->addScript('media/jui/js/chosen.jquery.min.js');
    }
    ?>

    <head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js"></script>
    </head>
    <div class="head">
    <?php if (!$isProfileAction) { ?>
    <div class="btn-toolbar auto">
        <a href="<?php echo $userListURL; ?>" class="btn btn-default icon arrow-left">
            <?php echo JText::_('COM_APIPORTAL_USERS_TITLE'); ?>
            </a>
        </div>
    <?php } ?>
    <h1 class="auto"><?php echo JText::_('Edit profile'); ?></h1>
    <p class="auto"><em></em></p>
    </div>

    <?php if ($error) {
        return;
    } ?>

    <?php if (!$isProfileAction) { ?>
    <div class="btn-toolbar">
        <div class="auto">
            <form id="delete-user-form" method="post" action="<?php echo $deleteUserURL; ?>">
                <button type="button" class="btn btn-default icon delete" data-toggle="modal" data-target="#confirm-delete"
                        data-name="<?php echo $this->escape($user->name); ?>"
                        data-object="<?php echo JText::_('COM_APIPORTAL_USERS_USER_OBJECT'); ?>">
                    <?php echo JText::_('COM_APIPORTAL_USER_EDIT_DELETE_LABEL'); ?>
                </button>
                <input type="hidden" name="userId" value="<?php echo $user->id; ?>"/>
                <input type="hidden" name="userName" value="<?php echo $this->escape($user->name); ?>"/>
                <input type="hidden" name="viewName" value="edit"/>
                <?php echo JHtml::_( 'form.token' ); ?>
            </form>
        </div>
        </div>
    <?php } ?>

    <div class="body auto">
    <?php if (!$isProfileAction) { ?>
    <h2><?php echo JText::_('COM_APIPORTAL_USER_EDIT_DETAILS_SECTION'); ?></h2>
    <?php } ?>
    <form id="user-form" class="form-horizontal" enctype="multipart/form-data" method="post"
        action="<?php echo $saveUserURL; ?>" novalidate>
        <div id="general">
            <fieldset>
                <div class="form-group">
                    <label class="col-sm-2 control-label" for="image-wrapper">
                        <?php echo JText::_('COM_APIPORTAL_USER_EDIT_GENERAL_IMAGE_LABEL'); ?>:
                    </label>

                    <div class="col-sm-2" id="image-wrapper">
                        <div class="fileinput" data-provides="fileinput">
                            <div class="fileinput-new">
                                <?php if ($user->image) { ?>
                                    <?php if (strpos($user->image, 'base64')) { ?>
                                        <div class="application logo"
                                            style="background-image: url('<?php echo $user->image; ?>')"></div>
                                    <?php } else { ?>
                                        <div class="application logo" data-trigger="fileinput"
                                            style="background-image: url('<?php echo $userImageURL; ?>')"></div>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div class="application logo" data-trigger="fileinput"
                                        style="background-image: url('<?php echo $addImageURL; ?>')"></div>
                                <?php } ?>
                            </div>
                            <div class="fileinput-exists">
                                <div class="fileinput-preview" data-trigger="fileinput"></div>
                            </div>
                            <input type="hidden" name="MAX_FILE_SIZE" value="1048576"/>
                            <input type="file" id="image" name="image" style="display: none;"/>
                        </div>
                        <div class="validation-message"></div>
            <span class="help-block">
                <?php echo JText::_('COM_APIPORTAL_USER_EDIT_IMAGE_HELP_TEXT'); ?>
            </span>
                    </div>
                </div>
                <!-- .form-group -->
 <div class="form-group" style="display: none;">
                <label class="col-sm-2 control-label" for="loginName">
                    <?php echo JText::_('COM_APIPORTAL_USER_VIEW_LOGIN_NAME_LABEL'); ?>*:
                </label>

                <div class="col-sm-4">
                    <input type="text" class="form-control" id="loginName" name="userdata[loginName]" value="<?php echo $this->escape($data['loginName']); ?>"  <?= $isProfileAction ? 'disabled' : null ?> />
                    <div class="validation-message"></div>
                    <div class="value-changed-message"></div>
                </div>
            </div>
            
                <!-- .form-group -->
    <div class="form-group">
                    <label class="col-sm-2 control-label" for="name">
                        <?php echo JText::_('COM_APIPORTAL_USER_VIEW_NAME_LABEL'); ?>*:
                    </label>

                    <div class="col-sm-4">
                        <input type="text" class="form-control" id="name" name="userdata[name]" value="<?php echo $this->escape($data['name']); ?>"  />
                        <div class="validation-message"></div>
                        <div class="value-changed-message"></div>
                    </div>
                </div>
            
                <!-- .form-group -->

                <?php if (!$isProfileAction) { ?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="organization">
                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_ORGANIZATION_LABEL'); ?>:
                        </label>
                        <?php if (ApiPortalHelper::hasAdminRole() && count($organizations) > 0) { ?>
                            <div class="col-sm-4">
                                <select class="form-control select-organization" id="organization"
                                        name="userdata[organizationId]"
                                        data-rule-required="true" data-msg-required="<?php echo $requiredMsg; ?>">
                                    <option value=''>
                                        <?php echo JText::_('COM_APIPORTAL_USER_EDIT_SELECT_ORGANIZATION_LABEL'); ?>
                                    </option>
                                    <?php foreach ($organizations as $organization): ?>
                                        <option
                                            value='<?php echo $organization->id; ?>' <?php echo($organization->id === $organizationId ? 'selected' : ''); ?>>
                                            <?php echo $this->escape($organization->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="validation-message"></div>
                            </div>
                        <?php } else { ?>
                            <p class="col-sm-4 form-control-static" id="organization"
                            name="organization"><?php echo ApiPortalHelper::cleanHtml($organizationName, false, true); ?></p>
                            <!--input type="hidden" name="organizationId" value="<?php //echo $organizationId; ?>"/-->
                        <?php } ?>
                    </div><!-- .form-group -->
                <?php } ?>

                <?php if (!$isProfileAction) { ?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="role">
                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_ROLE_LABEL'); ?>:
                        </label>

                        <div class="col-sm-4">
                            <select class="form-control select-organization" id="role" name="userdata[role]"
                                    data-rule-required="true" data-msg-required="<?php echo $requiredMsg; ?>">
                                <?php if (ApiPortalHelper::hasAdminRole()) { ?>
                                    <option
                                        value='admin'    <?php echo($data['role'] === 'admin' ? 'selected' : ''); ?>><?php echo JText::_('COM_APIPORTAL_USER_ROLE_ADMIN'); ?></option>
                                <?php } ?>
                                <option
                                    value='oadmin' <?php echo($data['role'] === 'oadmin' ? 'selected' : ''); ?>><?php echo JText::_('COM_APIPORTAL_USER_ROLE_ORGADMIN'); ?></option>
                                <option
                                    value='user'     <?php echo($data['role'] !== 'admin' && $data['role'] !== 'oadmin' ? 'selected' : ''); ?>><?php echo JText::_('COM_APIPORTAL_USER_ROLE_APPDEVELOPER'); ?></option>
                            </select>

                            <div class="validation-message"></div>
                        </div>
                    </div><!-- .form-group -->
                <?php } ?>
            <div class="form-group" style="display: none;">
                <label class="col-sm-2 control-label" for="email">
                    <?php echo JText::_('COM_APIPORTAL_USER_EDIT_EMAIL_LABEL'); ?>:
                </label>

                <div class="col-sm-4">
                    <input type="email" class="form-control" id="email" name="userdata[email]"
                           value="<?php echo $this->escape($data['email']); ?>">

                    <div class="validation-message"></div>
                </div>
            </div>




            <div class = "form-group">
                <h3>Dados complementares<h3>
                    <div class="form-group">
                    <label id="celular" class="col-sm-2 control-label" for="phone">
                        <?php echo JText::_('COM_APIPORTAL_USER_EDIT_DETAILS_PHONE_LABEL'); ?>:
                    </label>

                    <div class="col-sm-4">
                        <input type="tel" class="form-control" maxlength="13" id="phone" name="userdata[phone]"
                            placeholder="(11)999999999" value="<?php echo $this->escape($data['phone']); ?>">

                        <div class="validation-message"></div>
                    </div>
                </div>
                <!-- .form-group -->
                <!-- .form-group -->

                <div class="form-group">
                    <label style="font-size:14px;" class="col-sm-2 control-label" for="cpf">
                        CPF:
                    </label>

                    <div class="col-sm-4">
                        <input pattern="[0-9.]+" type="cpf" class="form-control" maxlength="11" id="cpf" name="userdata[cpf]"
                            value="<?php echo $this->escape($data['cpf']); ?>">

                        <div class="validation-message"></div>
                    </div>
                </div>
            
                <!-- .form-group -->
                <!-- .form-group -->
            
            <div class="form-group">
                    <label style="font-size:14px;"  class="col-sm-2 control-label" for="telefone">
                        Telefone:
                    </label>

                    <div class="col-sm-4">
                        <input type="text" class="form-control" maxlength="12" id="telefone" name="userdata[telefone]"
                            placeholder="(11)99999999" value="<?php echo $this->escape($data['telefone']); ?>">

                        <div class="validation-message"></div>
                    </div>
                </div>
    <!-- .form-group -->
            
            
                <?php if (!$isProfileAction) { ?>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-4">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="userdata[enabled]"
                                        value='true' <?php echo($data['enabled'] !== 'false' ? 'checked' : ''); ?>>
                                    <?php echo JText::_('COM_APIPORTAL_USER_EDIT_GENERAL_ENABLE_LABEL'); ?>
                                </label>
                            </div>
                        </div>
                    </div><!-- .form-group -->
                <?php } ?>

                <?php if ($user->internal) { ?>
                    <div class="form-group">
                        <label style="font-size:14px;" class="col-sm-2 control-label">
                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_PASSWORD_LABEL'); ?>:
                        </label>

                        <div class="col-sm-4">
                            <?php if (!$isProfileAction) { ?>
                                <label class="btn btn-default reset-password-users-button" data-toggle="modal"
                                    data-target="#reset-password-users-details">
                                    <?php echo JText::_('COM_APIPORTAL_USER_EDIT_RESET_PASSWORD'); ?>
                                </label>
                            <?php } ?>
                            <label class="btn btn-default change-password-users-button" data-toggle="modal"
                                data-target="#change-password-users-details">
                                <?php echo JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_PASSWORD'); ?>
                            </label>
                        </div>
                    </div>
                <?php } ?>
                <!-- .form-group -->

                <?php if (!$isProfileAction) { ?>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">
                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_DETAILS_REGISTERED_LABEL'); ?>:
                        </label>

                        <div class="col-sm-4 form-control-static">
                        <!--   <script>displayDate(<?php echo ApiPortalHelper::cleanHtml($data['createdOn'], false, true); ?>, 'D MMM YYYY, HH:mm');</script> -->
                            <?php echo ApiPortalHelper::convertDateTime(ApiPortalHelper::cleanHtml($data['createdOn'], false, true),JText::_('COM_APIPORTAL_LOCAL_DATE_TIME_FORMAT')); ?>
                        </div>
                    </div><!-- .form-group -->
                <?php } ?>

                <?php if ($isProfileAction) { ?>
                    <?php
                    // Iterate through the form fieldsets listed in .../models/forms/user.xml and display each one.
                    foreach ($this->form->getFieldsets() as $group => $fieldset):
                        ?>
                        <?php $fields = $this->form->getFieldset($group);?>
                        <?php if (count($fields)): ?>
                        <?php foreach ($fields as $field):// Iterate through the fields in the set and display them.?>
                            <?php if (!$field->hidden) { ?>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">
                                        <?php echo $field->label; ?>
                                    </label>

                                    <div class="col-sm-4">
                                        <?php echo $field->input; ?>
                                    </div>
                                </div><!-- .form-group -->
                            <?php } ?>
                        <?php endforeach; ?>
                    <?php endif;?>
                    <?php
                    endforeach;
                    ?>
                <?php } ?>

            </fieldset>
        </div>
        <?php echo JHtml::_( 'form.token' ); ?>
        <input type='hidden' id='user-save-type' name='save-type'>
        <input type="hidden" name="userId" value="<?php echo $user->id; ?>"/>
        <?php if (!$isProfileAction) { ?>
            <input type="hidden" name="userdata[type]" value="<?= $user->type ?>"/>
        <?php } ?>
    </form> <!-- user-form -->

    <?php if (!$isProfileAction) { ?>
        <h2><?php echo JText::_('COM_APIPORTAL_USER_EDIT_APPPERMISSIONS_SECTION'); ?></h2>

        <!-- Sharing Area -->
        <div class="tab-pane fade in active" id="sharing">
            <?php if ($remainingOrgApps) { ?>
                <label class="btn btn-default add-shared-apps-button" data-toggle="modal"
                    data-target="#add-shared-apps-details">
                    <?php echo JText::_('COM_APIPORTAL_USER_EDIT_ADD_APPLICATION_LABEL'); ?>
                </label>
            <?php } else { ?>
                <label class="btn btn-default add-shared-apps-button" disabled>
                    <?php echo JText::_('COM_APIPORTAL_USER_EDIT_ADD_APPLICATION_LABEL'); ?>
                </label>
                <span
                    class="no-additional-users"><em><?php echo JText::_('COM_APIPORTAL_USER_EDIT_SHARING_NO_ADDITIONAL_APPS'); ?></em></span>
            <?php } ?>

            <?php if (!$userApplications) { ?>
                <p class="application-not-shared">
                    <em><?php echo JText::_('COM_APIPORTAL_USER_EDIT_SHARING_NO_SHARED_APPS'); ?></em>
                </p>
            <?php } else {
                $viewAppDetailsURL = JRoute::_('index.php?option=com_apiportal&view=application&layout=view&applicationId=%s', false);
                ?>
                <div id="shared-app-edit">
                    <?php foreach ($userApplications as $userApplication): ?>
                        <div class="col-sm-12 control-group row shared-app-edit">
                            <div class="col-sm-2">
                                <?php
                                if (empty($newSharedApps)) {
                                    $newSharedApps = array();
                                }
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
                            ?>
                            <div class="col-sm-2 shared-app-permission switch-toggle <?php echo $disabled; ?>">
                                <form id="shared-app-form" role="form" class="shared-app-form">
                                    <input id="view-<?php echo $userApplication->id; ?>" name="permission" type="radio"
                                        value="view"
                                        <?php echo($userApplication->permission->permission == 'view' ? 'checked' : ''); ?> <?php echo $disabled; ?>>
                                    <label for="view-<?php echo $userApplication->id; ?>">
                                        <?php
                                        $viewText = JText::_('COM_APIPORTAL_USER_EDIT_SHARING_SHARED_VIEW_ONLY');
                                        if (!$disabled) {
                                            $viewText = ucfirst($viewText);
                                        }
                                        ?>
                                        <?php echo $viewText; ?>
                                    </label>

                                    <input id="edit-<?php echo $userApplication->id; ?>" name="permission" type="radio"
                                        value="manage"
                                        <?php echo($userApplication->permission->permission == 'manage' ? 'checked' : ''); ?> <?php echo $disabled; ?>>
                                    <label for="edit-<?php echo $userApplication->id; ?>">
                                        <?php
                                        $editText = JText::_('COM_APIPORTAL_USER_EDIT_SHARING_SHARED_MANAGE');
                                        if (!$disabled) {
                                            $editText = ucfirst($editText);
                                        }
                                        ?>
                                        <?php echo $editText; ?>
                                    </label>

                                    <input type="hidden" name="applicationId" value="<?php echo $userApplication->id; ?>">
                                    <input type="hidden" name="permissionId"
                                        value="<?php echo $userApplication->permission->id; ?>">
                                    <input type="hidden" name="userId" value="<?php echo $user->id; ?>">
                                    <?php echo JHtml::_( 'form.token' ); ?>
                                </form>
                            </div>
                            <?php if ($user->id !== $userApplication->createdBy) { ?>
                                <div class="col-sm-2">
                                    <div class="shared-app-remove">
                                        <form class="confirm-remove-form" role="form">
                                            <button type="button" class="btn btn-link" data-toggle="modal"
                                                    data-target="#confirm-remove"
                                                    data-name="<?php echo $this->escape($userApplication->name); ?>"
                                                    data-object="<?php echo JText::_('COM_APIPORTAL_USER_APPLICATION_OBJECT'); ?>">
                                                <?php echo JText::_('COM_APIPORTAL_USER_EDIT_SHARING_ACTIONS_REMOVE'); ?>
                                            </button>
                                            <input type="hidden" name="applicationId"
                                                value="<?php echo $userApplication->id; ?>">
                                            <input type="hidden" name="permissionId"
                                                value="<?php echo $userApplication->permission->id; ?>">
                                            <input type="hidden" name="userName"
                                                value="<?php echo $this->escape($user->name); ?>">
                                            <input type="hidden" name="appName"
                                                value="<?php echo $this->escape($userApplication->name); ?>">
                                        </form>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php } ?>
        </div><!-- .area-pane -->
    <?php } ?>

    <div id="general">
        <div class="form-actions">
            <button type='submit' class='btn btn-primary' id='user-save-and-list-button'>
                <?php echo JText::_('COM_APIPORTAL_USER_EDIT_PRIMARY_ACTION_LABEL'); ?>
            </button>
            <button type='button' class='btn btn-default' id='user-cancel-button'>
                <?php echo JText::_('COM_APIPORTAL_USER_EDIT_SECONDARY_ACTION_LABEL'); ?>
            </button>
        </div>
    </div>
    </div>
    <!-- Add Shared Apps Dialog -->
    <div class="modal fade" id="add-shared-apps-details" tabindex="-1" role="dialog"
        aria-labelledby="add-shared-apps-dialog-title" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h3 class="modal-title" id="add-shared-apps-dialog-title">
                        <?php echo JText::_('COM_APIPORTAL_USER_EDIT_ADD_SHARED_USERS_DIALOG_TITLE'); ?>
                    </h3>
                </div>
                <!-- .modal-header -->

                <div class="modal-body">
                    <div class="col-sm-11 modal-body-header">
                        <div class="tablesorter-filter" role="search">
                            <label>
                                <input type="text" class="form-control" id="add-shared-apps-filter" aria-controls="dtable"
                                    data-column='all'
                                    placeholder="<?php echo JText::_('COM_APIPORTAL_USER_EDIT_ADD_SHARED_USERS_DIALOG_FILTER_USERS'); ?>">
                            </label>
                        </div>
                    </div>

                    <div class="col-sm-11 modal-body-content">
                        <?php if (!$remainingOrgApps) { ?>
                            <p class="no-additional-users">
                                <em><?php echo JText::_('COM_APIPORTAL_USER_EDIT_ADD_SHARED_USERS_DIALOG_NO_ADDITIONAL_USER'); ?></em>
                            </p>
                        <?php } else { ?>
                            <form id="add-shared-apps-form" class="form-horizontal" role="form">
                                <div class="form-group">
                                    <table class="table add-shared-apps" id="add-shared-apps-table">
                                        <!-- Tablesorter requires a thead and a th tag -->
                                        <thead data-sorter="false" style="display: none;">
                                        <tr>
                                            <th></th>
                                        </tr>
                                        </thead>

                                        <tbody aria-live="polite" aria-relevant="all">
                                        <?php foreach ($remainingOrgApps as $app): ?>
                                            <tr>
                                                <td>
                                                    <div class="col-sm-12">
                                                        <div class="col-sm-1">
                                                            <input type="checkbox" id="<?php echo $app->id; ?>"
                                                                name="apps[]" value="<?php echo $app->id; ?>">
                                                        </div>
                                                        <label class="col-sm-10 add-shared-user"
                                                            for="<?php echo $app->id; ?>"><?php echo $this->escape($app->name); ?></label>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- .form-group -->
                                <input type="hidden" name="userId" value="<?php echo $user->id; ?>">
                            </form>
                        <?php } ?>
                    </div>
                </div>
                <!-- .modal-body -->

                <div class="modal-footer">
                    <div class="dialog-actions">
                        <label type="button" class="btn btn-primary" id="add-shared-apps-submit-button">
                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_ADD_SHARED_USERS_DIALOG_PRIMARY_ACTION_LABEL'); ?>
                        </label>
                        <label type="button" class="btn btn-default" data-dismiss="modal">
                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_ADD_SHARED_USERS_DIALOG_SECONDARY_ACTION_LABEL'); ?>
                        </label>
                    </div>
                </div>
                <!-- /modal-footer -->

            </div>
            <!-- .modal-content -->
        </div>
        <!-- .modal-dialog -->
    </div><!-- .modal add-shared-apps-details-->

    <!-- Change user password dialog -->
    <div class="modal fade" id="change-password-users-details" tabindex="-1" role="dialog"
        aria-labelledby="change-password-users-dialog-title" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h3 class="modal-title" id="change-password-users-dialog-title">
                        <?php echo JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_PASSWORD_DIALOG_TITLE'); ?>
                    </h3>
                </div>
                <!-- .modal-header -->

                <div class="modal-body" style="overflow-x: hidden;">
                    <div class="col-sm-11 modal-body-content">
                        <form id="change-password-users-form" class="form-horizontal" role="form" method="post">
                            <input type="hidden" name="userdata[id]" value="<?php echo $user->id; ?>"/>

                            <?php
                            // If it's profile action we need current password confirmation
                                if ($isProfileAction) {
                                    ?>
                                    <div class="form-group" id="current-password">
                                        <label class="col-sm-4 control-label" for="password-current">
                                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_PASSWORD_DIALOG_CURRENT_PASSWORD_LABEL'); ?>
                                            :
                                        </label>

                                        <div class="col-sm-7" id="fld_pwd2">
                                            <input type="password" class="form-control" id="password-current"
                                                name="userdata[password_current]"
                                                autocomplete="off">

                                            <div class="validation-message"></div>
                                        </div>
                                    </div>
                                    <!-- .form-group -->
                                    <?php
                                }
                            ?>

                            <div class="form-group" id="setpassword">
                                <label class="col-sm-4 control-label" for="password1" id="lbl_pwd">
                                    <?php echo JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_PASSWORD_DIALOG_NEW_PASSWORD_LABEL'); ?>
                                    :
                                </label>

                                <div class="col-sm-7" id="fld_pwd2">
                                    <input type="password" class="form-control" id="password1" name="userdata[password1]"
                                        autocomplete="off">

                                    <div class="validation-message"></div>
                                </div>
                            </div>
                            <!-- .form-group -->

                            <div class="form-group" id="confirmpassword">
                                <label class="col-sm-4 control-label" for="password2" id="lbl_pwd2">
                                    <?php echo JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_PASSWORD_DIALOG_CONFIRM_PASSWORD_LABEL'); ?>
                                    :
                                </label>

                                <div class="col-sm-7" id="fld_pwd2">
                                    <input type="password" class="form-control" id="password2" name="userdata[password2]"
                                        autocomplete="off">

                                    <div class="validation-message"></div>
                                </div>
                            </div>
                            <!-- .form-group -->

                            <?php echo JHtml::_( 'form.token' ); ?>
                        </form>
                    </div>

                </div>
                <!-- .modal-body -->

                <div class="modal-footer">
                    <div class="dialog-actions">
                        <button type="button" class="btn btn-primary" id="change-password-users-submit-button">
                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_PASSWORD_DIALOG_PRIMARY_ACTION_LABEL'); ?>
                        </button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_PASSWORD_DIALOG_SECONDARY_ACTION_LABEL'); ?>
                        </button>
                    </div>
                </div>
                <!-- /modal-footer -->

            </div>
            <!-- .modal-content -->
        </div>
        <!-- .modal-dialog -->
    </div><!-- .modal change-password-users-details-->

    <!-- Reset user password dialog -->
    <div class="modal fade" id="reset-password-users-details" tabindex="-1" role="dialog"
        aria-labelledby="reset-password-users-dialog-title" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <!-- .modal-header -->
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h3 class="modal-title" id="reset-password-users-dialog-title">
                        <?php echo JText::_('COM_APIPORTAL_USER_EDIT_RESET_PASSWORD_DIALOG_TITLE'); ?>
                    </h3>
                </div>
                <!-- /modal-header -->

                <!-- .modal-body -->
                <!-- /modal-body -->

                <!-- .modal-footer -->
                <div class="modal-footer">
                    <div class="dialog-actions">
                        <button type="button" class="btn btn-primary" id="reset-password-users-submit-button">
                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_RESET_PASSWORD_DIALOG_PRIMARY_ACTION_LABEL'); ?>
                        </button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_RESET_PASSWORD_DIALOG_SECONDARY_ACTION_LABEL'); ?>
                        </button>
                    </div>
                </div>
                <!-- /modal-footer -->

            </div>
            <!-- .modal-content -->
        </div>
        <!-- .modal-dialog -->
    </div><!-- .modal reset-password-users-details-->

    <!-- Confirm Remove Dialog -->
    <div class="modal fade" id="confirm-remove" tabindex="-1" role="dialog" aria-labelledby="confirm-remove-dialog-title"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h3 class="modal-title" id="confirm-remove-dialog-title">
                    </h3>
                </div>
                <!-- .modal-header -->

                <div class="modal-body">
                    <p id="confirm-remove-dialog-text"></p>
                </div>
                <!-- .modal-body -->

                <div class="modal-footer">
                    <div class="dialog-actions">
                        <button type="submit" class="btn btn-primary" id="confirm-remove-button"></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_CONFIRM_REMOVE_DIALOG_SECONDARY_ACTION_LABEL'); ?>
                        </button>
                    </div>
                </div>
                <!-- /modal-footer -->

            </div>
            <!-- .modal-content -->
        </div>
        <!-- .modal-dialog -->
    </div><!-- .modal -->

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
                            <?php echo JText::_('COM_APIPORTAL_USER_EDIT_CONFIRM_DELETE_DIALOG_SECONDARY_ACTION_LABEL'); ?>
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

    
        // Validates email values
        function validateEmail(email) {
            var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            re = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
            return email && re.test(email);
        }

        //
        var userFormOnEnter;

        // jQuery is loaded in 'noconflict' mode.
        jQuery(document).ready(function ($) {
            
            
            $("#telefone").mask("(00)0000-0000");
            $("#cpf").mask("000.000.000-00");
            $("#phone").mask("(00)0000Z-0000", {translation:  {'Z': {pattern: /[0-9]/, optional: true}}});



            // Timezone selector
            jQuery('#jform_params_timezone').val("<?= isset(json_decode(JFactory::getUser()->get('params'))->timezone) ? $this->escape(json_decode(JFactory::getUser()->get('params'))->timezone) : null;?>");
            jQuery('#jform_params_timezone').chosen({
                "disable_search_threshold": 10,
                "allow_single_deselect": true,
                "placeholder_text_multiple": "<?= JText::_('COM_APIPORTAL_APITEST_SELECT_SOME_OPTIONS') ?>",
                "placeholder_text_single": "<?= JText::_('COM_APIPORTAL_APITEST_SELECT_OPTION') ?>",
                "no_results_text": "<?= JText::_('COM_APIPORTAL_APITEST_NO_RESULT_MATCH') ?>"
            });

            $('#user-form').validate({
                ignore: [],
                rules: {
                    'userdata[telefone]': {
                        telefone: true,
                        required: false,
                        maxlength: '13'
                    },
                
                    'userdata[cpf]': {
                        cpf: true,
                        required: false,
                        maxlength: '14'
                    },
                    'userdata[phone]': {
                        phone: true,
                        required: false
                    },

                    'userdata[loginName]': {
                        required: true,
                        maxlength: <?php echo $maxFieldLen; ?>
                    },
                    'userdata[name]': {
                        required: true,
                        maxlength: <?php echo $maxFieldLen; ?>
                    },
                    'userdata[email]': {
                        email: true,
                        required: true,
                        maxlength: <?php echo $maxFieldLen; ?>
                    },
                    
                    'userdata[organizationId]': {
                        required: true,
                        maxlength: <?php echo $maxTextAreaLen; ?>
                    },
                    'userdata[role]': {
                        required: true,
                        maxlength: <?php echo $maxFieldLen; ?>
                    },
                    image: {
                        required: false,
                        accept: 'image/*',
                        maxImageSize: 1048576
                    }
                },
                messages: {
                    'userdata[loginName]': {
                        required: '<?php echo $requiredMsg; ?>',
                        maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                    },
                    cpf: { 
                        cpf: 'CPF inválido'
                    },

                    
                    'userdata[name]': {
                        required: '<?php echo $requiredMsg; ?>',
                        maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                    },
                    'userdata[email]': {
                        email: '<?php echo $invalidEmailMsg; ?>',
                        required: '<?php echo $requiredMsg; ?>',
                        maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                    },
                    'userdata[organizationId]': {
                        required: '<?php echo $requiredMsg; ?>',
                        maxlength: '<?php echo sprintf($maxLengthMsg, $maxTextAreaLen); ?>'
                    },
                    'userdata[role]': {
                        required: '<?php echo $requiredMsg; ?>',
                        maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                    },
                    image: {
                        accept: '<?php echo JText::_('COM_APIPORTAL_IMAGE_ACCEPT_TYPES'); ?>',
                        maxImageSize: '<?php echo JText::_('COM_APIPORTAL_IMAGE_MAX_SIZE'); ?>'
                    }
                },
                onkeyup: false,
                errorPlacement: function (label, elem) {
                    elem.closest('div').parent().find('.validation-message').append(label);
                }
            });

    $.validator.addMethod('phone', function (value, element) {
        value = value.replace(/\D/g,'');
        value = value.replace("(","");
        value = value.replace(")", "");
        value = value.replace("-", "");
        value = value.replace(" ", "").trim();
        if (value == '0000000000') {
            return (this.optional(element) || false);
        } else if (value == '00000000000') {
            return (this.optional(element) || false);
        } 
        if (["00", "01", "02", "03", , "04", , "05", , "06", , "07", , "08", "09", "10"].indexOf(value.substring(0, 2)) != -1) {
            return (this.optional(element) || false);
        }
        if (value.length < 10 || value.length > 11) {
            return (this.optional(element) || false);
        }
        if (["6", "7", "8", "9"].indexOf(value.substring(2, 3)) == -1) {
            return (this.optional(element) || false);
        }
        return (this.optional(element) || true);
    }, 'Informe um celular válido'); 


    $.validator.addMethod('telefone', function (value, element) {
            value = value.replace("(", "");
            value = value.replace(")", "");
            value = value.replace("-", "");
            value = value.replace(" ", "").trim();
            if (value == '0000000000') {
                return (this.optional(element) || false);
            } else if (value == '00000000000') {
                return (this.optional(element) || false);
            }
            if (["00", "01", "02", "03", , "04", , "05", , "06", , "07", , "08", "09", "10"].indexOf(value.substring(0, 2)) != -1) {
                return (this.optional(element) || false);
            }
            if (value.length < 10 || value.length > 11) {
                return (this.optional(element) || false);
            }
            if (["1", "2", "3", "4","5"].indexOf(value.substring(2, 3)) == -1) {
                return (this.optional(element) || false);
            }
            return (this.optional(element) || true);
        }, 'Informe um telefone válido'); 

    $.validator.addMethod("cpf", function(value, element) {
        value = jQuery.trim(value);

        value = value.replace('.','');
        value = value.replace('.','');
        cpf = value.replace('-','');
        while(cpf.length < 11) cpf = "0"+ cpf;
        var expReg = /^0+$|^1+$|^2+$|^3+$|^4+$|^5+$|^6+$|^7+$|^8+$|^9+$/;
        var a = [];
        var b = new Number;
        var c = 11;
        for (i=0; i<11; i++){
            a[i] = cpf.charAt(i);
            if (i < 9) b += (a[i] * --c);
        }
        if ((x = b % 11) < 2) { a[9] = 0 } else { a[9] = 11-x }
        b = 0;
        c = 11;
        for (y=0; y<10; y++) b += (a[y] * c--);
        if ((x = b % 11) < 2) { a[10] = 0; } else { a[10] = 11-x; }

        var retorno = true;
        if ((cpf.charAt(9) != a[9]) || (cpf.charAt(10) != a[10]) || cpf.match(expReg)) retorno = false;

        return this.optional(element) || retorno;

    },'cpf invalido');
            
            

            // Add a rule for the loginName/email to match the regex from API Manager
            $.validator.addMethod("regexLoginName", function (value, element) {
                // The test() method doesn't work properly here so use match instead
                // Use addslashes to prevent XSS
                var regExPatt = "<?= addslashes($this->loginNameRegex) ?>";
                // If we have regExPatt continue with the validation
                if (regExPatt) {
                    var re = value.match(regExPatt);
                    // return the result re[0] is the matched result and the re.input is the initial inpit
                    return this.optional(element) || re[0] == re.input;
                }
                // In case we don't have the pattern don't apply the rule.
                return true;
            },  "<?= JText::_('COM_APIPORTAL_LOGINNAME_DONT_MATCH'); ?>");

            // Add the rule to the element
            $('input[name="userdata[loginName]"]').rules("add", {
                regexLoginName: true
            });

            $('input[name="userdata[cpf]"]').rules("add", {
                cpf: true
            });
            
            $('input[name="userdata[telefone]"]').rules("add", {
                telefone: true
            });

            $('input[name="userdata[phone]"]').rules("add", {
                phone: true
            });

            // Validate image file size after every change.
            $('.fileinput').on('change.bs.fileinput', validateImage);

            // Save state of the user form when the page was loaded
            userFormOnEnter = $('#user-form').serializeAll();

            // If we are leaving the page, make sure there are no unsaved changes
            $(window).on('beforeunload', function (e) {
                userFormOnLeave = $('#user-form').serializeAll();

                if (userFormOnEnter != userFormOnLeave) {
                    var warningText = "<?php echo sprintf(
                        JText::_('COM_APIPORTAL_USER_UNSAVED_CHANGES_DIALOG_TEXT'),
                        JText::_('COM_APIPORTAL_USER_UNSAVED_CHANGES_DIALOG_LEAVING_PAGE')); ?>";

                    return warningText;
                }
            });

            // Submit user form data
            $('#user-save-and-list-button').on('click', function (e) {
                if ($('#user-form').valid()) {
                    $('#user-save-type').val('save-and-list');

                    // Prevent the 'beforeunload' handler from stopping the submit
                    userFormOnEnter = $('#user-form').serializeAll();
                }
                $('#user-form').submit();
            });

            // Cancel user editing and return to 'list'
            $('#user-cancel-button').on('click', function (e) {
                // Prevent the 'beforeunload' handler from stopping the cancel
                resetUserForm();

                document.location.href = '<?php
                    if ($isProfileAction) {
                        echo $userProfileURL;
                    } else {
                        echo $userListURL;
                    }
                    ?>';
            });

            // Sharing dialog filter
            if ($('#add-shared-apps-table')) {
                $('#add-shared-apps-table').tablesorter({
                    widgets: ['filter'],
                    widgetOptions: {
                        filter_columnFilters: false,
                        filter_external: '#add-shared-apps-filter'
                    }
                });
            }

            // Submit add shared Apps form data.
            $('#add-shared-apps-submit-button').on('click', function (e) {
                var userForm = $('#user-form');
                userForm.empty();

                $("#add-shared-apps-form").find('input[name="apps[]"]').each(function() {
                    if ($(this).attr("checked")) {
                        userForm.append('<input type="hidden" name="apps['+$(this).val()+']" value="'+$(this).val()+'"/>');
                    }
                });
                userForm.append('<?= JHtml::_( 'form.token' ); ?>');
                userForm.append('<input type="hidden" name="add-shared-post" />');
                // Prevent the 'beforeunload' handler from stopping the submit
                userFormOnEnter = userForm.serializeAll();

                // Submit Shared Apps
                userForm.attr("action", "<?php echo $addSharedAppsURL; ?>");
                userForm.submit();
            });

            // Submit shared Apps permission change (AJAX).
            $('#shared-app-edit').find($('input[type=radio]')).on('click', function (e) {
                // var spinner = new Spinner(spinOpts).spin($('body')[0]);

                // TODO: Handle AJAX errors
                $.post('<?php echo $updateSharedAppURL; ?>', $(this).closest('#shared-app-form').serialize(), function (response) {
                    // spinner.stop();
                });
            });


            // Change password form validation
            $('#change-password-users-form').validate({
                ignore: [],
                rules: {
                    'userdata[password_current]': {
                        required: true,
                        maxlength: <?php echo $maxFieldLen; ?>,
                        minlength: <?php echo $config->minimumPasswordLength; ?>
                    },
                    'userdata[password1]': {
                        required: true,
                        maxlength: <?php echo $maxFieldLen; ?>,
                        minlength: <?php echo $config->minimumPasswordLength; ?>
                    },
                    'userdata[password2]': {
                        required: true,
                        maxlength: <?php echo $maxFieldLen; ?>,
                        minlength: <?php echo $config->minimumPasswordLength; ?>,
                        equalTo: "#password1"
                    }
                },
                messages: {
                    'userdata[password_current]': {
                        required: '<?php echo $requiredMsg; ?>',
                        minlength: '<?php echo sprintf($minLengthMsg, $config->minimumPasswordLength); ?>',
                        maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                    },
                    'userdata[password1]': {
                        required: '<?php echo $requiredMsg; ?>',
                        minlength: '<?php echo sprintf($minLengthMsg, $config->minimumPasswordLength); ?>',
                        maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                    },
                    'userdata[password2]': {
                        required: '<?php echo $requiredMsg; ?>',
                        minlength: '<?php echo sprintf($minLengthMsg, $config->minimumPasswordLength); ?>',
                        maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>',
                        equalTo: '<?php echo JText::_('COM_APIPORTAL_PASSWORDS_DONT_MATCH'); ?>'
                    }
                },
                onkeyup: false,
                errorPlacement: function (label, elem) {
                    elem.closest('div').parent().find('.validation-message').append(label);
                }
            });

            // Validate image file size after every change.
            $('.fileinput').on('change.bs.fileinput', validateImage);

            // Submit change password.
            $('#change-password-users-submit-button').on('click', function (e) {
                var changeForm = $('#change-password-users-form');
                if (changeForm.valid()) {
                    // Disable password saving prompt
                    changeForm.attr('autocomplete', 'off');

                    // Prevent the 'beforeunload' handler from stopping the submit
                    var userFormOnEnter = changeForm.serializeAll();

                    // Submit Change Password
                    changeForm.attr("action", "<?= $userChangePasswordURL ?>");
                    changeForm.submit();
                }
                return false;
            });

            // Submit reset password.
            $('#reset-password-users-submit-button').on('click', function (e) {
                // Prevent the 'beforeunload' handler from stopping the submit
                var userForm = $('#user-form');
                userForm.empty();
                userFormOnEnter = userForm.serializeAll();
                // Submit Reset Password
                userForm.append('<?= JHtml::_( 'form.token' ); ?>');
                userForm.append('<input type="hidden" name="reset-pass" value="1" />');
                userForm.attr("action", "<?= $userResetPasswordURL ?>");
                userForm.submit();
                return false;
            });

            // Set remove confirmation text and form.
            $('#confirm-remove').on('show.bs.modal', function (e) {
                var relatedTarget = e.relatedTarget ? e.relatedTarget : e.fromElement;

                var removeTitle = "<?php echo JText::_('COM_APIPORTAL_USER_EDIT_CONFIRM_REMOVE_DIALOG_TITLE'); ?>";
                var removeHtml = "<?php echo JText::_('COM_APIPORTAL_USER_EDIT_CONFIRM_REMOVE_DIALOG_TEXT'); ?>";
                var removeButton = "<?php echo JText::_('COM_APIPORTAL_USER_EDIT_CONFIRM_REMOVE_DIALOG_PRIMARY_ACTION_LABEL'); ?>";

                $('#confirm-remove-dialog-title').text(sprintf(removeTitle, $(relatedTarget).data('object')));
                $('#confirm-remove-dialog-text').html(sprintf(removeHtml, escapeHTML($(relatedTarget).data('name'))));
                $('#confirm-remove-button').text(sprintf(removeButton, $(relatedTarget).data('object')));
                $('#confirm-remove-button').data('form', $(relatedTarget).closest('form'));
            });

            // add validators to Remove app sharing fomrs
            tmp = $('.confirm-remove-form, .shared-app-form');
            for (var key = 0; key < tmp.length; key++) {
                $(tmp[key]).validate({ignore: [], rules: {}});
            }

            // Submit confirm remove form.
            $('#confirm-remove-button').on('click', function (e) {
                var userForm = $('#user-form');
                // Copy targeted form fields and values to the #user-form
                userForm.append('<input type="hidden" name="applicationId"/>');
                userForm.append('<input type="hidden" name="permissionId"/>');
                userForm.append('<input type="hidden" name="userName"/>');
                userForm.append('<input type="hidden" name="appName"/>');

                userForm.find('input[name="applicationId"]').val($(this).data('form').find('input[name="applicationId"]').val());
                userForm.find('input[name="permissionId"]').val($(this).data('form').find('input[name="permissionId"]').val());
                userForm.find('input[name="userName"]').val($(this).data('form').find('input[name="userName"]').val());
                userForm.find('input[name="appName"]').val($(this).data('form').find('input[name="appName"]').val());
                userForm.append('<?= JHtml::_( 'form.token' ); ?>');

                // Prevent the 'beforeunload' handler from stopping the submit
                userFormOnEnter = userForm.serializeAll();

                // Submit Shared Apps
                userForm.attr("action", "<?php echo htmlentities($removeSharedAppURL); ?>");
                userForm.submit();
            });

            // Set delete confirmation text and form.
            $('#confirm-delete').on('show.bs.modal', function (e) {
                var relatedTarget = e.relatedTarget ? e.relatedTarget : e.fromElement;

                var deleteTitle = "<?php echo JText::_('COM_APIPORTAL_USER_EDIT_CONFIRM_DELETE_DIALOG_TITLE'); ?>";
                var deleteHtml = "<?php echo JText::_('COM_APIPORTAL_USER_EDIT_CONFIRM_DELETE_DIALOG_TEXT'); ?>";
                var deleteButton = "<?php echo JText::_('COM_APIPORTAL_USER_EDIT_CONFIRM_DELETE_DIALOG_PRIMARY_ACTION_LABEL'); ?>";

                $('#confirm-delete-dialog-title').text(sprintf(deleteTitle, $(relatedTarget).data('object')));
                $('#confirm-delete-dialog-text').html(sprintf(deleteHtml, escapeHTML($(relatedTarget).data('name'))));
                $('#confirm-delete-button').text(sprintf(deleteButton, $(relatedTarget).data('object')));
                $('#confirm-delete-button').data('form', $(relatedTarget).closest('form'));
            });

            // Submit confirm delete form.
            <?php if (!$isProfileAction) { ?>
            $('#confirm-delete-button').on('click', function (e) {
                $(this).data('form').submit();
            });
            <?php } ?>

            <?php if ($data['loginName'] && !$isProfileAction) { ?>
            $('#loginName').on('change', function (e) {
                if ($(this).val() != "" && $(this).val() != "<?php echo $this->escape($data['loginName']);?>") {
                    $(this).parent().find('.value-changed-message').empty();
                    $(this).parent().find('.value-changed-message').append("<label class='warning' for='loginName'><?= JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_LOGIN_NAME') ?></label>");
                } else {
                    $(this).parent().find('.value-changed-message').empty();
                }
            });
            <?php } ?>

            /**
             * This is in case when email and user ID are sync and user is internal.
             * Changes in email field should appear in user ID, too.
             */
            <?php if ($data['loginName'] == $data['email'] && $user->type == APIPortalModelUser::USER_TYPE_INTERNAL) { ?>
            $('#email').on('change', function () {
                if ($(this).val() != "") {
                    var loginName = $('#loginName');
                    loginName.val($(this).val().toString());
                    loginName.parent().find('.value-changed-message').empty();
                    <?php if (!$isProfileAction) { ?>
                    loginName.parent().find('.value-changed-message').append("<label class='warning' for='loginName'><?= JText::_('COM_APIPORTAL_USER_EDIT_CHANGE_LOGIN_NAME') ?></label>");
                    <?php } ?>
                    if ($(this).val() == "<?= $this->escape($data['email']) ?>") {
                        loginName.parent().find('.value-changed-message').empty();
                    }
                }
            });
            <?php


            }
            ?>

        });

    </script>
