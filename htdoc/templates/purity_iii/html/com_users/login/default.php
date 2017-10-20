<?php
    defined('_JEXEC') or die('Restricted access');

    JHtml::_('behavior.keepalive');
    JHtml::_('jquery.framework');

    JLoader::register('ApiPortalHelper', JPATH_BASE.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'apiportal.php');
    JLoader::register('ApiPortalValidator', JPATH_BASE.DS.'components'.DS.'com_apiportal'.DS.'helpers'.DS.'validator.php');

    $registrationEnabled = true;
    $config = ApiPortalHelper::getAPIManagerAppInfo();
    if (property_exists($config, 'registrationEnabled')) {
        $registrationEnabled = $config->registrationEnabled;
    }

    $maxFieldLen = ApiPortalValidator::MAX_FIELD_LEN;

    $requiredMsg = JText::_('JGLOBAL_FIELD_REQUIRED');
    $maxLengthMsg = str_replace('%s', '{0}', JText::_('JGLOBAL_FIELD_TOO_LONG'));

    $signUpURL = JRoute::_('index.php?option=com_apiportal&view=registration', false);
    $resetURL = JRoute::_('index.php?option=com_apiportal&view=reset', false);
    $loginURL = JRoute::_('index.php?options=com_users&task=user.login', false);

    $landingURL = JURI::base(false) . 'index.php?option=com_apiportal&task=landing.page';

    $app = JFactory::getApplication();

    // Check for any existing login data from a previous login attempt
    $data = array('username' => '');
    $formData = $app->getUserState('users.login.form.data', null);
    if ($formData) {
        $data = array_merge($data, $formData);
    }
    $app->setUserState('users.login.form.data', null);

    // Check for a return value override in the URL
    $return =  JRequest::getVar('return', null, 'get', 'STRING');
    $return = $this->escape($return);
    if ($return) {
        // URL value overrides any preset default return value
        $data['return'] = $return;
    } else {
        $data['return'] = base64_encode($landingURL);
    }

    /*
     * If 'state' is set, we've been redirected here via ApiPortalHelper::checkSession()
     * or the overridden JApplicationSite->authorise().
     */
    $state = JRequest::getVar('session', null, 'get', 'STRING');
    if ($state) {
        if ($state == 'expired') {
            $app->enqueueMessage(JText::_('JGLOBAL_SESSION_TIMEOUT'), 'notice');
        } else {
            $app->enqueueMessage(JText::_('JGLOBAL_SESSION_ERROR'), 'notice');
        }
    }

    // For input validation
    $document = JFactory::getDocument();
    $document->addScript('components/com_apiportal/assets/js/jquery.validate.js');
?>
<div class="container"> 
   <div class="row">
      <div class="col-xs-6">
       <h1><?php echo JText::_('COM_USERS_SIGN_IN_TITLE'); ?></h1>

<form id="login-form" method="post" action="<?php echo $loginURL; ?>" novalidate>
    <div class="form-group">
    <label class="control-label" for="username">
        <?php echo JText::_('COM_APIPORTAL_USER_VIEW_LOGIN_NAME_LABEL'); ?>:
      </label>
        <input type="text" class="form-control" tabindex='1' id="username" name="username"
         placeholder="mail@me.com"  value="<?php echo $this->escape($data['username']); ?>"/>
        <div class="validation-message"></div>
      <?php if ($registrationEnabled) { ?>
        <?php $tabindex = 5; ?>
        <span class="help-block">
          
            
          </a>
        </span>
      <?php } else { ?>
        <?php $tabindex = 4; ?>
      <?php } ?>
    </div><!-- .form-group -->
    <div class="form-group">
    <label class="control-label" for="password">
        <?php echo JText::_('COM_USERS_SIGN_IN_PASSWORD_LABEL'); ?>:
      </label>
        <input type="password" class="form-control" tabindex='2' id="password" name="password" autocomplete="off" />
        <div class="validation-message"></div>
          <?php if (isset($config->resetPasswordEnabled) && $config->resetPasswordEnabled) { ?>
              <span class="help-block">
		          <a href="<?php echo $resetURL; ?>" tabindex='<?php echo $tabindex++; ?>'>
		            <?php echo JText::_('COM_USERS_SIGN_IN_RESET_LINK_LABEL'); ?>
		          </a>
                </span>
          <?php } ?>
    </div><!-- .form-group -->

    <div class="form-group">
        <button type="submit" class="btn btn-primary" id="btn-login" tabindex='3'>
          <?php echo JText::_('COM_USERS_SIGN_IN_PRIMARY_ACTION_LABEL'); ?>
        </button>
    </div><!-- .form-group -->

      <?php echo JHtml::_( 'form.token' ); ?>
    <input type="hidden" name="return" value="<?php echo $data['return']; ?>"/>
</form>
      
      </div>
     <div class="col-xs-6">
        <h1>Cadastre-se</h1>

        <form id="login-form">
           <div>

                  Lorem ipsum dolor sit amet, consectetur 
                  adipiscing elit. 
                  Aenean euismod bibendum laoreet. Proin 
                  gravida dolor sit amet lacus accumsan et viverra 
                  justo commodo. Proin sodales pulvinar tempor. Cum sociis 
                  natoque penatibus et magnis dis parturient montes, 
                  nascetur ridiculus mus. Nam fermentum, 
                  nulla luctus pharetra vulputate, felis tellus mollis orci, 
                  sed rhoncus sapien nunc eget.

           </div>
<p></p>

    
            <div class="form-group">
          <span>
          
          <a href="<?php echo $signUpURL; ?>" tabindex='4'>
            <?php echo JText::_('COM_USERS_SIGN_IN_SIGN_UP_LINK_LABEL'); ?>
          </a>
        </span>
    </div><!-- .form-group -->

            </form>
        
        
        
     </div>
   
   </div>

</div>


<script type="text/javascript">
    // jQuery is loaded in 'noconflict' mode.
    jQuery(document).ready(function($) {

        $('#login-form').validate({
            rules: {
                username: {
                    email: true,
                    required: true,
                    maxlength: <?php echo $maxFieldLen; ?>
                },
                password: {
                    required: true,
                    maxlength: <?php echo $maxFieldLen; ?>
                }
            },
            messages: {
                username: {
                    required: '<?php echo $requiredMsg; ?>',
                    maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                },
                password: {
                    required: '<?php echo $requiredMsg; ?>',
                    maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                },
            },
            onkeyup: false,
            errorPlacement: function(label, elem) {
                elem.next('.validation-message').append(label);
            }

        });
    });
</script>
