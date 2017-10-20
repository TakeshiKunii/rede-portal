<?php
    defined('_JEXEC') or die('Restricted access');

//conexão com banco de dados para mudar o valor do orgcode

$db = JFactory::getDbo();
$query = $db->getQuery(true);
$query->select($db->quoteName('orgCode'));
$query->from($db->quoteName('#__parameters'));

//set query
$db->setQuery($query);

//return query data
$results = $db->loadObjectList();
$json= json_encode($results, true);
$json_decode = json_decode($json, true);

 


    JHtml::_('behavior.modal');
    JHtml::_('behavior.keepalive');

    $maxFieldLen = ApiPortalValidator::MAX_FIELD_LEN;

    $requiredMsg = JText::_('JGLOBAL_FIELD_REQUIRED');
    $invalidEmailMsg = JText::_('JGLOBAL_FIELD_INVALID_EMAIL');
    $maxLengthMsg = str_replace('%s', '{0}', JText::_('JGLOBAL_FIELD_TOO_LONG'));

    $app = JFactory::getApplication();

    $data = array('name' => '', 'email' => '', 'token' => 'e8f201');
    $formData =  $app->getUserState(ApiPortalSessionVariables::USER_REGISTRATION_DATA, null);
    if ($formData) {
        $data = array_merge($data, $formData);
    }
    $app->setUserState(ApiPortalSessionVariables::USER_REGISTRATION_DATA, null);

    $signUpURL = JURI::base(false) . 'index.php?option=com_apiportal&task=registration.submit';
    $tcURL = 'index.php?option=com_apiportal&view=terms&format=raw';

    if (ApiPortalHelper::isCaptchaRequired()) {
        JPluginHelper::importPlugin('captcha');
        $dispatcher = JEventDispatcher::getInstance();
        $dispatcher->trigger('onInit','dynamic_recaptcha_1');
    }

    // For input validation
    $document = JFactory::getDocument();
    $document->addScript('components/com_apiportal/assets/js/jquery.validate.js');
?>

<h1><?php echo JText::_('COM_APIPORTAL_REGISTRATION_TITLE'); ?></h1>

<form id="register-form" method="post" action="<?php echo $signUpURL; ?>" novalidate>
  <fieldset>
    <div class="form-group">
      <label class="control-label" for="name">
        <?php echo JText::_('COM_APIPORTAL_REGISTRATION_FULL_NAME_LABEL'); ?>:
      </label>
        <input type="text" class="form-control" tabindex='1' id="name" name="apidata[name]"
          value="<?php echo $this->escape($data['name']); ?>">
        <div class="validation-message"></div>
    </div><!-- .form-group -->

    <div class="form-group">
      <label class="control-label" for="email">
        <?php echo JText::_('COM_APIPORTAL_REGISTRATION_EMAIL_LABEL'); ?>:
      </label>
        <input type="email" class="form-control" tabindex='2' id="email" name="apidata[email]"
          value="<?php echo $this->escape($data['email']); ?>">
        <div class="validation-message"></div>
        <span class="help-block"><?php echo JText::_('COM_APIPORTAL_EMAIL_TO_LOGIN_NAME')?></span>
    </div><!-- .form-group -->

    <div class="form-group" id='password_id'>
      <label class="control-label" for="password">
        <?php echo JText::_('COM_APIPORTAL_REGISTRATION_PASSWORD_LABEL'); ?>:
      </label>
        <div class="input-group">
          <input type="password" class="form-control" tabindex='3' id="password" name="password"
            autocomplete="off">
          <span class="input-group-addon" id="mask-group"><i class="fa fa-eye-slash" id="mask-icon"></i></span>
          <input type="hidden" id="confirm-password-off" name="confirm_password_off" value="false">
        </div>
        <div class="validation-message"></div>
    </div><!-- .form-group -->

    <div class="form-group" id='confirm_password_group'>
      <label class="control-label" for="confirm_password">
        <?php echo JText::_('COM_APIPORTAL_REGISTRATION_CONFIRM_PASSWORD_LABEL'); ?>:
      </label>
        <input type="password" class="form-control" tabindex='4' id="confirm_password" name="confirm_password"
          autocomplete="off">
        <div class="validation-message"></div>
    </div><!-- .form-group -->

      <div class="form-inline">
          <div style="white-space: nowrap"> <!-- nowrap required to keep span text on same line as inut field -->
            <input type="hidden"  class="form-control" tabindex='5' id="token" name="apidata[token]"
              value="<?php echo $json_decode[0]['orgCode'] ?>">
         
      </div>
    </div><!-- .form-group -->

  <?php if (ApiPortalHelper::isCaptchaRequired()) { ?>
    <?php $tabindex = 7; ?>
    <div class="form-group">
        <div id="dynamic_recaptcha_1"  tabindex='6'></div>
    </div><!-- .form-group -->
  <?php } else { ?>
    <?php $tabindex = 6; ?>
  <?php } ?>

    <div class="form-group">
          <label class="checkbox-inline">
            <input type="checkbox" tabindex='<?php echo $tabindex++; ?>' id="terms_and_conditions"
              name="apidata[TermsAndConditionsAccepted]">
            <?php
              $tcAnchorAttrs = "tabindex=$tabindex++' data-toggle='modal' data-target='#tc-modal-dialog'";
              $tcTextAndAnchor = sprintf(JText::_('COM_APIPORTAL_REGISTRATION_TERMS_AND_CONDITIONS_LABEL'), $tcAnchorAttrs);
              echo $tcTextAndAnchor;
            ?>
          </label>
          <div class="validation-message"></div>
    </div><!-- .form-group -->

      <?php echo JHtml::_( 'form.token' ); ?>

    <div class="form-group">
        <button type="submit" class="btn btn-primary" tabindex='<?php echo $tabindex++; ?>'
          id="btn-register" disabled><?php echo JText::_('COM_APIPORTAL_REGISTRATION_PRIMARY_ACTION_LABEL'); ?></button>
    </div><!-- .form-group -->
  </fieldset>
</form>

<!-- T&C Modal -->
<div class="modal fade" id="tc-modal-dialog" tabindex="-1" role="dialog" aria-labelledby="tc-modal-label" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h3 class="modal-title" id="tc-modal-label">
          <?php echo JText::_('COM_APIPORTAL_REGISTRATION_TERMS_AND_CONDITIONS_DIALOG_TITLE'); ?>
        </h3>
      </div><!-- .modal-header -->

      <div class="modal-body">
        <div class="terms-and-condtions-content" id="tc-modal-content"></div>
      </div>

      <div class="modal-footer">
        <div class="dialog-actions">
          <button type="button" class="btn btn-primary" data-dismiss="modal">
            <?php echo JText::_('COM_APIPORTAL_REGISTRATION_TERMS_AND_CONDITIONS_DIALOG_CLOSE_LABEL'); ?>
          </button>
        </div>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script type="text/javascript">
    // jQuery is loaded in 'noconflict' mode.
    jQuery(document).ready(function($) {

        $('#terms_and_conditions').click(function() {
            if (this.checked) {
                $('#btn-register').removeAttr("disabled");
            } else {
                $('#btn-register').attr("disabled", "disabled");
            }
        });
        var registerForm = $('#register-form');

        registerForm.validate({
            rules: {
                'apidata[name]': {
                    required: true,
                    maxlength: <?php echo $maxFieldLen; ?>
                },
                'apidata[email]': {
                    email: true,
                    required: true,
                    maxlength: <?php echo $maxFieldLen; ?>
                },
                'apidata[token]': {
                    required: false,
                    maxlength: <?php echo $maxFieldLen; ?>
                },
                password: {
                    required: true,
                    maxlength: <?php echo $maxFieldLen; ?>
                },
                confirm_password: {
                    required: function(element) {
                        return $('#confirm-password-off').val() == 'false';
                    },
                    maxlength: <?php echo $maxFieldLen; ?>,
                    equalTo: '#password'
                },
                'apidata[TermsAndConditionsAccepted]': {
                    required: true
                }
            },
            messages: {
                'apidata[name]': {
                    required: '<?php echo $requiredMsg; ?>',
                    maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                },
                'apidata[email]': {
                    email: '<?php echo $invalidEmailMsg; ?>',
                    required: '<?php echo $requiredMsg; ?>',
                    maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                },
                'apidata[token]': {
                    maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                },
                password: {
                    required: '<?php echo $requiredMsg; ?>',
                    maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                },
                confirm_password: {
                    required: '<?php echo $requiredMsg; ?>',
                    maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>',
                    equalTo: '<?php echo JText::_('COM_APIPORTAL_PASSWORDS_DONT_MATCH'); ?>'
                },
                'apidata[TermsAndConditionsAccepted]': {
                    required: '<?php echo JText::_('COM_APIPORTAL_REGISTRATION_TERMS_AND_CONDITIONS_ACCEPT'); ?>'
                }
            },
            onkeyup: false,
            errorPlacement: function(label, elem) {
                elem.next('.validation-message').append(label);
            }
        });

        // Add a rule for the loginName/email to match the regex from API Manager
        $.validator.addMethod("regexLogin", function (value, element) {
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
        $('input[name="apidata[email]"]').rules("add", {
            regexLogin: true
        });

        // Toggle password masking and password confirmation
        $('#mask-group').on('click', function(e) {
            var password_field = $(this).closest('div').find('#password');

            password_field.attr('type', function() {
                var type = password_field.attr('type') == 'password' ? 'text' : 'password';

                if (type == 'text') {
                    $('#confirm_password_group').hide();
                    $('#mask-icon').removeClass('fa-eye-slash');
                    $('#mask-icon').addClass('fa-eye');
                    $('#confirm-password-off').val('true');
                } else {
                    $('#confirm_password_group').show();
                    $('#mask-icon').removeClass('fa-eye');
                    $('#mask-icon').addClass('fa-eye-slash');
                    $('#confirm-password-off').val('false');
                }

                return type;
            });
        });

        $('#tc-modal-dialog').on('show.bs.modal', function(e) {
            $.get('<?php echo $tcURL; ?>', function(data) {
                $("#tc-modal-content").html(data);
            });
        });
    });
</script>
