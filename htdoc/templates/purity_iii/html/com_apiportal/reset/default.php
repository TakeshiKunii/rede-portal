<?php
    defined('_JEXEC') or die('Restricted access');

    JHtml::_('behavior.keepalive');

    $maxFieldLen = ApiPortalValidator::MAX_FIELD_LEN;

    $requiredMsg = JText::_('JGLOBAL_FIELD_REQUIRED');
    $invalidEmailMsg = JText::_('JGLOBAL_FIELD_INVALID_EMAIL');
    $maxLengthMsg = str_replace('%s', '{0}', JText::_('JGLOBAL_FIELD_TOO_LONG'));

    $resetURL = JURI::base(false) . 'index.php?option=com_apiportal&task=reset.submit';

    // For input validation
    $document = JFactory::getDocument();
    $document->addScript('components/com_apiportal/assets/js/jquery.validate.js');
?>

<h1><?php echo JText::_('COM_APIPORTAL_RESET_TITLE'); ?></h1>

<form id="reset-form" method="post" action="<?php echo $resetURL;  ?>" novalidate>
  <fieldset>
    <div class="form-group">
      <label class="control-label" for="email">
        <?php echo JText::_('COM_APIPORTAL_RESET_EMAIL_LABEL'); ?>:
      </label>
        <input type="email" class="form-control" tabindex='1' id="email" name="email">
        <div class="validation-message"></div>
        <span class="help-block">
          <?php echo JText::_('COM_APIPORTAL_RESET_EMAIL_HELP_TEXT'); ?>
        </span>
    </div><!-- .form-group -->

      <?php echo JHtml::_( 'form.token' ); ?>

    <div class="form-group">
        <button type="submit" class="btn btn-primary" tabindex='2' id="btn-reset">
          <?php echo JText::_('COM_APIPORTAL_RESET_PRIMARY_ACTION_LABEL'); ?>
        </button>
    </div><!-- .form-group -->
  </fieldset>
</form>

<script type="text/javascript">
    // jQuery is loaded in 'noconflict' mode.
    jQuery(document).ready(function($) {

        $('#reset-form').validate({
            rules: {
                email: {
                    email: true,
                    required: true,
                    maxlength: <?php echo $maxFieldLen; ?>
                }
            },
            messages: {
                email: {
                    email: '<?php echo $invalidEmailMsg; ?>',
                    required: '<?php echo $requiredMsg; ?>',
                    maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                }
            },
            onkeyup: false,
            errorPlacement: function(label, elem) {
                elem.next('.validation-message').append(label);
            }
        });
    });
</script>
