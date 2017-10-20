<?php

defined('_JEXEC') or die('Restricted access');

$config = new ApiportalModelapiportal();

$usersModels = new APIPortalModelUser();

$currentUserId = ApiPortalHelper::getCurrentUserPortalId();

$user = $usersModels->getItem($currentUserId);

$json_encode = json_encode($user);

$json_decode = json_decode($json_encode, true);


$error = false;
if (!isset($this->item)) {
    $error = true;
}

$applcation = $this->item->application;
$permissions = ApiportalHelper::getPermission($application);

if (!$error) {
    $maxFieldLen = ApiPortalValidator::MAX_FIELD_LEN;
    $maxTextAreaLen = ApiPortalValidator::MAX_TEXTAREA_LEN;

    $requiredMsg = JText::_('JGLOBAL_FIELD_REQUIRED');
    $invalidEmailMsg = JText::_('JGLOBAL_FIELD_INVALID_EMAIL');
    $maxLengthMsg = str_replace('%s', '{0}', JText::_('JGLOBAL_FIELD_TOO_LONG'));

    $app = JFactory::getApplication();

    $data = array(
        'name' => '',
        'token'=>'',
        'cpf' => $json_decode['user']['cpf'],
        'pv'=>'',
        'authBase64'=>'',
        'description' => '',
        'phone' => $json_decode['user']['phone'],
        'email' => $json_decode['user']['email'],
        'enabled' => 'true',
        'apis' => array()
    );
    $formData = $app->getUserState(ApiPortalSessionVariables::APP_CREATE_DATA, null);
    if ($formData) {
        $data = array_merge($data, $formData);
    }
    $app->setUserState(ApiPortalSessionVariables::APP_CREATE_DATA, null);

    $discoveredApis = isset($this->item->apis) ? $this->item->apis : array();
    $organizations = isset($this->item->organizations) ? $this->item->organizations : array();
    $config = isset($this->item->config) ? $this->item->config : new stdClass();

    if (ApiPortalHelper::hasAdminRole() && count($organizations) > 1) {
        /*
         * If we have more than 1 organization, then this can only be the Super Admin ('apiadmin') and we
         * will need to provide a dropdown list to select which organization to create the application in.
         */
        $organizationId = JRequest::getVar('organizationId', null, 'String');
        $organizationId = $this->escape($organizationId);
    } else {
        /*
         * Otherwise just display the organization name. The application will
         * be created in the current user's organization by the createApp task.
         */
        $session = JFactory::getSession();
        $organizationId = $session->get(ApiPortalSessionVariables::MANAGER_ORG_ID);
        $organizationName = ApiPortalHelper::getOrganizationName($organizationId, $organizations);
    }

    // We only need to use JRoute on URLs that are visible in the browser address bar
    $createAppWithOrgIdURL = JRoute::_('index.php?option=com_apiportal&view=application&layout=create&organizationId=%s', false);

    // These are all actions (tasks), don't use JRoute, but include Itemid
    $itemId = JRequest::getVar('Itemid', 0, 'INT');
    $itemId = $this->escape($itemId);
    $createAppURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.createApp&Itemid=' . $itemId;

    $addImageURL = 'components/com_apiportal/assets/img/add_image.png';

    $document = JFactory::getDocument();

    // For filtering & sorting on checkbox state
   
    $document->addScript('components/com_apiportal/assets/js/tablesorter/widgets/widget-grouping.js');

    // For image thumbnails preview/upload
    $document->addStyleSheet('components/com_apiportal/assets/css/jasny-bootstrap.css');
    $document->addScript('components/com_apiportal/assets/js/jasny-bootstrap.js');
    $document->addScript('components/com_apiportal/assets/js/additional-methods.js');

    // For sprintf utility
    $document->addScript('components/com_apiportal/assets/js/util/sprintf.js');
}
?>

<div class="head">
  <div class="btn-toolbar auto">
    <a href="<?php echo $appListURL; ?>" class="btn btn-default icon arrow-left">
        <?php echo JText::_('COM_APIPORTAL_APPLICATIONS_TITLE'); ?>
    </a>
  </div>
  <h1 class="auto"><?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_TITLE'); ?></h1>
  <p class="auto"><em><!-- placeholder --></em></p>
</div>

<?php if ($error) {
    return;
} ?>

<h2 id="message"></h2>

<div id="general" class="body auto">
<h2><?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_SECTION'); ?></h2>
    <form id="application-form" class="form-horizontal" enctype="multipart/form-data" method="post"
          action="<?php echo $createAppURL; ?>" novalidate>
        <fieldset>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="image-wrapper">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_IMAGE_LABEL'); ?>:
                </label>

                <div class="col-sm-2" id="image-wrapper">
                    <div class="fileinput" data-provides="fileinput">
                        <div class="fileinput-new">
                            <div class="application logo" data-trigger="fileinput"
                                 style="background-image: url('<?php echo $addImageURL; ?>')"></div>
                        </div>
                        <div class="fileinput-exists">
                            <div class="fileinput-preview" data-trigger="fileinput"></div>
                        </div>
                        <input type="hidden" name="MAX_FILE_SIZE" value="1048576"/>
                        <input type="file" id="image" name="image" style="display: none;"/>
                    </div>
                    <div class="validation-message"></div>
          <span class="help-block">
            <?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_IMAGE_HELP_TEXT'); ?>
          </span>
                </div>
            </div>
            <!-- .form-group -->

            <div class="form-group">
                <?php if (ApiPortalHelper::hasAdminRole() && count($organizations) > 1) { ?>
                    <div class="col-sm-4">
                        <select class="form-control select-organization" id="organization" name="organizationId"
                                data-rule-required="true" data-msg-required="<?php echo $requiredMsg; ?>">
                            <option value=''>
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_SELECT_ORGANIZATION_LABEL'); ?>
                            </option>
                            <?php foreach ($organizations as $organization): ?>
                                <option
                                    value='<?php echo $organization->id; ?>' <?php echo($organization->id == $organizationId ? 'selected' : ''); ?>>
                                    <?php echo ApiPortalHelper::cleanHtml($organization->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="validation-message"></div>
                    </div>
                <?php } else { ?>
                    
                <?php } ?>
            </div>
            <!-- .form-group -->

            <div class="form-group">
                <label class="col-sm-2 control-label" for="name">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_NAME_LABEL'); ?>:
                </label>

                <div class="col-sm-4">
                    <input type="text" class="form-control" id="name" name="apidata[name]"
                           value="<?php echo $this->escape($data['name']); ?>">

                    <div class="validation-message"></div>
                </div>
            </div>
            <!-- .form-group -->

            <div class="form-group">
                <label class="col-sm-2 control-label" for="description">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_GENERAL_DESCRIPTION_LABEL'); ?>:
                </label>

                <div class="col-sm-4">
          <textarea class="form-control" id="description" name="apidata[description]"
                    rows="3"><?php echo $this->escape($data['description']); ?></textarea>

                    <div class="validation-message"></div>
                </div>
            </div>
            <!-- .form-group -->
          <input type="hidden" class="form-control" id="pv" name="apidata[pv]"
                value="<?php echo $this->escape($data['pv']); ?>">

          <input type="hidden" class="form-control" id="token" name="apidata[token]"
                value="<?php echo $this->escape($data['token']); ?>">

          <input type="hidden" class="form-control" id="autBase64" name="apidata[authBase64]" 
                 value="<?php echo $this->escape($data['authBase64']); ?>">
                   
                      <!------------inputs não utlizados -------------------->
                      <!------------valores sendo passados fixos no model ----->
          
          <input type="hidden" class="form-control" id="cpf" name="apidata[cpf]" 
                 value=" <?php echo $this->escape($data['cpf']); ?>">

          <input type="hidden" type="tel" class="form-control" id="phone" name="apidata[phone]"  value="<?php echo $this->escape($data['phone']); ?>">
          
                       <!----------- Fim inputs não utilizados --------------->
          
          <input type="hidden" type="email" class="form-control" id="email" name="apidata[email]"  value="<?php echo $this->escape($data['email']); ?>">

                   
         


                            <input type="hidden" name="apidata[enabled]"
                                   value='true' <?php echo($data['enabled'] == 'true' ? 'checked' : ''); ?>>
                           

            <!-- .form-group -->
        </fieldset>

        <div id="select-apis">
            <h2><?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_APIS_SECTION'); ?></h2>
            <!-- Check if there are actually any published APIS's, and if not, don't just render table header and filter -->
            <?php if (!$discoveredApis) { ?>
                <?php if (ApiPortalHelper::hasAdminRole() && count($organizations) > 1 && !$organizationId) { ?>
                    <p class='no-apis-available'>
                        <em><?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_APIS_SELECT_ORGANIZATION'); ?></em>
                    </p>
                <?php } else { ?>
                    <p class='no-apis-available'>
                        <em><?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_APIS_NO_APIS_AVAILABLE'); ?></em>
                    </p>
                <?php } ?>
            <?php } else { ?>
                <div class="col-sm-3 table-filter">
                    <div class="tablesorter-filter" role="search">
                        <label>
                            <input type="text" class="form-control" aria-controls="dtable" data-column='all' id="apis-filter"
                                   placeholder="<?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_APIS_FILTER_APIS'); ?>">
                        </label>
                    </div>
                </div>

                <table class="table table-striped table-bordered table-hover tablesorter" id="apis-table">
                    <thead>
                    <tr>
                        <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_APIS_NAME_HEADER'); ?></th>
                        <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_APIS_DESCRIPTION_HEADER'); ?></th>
                        <th class="filter-false"><?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_APIS_SELECTED_HEADER'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($discoveredApis as $available): ?>
                        <tr>
                            <?php if (ApiPortalHelper::isEnabled($available)) { ?>
                                <td class="col-lg-2"><?php echo $this->escape($available->name); ?></td>
                            <?php } else { ?>
                                <td class="disabled-text-effect col-lg-2">
                                    <div class="api-tooltip api-disabled" data-toggle="tooltip"
                                         title="<?php echo JText::_('COM_APIPORTAL_APPLICATIONS_API_DISABLED'); ?>">
                                        <i class="fa fa-ban"></i> <?php echo $this->escape($available->name); ?>
                                    </div>
                                </td>
                            <?php } ?>
                            <td class="api-description-<?= $available->apiId ?> hidden-xs <?php echo(ApiPortalHelper::isEnabled($available) ? '' : 'disabled-text-effect'); ?>">

                            </td>
                            <script>
                                jQuery('.api-description-<?= $available->apiId ?>').append(apiDescriptionRender(<?= json_encode($available->description) ?>, "<?= $available->documentationUrl ?>", false));
                            </script>
                            <td class="col-sm-1">
                                <?php if (ApiPortalHelper::isEnabled($available)) { ?>
                                    <?php if ($data['apis']) { ?>
                                        <?php foreach ($data['apis'] as $apiId): ?>
                                            <?php if ($available->apiId == $apiId) { ?>
                                                <input type="radio" name="apidata[apis][]"
                                                       value="<?php echo $available->apiId; ?>" checked>
                                                <?php break; ?>
                                            <?php } ?>
                                        <?php endforeach; ?>
                                    <?php } else { ?>
                                        <input type="radio" name="apidata[apis][]"
                                               value="<?php echo $available->apiId; ?>">
                                    <?php } ?>
                                <?php } else { ?>
                                    <input type="radio" name="apidata[apis][]"
                                           value="<?php echo $available->apiId; ?>" disabled>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>

        <?php echo JHtml::_( 'form.token' ); ?>
        <input type="hidden" name="submitted" value="1" />
        <input type='hidden' id='application-save-type' name='save-type'>

        <div class="form-actions">
            <button type='button' class='btn btn-primary' id='application-save-and-list-button'>
                <?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_PRIMARY_ACTION_LABEL'); ?>
            </button>
            <?php if (($this->appAutoApprove && $permissions == 'manage') || $this->delegateApplicationAdministration === true):?>
                <button type='button' class='btn btn-default' id='application-save-and-auth-button'>
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_SECONDARY_ACTION_LABEL'); ?>
                </button>
            <?php endif;?>
            <button type='button' class='btn btn-default' id='application-cancel-button'>
                <?php echo JText::_('COM_APIPORTAL_APPLICATION_CREATE_TERTIARY_ACTION_LABEL'); ?>
            </button>
        </div>
    </form>
</div>

<script type="text/javascript">

//Function para validar se os campos cpf/telefone foram preenchidos
//Não esta sendo utilizada,decido que os campos vão ser passados com valor fixo
  
  
 /* function valida(){
  var x = document.getElementById('phone').value;
  var message = document.getElementById("message");
  var form = document.getElementById("application-form");
  var appName = document.getElementById("name").value;
   try {

    if(x == '') throw '<strong>Aviso</strong> Para criar sua loja complete seu cadastro <a href =https://192.168.56.20/pt/profile-menu/profile/user/edit?ep=profile-menu>aqui</a>'
   }
   catch(err) {
          message.innerHTML = err;
          message.className += 'alert alert-warning';
          window.scrollTo(0, 0);
      }
   if (x != '' && appName != ''){
    form.submit();

   }

  }
  */

    var applicationFormOnEnter;

    // jQuery is loaded in 'noconflict' mode.
    jQuery(document).ready(function ($) {


       $('input[name="apidata[apis][]"]').on('change', function() {
   $('input[name="apidata[apis][]"]').not(this).prop('checked', false);
});

        $('#application-form').validate({
            ignore: [],
            rules: {
                'apidata[name]': {
                    required: true,
                    maxlength: <?php echo $maxFieldLen; ?>
                },
                'apidata[description]': {
                    required: false,
                    maxlength: <?php echo $maxTextAreaLen; ?>
                },
                
                image: {
                    required: false,
                    accept: 'image/*',
                    maxImageSize: 1048576
                }
            },
            messages: {
                'apidata[name]': {
                    required: '<?php echo $requiredMsg; ?>',
                    maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                },
                'apidata[description]': {
                    required: '<?php echo $requiredMsg; ?>',
                    maxlength: '<?php echo sprintf($maxLengthMsg, $maxTextAreaLen); ?>'
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

        if ($('#apis-table')) {
            $('#apis-table').tablesorter({
                widgets: ['filter'],
                widgetOptions: {
                    filter_columnFilters: false,
                    filter_external: '#apis-filter'
                },
                headers: {
                    2: {sorter: 'checkbox'}
                },
                sortList: [[0, 0]]
            });
            $.tablesorter.filter.bindSearch($('#apis-table'), $('.search'), false);
        }

        // Validate image file size after every change.
        $('.fileinput').on('change.bs.fileinput', validateImage);

        // Save state of the application form when the page was loaded
        applicationFormOnEnter = $('#application-form').serializeAll();

        // If we are leaving the page, make sure there are no unsaved changes
        $(window).on('beforeunload', function (e) {
            applicationFormOnLeave = $('#application-form').serializeAll();

            if (applicationFormOnEnter != applicationFormOnLeave) {
                var warningText = "<?php echo sprintf(
                    JText::_('COM_APIPORTAL_APPLICATION_UNSAVED_CHANGES_DIALOG_TEXT'),
                    JText::_('COM_APIPORTAL_APPLICATION_UNSAVED_CHANGES_DIALOG_LEAVING_PAGE')); ?>";

                return warningText;
            }
        });

        // Submit application form data
        $('#application-save-and-list-button').on('click', function (e) {
            
            if ($('#application-form').valid()) {
                $('#application-save-type').val('save-and-list');

                // Prevent the 'beforeunload' handler from stopping the submit
                applicationFormOnEnter = $('#application-form').serializeAll();
            }
             $('#application-form').submit();
        });

        $('#application-save-and-auth-button').on('click', function (e) {
          
            if ($('#application-form').valid()) {
                $('#application-save-type').val('save-and-auth');

                // Prevent the 'beforeunload' handler from stopping the submit
                applicationFormOnEnter = $('#application-form').serializeAll();
            }
            $('#application-form').submit();
        });

        // Cancel application editing and return to 'list'
        $('#application-cancel-button').on('click', function (e) {
            // Prevent the 'beforeunload' handler from stopping the cancel
            resetApplicationForm();

            document.location.href = '<?php echo $appListURL; ?>';
        });

        $('.select-organization').change(function () {
            var organizationId = $('.select-organization option:selected').attr('value');
            //document.location.href = sprintf('<?php //echo $createAppWithOrgIdURL; ?>', organizationId);
        });

        // Initialize tooltips
        $('.api-tooltip').tooltip({placement: 'bottom'});
    });
</script>
