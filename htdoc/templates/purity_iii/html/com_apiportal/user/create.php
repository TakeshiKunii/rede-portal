<?php
defined('_JEXEC') or die('Restricted access');

$userListURL = JRoute::_('index.php?option=com_apiportal&view=users', false);

$error = false;
if (!isset($this->item)) {
    $error = true;
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
        'enabled' => 'true',
        'loginName' => '',
        'name' => '',
        'email' => '',
        'phone' => '',
        'role' => 'user',
        'organizationId' => ApiPortalHelper::getActiveOrganizationId()
    );
    $formData =  $app->getUserState(ApiPortalSessionVariables::USER_CREATE_DATA, null);
    if ($formData) {
        $data = array_merge($data, $formData);
    }
    $app->setUserState(ApiPortalSessionVariables::USER_CREATE_DATA, null);

    $organizations = isset($this->item->organizations) ? $this->item->organizations : array();
    $config = isset($this->item->config) ? $this->item->config : new stdClass();

    if (ApiPortalHelper::hasAdminRole() && count($organizations)  > 0) {
        /*
         * If we have more than 1 organization, then this can only be the Super Admin ('apiadmin') and we
         * will need to provide a dropdown list to select which organization to create the user in.
         */
        $organizationId = ApiPortalHelper::cleanHtml($data['organizationId'], false, true);
    } else {
        /*
         * Otherwise just display the organization name. The user will
         * be created in the current user's organization by the createApp task.
         */
        $session = JFactory::getSession();
        $organizationId = ApiPortalHelper::getActiveOrganizationId();
        $organizationName = ApiPortalHelper::getOrganizationName($organizationId, $organizations);
    }

    // Password apporach
    $isManuallyPassword = array_key_exists ('passwordapproach', $data) && $data['passwordapproach'] === 'manually';
    $isRanodmPassword = !$isManuallyPassword;// && (!array_key_exists ('passwordapproach', $data) || $data['passwordapproach'] === 'random' || $data['passwordapproach'] === '');

    // These are all actions (tasks), don't use JRoute, but include Itemid
    $itemId = JRequest::getVar('Itemid', 0, 'INT');
    $itemId = ApiPortalHelper::cleanHtml($itemId, false, true);
    $createUserURL = JURI::base(false) . 'index.php?option=com_apiportal&task=user.createUser&Itemid=' . $itemId;

    $addImageURL = 'components/com_apiportal/assets/img/add_image.png';

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
}
?>

<?php if ($error) { return; } ?>

<div class="head">
  <div class="btn-toolbar auto">
	<a href="<?php echo $userListURL; ?>" class="btn btn-default icon arrow-left">
		<?php echo JText::_('COM_APIPORTAL_USERS_TITLE'); ?>
	</a>
  </div>
  <h1 class="auto"><?php echo JText::_('COM_APIPORTAL_USER_CREATE_TITLE'); ?></h1>
  <p class="auto"><em></em></p>
</div>
<div class="body auto">
<h2><?php echo JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_SECTION'); ?></h2>
<div id="general">
    <form id="user-form" class="form-horizontal" enctype="multipart/form-data" method="post"
          action="<?php echo $createUserURL; ?>" novalidate>
        <fieldset>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="name">
                    <?php echo JText::_('COM_APIPORTAL_USER_VIEW_LOGIN_NAME_LABEL'); ?>*:
                </label>
                <div class="col-sm-4">
                    <input type="text" class="form-control" id="login-name" name="userdata[loginName]" value="<?php echo ApiPortalHelper::cleanHtml($data['loginName'], false, true); ?>">
                    <div class="validation-message"></div>
                </div>
            </div><!-- .form-group -->

            <div class="form-group">
                <label class="col-sm-2 control-label" for="name">
                    <?php echo JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_NAME_LABEL'); ?>:
                </label>
                <div class="col-sm-4">
                    <input type="text" class="form-control" id="name" name="userdata[name]" value="<?php echo ApiPortalHelper::cleanHtml($data['name'], false, true); ?>">
                    <div class="validation-message"></div>
                </div>
            </div><!-- .form-group -->

            <div class="form-group">
                <label class="col-sm-2 control-label" for="email">
                    <?php echo JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_EMAIL_LABEL'); ?>:
                </label>
                <div class="col-sm-4">
                    <input type="email" class="form-control" id="email" name="userdata[email]" value="<?php echo ApiPortalHelper::cleanHtml($data['email'], false, true); ?>">
                    <div class="validation-message"></div>
                </div>
            </div><!-- .form-group -->

            <div class="form-group">
                <label class="col-sm-2 control-label" for="organization">
                    <?php echo JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_ORGANIZATION_LABEL'); ?>:
                </label>
                <?php if (ApiPortalHelper::hasAdminRole() && count($organizations) > 0) { ?>
                    <div class="col-sm-4">
                        <select class="form-control select-organization" id="organization" name="userdata[organizationId]"
                                data-rule-required="true" data-msg-required="<?php echo $requiredMsg; ?>">
                            <option value=''>
                                <?php echo JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_SELECT_ORGANIZATION_LABEL'); ?>
                            </option>
                            <?php foreach ($organizations as $organization): ?>
                                <option value='<?php echo $organization->id; ?>' <?php echo ($organization->id === $organizationId ? 'selected' : ''); ?>>
                                    <?php echo ApiPortalHelper::cleanHtml($organization->name, false, true); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="validation-message"></div>
                    </div>
                <?php } else { ?>
                    <p class="col-sm-4 form-control-static" id="organization" name="organization"><?php echo ApiPortalHelper::cleanHtml($organizationName, false, true); ?></p>
                <?php } ?>
            </div><!-- .form-group -->

            <div class="form-group">
                <label class="col-sm-2 control-label" for="role">
                    <?php echo JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_ROLE_LABEL'); ?>:
                </label>
                <div class="col-sm-4">
                    <select class="form-control select-organization" id="role" name="userdata[role]"
                            data-rule-required="true" data-msg-required="<?php echo $requiredMsg; ?>">
                        <?php if (ApiPortalHelper::hasAdminRole()) { ?>
                            <option value='admin'    <?php echo ($data['role'] === 'admin'    ? 'selected' : ''); ?>><?php echo JText::_('COM_APIPORTAL_USER_ROLE_ADMIN'); ?></option>
                        <?php } ?>
                        <option value='oadmin' <?php echo ($data['role'] === 'oadmin' ? 'selected' : ''); ?>><?php echo JText::_('COM_APIPORTAL_USER_ROLE_ORGADMIN'); ?></option>
                        <option value='user'     <?php echo ($data['role'] !== 'admin' &&  $data['role'] !== 'oadmin'  ? 'selected' : ''); ?>><?php echo JText::_('COM_APIPORTAL_USER_ROLE_APPDEVELOPER'); ?></option>
                    </select>
                    <div class="validation-message"></div>
                </div>
            </div><!-- .form-group -->

            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-4">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="userdata[enabled]" value='true' <?php echo ($data['enabled'] !== 'false' ? 'checked' : ''); ?>>
                            <?php echo JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_ENABLE_LABEL'); ?>
                        </label>
                    </div>
                </div>
            </div><!-- .form-group -->

            <div class="form-group">
                <label class="col-sm-2 control-label">
                    <?php echo JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_PASSWORD_LABEL'); ?>:
                </label>
                <div class="col-sm-4">
                    <div class="radio">
                        <label><input type="radio" name="userdata[passwordapproach]" id="pwd_random" value="random" <?php echo $isRanodmPassword?'checked':'';?>><?php echo JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_PASSWORD_EMAIL_RANDOM_PASSWORD'); ?></label>
                    </div>
                    <div class="radio">
                        <label><input type="radio" name="userdata[passwordapproach]" id="pwd_manually" value="manually" <?php echo $isManuallyPassword?'checked':'';?>><?php echo JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_PASSWORD_SET_MANUALLY'); ?></label>
                    </div>
                    <div class="validation-message"></div>
                </div>
            </div><!-- .form-group -->

            <div class="form-group" id="setpassword">
                <label class="col-sm-2 control-label" for="password1" id="lbl_pwd" <?php echo $isRanodmPassword?'style="display:none"':'';?>>
                    <?php echo JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_SET_PASSWORD_LABEL'); ?>:
                </label>
                <div class="col-sm-4" id="fld_pwd2" <?php echo $isRanodmPassword?'style="display:none"':'';?>>
                    <input type="password" class="form-control" id="password1" name="userdata[password1]" />
                    <div class="validation-message"></div>
                </div>
            </div><!-- .form-group -->

            <div class="form-group" id="confirmpassword">
                <label class="col-sm-2 control-label" for="password2" id="lbl_pwd2" <?php echo $isRanodmPassword?'style="display:none"':'';?>>
                    <?php echo JText::_('COM_APIPORTAL_USER_CREATE_GENERAL_CONFIRM_PASSWORD_LABEL'); ?>:
                </label>
                <div class="col-sm-4" id="fld_pwd2" <?php echo $isRanodmPassword?'style="display:none"':'';?>>
                    <input type="password" class="form-control" id="password2" name="userdata[password2]" />
                    <div class="validation-message"></div>
                </div>
            </div><!-- .form-group -->

        </fieldset>

        <input type='hidden' id='user-save-type' name='save-type'>

        <div class="form-actions">
            <button type='button' class='btn btn-primary' id='user-save-and-list-button'>
                <?php echo JText::_('COM_APIPORTAL_USER_CREATE_PRIMARY_ACTION_LABEL'); ?>
            </button>
            <button type='button' class='btn btn-default' id='user-cancel-button'>
                <?php echo JText::_('COM_APIPORTAL_USER_CREATE_SECONDARY_ACTION_LABEL'); ?>
            </button>
        </div>

        <?php echo JHtml::_( 'form.token' ); ?>
    </form>
</div>
</div>
<script type="text/javascript">
    var userFormOnEnter;

    // jQuery is loaded in 'noconflict' mode.
    jQuery(document).ready(function($) {

        $('#user-form').validate({
            ignore: [],
            rules: {
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
                'userdata[password1]': {
                    required: {
                        depends: function(element) {
                            return isPasswordRequired();
                        }
                    },
                    minlength: {
                        param: <?php echo $config->minimumPasswordLength; ?>,
                        depends: function(element) {
                            return isPasswordRequired();
                        }
                    },
                    maxlength: {
                        param: <?php echo $maxFieldLen; ?>,
                        depends: function(element) {
                            return isPasswordRequired();
                        }
                    }
                },
                'userdata[password2]': {
                    required: {
                        depends: function(element) {
                            return isPasswordRequired();
                        }
                    },
                    minlength: {
                        param: <?php echo $config->minimumPasswordLength; ?>,
                        depends: function(element) {
                            return isPasswordRequired();
                        }
                    },
                    maxlength: {
                        param: <?php echo $maxFieldLen; ?>,
                        depends: function(element) {
                            return isPasswordRequired();
                        }
                    },
                    equalTo : {
                        param: "#password1",
                        depends: function(element) {
                            return isPasswordRequired();
                        }
                    }
                }
            },
            messages: {
                'userdata[loginName]': {
                    required: '<?php echo $requiredMsg; ?>',
                    maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
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
            errorPlacement: function(label, elem) {
                elem.closest('div').parent().find('.validation-message').append(label);
            }
        });

        // Save state of the user form when the page was loaded
        userFormOnEnter = $('#user-form').serializeAll();

        // If we are leaving the page, make sure there are no unsaved changes
        $(window).on('beforeunload', function(e) {
            userFormOnLeave = $('#user-form').serializeAll();

            if (userFormOnEnter != userFormOnLeave) {
                var warningText = "<?php echo sprintf(
                    JText::_('COM_APIPORTAL_USER_UNSAVED_CHANGES_DIALOG_TEXT'),
                    JText::_('COM_APIPORTAL_USER_UNSAVED_CHANGES_DIALOG_LEAVING_PAGE')); ?>";

                return warningText;
            }
        });

        // Submit user form data
        $('#user-save-and-list-button').on('click', function(e) {
            if ($('#user-form').valid()) {
                $('#user-save-type').val('save-and-list');

                // Prevent the 'beforeunload' handler from stopping the submit
                userFormOnEnter = $('#user-form').serializeAll();
            }
            $('#user-form').submit();
        });

        // Cancel user editing and return to 'list'
        $('#user-cancel-button').on('click', function(e) {
            // Prevent the 'beforeunload' handler from stopping the cancel
            resetUserForm();

            document.location.href = '<?php echo $userListURL; ?>';
        });

        // Initialize password related controls
        var rbManuallPassword = $('input[type=radio][value=manually]');
        // Show/Hide Password fields
        function isPasswordRequired() {
            return (rbManuallPassword.length>0 && rbManuallPassword[0].checked);
        }
        $('#pwd_random, #pwd_manually').on('change', function(e) {
            if (isPasswordRequired()) {
                $('#setpassword, #confirmpassword').children().show();
            } else {
                $('#setpassword, #confirmpassword').children().hide();
            }
        });

    });
</script>
