<?php
defined('_JEXEC') or die('Restricted access');

// Manage hidden tab for public API user
$publicApiAction = ApiPortalHelper::hasHiddenTabforPublicUser();

$appListURL = JRoute::_('index.php?option=com_apiportal&view=applications', false);
JFactory::getSession()->set('appUserId',JFactory::getSession()->get('user')->get('id'));
$error = false;
if (!isset($this->item)) {
    $error = true;
} else {
    $application = isset($this->item->application) ? $this->item->application : null;
    if (!$application) {
        $error = true;
        $app = JFactory::getApplication();
        $app->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_EDIT_NOT_FOUND_ALERT'), 'notice');
    }
}

if (!$error) {
    $maxFieldLen = ApiPortalValidator::MAX_FIELD_LEN;
    $maxTextAreaLen = ApiPortalValidator::MAX_TEXTAREA_LEN;

    $requiredMsg = JText::_('JGLOBAL_FIELD_REQUIRED');
    $invalidEmailMsg = JText::_('JGLOBAL_FIELD_INVALID_EMAIL');
    $maxLengthMsg = str_replace('%s', '{0}', JText::_('JGLOBAL_FIELD_TOO_LONG'));

    $app = JFactory::getApplication();

    $data = array(
        'name' => ApiPortalHelper::cleanHtml($application->name),
        'description' => ApiPortalHelper::cleanHtml($application->description),
        'phone' => $this->escape($application->phone),
        'email' => $this->escape($application->email),
        'enabled' => $this->escape($application->enabled)
    );
    $formData = $app->getUserState(ApiPortalSessionVariables::APP_EDIT_DATA, null);
    if ($formData) {
        $data = array_merge($data, $formData);
    }
    $app->setUserState(ApiPortalSessionVariables::APP_EDIT_DATA, null);

    $newSharedUsers = $app->getUserState(ApiPortalSessionVariables::APP_EDIT_NEW_USER, array());
    $app->setUserState(ApiPortalSessionVariables::APP_EDIT_NEW_USER, null);

    $selectedApis = isset($application->apis) ? $application->apis : array();
    $discoveredApis = isset($this->item->apis) ? $this->item->apis : array();
    $apiKeys = isset($application->apikeys) ? $application->apikeys : array();
    $oauthClients = isset($application->oauth) ? $application->oauth : array();
    $isExtClients = empty($application->extClients) ? false : true;
    $applicationUsers = isset($application->users) ? $application->users : array();
    $remainingOrgUsers = isset($this->item->orgUsers) ? $this->item->orgUsers : array();

    $permission = ApiPortalHelper::getPermission($application);

    $currentUserId = ApiPortalHelper::getCurrentUserPortalId();

    $baseViewURL = 'index.php?option=com_apiportal&view=application&layout=view';
    $baseEditURL = 'index.php?option=com_apiportal&view=application&layout=edit';

    // We only need to use JRoute on URLs that are visible in the browser address bar
    $viewAppURL = JRoute::_($baseViewURL . '&applicationId=' . $application->id, false);
    $apiKeyListURL = JRoute::_($baseEditURL . '&tab=authentication&applicationId=' . $application->id, false);
    $oauthListURL = JRoute::_($baseEditURL . '&tab=authentication&applicationId=' . $application->id, false);

    // Make sure we have permission to edit this application
    if (ApiPortalHelper::isPending($application)) {
        // Redirect back to view page
        $app = JFactory::getApplication();
        $app->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_EDIT_PENDING_ALERT'), 'notice');
        $app->redirect($viewAppURL);
    } else if ($permission != 'manage') {
        // Redirect back to view page
        $app = JFactory::getApplication();
        $app->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARED_VIEW_ONLY_ALERT'), 'warning');
        $app->redirect($viewAppURL);
    }

    // These are all actions (tasks), don't use JRoute, but include Itemid
    $itemId = JRequest::getVar('Itemid', 0, 'INT');
    $itemId = $this->escape($itemId);

    $updateAppURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.updateApp&Itemid=' . $itemId;
    $deleteAppURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.deleteApp&Itemid=' . $itemId;

    $apiKeyCreateURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.createKey&Itemid=' . $itemId;
    $apiKeyUpdateURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.updateKey&Itemid=' . $itemId;
    $apiKeyDisableURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.disableKey&Itemid=' . $itemId;
    $apiKeyEnableURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.enableKey&Itemid=' . $itemId;
    $apiKeyDeleteURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.deleteKey&Itemid=' . $itemId;

    $oauthClientCreateURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.createOAuth&Itemid=' . $itemId;
    $oauthClientUpdateURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.updateOAuth&Itemid=' . $itemId;
    $oauthClientDisableURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.disableOAuth&Itemid=' . $itemId;
    $oauthClientEnableURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.enableOAuth&Itemid=' . $itemId;
    $oauthClientDeleteURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.deleteOAuth&Itemid=' . $itemId;
    $oauthClientNewSecretURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.newOAuthSecret&format=json&Itemid=' . $itemId;

    $extClientChangeStateURL = JUri::base(false) . 'index.php?option=com_apiportal&task=application.changeExtOAuthState&Itemid=' . $itemId;
    $extClientDeleteURL = JUri::base(false) . 'index.php?option=com_apiportal&task=application.deleteExtOAuth&Itemid=' . $itemId;
    $extClientUpdateURL = JUri::base(false) . 'index.php?option=com_apiportal&task=application.updateExtOAuth&Itemid=' . $itemId;
    $extClientCreateURL = JUri::base(false) . 'index.php?option=com_apiportal&task=application.createExtOAuth&Itemid=' . $itemId;

    $addSharedUsersURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.addSharedUsers&Itemid=' . $itemId;
    $updateSharedUserURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.updateSharedUser&format=json&Itemid=' . $itemId;
    $removeSharedUserURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.removeSharedUser&Itemid=' . $itemId;

    $appImageURL = JURI::base(false) . 'index.php?option=com_apiportal&view=image&format=raw&applicationId=' . $application->id;
    $addImageURL = 'components/com_apiportal/assets/img/add_image.png';

    $tab = JRequest::getString('tab', null);
    $tab = ApiPortalHelper::cleanHtml($tab, false, true);
    if ($tab == null) {
        $tab = 'details';
    }

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

    // For AJAX spinner
    // $document->addScript('components/com_apiportal/assets/js/spin.js');
}


?>

<div class="head">
  <div class="btn-toolbar auto">
    <a href="<?php echo $appListURL; ?>" class="btn btn-default icon arrow-left">
        <?php echo JText::_('COM_APIPORTAL_APPLICATIONS_TITLE'); ?>
    </a>
  </div>
  <h1 class="auto"><?php echo sprintf(JText::_('COM_APIPORTAL_APPLICATION_EDIT_TITLE'), $this->escape($application->name)); ?></h1>
  <p class="auto"><em><!-- placeholder --></em></p>
</div>

<?php if ($error) {
    return;
} ?>

<div class="btn-toolbar" role="toolbar">
    <div class="auto">
    <div class="action-group">
        <form id="delete-application-form" method="post" action="<?php echo $deleteAppURL; ?>">
                <button type="button" class="btn btn-default icon delete" data-toggle="modal" data-target="#confirm-delete"
                    data-name="<?php echo $this->escape($application->name); ?>"
                    data-object="<?php echo JText::_('COM_APIPORTAL_APPLICATION_APPLICATION_OBJECT'); ?>">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_DELETE_LABEL'); ?>
            </button>
            <input type="hidden" name="applicationId" value="<?php echo $application->id; ?>"/>
            <input type="hidden" name="applicationName" value="<?php echo $this->escape($application->name); ?>"/>
            <input type="hidden" name="viewName" value="edit"/>
            <?php echo JHtml::_( 'form.token' ); ?>
            <input type="hidden" name="submitted" value="1" />
        </form>
        </div>
    </div>
</div>

<div class="body auto">
    <div class="tabs " id="tabs">
        <ul class="nav nav-tabs">
            <li <?php echo($tab == 'details' ? 'class="active"' : ''); ?>>
                <a href="#details"
                   data-toggle="tab"><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_DETAILS_TAB_NAME'); ?></a>
            </li>
            <li style="display:none" <?php echo($tab == 'authentication' ? 'class="active"' : '');  echo $publicApiAction; ?> >
                <a href="#authentication"
                   data-toggle="tab"><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_TAB_NAME'); ?></a>
            </li>
            <?php if (!ApiPortalHelper::isCommunity($application)) { ?>
                <li <?php echo($tab == 'sharing' ? 'class="active"' : ''); echo $publicApiAction; ?>> 
                    <a href="#sharing"
                       data-toggle="tab"><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_TAB_NAME'); ?></a>
                </li>
            <?php } ?>
        </ul>

        <div class="tab-content">
            <!-- Application Details Tab -->
            <div class="tab-pane fade <?php echo($tab == 'details' ? 'in active' : ''); ?>" id="details">
                <h2><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_SECTION'); ?></h2>

                <div id="general">
                    <form id="application-form" class="form-horizontal" enctype="multipart/form-data" method="post"
                          action="<?php echo $updateAppURL; ?>" novalidate>
                        <fieldset>
                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="image-wrapper">
                                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_IMAGE_LABEL'); ?>:
                                </label>

                                <div class="col-sm-2" id="image-wrapper">
                                    <div class="fileinput" data-provides="fileinput">
                                        <div class="fileinput-new">
                                            <?php if ($application->image) { ?>
                                                <?php if (strpos($application->image, 'base64')) { ?>
                                                    <div class="application logo"
                                                         style="background-image: url('<?php echo $application->image; ?>')"></div>
                                                <?php } else { ?>
                                                    <div class="application logo" data-trigger="fileinput"
                                                         style="background-image: url('<?php echo $appImageURL; ?>')"></div>
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
                      <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_IMAGE_HELP_TEXT'); ?>
                    </span>
                                </div>
                            </div>
                            <!-- .form-group -->

                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="organization">
                                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_ORGANIZATION_LABEL'); ?>:
                                </label>

                                <p class="col-sm-4 form-control-static" id="organization"
                                   name="organization"><?php echo $this->escape($application->organizationName); ?>
                                </p>
                            </div>
                            <!-- .form-group -->

                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="name">
                                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_NAME_LABEL'); ?>:
                                </label>

                                <div class="col-sm-4">
                                    <input type="text" class="form-control" id="name" name="apidata[name]"
                                           value="<?php echo $data['name']; ?>">

                                    <div class="validation-message"></div>
                                </div>
                            </div>
                            <!-- .form-group -->

                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="description">
                                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_DESCRIPTION_LABEL'); ?>:
                                </label>

                                <div class="col-sm-4">
                    <textarea class="form-control" id="description" name="apidata[description]"
                              rows="3"><?php echo $data['description']; ?></textarea>

                                    <div class="validation-message"></div>
                                </div>
                            </div>
                            <!-- .form-group -->

                            
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-4">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="apidata[enabled]"
                                                   value="true" <?php echo($data['enabled'] == 'true' ? 'checked' : ''); ?>>
                                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERAL_ENABLE_LABEL'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <!-- .form-group -->
                        </fieldset>

                        <div id="select-apis">
                            <h2><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_APIS_SECTION'); ?></h2>
                            <!-- Check if there are actually any published APIS's, and if not, don't just render table header and filter -->
                            <?php if (!$discoveredApis) { ?>
                                <p class="no-apis-available">
                                    <em><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_APIS_NO_APIS_AVAILABLE'); ?></em>
                                </p>
                            <?php } else { ?>
                                <div class="col-sm-3 table-filter">
                                    <div class="tablesorter-filter" role="search">
                                        <label>
                                            <input type="text" class="form-control" id="apis-filter" aria-controls="dtable"
                                                   data-column='all'
                                                   placeholder="<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_APIS_FILTER_APIS'); ?>">
                                        </label>
                                    </div>
                                </div>

                                <table class="table table-striped table-bordered table-hover" id="apis-table">
                                    <thead>
                                    <tr>
                                        <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_APIS_NAME_HEADER'); ?></th>
                                        <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_APIS_DESCRIPTION_HEADER'); ?></th>
                                        <th class="filter-false"><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_APIS_SELECTED_HEADER'); ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($discoveredApis as $available): ?>
                                        <?php {
                                            /*
                                             * We can't use quite the same logic here as in view.php, since we need to display
                                             * all of the discovered APIs, and distinguish the ones that have been added to the
                                             * application already from those that haven't.
                                             *
                                             * See comment in view.php regarding API states.
                                             */
                                            $api = new stdClass();
                                            $checked = false;
                                            foreach ($selectedApis as $selected):
                                                if ($available->apiId == $selected->apiId) {
                                                    $api = $selected;
                                                    $checked = true;
                                                    break;
                                                }
                                            endforeach;
                                        } ?>
                                        <tr>
                                            <!-- Eschew Obfuscation! -->
                                            <?php if (ApiPortalHelper::isEnabled($available)) { ?>
                                                <?php if (ApiPortalHelper::isPending($api)) { ?>
                                                    <td class="disabled-text-effect col-lg-2">
                                                        <div class="api-tooltip api-pending" data-toggle="tooltip"
                                                             title="<?php echo JText::_('COM_APIPORTAL_APPLICATIONS_API_PENDING'); ?>">
                                                            <i class="fa fa-clock-o"></i> <?= $this->escape($available->name); ?>
                                                        </div>
                                                    </td>
                                                <?php } else { ?>
                                                    <td class="col-lg-2">
                                                        <?php
                                                        $apiUrl = JUri::base().'index.php?option=com_apiportal&view=apitester&usage=api&tab=tests&Itemid=0&apiName=' . rawurlencode($available->name) . '&apiId=' . $available->apiId;
                                                        ?>
                                                        <a href="<?= (string)$apiUrl ?>">
                                                            <?= $this->escape($available->name); ?>
                                                        </a>
                                                    </td>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <?php if (ApiPortalHelper::isPending($api)) { ?>
                                                    <td class="disabled-text-effect col-lg-2">
                                                        <div class="api-tooltip api-pending" data-toggle="tooltip"
                                                             title="<?php echo JText::_('COM_APIPORTAL_APPLICATIONS_API_PENDING'); ?>">
                                                            <i class="fa fa-clock-o"></i> <?= $this->escape($available->name); ?>
                                                        </div>
                                                    </td>
                                                <?php } else { ?>
                                                    <td class="disabled-text-effect col-lg-2">
                                                        <div class="api-tooltip api-disabled" data-toggle="tooltip"
                                                             title="<?php echo JText::_('COM_APIPORTAL_APPLICATIONS_API_DISABLED'); ?>">
                                                            <i class="fa fa-ban"></i> <?= $this->escape($available->name); ?>
                                                        </div>
                                                    </td>
                                                <?php } ?>
                                            <?php } ?>

                                            <?php if (!ApiPortalHelper::isEnabled($available) || ApiPortalHelper::isPending($api)) { ?>
                                                <td class="hidden-xs disabled-text-effect description-api-<?= $available->id ?>">

                                                </td>
                                            <?php } else { ?>
                                                <td class="hidden-xs description-api-<?= $available->id ?>">

                                                </td>
                                            <?php } ?>

                                            <script>
                                                jQuery('.description-api-<?= $available->id ?>').append(apiDescriptionRender(<?= json_encode($available->description) ?>, "<?= $available->documentationUrl ?>", false));
                                            </script>

                                            <td class="col-sm-1">
                                                <?php if (ApiPortalHelper::isEnabled($available)) { ?>
                                                    <input type="checkbox" name="apis[]"
                                                           value="<?php echo $available->apiId; ?>" <?php echo($checked ? 'checked' : ''); ?>>
                                                <?php } else { ?>
                                                    <!-- API is disabled: If it's unchecked, disable it so that it can't be added to the application -->
                                                    <?php if ($checked) { ?>
                                                        <input type="checkbox" name="apis[]"
                                                               value="<?php echo $available->apiId; ?>" checked>
                                                    <?php } else { ?>
                                                        <input type="checkbox" name="apis[]"
                                                               value="<?php echo $available->apiId; ?>" disabled>
                                                    <?php } ?>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <!-- Glad that's over with! -->
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php } ?>
                        </div>

                        <input type="hidden" name="submitted" value="1" />
                        <?php echo JHtml::_( 'form.token' ); ?>
                        <input type="hidden" name="applicationId" value="<?php echo $application->id; ?>"/>
                        <input type="hidden" name="organizationId"
                               value="<?php echo $this->escape($application->organizationId); ?>"/>

                        <!-- A bug in API Manager requires the following two to be present on update. -->
                        <input type="hidden" name="createdBy"
                               value="<?php echo $this->escape($application->createdBy); ?>"/>
                        <input type="hidden" name="createdOn"
                               value="<?php echo $this->escape($application->createdOn); ?>"/>

                        <div class="form-actions">
                            <button type='button' id='application-submit-button' class='btn btn-primary'>
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_PRIMARY_ACTION_LABEL'); ?>
                            </button>
                            <button type='button' id='application-cancel-button' class='btn btn-default'>
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_SECONDARY_ACTION_LABEL'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- .tab-pane -->

            <!-- Authentication Tab -->
            <div class="tab-pane fade <?php echo($tab == 'authentication' ? 'in active' : ''); ?>" id="authentication">

                <!-- API Keys -->
                <div class="panel panel-default" id="api-keys-collapse">
                    <div class="panel-heading">
                        <h2 class="panel-title">
                            <a data-toggle="collapse" data-parent="#api-keys-collapse" href="#collapse-api-keys-overview">
                                <?php if ($apiKeys) { ?>
                                    <i class="indicator fa fa-chevron-down"></i>
                                <?php } else { ?>
                                    <i class="indicator fa fa-chevron-right"></i>
                                <?php } ?>
                                <strong><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEYS_SECTION'); ?></strong>
                            </a>
                        </h2>
                    </div>
                    <!-- .panel-heading -->

                    <div class="panel-collapse collapse <?php echo($apiKeys ? 'in' : ''); ?>"
                         id="collapse-api-keys-overview">
                        <div class="panel-body" id="api-keys-overview">
                            <form id="api-key-create-form" method="post" action="<?php echo $apiKeyCreateURL; ?>">
                                <button type="submit" class="btn btn-default">
                                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERATE_LABEL'); ?>
                                </button>
                                <?php echo JHtml::_( 'form.token' ); ?>
                                <input type="hidden" name="applicationId" value="<?php echo $application->id; ?>"/>
                            </form>

                            <?php if (!$apiKeys) { ?>
                                <p class="no-api-keys-defined">
                                    <em><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEYS_NO_KEYS_DEFINED'); ?></em>
                                </p>
                            <?php } else { ?>
                                <table class="table table-striped table-bordered table-hover" id="api-keys-table">
                                    <thead>
                                    <tr>
                                        <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEYS_NAME_HEADER'); ?></th>
                                        <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEYS_CORS_HEADER'); ?></th>
                                        <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEYS_CREATED_HEADER'); ?></th>
                                        <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEYS_ACTIONS_HEADER'); ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($apiKeys as $apiKey): ?>
                                        <tr>
                                            <?php if (ApiPortalHelper::isEnabled($apiKey)) { ?>
                                                <td><?php echo $apiKey->id; ?></td>
                                            <?php } else { ?>
                                                <td class="disabled-text-effect"><i
                                                        class="fa fa-ban"></i> <?php echo $apiKey->id; ?></td>
                                            <?php } ?>
                                            <td class="hidden-xs <?php echo(ApiPortalHelper::isEnabled($apiKey) ? '' : 'disabled-text-effect'); ?>">
                                                <?php echo implode("<br/>", explode(",", htmlspecialchars(implode(",", $apiKey->corsOrigins)))); ?>
                                            </td>
                                            <td class="hidden-xs  <?php echo(ApiPortalHelper::isEnabled($apiKey) ? '' : 'disabled-text-effect'); ?>">
                                                <?= ApiPortalHelper::convertDateTime($apiKey->createdOn,JText::_('COM_APIPORTAL_LOCAL_DATE_TIME_FORMAT')); ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <a class="btn dropdown-toggle icon chevron-down" data-toggle="dropdown" href="#">
                                                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEYS_ACTIONS_TEXT'); ?>
                                                    </a>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button type="button" class="btn btn-link"
                                                                    onClick='showAPIKeyDialog(JSON.parse("<?php echo addslashes(json_encode($apiKey)); ?>"))'>
                                                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEYS_ACTIONS_EDIT'); ?>
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button type="button" class="btn btn-link"
                                                                    onClick='showAPIKeySecret("<?php echo $apiKey->secret; ?>")'>
                                                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEYS_ACTIONS_VIEW_SECRET'); ?>
                                                            </button>
                                                        </li>
                                                        <?php if (ApiPortalHelper::isEnabled($apiKey)) { ?>
                                                            <li>
                                                                <form id="api-key-disable-form" method="post"
                                                                      action="<?php echo $apiKeyDisableURL; ?>">
                                                                    <button type="submit" class="btn btn-link">
                                                                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEYS_ACTIONS_DISABLE'); ?>
                                                                    </button>
                                                                    <?php echo JHtml::_( 'form.token' ); ?>
                                                                    <input type="hidden" name="applicationId"
                                                                           value="<?php echo $application->id; ?>"/>
                                                                    <input type="hidden" name="apiKeyId"
                                                                           value="<?php echo $apiKey->id; ?>"/>
                                                                </form>
                                                            </li>
                                                        <?php } else { ?>
                                                            <li>
                                                                <form id="api-key-enable-form" method="post"
                                                                      action="<?php echo $apiKeyEnableURL; ?>">
                                                                    <button type="submit" class="btn btn-link">
                                                                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEYS_ACTIONS_ENABLE'); ?>
                                                                    </button>
                                                                    <?php echo JHtml::_( 'form.token' ); ?>
                                                                    <input type="hidden" name="applicationId"
                                                                           value="<?php echo $application->id; ?>"/>
                                                                    <input type="hidden" name="apiKeyId"
                                                                           value="<?php echo $apiKey->id; ?>"/>
                                                                </form>
                                                            </li>
                                                        <?php } ?>
                                                        <li>
                                                            <form id="api-key-delete-form" method="post"
                                                                  action="<?php echo $apiKeyDeleteURL; ?>">
                                                                <button type="button" class="btn btn-link"
                                                                        data-toggle="modal" data-target="#confirm-delete"
                                                                        data-name="<?php echo $apiKey->id; ?>"
                                                                        data-object="<?php echo JText::_('COM_APIPORTAL_APPLICATION_API_KEY_OBJECT'); ?>">
                                                                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEYS_ACTIONS_DELETE'); ?>
                                                                </button>
                                                                <?php echo JHtml::_( 'form.token' ); ?>
                                                                <input type="hidden" name="applicationId"
                                                                       value="<?php echo $application->id; ?>"/>
                                                                <input type="hidden" name="apiKeyId"
                                                                       value="<?php echo $apiKey->id; ?>"/>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <!-- .dropdwon -->
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php } ?>
                        </div>
                        <!-- .panel-body -->
                    </div>
                    <!-- .panel-collapse -->
                </div>
                <!-- .panel -->

                <!-- OAuth Credentials -->
                <div class="panel panel-default" id="oauth-creds-collapse">
                    <div class="panel-heading">
                        <h2 class="panel-title">
                            <a data-toggle="collapse" data-parent="#oauth-creds-collapse" href="#collapse-oauth-overview">
                                <?php if ($oauthClients) { ?>
                                    <i class="indicator fa fa-chevron-down"></i>
                                <?php } else { ?>
                                    <i class="indicator fa fa-chevron-right"></i>
                                <?php } ?>
                                <strong><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_SECTION'); ?></strong>
                            </a>
                        </h2>
                    </div>
                    <!-- .panel-heading -->

                    <div class="panel-collapse collapse <?php echo($oauthClients ? 'in' : ''); ?>"
                         id="collapse-oauth-overview">
                        <div class="panel-body" id="oauth-overview">
                            <button type="button" class="btn btn-default" onClick='showOAuthDialog(null)'>
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERATE_LABEL'); ?>
                            </button>

                            <?php if (!$oauthClients) { ?>
                                <p class="no-oauth-clients-defined">
                                    <em><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_NO_CLIENTS_DEFINED'); ?></em>
                                </p>
                            <?php } else { ?>
                                <table class="table table-striped table-bordered table-hover" id="oauth-clients-table">
                                    <thead>
                                    <tr>
                                        <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ID_HEADER'); ?></th>
                                        <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_TYPE_HEADER'); ?></th>
                                        <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_CORS_HEADER'); ?></th>
                                        <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_URLS_HEADER'); ?></th>
                                        <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_CREATED_HEADER'); ?></th>
                                        <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ACTIONS_HEADER'); ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($oauthClients as $oauthClient): ?>
                                        <tr>
                                            <?php if (ApiPortalHelper::isEnabled($oauthClient)) { ?>
                                                <td><?php echo $oauthClient->id; ?></td>
                                            <?php } else { ?>
                                                <td class="disabled-text-effect"><i
                                                        class="fa fa-ban"></i> <?php echo $oauthClient->id; ?></td>
                                            <?php } ?>
                                            <td class="hidden-xs <?php echo(ApiPortalHelper::isEnabled($oauthClient) ? '' : 'disabled-text-effect'); ?>">
                                                <?php if ($oauthClient->type == 'confidential') { ?>
                                                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_TYPE_CONFIDENTIAL'); ?>
                                                <?php } else { ?>
                                                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_TYPE_PUBLIC'); ?>
                                                <?php } ?>
                                            </td>
                                            <td class="hidden-xs <?php echo(ApiPortalHelper::isEnabled($oauthClient) ? '' : 'disabled-text-effect'); ?>">
                                                <?php echo implode("<br/>", explode(",", htmlspecialchars(implode(",", $oauthClient->corsOrigins)))); ?>
                                            </td>
                                            <td class="hidden-xs <?php echo(ApiPortalHelper::isEnabled($oauthClient) ? '' : 'disabled-text-effect'); ?>">
                                                <?= $this->escape(implode(", ", $oauthClient->redirectUrls)); ?>
                                            </td>
                                            <td class="hidden-xs <?php echo(ApiPortalHelper::isEnabled($oauthClient) ? '' : 'disabled-text-effect'); ?>">
                                                <script>displayDate(<?php echo $oauthClient->createdOn; ?>, 'D MMM YYYY, HH:mm');</script>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <a class="btn dropdown-toggle icon chevron-down" data-toggle="dropdown" href="#">
                                                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ACTIONS_TEXT'); ?>
                                                    </a>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button type="button" class="btn btn-link"
                                                                    onClick='showOAuthDialog(JSON.parse("<?php echo addslashes(json_encode($oauthClient)); ?>"))'>
                                                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ACTIONS_EDIT'); ?>
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button type="button" class="btn btn-link"
                                                                    onClick='showOAuthSecret("<?php echo $oauthClient->secret; ?>", "<?php echo $oauthClient->id; ?>")'>
                                                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ACTIONS_VIEW_SECRET'); ?>
                                                            </button>
                                                        </li>
                                                        <?php if (ApiPortalHelper::isEnabled($oauthClient)) { ?>
                                                            <li>
                                                                <form id="oauth-client-disable-form" method="post"
                                                                      action="<?php echo $oauthClientDisableURL; ?>">
                                                                    <button type="submit" class="btn btn-link">
                                                                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ACTIONS_DISABLE'); ?>
                                                                    </button>
                                                                    <?php echo JHtml::_( 'form.token' ); ?>
                                                                    <input type="hidden" name="applicationId"
                                                                           value="<?php echo $application->id; ?>"/>
                                                                    <input type="hidden" name="oauthClientId"
                                                                           value="<?php echo $oauthClient->id; ?>"/>
                                                                </form>
                                                            </li>
                                                        <?php } else { ?>
                                                            <li>
                                                                <form id="oauth-client-enable-form" method="post"
                                                                      action="<?php echo $oauthClientEnableURL; ?>">
                                                                    <button type="submit" class="btn btn-link">
                                                                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ACTIONS_ENABLE'); ?>
                                                                    </button>
                                                                    <?php echo JHtml::_( 'form.token' ); ?>
                                                                    <input type="hidden" name="applicationId"
                                                                           value="<?php echo $application->id; ?>"/>
                                                                    <input type="hidden" name="oauthClientId"
                                                                           value="<?php echo $oauthClient->id; ?>"/>
                                                                </form>
                                                            </li>
                                                        <?php } ?>
                                                        <li>
                                                            <form id="oauth-client-delete-form" method="post"
                                                                  action="<?php echo $oauthClientDeleteURL; ?>">
                                                                <button type="button" class="btn btn-link"
                                                                        data-toggle="modal" data-target="#confirm-delete"
                                                                        data-name="<?php echo $oauthClient->id; ?>"
                                                                        data-object="<?php echo JText::_('COM_APIPORTAL_APPLICATION_OAUTH_CLIENT_OBJECT'); ?>">
                                                                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ACTIONS_DELETE'); ?>
                                                                </button>
                                                                <?php echo JHtml::_( 'form.token' ); ?>
                                                                <input type="hidden" name="applicationId"
                                                                       value="<?php echo $application->id; ?>"/>
                                                                <input type="hidden" name="oauthClientId"
                                                                       value="<?php echo $oauthClient->id; ?>"/>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <!-- .dropdown -->
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php } ?>
                        </div>
                        <!-- .panel-body -->
                    </div>
                    <!-- .panel-collapse -->
                </div>
                <!-- .panel -->

                <!-- External Credentials -->
                <div class="panel panel-default" id="ext-oauth-creds-collapse">
                    <div class="panel-heading">
                        <h2 class="panel-title">
                            <a data-toggle="collapse" data-parent="#ext-oauth-creds-collapse" href="#collapse-ext-oauth-overview">
                                <?php if ($isExtClients) { ?>
                                    <i class="indicator fa fa-chevron-down"></i>
                                <?php } else { ?>
                                    <i class="indicator fa fa-chevron-right"></i>
                                <?php } ?>
                                <strong><?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENTS_SECTION'); ?></strong>
                            </a>
                        </h2>
                    </div>
                    <!-- .panel-heading -->
                <div class="panel-collapse collapse <?= $isExtClients ? 'in' : ''; ?>"
                     id="collapse-ext-oauth-overview">
                    <div class="panel-body" id="ext-oauth-overview">
                        <button type="button" class="btn btn-default" onClick='showExtOAuthDialog(null)'>
                            <?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_GENERATE_LABEL'); ?>
                        </button>

                        <?php if (!$isExtClients && is_array($application->extClients)) { ?>
                            <p class="no-oauth-clients-defined">
                                <em><?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_NO_CLIENTS_DEFINED'); ?></em>
                            </p>
                        <?php } else { ?>
                            <table class="table table-striped table-bordered table-hover" id="ext-oauth-clients-table">
                                <thead>
                                <tr>
                                    <th><?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ID_HEADER'); ?></th>
                                    <th class="hidden-xs"><?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_CORS_HEADER'); ?></th>
                                    <th class="hidden-xs"><?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_CREATED_HEADER'); ?></th>
                                    <th><?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ACTIONS_HEADER'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($application->extClients as $extClient) { ?>
                                    <tr>
                                        <?php if (ApiPortalHelper::isEnabled($extClient)) { ?>
                                            <td><?= $this->escape($extClient->clientId); ?></td>
                                        <?php } else { ?>
                                            <td class="disabled-text-effect"><i
                                                    class="fa fa-ban"></i> <?= $this->escape($extClient->clientId); ?></td>
                                        <?php } ?>
                                        <td class="hidden-xs <?= ApiPortalHelper::isEnabled($extClient) ? '' : 'disabled-text-effect'; ?>">
                                            <?= implode("<br/>", explode(",", htmlspecialchars(implode(",", $extClient->corsOrigins)))); ?>
                                        </td>
                                        <td class="hidden-xs <?= ApiPortalHelper::isEnabled($extClient) ? '' : 'disabled-text-effect'; ?>">
                                            <?= ApiPortalHelper::convertDateTime($oauthClient->createdOn,JText::_('COM_APIPORTAL_LOCAL_DATE_TIME_FORMAT')); ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <a class="btn dropdown-toggle icon chevron-down" data-toggle="dropdown" href="#">
                                                    <?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENTS_ACTIONS_TEXT'); ?>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <button type="button" class="btn btn-link"
                                                                onClick='showExtOAuthDialog(<?= json_encode($extClient, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'>
                                                            <?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENTS_ACTIONS_EDIT'); ?>
                                                        </button>
                                                    </li>
                                                    <?php if (ApiPortalHelper::isEnabled($extClient)) { ?>
                                                        <li>
                                                            <form id="ext-oauth-client-disable-form" method="post"
                                                                  action="<?= $extClientChangeStateURL; ?>">
                                                                <button type="submit" class="btn btn-link">
                                                                    <?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ACTIONS_DISABLE'); ?>
                                                                </button>
                                                                <?php echo JHtml::_( 'form.token' ); ?>
                                                                <input type="hidden" name="applicationId"
                                                                       value="<?= $application->id; ?>"/>
                                                                <input type="hidden" name="objectId"
                                                                       value="<?= $extClient->id; ?>"/>
                                                            </form>
                                                        </li>
                                                    <?php } else { ?>
                                                        <li>
                                                            <form id="ext-oauth-client-enable-form" method="post"
                                                                  action="<?= $extClientChangeStateURL; ?>">
                                                                <button type="submit" class="btn btn-link">
                                                                    <?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ACTIONS_ENABLE'); ?>
                                                                </button>
                                                                <?php echo JHtml::_( 'form.token' ); ?>
                                                                <input type="hidden" name="applicationId"
                                                                       value="<?= $application->id; ?>"/>
                                                                <input type="hidden" name="objectId"
                                                                       value="<?= $extClient->id; ?>"/>
                                                            </form>
                                                        </li>
                                                    <?php } ?>
                                                    <li>
                                                        <form id="ext-oauth-client-delete-form" method="post"
                                                              action="<?= $extClientDeleteURL; ?>">
                                                            <button type="button" class="btn btn-link"
                                                                    data-toggle="modal" data-target="#confirm-delete"
                                                                    data-name="<?= $this->escape($extClient->clientId); ?>"
                                                                    data-object="<?= JText::_('COM_APIPORTAL_APPLICATION_EXT_OAUTH_CLIENT_OBJECT'); ?>">
                                                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENTS_ACTIONS_DELETE'); ?>
                                                            </button>
                                                            <?php echo JHtml::_( 'form.token' ); ?>
                                                            <input type="hidden" name="applicationId" value="<?= $application->id; ?>" />
                                                            <input type="hidden" name="objectId" value="<?= $extClient->id; ?>" />
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                            <!-- .dropdown -->
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>
                    </div>
                    <!-- .panel-body -->
                </div>
                <!-- .panel-collapse -->
            </div>
            <!-- .panel -->

            </div>
            <!-- .tab-pane -->

            <?php if (!ApiPortalHelper::isCommunity($application)) { ?>
                <!-- Sharing Tab -->
                <div class="tab-pane fade <?php echo($tab == 'sharing' ? 'in active' : ''); ?>" id="sharing">
                    <?php if ($remainingOrgUsers) { ?>
                        <button class="btn btn-default add-shared-users-button" data-toggle="modal"
                                data-target="#add-shared-users-details">
                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_ADD_USERS_LABEL'); ?>
                        </button>
                    <?php } else { ?>
                        <button class="btn btn-default add-shared-users-button" disabled>
                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_ADD_USERS_LABEL'); ?>
                        </button>
                        <span
                            class="no-additional-users"><em><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_NO_ADDITIONAL_USERS'); ?></em></span>
                    <?php } ?>

                    <?php if (!$applicationUsers) { ?>
                        <p class="application-not-shared">
                            <em><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_NO_SHARED_USERS'); ?></em>
                        </p>
                    <?php } else { ?>
                        <div id="shared-user-edit">
                            <?php foreach ($applicationUsers as $user): ?>
                                <div class="col-sm-12 control-group row shared-user-edit">
                                    <div class="col-sm-2">
                                        <?php
                                        if (in_array($user->id, $newSharedUsers)) {
                                            $sharedUserName = '<strong>' . $this->escape($user->name) . '</strong>';
                                        } else {
                                            $sharedUserName = $this->escape($user->name);
                                        }
                                        if ($currentUserId == $user->id) {
                                            $sharedUserName .= ' ';
                                            $sharedUserName .= JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_USER_IS_SELF');
                                        }
                                        ?>
                                        <span class="shared-user-name"><?php echo $sharedUserName; ?></span>
                                    </div>
                                    <?php
                                    $disabled = '';
                                    if ($currentUserId == $user->id) {
                                        $disabled = 'disabled';
                                    }
                                    ?>
                                    <div class="col-sm-2 shared-user-permission switch-toggle <?php echo $disabled; ?>">
                                        <form id="shared-user-form" role="form">
                                            <input id="view-<?php echo $user->id; ?>" name="permission" type="radio"
                                                   value="view"
                                                <?php echo($user->permission->permission == 'view' ? 'checked' : ''); ?> <?php echo $disabled; ?>>
                                            <label for="view-<?php echo $user->id; ?>">
                                                <?php
                                                $viewText = JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_SHARED_VIEW_ONLY');
                                                if (!$disabled) {
                                                    $viewText = ucfirst($viewText);
                                                }
                                                ?>
                                                <?php echo $viewText; ?>
                                            </label>

                                            <input id="edit-<?php echo $user->id; ?>" name="permission" type="radio"
                                                   value="manage"
                                                <?php echo($user->permission->permission == 'manage' ? 'checked' : ''); ?> <?php echo $disabled; ?>>
                                            <label for="edit-<?php echo $user->id; ?>">
                                                <?php
                                                $editText = JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_SHARED_MANAGE');
                                                if (!$disabled) {
                                                    $editText = ucfirst($editText);
                                                }
                                                ?>
                                                <?php echo $editText; ?>
                                            </label>

                                            <?php echo JHtml::_( 'form.token' ); ?>
                                            <input type="hidden" name="applicationId"
                                                   value="<?php echo $application->id; ?>">
                                            <input type="hidden" name="permissionId"
                                                   value="<?php echo $user->permission->id; ?>">
                                            <input type="hidden" name="userId" value="<?php echo $user->id; ?>">
                                        </form>
                                    </div>
                                    <?php if ($currentUserId != $user->id) { ?>
                                        <div class="col-sm-2">
                                            <div class="shared-user-remove">
                                                <form role="form" method="post"
                                                      action="<?php echo $removeSharedUserURL; ?>">
                                                    <button type="button" class="btn btn-link" data-toggle="modal"
                                                            data-target="#confirm-remove"
                                                            data-name="<?php echo $this->escape($user->name); ?>"
                                                            data-object="<?php echo JText::_('COM_APIPORTAL_APPLICATION_USER_OBJECT'); ?>">
                                                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_ACTIONS_REMOVE'); ?>
                                                    </button>
                                                    <?php echo JHtml::_( 'form.token' ); ?>
                                                    <input type="hidden" name="applicationId"
                                                           value="<?php echo $application->id; ?>">
                                                    <input type="hidden" name="permissionId"
                                                           value="<?php echo $user->permission->id; ?>">
                                                    <input type="hidden" name="userName"
                                                           value="<?php echo $this->escape($user->name); ?>">
                                                </form>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php } ?>
                </div><!-- .tab-pane -->
            <?php } ?>
        </div>
        <!-- .tab-content -->
    </div><!-- .tabs -->
</div><!-- .body.auto -->

<!-- API Key Edit Dialog -->
<div class="modal fade" id="api-key-details" tabindex="-1" role="dialog" aria-labelledby="api-key-dialog-title"
     aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title" id="api-key-dialog-title"></h3>
            </div>
            <!-- .modal-header -->

            <div class="modal-body">
                <form id="api-edit-key-form" class="form-horizontal" role="form" method="post"
                      action="<?php echo $apiKeyUpdateURL; ?>">
                    <fieldset>
                        <div class="form-group api-key-edit-mode">
                            <label class="col-sm-3 control-label" for="api-key-id">
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEY_DIALOG_API_KEY_LABEL'); ?>:
                            </label>

                            <p class="col-sm-9 form-control-static"><input class="form-control" id="api-key-id"
                                                                           name="api-key-id" disabled='true'/></p>
                        </div>
                        <!-- .form-group -->

                        <div class="form-group">
                            <label class="col-sm-3 control-label" for="api-key-cors-origins">
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEY_DIALOG_CORS_LABEL'); ?>:
                            </label>

                            <div class="col-sm-9">
                <textarea class="form-control" rows="2" id="api-key-cors-origins" name="corsOrigins"
                          data-rule-maxlength="<?php echo $maxTextAreaLen; ?>"
                          data-msg-maxlength="<?php echo sprintf($maxLengthMsg, $maxTextAreaLen); ?>"
                    ></textarea>
                <span class="help-block">
                  <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEY_DIALOG_CORS_HELP_TEXT'); ?>
                </span>
                            </div>
                        </div>
                        <!-- .form-group -->
                    </fieldset>
                    <?php echo JHtml::_( 'form.token' ); ?>
                    <input type="hidden" id="app-id" name="applicationId" value="<?php echo $application->id; ?>">
                    <input type="hidden" id="key-id" name="apiKeyId">
                </form>
            </div>
            <!-- .modal-body -->

            <div class="modal-footer">
                <div class="dialog-actions">
                    <button type="submit" class="btn btn-primary" id="api-key-submit-button"></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEY_DIALOG_SECONDARY_ACTION_LABEL'); ?>
                    </button>
                </div>
            </div>
            <!-- /modal-footer -->

        </div>
        <!-- .modal-content -->
    </div>
    <!-- .modal-dialog -->
</div><!-- .modal -->

<!-- OAuth Client Generate/Edit Dialog -->
<div class="modal fade" id="oauth-client-details" tabindex="-1" role="dialog"
     aria-labelledby="oauth-client-dialog-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title" id="oauth-client-dialog-title"></h3>
            </div>
            <!-- .modal-header -->

            <div class="modal-body">
                <form id="oauth-client-form" class="form-horizontal" role="form" method="post">
                    <fieldset>
                        <div class="form-group oauth-client-edit-mode">
                            <label class="col-sm-3 control-label" for="oauth-client-id">
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_ID_LABEL'); ?>:
                            </label>

                            <p class="col-sm-9 form-control-static" id="oauth-client-id"></p>
                        </div>
                        <!-- .form-group -->

                        <div class="form-group">
                            <label class="col-sm-3 control-label" for="oauth-client-type-wrapper">
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_TYPE_LABEL'); ?>
                                :
                            </label>

                            <div class="col-sm-9" id="oauth-client-type-wrapper">
                                <div class="radio-inline">
                                    <input type="radio" id="oauth-client-type-confidential" name="client-type"
                                           value="confidential">
                                    <label for="oauth-client-type-confidential">
                                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_TYPE_CONFIDENTIAL'); ?>
                                    </label>
                                </div>
                                <div class="radio-inline">
                                    <input type="radio" id="oauth-client-type-public" name="client-type" value="public">
                                    <label for="oauth-client-type-public">
                                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_TYPE_PUBLIC'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <!-- .form-group -->

                        <div class="form-group oauth-client-edit-mode">
                            <label class="col-sm-3 control-label" for="oauth-client-cors-origins">
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_CORS_LABEL'); ?>
                                :
                            </label>

                            <div class="col-sm-9">
                                <textarea class="form-control" rows="2" id="oauth-client-cors-origins" name="corsOrigins"
                                          data-rule-maxlength="<?php echo $maxTextAreaLen; ?>"
                                          data-msg-maxlength="<?php echo sprintf($maxLengthMsg, $maxTextAreaLen); ?>">
                                </textarea>
                                <span class="help-block">
                                  <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_CORS_HELP_TEXT'); ?>
                                </span>
                            </div>
                        </div>
                        <!-- .form-group -->
                        <div class="form-group">
                            <label class="col-sm-3 control-label" for="oauth-client-redirect-urls">
                                <?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_URLS_LABEL'); ?>
                                :
                            </label>

                            <div class="col-sm-9">
                                <textarea class="form-control" rows="2" id="oauth-client-redirect-urls" name="redirect-urls"
                                          data-rule-maxlength="<?php echo $maxTextAreaLen; ?>"
                                          data-msg-maxlength="<?php echo sprintf($maxLengthMsg, $maxTextAreaLen); ?>"></textarea>
                                <span class="help-block">
                                  <?= JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_URLS_HELP_TEXT'); ?>
                                </span>
                            </div>
                        </div>
                        <!-- .form-group -->

                        <div class="form-group">
                            <label class="col-sm-3 control-label" for="oauth-client-x509-certificate">
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_X509_LABEL'); ?>
                                :
                            </label>

                            <div class="col-sm-9">
                                <textarea class="form-control" rows="5" id="oauth-client-x509-certificate" name="x509-certificate"
                                          placeholder="<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_X509_HELP_TEXT'); ?>"
                                          data-rule-maxlength="<?php echo $maxTextAreaLen; ?>"
                                          data-msg-maxlength="<?php echo sprintf($maxLengthMsg, $maxTextAreaLen); ?>">

                                </textarea>
                            </div>
                        </div>
                        <!-- .form-group -->
                    </fieldset>
                    <input type="hidden" id="app-id" name="applicationId" value="<?php echo $application->id; ?>">
                    <input type="hidden" id="client-id" name="oauthClientId">
                    <?php echo JHtml::_( 'form.token' ); ?>
                </form>
            </div>
            <!-- .modal-body -->

            <div class="modal-footer">
                <div class="dialog-actions">
                    <button type="submit" class="btn btn-primary" id="oauth-client-submit-button"></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_SECONDARY_ACTION_LABEL'); ?>
                    </button>
                </div>
            </div>
            <!-- /modal-footer -->
        </div>
        <!-- .modal-content -->
    </div>
    <!-- .modal-dialog -->
</div><!-- .modal -->

<!-- External OAuth Client Generate/Edit Dialog -->
<div class="modal fade" id="ext-oauth-client-details" tabindex="-1" role="dialog"
     aria-labelledby="ext-oauth-client-dialog-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title" id="ext-oauth-client-dialog-title"></h3>
            </div>
            <!-- .modal-header -->

            <div class="modal-body">
                <form id="ext-oauth-client-form" class="form-horizontal" role="form" method="post">
                    <fieldset>
                        <div class="form-group ext-oauth-client-edit-mode">
                            <label class="col-sm-3 control-label" for="ext-oauth-client-id">
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENT_DIALOG_ID_LABEL'); ?>:
                            </label>

                            <div class="col-sm-9">
                                <input class="form-control" id="ext-oauth-client-id" name="clientId" />
                            </div>
                        </div>
                        <!-- .form-group -->

                        <div class="form-group ext-oauth-client-edit-mode">
                            <label class="col-sm-3 control-label" for="ext-oauth-client-cors-origins">
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENT_DIALOG_CORS_LABEL'); ?>
                                :
                            </label>

                            <div class="col-sm-9">
                                <textarea class="form-control" rows="2" id="ext-oauth-client-cors-origins" name="corsOrigins"
                                          data-rule-maxlength="<?= $maxTextAreaLen; ?>"
                                          data-msg-maxlength="<?= sprintf($maxLengthMsg, $maxTextAreaLen); ?>"></textarea>
                                <span class="help-block">
                                  <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENT_DIALOG_CORS_HELP_TEXT'); ?>
                                </span>
                            </div>
                        </div>
                        <!-- .form-group -->

                    </fieldset>
                    <?php echo JHtml::_( 'form.token' ); ?>
                    <input type="hidden" id="app-id" name="applicationId" value="<?= $application->id; ?>" />
                    <input type="hidden" id="object-id" name="objectId" />
                    <input type="hidden" id="object-enabled" name="objectEnabled" />
                </form>
            </div>
            <!-- .modal-body -->

            <div class="modal-footer">
                <div class="dialog-actions">
                    <button type="submit" class="btn btn-primary" id="ext-oauth-client-submit-button"></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENT_DIALOG_SECONDARY_ACTION_LABEL'); ?>
                    </button>
                </div>
            </div>
            <!-- /modal-footer -->
        </div>
        <!-- .modal-content -->
    </div>
    <!-- .modal-dialog -->
</div><!-- .modal -->

<!-- View API Key Secret Dialog -->
<div class="modal fade" id="api-key-secret-details" tabindex="-1" role="dialog"
     aria-labelledby="api-key-secret-dialog-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title" id="api-key-secret-dialog-title">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEY_SECRET_KEY_DIALOG_TITLE'); ?>
                </h3>
            </div>
            <!-- .modal-header -->

            <div class="modal-body">
                <label class="col-sm-2 control-label hidden-xs" for="api-key-secret-key">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEY_SECRET_KEY_DIALOG_SECRET_LABEL'); ?>:
                </label>

                <p class="col-sm-10" id="api-key-secret-key"></p>
            </div>
            <!-- .modal-body -->

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEY_SECRET_KEY_DIALOG_CLOSE_LABEL'); ?>
                </button>
            </div>
            <!-- .modal-footer -->

        </div>
        <!-- .modal-content -->
    </div>
    <!-- .modal-dialog -->
</div><!-- .modal -->

<!-- View OAuth Secret Dialog -->
<div class="modal fade" id="oauth-secret-details" tabindex="-1" role="dialog"
     aria-labelledby="oauth-secret-dialog-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title" id="oauth-secret-dialog-title">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_SECRET_KEY_DIALOG_TITLE'); ?>
                </h3>
            </div>
            <!-- .modal-header -->

            <div class="modal-body">
                <form id="oauth-secret-form" class="form-horizontal" role="form">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-2 control-label hidden-xs" id="oauth-secret-label"
                                   for="oauth-secret-key">
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_SECRET_KEY_DIALOG_SECRET_LABEL'); ?>
                                :
                            </label>

                            <p class="col-sm-6 form-control-static" id="oauth-secret-key"></p>
                            <button type="button" class="col-sm-3 btn btn-default" id="oauth-new-secret-button">
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_SECRET_KEY_DIALOG_NEW_SECRET_ACTION_LABEL'); ?>
                            </button>
                        </div>
                        <!-- .form-group -->
                    </fieldset>
                    <input type="hidden" id="secret-app-id" name="applicationId"
                           value="<?php echo $application->id; ?>">
                    <input type="hidden" id="secret-client-id" name="oauthClientId">
                    <?php echo JHtml::_( 'form.token' ); ?>
                </form>
            </div>
            <!-- .modal-body -->

            <div class="modal-footer">
                <!-- Fake 'form' to allow a submit-based page refresh if a new secret was generated -->
                <form id="oauth-close-form" method="get" action="<?php echo $oauthListURL; ?>">
                    <div class="dialog-actions">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">
                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_SECRET_KEY_DIALOG_CLOSE_LABEL'); ?>
                        </button>
                    </div>
                </form>
            </div>
            <!-- .modal-footer -->

        </div>
        <!-- .modal-content -->
    </div>
    <!-- .modal-dialog -->
</div><!-- .modal -->

<!-- Add Shared Users Dialog -->
<div class="modal fade" id="add-shared-users-details" tabindex="-1" role="dialog"
     aria-labelledby="add-shared-users-dialog-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title" id="add-shared-users-dialog-title">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_ADD_SHARED_USERS_DIALOG_TITLE'); ?>
                </h3>
            </div>
            <!-- .modal-header -->

            <div class="modal-body">
                <div class="col-sm-11 modal-body-header">
                    <div class="tablesorter-filter" role="search">
                        <label>
                            <input type="text" class="form-control" id="add-shared-users-filter" aria-controls="dtable"
                                   data-column='all'
                                   placeholder="<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_ADD_SHARED_USERS_DIALOG_FILTER_USERS'); ?>">
                        </label>
                    </div>
                </div>

                <div class="col-sm-11 modal-body-content">
                    <?php if (!$remainingOrgUsers) { ?>
                        <p class="no-additional-users">
                            <em><?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_ADD_SHARED_USERS_DIALOG_NO_ADDITIONAL_USER'); ?></em>
                        </p>
                    <?php } else { ?>
                        <form id="add-shared-users-form" class="form-horizontal" role="form" method="post"
                              action="<?php echo $addSharedUsersURL; ?>">
                            <div class="form-group">
                                <table class="table add-shared-users" id="add-shared-users-table">
                                    <!-- Tablesorter requires a thead and a th tag -->
                                    <thead data-sorter="false" style="display: none;">
                                    <tr>
                                        <th></th>
                                    </tr>
                                    </thead>

                                    <tbody aria-live="polite" aria-relevant="all">
                                    <?php foreach ($remainingOrgUsers as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="col-sm-12">
                                                    <div class="col-sm-1">
                                                        <input type="checkbox" id="<?php echo $user->id; ?>"
                                                               name="users[]" value="<?php echo $user->id; ?>">
                                                    </div>
                                                    <label class="col-sm-10 add-shared-user"
                                                           for="<?php echo $user->id; ?>"><?php echo $this->escape($user->name); ?></label>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tbody>
                                </table>
                            </div>
                            <!-- .form-group -->
                            <?php echo JHtml::_( 'form.token' ); ?>
                            <input type="hidden" name="applicationId" value="<?php echo $application->id; ?>">
                        </form>
                    <?php } ?>
                </div>
            </div>
            <!-- .modal-body -->

            <div class="modal-footer">
                <div class="dialog-actions">
                    <button type="submit" class="btn btn-primary" id="add-shared-users-submit-button">
                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_ADD_SHARED_USERS_DIALOG_PRIMARY_ACTION_LABEL'); ?>
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_ADD_SHARED_USERS_DIALOG_SECONDARY_ACTION_LABEL'); ?>
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
                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_CONFIRM_REMOVE_DIALOG_SECONDARY_ACTION_LABEL'); ?>
                    </button>
                </div>
            </div>
            <!-- /modal-footer -->

        </div>
        <!-- .modal-content -->
    </div>
    <!-- .modal-dialog -->
</div><!-- .modal -->

<!-- Unsaved Changes Dialog -->
<div class="modal fade" id="confirm-unsaved" tabindex="-1" role="dialog" aria-labelledby="confirm-unsaved-dialog-title"
     aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title" id="confirm-unsaved-dialog-title"></h3>
            </div>
            <!-- .modal-header -->

            <div class="modal-body">
                <p id="confirm-unsaved-dialog-text"></p>
            </div>
            <!-- .modal-body -->

            <div class="modal-footer">
                <div class="dialog-actions">
                    <button type="button" class="btn btn-primary" id="confirm-unsaved-stay-button"></button>
                    <button type="button" class="btn btn-default" id="confirm-unsaved-leave-button"></button>
                </div>
            </div>
            <!-- /modal-footer -->

        </div>
        <!-- .modal-content -->
    </div>
    <!-- .modal-dialog -->
</div><!-- .modal -->

<script type="text/javascript">
    var applicationFormOnEnter;

    // jQuery is loaded in 'noconflict' mode.
    jQuery(document).ready(function ($) {

$('input[name="apis[]"]').on('change', function() {
   $('input[name="apis[]"]').not(this).prop('checked', false);
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
                'apidata[phone]': {
                    required: false,
                    maxlength: <?php echo $maxFieldLen; ?>
                },
                'apidata[email]': {
                    email: true,
                    required: false,
                    maxlength: <?php echo $maxFieldLen; ?>
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
                'apidata[phone]': {
                    required: '<?php echo $requiredMsg; ?>',
                    maxlength: '<?php echo sprintf($maxLengthMsg, $maxFieldLen); ?>'
                },
                'apidata[email]': {
                    email: '<?php echo $invalidEmailMsg; ?>',
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

        $('#api-edit-key-form').validate();
        $('#oauth-client-form').validate();

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
        }

        if ($('#api-keys-table')) {
            $('#api-keys-table').tablesorter({
                headers: {
                    3: {sorter: false}
                },
                sortList: [[0, 0]]
            })
        }

        if ($('#oauth-clients-table')) {
            $('#oauth-clients-table').tablesorter({
                headers: {
                    5: {sorter: false}
                },
                sortList: [[0, 0]]
            })
        }

        if ($('#ext-oauth-clients-table')) {
            $('#ext-oauth-clients-table').tablesorter({
                headers: {
                    3: {sorter: false}
                },
                sortList: [[0, 0]]
            })
        }

        if ($('#add-shared-users-table')) {
            $('#add-shared-users-table').tablesorter({
                widgets: ['filter'],
                widgetOptions: {
                    filter_columnFilters: false,
                    filter_external: '#add-shared-users-filter'
                }
            });
        }

        // Validate image file size after every change.
        $('.fileinput').on('change.bs.fileinput', validateImage);

        // Save state of the application form when the page was loaded
        applicationFormOnEnter = $('#application-form').serializeAll();

        // If we are leaving the page, make sure there are no unsaved changes
        $(window).on('beforeunload', function (e) {
            var applicationFormOnLeave = $('#application-form').serializeAll();

            if (applicationFormOnEnter != applicationFormOnLeave) {
                var warningText = "<?php echo sprintf(
                    JText::_('COM_APIPORTAL_APPLICATION_UNSAVED_CHANGES_DIALOG_TEXT'),
                    JText::_('COM_APIPORTAL_APPLICATION_UNSAVED_CHANGES_DIALOG_LEAVING_PAGE')); ?>";

                return warningText;
            }
        });

        // If we are leaving the details tab, make sure there are no unsaved changes
        $('a[data-toggle="tab"]').on('show.bs.tab', function (e) {
            // target is current tab, relatedTarget is the one we came from.
            var target = e.target ? e.target : e.srcElement;
            var relatedTarget = e.relatedTarget ? e.relatedTarget : e.fromElement;

            var detailsTab = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_DETAILS_TAB_NAME'); ?>";
            var leavingTab = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_UNSAVED_CHANGES_DIALOG_LEAVING_TAB'); ?>";
            var applicationFormOnLeave = $('#application-form').serializeAll();

            if (relatedTarget.innerHTML == detailsTab && applicationFormOnEnter != applicationFormOnLeave) {
                $('#confirm-unsaved-stay-button').on('click', function (e) {
                    // Unbind both click handlers
                    $('#confirm-unsaved-stay-button').unbind('click');
                    $('#confirm-unsaved-leave-button').unbind('click');

                    $("#confirm-unsaved").modal('hide');
                });

                $('#confirm-unsaved-leave-button').on('click', function (e) {
                    // Unbind both click handlers
                    $('#confirm-unsaved-stay-button').unbind('click');
                    $('#confirm-unsaved-leave-button').unbind('click');

                    // Reset the form
                    resetApplicationForm();

                    // Navigate to the new tab
                    $hash = $(target).prop('hash');
                    $('#tabs a[href=' + $hash + ']').tab('show')

                    $("#confirm-unsaved").modal('hide');
                });

                e.preventDefault();
                showUnsavedChangesDialog(leavingTab);
            }
        });

        // Submit application form data
        $('#application-submit-button').on('click', function (e) {
            // Prevent the 'beforeunload' handler from stopping the submit
            applicationFormOnEnter = $('#application-form').serializeAll();

            $('#application-form').submit();
        });

        // Cancel application editing and return to 'view'
        $('#application-cancel-button').on('click', function (e) {
            // Prevent the 'beforeunload' handler from stopping the submit
            resetApplicationForm();

            document.location.href = '<?php echo $viewAppURL; ?>';
        });

        // Update the 'tab' portion of the URL in the browser address bar.
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            // target is current tab, relatedTarget is the one we came from.
            var target = e.target ? e.target : e.srcElement;
            var relatedTarget = e.relatedTarget ? e.relatedTarget : e.fromElement;
            var currentURL = window.location.href;
            var newURL = null;

            var detailsTab = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_DETAILS_TAB_NAME'); ?>";
            var authTab = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_AUTHENTICATION_TAB_NAME'); ?>";
            var sharingTab = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_SHARING_TAB_NAME'); ?>";

            if (relatedTarget.innerHTML == detailsTab) {
                newURL = currentURL.replace('details', target.innerHTML == authTab ? 'authentication' : 'sharing');
            } else if (relatedTarget.innerHTML == authTab) {
                newURL = currentURL.replace('authentication', target.innerHTML == detailsTab ? 'details' : 'sharing');
            } else if (relatedTarget.innerHTML == sharingTab) {
                newURL = currentURL.replace('sharing', target.innerHTML == detailsTab ? 'details' : 'authentication');
            }

            if (newURL) {
                window.history.replaceState([], '', newURL);
            }
        });

        // Set delete confirmation text and form.
        $('#confirm-delete').on('show.bs.modal', function (e) {
            var relatedTarget = e.relatedTarget ? e.relatedTarget : e.fromElement;

            var deleteTitle = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_CONFIRM_DELETE_DIALOG_TITLE'); ?>";
            var deleteHtml = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_CONFIRM_DELETE_DIALOG_TEXT'); ?>";
            var deleteButton = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_CONFIRM_DELETE_DIALOG_PRIMARY_ACTION_LABEL'); ?>";

            $('#confirm-delete-dialog-title').text(sprintf(deleteTitle, $(relatedTarget).data('object')));
            $('#confirm-delete-dialog-text').html(sprintf(deleteHtml, escapeHTML($(relatedTarget).data('name'))));
            $('#confirm-delete-button').text(sprintf(deleteButton, $(relatedTarget).data('object')));
            $('#confirm-delete-button').data('form', $(relatedTarget).closest('form'));
        });

        // Set remove confirmation text and form.
        $('#confirm-remove').on('show.bs.modal', function (e) {
            var relatedTarget = e.relatedTarget ? e.relatedTarget : e.fromElement;

            var removeTitle = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_CONFIRM_REMOVE_DIALOG_TITLE'); ?>";
            var removeHtml = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_CONFIRM_REMOVE_DIALOG_TEXT'); ?>";
            var removeButton = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_CONFIRM_REMOVE_DIALOG_PRIMARY_ACTION_LABEL'); ?>";

            $('#confirm-remove-dialog-title').text(sprintf(removeTitle, $(relatedTarget).data('object')));
            $('#confirm-remove-dialog-text').html(sprintf(removeHtml, escapeHTML($(relatedTarget).data('name'))));
            $('#confirm-remove-button').text(sprintf(removeButton, $(relatedTarget).data('object')));
            $('#confirm-remove-button').data('form', $(relatedTarget).closest('form'));
        });

        // Reset the api key modal.
        $('#api-key-details').on('hide.bs.modal', function (e) {
            $('#key-id').val('');
            $('#api-key-cors-origins').val('');
            $('#api-edit-key-form').valid();
        });

        // Reset the oauth client modal.
        $('#oauth-client-details').on('hide.bs.modal', function (e) {
            $('#client-id').val('');
            $("#oauth-client-type-confidential").attr('checked', 'checked');
            $("#oauth-client-cors-origins").val('');
            $("#oauth-client-redirect-urls").val('');
            $("#oauth-client-x509-certificate").val('');
            $('#oauth-client-form').valid();
        });

        // Reset the external oauth client modal.
        $('#ext-oauth-client-details').on('hide.bs.modal', function (e) {
            $('#ext-client-id').val('');
            $("#ext-oauth-client-cors-origins").val('');
            $('#oauth-client-form').valid();
        });

        // Reset the add shared user modal.
        $('#add-shared-users-details').on('hide.bs.modal', function (e) {
            $('#add-shared-users-details').find($('input:checkbox')).attr('checked', false);
        });

        // Refresh the page on oauth secret modal close if a new secret was generated.
        var newSecretLabel = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_SECRET_KEY_DIALOG_NEW_SECRET_LABEL'); ?>" + ':';
        $('#oauth-secret-details').on('hide.bs.modal', function (e) {
            if ($('#oauth-secret-label').text() == newSecretLabel) {
                e.preventDefault();
                $('#oauth-close-form').submit();
            }
        });

        // Submit api key form data.
        $('#api-key-submit-button').on('click', function (e) {
            $('#api-edit-key-form').submit();
        });

        // Submit oauth client form data.
        $('#oauth-client-submit-button').on('click', function (e) {
            $('#oauth-client-form').submit();
        });

        // Submit external oauth form data
        $('#ext-oauth-client-submit-button').on('click', function (e) {
            $('#ext-oauth-client-form').submit();
        });

        // Submit oauth new secret request (AJAX).
        $('#oauth-new-secret-button').on('click', function (e) {
            // var spinner = new Spinner(spinOpts).spin($('body')[0]);

            // TODO: Handle AJAX errors
            $.post('<?php echo $oauthClientNewSecretURL; ?>', $('#oauth-secret-form').serialize(), function (response) {
                $('#oauth-secret-label').text(newSecretLabel);
                $('#oauth-secret-key').text(response.data.secret);

                // spinner.stop();
            });
        });

        // Submit add shared user form data.
        $('#add-shared-users-submit-button').on('click', function (e) {
            $('#add-shared-users-form').submit();
        });

        // Submit shared user permission change (AJAX).
        $('#shared-user-edit').find($('input[type=radio]')).on('click', function (e) {
            // var spinner = new Spinner(spinOpts).spin($('body')[0]);

            // TODO: Handle AJAX errors
            $.post('<?php echo $updateSharedUserURL; ?>', $(this).closest('#shared-user-form').serialize(), function (response) {
                // spinner.stop();
            });
        });

        // Submit confirm delete form.
        $('#confirm-delete-button').on('click', function (e) {
            $(this).data('form').submit();
        });

        // Submit confirm remove form.
        $('#confirm-remove-button').on('click', function (e) {
            $(this).data('form').submit();
        });

        $('#api-keys-collapse').on('hide.bs.collapse', toggleChevron);
        $('#api-keys-collapse').on('show.bs.collapse', toggleChevron);
        $('#oauth-creds-collapse').on('hide.bs.collapse', toggleChevron);
        $('#oauth-creds-collapse').on('show.bs.collapse', toggleChevron);
        $('#ext-oauth-creds-collapse').on('hide.bs.collapse', toggleChevron);
        $('#ext-oauth-creds-collapse').on('show.bs.collapse', toggleChevron);

        // Initialize tooltips
        $('.api-tooltip').tooltip({placement: 'bottom'});
    });

    function showAPIKeyDialog(details) {
        (function ($) {
            if (details) {
                // Set hidden client id form input.
                $("#key-id").val(details['id']);

                // Convert the cors origins array to newline separated text.
                var corsOrigins = details['corsOrigins'];
                if (corsOrigins.length) {
                    corsOrigins = corsOrigins.join("\n");
                }

                $("#api-key-dialog-title").text(
                    "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEY_DIALOG_TITLE'); ?>"
                );
                $("#api-key-submit-button").text(
                    "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_API_KEY_DIALOG_PRIMARY_ACTION_LABEL'); ?>"
                );

                $("#api-key-id").val(details['id']);
                $("#api-key-cors-origins").val(corsOrigins ? corsOrigins : '');

                $(".api-key-edit-mode").show();
            }
            $("#api-key-details").modal({keyboard: true});
        })(jQuery);
    }

    function showOAuthDialog(details) {
        (function ($) {
            if (details) {
                // Set form action to 'update'.
                $('#oauth-client-form').attr('action', '<?php echo $oauthClientUpdateURL; ?>');

                // Set hidden client id form input.
                $("#client-id").val(details['id']);

                // Check the appropriate radio button.
                if (details['type'] == 'confidential') {
                    $("#oauth-client-type-confidential").attr('checked', 'checked');
                } else if (details['type'] == 'public') {
                    $("#oauth-client-type-public").attr('checked', 'checked');
                }

                // Convert the redirect urls and cors origins arrays to newline separated text.
                var corsOrigins = details['corsOrigins'];
                if (corsOrigins.length) {
                    corsOrigins = corsOrigins.join("\n");
                }

                var redirectUrls = details['redirectUrls'];
                if (redirectUrls.length) {
                    redirectUrls = redirectUrls.join("\n");
                }

                $("#oauth-client-dialog-title").text(
                    "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_EDIT_TITLE'); ?>"
                );
                $("#oauth-client-submit-button").text(
                    "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_PRIMARY_ACTION_EDIT_LABEL'); ?>"
                );

                $("#oauth-client-id").text(details['id']);
                $("#oauth-client-cors-origins").val(corsOrigins ? corsOrigins : '');
                $("#oauth-client-redirect-urls").val(redirectUrls ? redirectUrls : '');
                $("#oauth-client-x509-certificate").val(details['cert'] ? details['cert'] : '');

                $(".oauth-client-edit-mode").show();
            } else {
                // Set form action to 'create'
                $('#oauth-client-form').attr('action', '<?php echo $oauthClientCreateURL; ?>');

                // Default to confidential
                $("#oauth-client-type-confidential").attr('checked', 'checked');

                $("#oauth-client-dialog-title").text(
                    "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_GENERATE_TITLE'); ?>"
                );
                $("#oauth-client-submit-button").text(
                    "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_OAUTH_CLIENT_DIALOG_PRIMARY_ACTION_GENERATE_LABEL'); ?>"
                );

                $(".oauth-client-edit-mode").hide();
            }
            $("#oauth-client-details").modal({keyboard: true});
        })(jQuery);
    }

    // Called from button to generate new External OAuth
    // Modal popup
    function showExtOAuthDialog(details) {
        (function ($) {
            if (details) {
                // Set form action to 'update'.
                $('#ext-oauth-client-form').attr('action', '<?php echo $extClientUpdateURL; ?>');

                // Set hidden object id form input.
                $("#object-id").val(details['id']);
                // Set hidden object enabled
                $("#object-enabled").val(details['enabled']);

                // Convert the redirect urls and cors origins arrays to newline separated text.
                var corsOrigins = details['corsOrigins'];
                if (corsOrigins.length) {
                    corsOrigins = corsOrigins.join("\n");
                }

                $("#ext-oauth-client-dialog-title").text(
                    "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENT_DIALOG_EDIT_TITLE'); ?>"
                );
                $("#ext-oauth-client-submit-button").text(
                    "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENT_DIALOG_PRIMARY_ACTION_EDIT_LABEL'); ?>"
                );

                $("#ext-oauth-client-id").val(details['clientId']);
                $("#ext-oauth-client-cors-origins").val(corsOrigins ? corsOrigins : '');

                $(".est-oauth-client-edit-mode").show();
            } else {
                // Set form action to 'create'
                $('#ext-oauth-client-form').attr('action', '<?php echo $extClientCreateURL; ?>');

                $("#ext-oauth-client-dialog-title").text(
                    "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENT_DIALOG_GENERATE_TITLE'); ?>"
                );
                $("#ext-oauth-client-submit-button").text(
                    "<?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_EXT_OAUTH_CLIENT_DIALOG_PRIMARY_ACTION_GENERATE_LABEL'); ?>"
                );

            }
            $("#ext-oauth-client-details").modal({keyboard: true});
        })(jQuery);
    }

    function showAPIKeySecret(secret) {
        (function ($) {
            $("#api-key-secret-key").text(secret);
            $("#api-key-secret-details").modal({keyboard: true});
        })(jQuery);
    }

    function showOAuthSecret(secret, oauthClientId) {
        (function ($) {
            // Set hidden client id form input.
            $("#secret-client-id").val(oauthClientId);

            $("#oauth-secret-key").text(secret);
            $("#oauth-secret-details").modal({keyboard: true});
        })(jQuery);
    }

    function showUnsavedChangesDialog(leavingFrom) {
        (function ($) {
            var title = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_UNSAVED_CHANGES_DIALOG_TITLE'); ?>";
            var text = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_UNSAVED_CHANGES_DIALOG_TEXT'); ?>";
            var stay = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_UNSAVED_CHANGES_DIALOG_PRIMARY_ACTION_LABEL'); ?>";
            var leave = "<?php echo JText::_('COM_APIPORTAL_APPLICATION_UNSAVED_CHANGES_DIALOG_SECONDARY_ACTION_LABEL'); ?>";

            $('#confirm-unsaved-dialog-title').text(sprintf(title, toProperCase(leavingFrom)));
            $('#confirm-unsaved-dialog-text').text(sprintf(text, leavingFrom));
            $('#confirm-unsaved-stay-button').text(sprintf(stay, leavingFrom));
            $('#confirm-unsaved-leave-button').text(sprintf(leave, leavingFrom));

            $("#confirm-unsaved").modal({keyboard: true});
        })(jQuery);
    }
</script>
