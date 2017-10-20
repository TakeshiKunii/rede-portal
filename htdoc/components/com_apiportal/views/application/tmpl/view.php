<?php
defined('_JEXEC') or die('Restricted access');

$appListURL = JRoute::_('index.php?option=com_apiportal&view=applications', false);
JFactory::getSession()->set('appUserId',JFactory::getSession()->get('user')->get('id'));
// Manage hidden tab for public API user
$publicApiAction = ApiPortalHelper::hasHiddenTabforPublicUser();

$error = false;
if (!isset($this->item)) {
    $error = true;
} else {
    $application = isset($this->item->application) ? $this->item->application : null;
    $permissions = ApiportalHelper::getPermission($application);
    if (!$application) {
        $error = true;
        $app = JFactory::getApplication();
        $app->enqueueMessage(JText::_('COM_APIPORTAL_APPLICATION_VIEW_NOT_FOUND_ALERT'), 'notice');
    }
}

if (!$error) {
    $itemId = JRequest::getVar('Itemid', 0, 'INT');
    $itemId = $this->escape($itemId);
    $deleteAppURL = JURI::base(false) . 'index.php?option=com_apiportal&task=application.deleteApp&Itemid=' . $itemId;

    $discoveredApis = isset($this->item->apis) ? $this->item->apis : array();
    $selectedApis = isset($application->apis) ? $application->apis : array();

    $apiKeys = isset($application->apikeys) ? $application->apikeys : array();
    $oauthClients = isset($application->oauth) ? $application->oauth : array();
    $applicationUsers = isset($application->users) ? $application->users : array();
    $isExtClients = empty($application->extClients) ? false : true;

    $permission = ApiPortalHelper::getPermission($application);

    $currentUserId = ApiPortalHelper::getCurrentUserPortalId();

    // We only need to use JRoute on URLs that are visible in the browser address bar
    $appListURL = JRoute::_('index.php?option=com_apiportal&view=applications', false);
    $appEditURL = JRoute::_('index.php?option=com_apiportal&view=application&layout=edit&tab=details&applicationId=' . $application->id, false);
    $appEditURLAuth = JRoute::_('index.php?option=com_apiportal&view=application&layout=edit&tab=authentication&applicationId=' . $application->id, false);

    $appImageURL = JURI::base(false) . 'index.php?option=com_apiportal&view=image&format=raw&applicationId=' . $application->id;
    $noImageURL = 'components/com_apiportal/assets/img/no_image.png';

    $tab = JRequest::getString('tab', null);
    $tab = ApiPortalHelper::cleanHtml($tab, false, true);
    if ($tab == null) {
        $tab = 'details';
    }

    function formatQuota($methods)
    {
        $noRestrictions = JText::_('COM_APIPORTAL_APPLICATION_VIEW_QUOTA_NO_RESTRICTIONS');
        if ($methods == null) {
            return "<em>$noRestrictions</em>";
        } else {
            $html = "<ul class=\'quota-methods\'>";
            foreach ($methods as $name => $restrictions) {
                $html .= "<li class=\'quota-method\'><strong>".ApiPortalHelper::cleanHtml($name)."</strong></li>";
                $html .= "<ul class=\'quota-restrictions\'>";
                if ($restrictions) {
                    foreach ($restrictions as $restriction) {
                        $html .= "<li class=\'quota-restriction\'>".ApiPortalHelper::cleanHtml($restriction)."</li>";
                    }
                } else {
                    $html .= "<li class=\'quota-restriction\'>$noRestrictions</li>";
                }
                $html .= "</ul>";
            }
            $html .= "</ul>";

            return $html;
        }
    }

    function formatScopes($securityProfile, $methods)
    {
        $html = "<ul class=\'endpoint-scopes\'>";
        foreach ($securityProfile->devices as $device) {
            if ($device->type == "oauth") {
                if (isset($device->scopes)) {
                    foreach ($device->scopes as $scope) {
                        $html .= "<li class=\'endpoint-scope\'>" . ApiPortalHelper::cleanHtml($scope) . "</li>";
                    }
                }
            }
        }
        $html .= "</ul>";
        if (count($methods) > 0) {
            $html .= "<ul class=\'method-scopes\'>";
            foreach ($methods as $method) {
                if (count($method->oauthscopes) > 0) {
                    $html .= "<li class=\'scope-method\'>" . ApiPortalHelper::cleanHtml($method->name) . "</li>";
                    $html .= "<ul class=\'scopes\'>";
                    foreach ($method->oauthscopes as $scope) {
                        $html .= "<li class=\'scope\'>" . ApiPortalHelper::cleanHtml($scope) . "</li>";
                    }
                    $html .= "</ul>";
                }
            }
            $html .= "</ul>";
        }
        return $html;
    }

    // For image thumbnails
    $document = JFactory::getDocument();
    $document->addStyleSheet('components/com_apiportal/assets/css/jasny-bootstrap.css');
    $document->addScript('components/com_apiportal/assets/js/jasny-bootstrap.js');

    // For date/time formatting
    $document->addScript('components/com_apiportal/assets/js/moment.js');

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
  <h1 class="auto"><?php echo sprintf(JText::_('COM_APIPORTAL_APPLICATION_VIEW_TITLE'), ApiPortalHelper::cleanHtml($application->name, false, true)); ?></h1>
  <p class="auto"><em><!-- placeholder --></em></p>
</div>

<?php 




?>

<?php if ($error) {
    return;
} ?>

<div class="btn-toolbar" role="toolbar" <?php echo $publicApiAction;?>>
    <div class="auto">
    <div class="action-group">
<?php if (ApiPortalHelper::isApiManagerUser() && $permission == 'manage') { ?>
        <form id="delete-application-form" method="post" action="<?php echo $deleteAppURL; ?>">
            <button type="button" class="btn btn-default icon delete" data-toggle="modal" data-target="#confirm-delete"
                    data-name="<?php echo $this->escape($application->name); ?>"
                    data-object="<?php echo JText::_('COM_APIPORTAL_APPLICATION_APPLICATION_OBJECT'); ?>">
                <?php echo JText::_('COM_APIPORTAL_APPLICATION_EDIT_DELETE_LABEL'); ?>
            </button>
            <input type="hidden" name="applicationId" value="<?php echo $application->id; ?>"/>
            <input type="hidden" name="applicationName" value="<?php echo $this->escape($application->name); ?>"/>
            <input type="hidden" name="viewName" value="view"/>
            <?php echo JHtml::_( 'form.token' ); ?>
        </form>
<?php } ?>

    <?php if (ApiPortalHelper::isPending($application)) { ?>
        <button type="button" class="btn btn-default" disabled>
            <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_PENDING_LABEL'); ?>
        </button>
    <?php } else if (($permission == 'manage' || $this->item->application->createdBy == $this->userPid) && $permission != 'view') {//if orgAdmin has rights or he is creator of the application ?>
        <a href="<?php echo $appEditURL; ?>" class="btn btn-default icon pencil">
            <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_EDIT_LABEL'); ?>
        </a>
    <?php } else { ?>
        <button type="button" class="btn btn-default icon info-circle" disabled>
            <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SHARED_VIEW_ONLY_LABEL'); ?>
        </button>
    <?php } ?>
        </div>
    </div>
</div>

<div class="body auto">
<div class="tabs" id="tabs">
    <ul class="nav nav-tabs">
        <li <?php echo($tab == 'details' ? 'class="active"' : ''); ?>>
            <a href="#details" data-toggle="tab">
                <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_DETAILS_TAB_NAME'); ?>
            </a>
        </li>
        <?php if (!ApiPortalHelper::isPending($application)) { ?>
            <li <?php echo($tab !== null && $tab !== '' && $tab !== 'details' ? 'class="active"' : '');  echo $publicApiAction; ?>>
                <a href="<?php echo JRoute::_('index.php?option=com_apiportal&view=application&layout=view&applicationId=' . urlencode($application->id) . '&cn=' . urlencode($application->id) . '&tab=messages'); ?>"
                   data-toggle="TODO">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_METRICS_TAB_NAME'); ?>
                </a>
            </li>
        <?php } ?>
    </ul>
    <div class="tab-content">
        <!-- Application Details Tab -->
        <div class="tab-pane fade <?php echo($tab == 'details' ? 'in active' : ''); ?>" id="details">

            <h2><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_SECTION'); ?></h2>

            <div id="general" class="col-sm-12 panel panel-default">
                <div class="col-sm-2">
                    <?php if ($application->image) { ?>
                        <?php if (strpos($application->image, 'base64')) { ?>
                            <div class="application logo"
                                 style="background-image: url('<?php echo $application->image; ?>')"></div>
                        <?php } else { ?>
                            <div class="application logo"
                                 style="background-image: url('<?php echo $appImageURL; ?>')"></div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="application logo" style="background-image: url('<?php echo $noImageURL; ?>')"></div>
                    <?php } ?>
                </div>
                <!-- .col-sm-2 -->

                <div class="col-sm-5">
                    <div class="control-group row">
                        <label class="col-sm-3 control-label" for="name">
                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_NAME_LABEL'); ?>:
                        </label>

                        <div class="col-sm-9" id="name"
                             name="name"><?php echo $this->escape($application->name); ?></div>
                    </div>
                    <!-- .control-group -->

                    <div class="control-group row">
                        <label class="col-sm-3 control-label" for="description">
                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_DESCRIPTION_LABEL'); ?>:
                        </label>

                        <div class="col-sm-9" id="description"
                             name="description"><?php echo $this->escape($application->description); ?></div>
                    </div>
                    <!-- .control-group -->

                    <div class="control-group row">
                        <label class="col-sm-3 control-label" for="organization">
                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_ORGANIZATION_LABEL'); ?>:
                        </label>

                        <div class="col-sm-9" id="organization"
                             name="organization"><?php echo $this->escape($application->organizationName); ?></div>
                    </div>
                    <!-- .control-group -->

                    <div class="control-group row">
                        <label class="col-sm-3 control-label" for="phone">
                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_PHONE_LABEL'); ?>:
                        </label>

                        <div class="col-sm-9" id="phone"
                             name="phone"><?php echo $this->escape($application->phone); ?></div>
                    </div>
                    <!-- .control-group -->

                    <div class="control-group row">
                        <label class="col-sm-3 control-label" for="email">
                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_EMAIL_LABEL'); ?>:
                        </label>
                        <a class="col-sm-9" id="email" name="email" href="mailto:<?php echo $this->escape($application->email); ?>"
                           target="_top">
                            <?php echo $this->escape($application->email); ?>
                        </a>
                    </div>
                    <!-- .control-group -->
                </div>
                <!-- .col-sm-5 -->

                <div class="col-sm-5">
                    <div class="control-group row">
                        <label class="col-sm-3 control-label" for="enabled">
                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_ENABLED_LABEL'); ?>:
                        </label>

                        <div class="col-sm-9" id="enabled" name="enabled">
                            <?php if (ApiPortalHelper::isEnabled($application)) { ?>
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_ENABLED_YES_TEXT'); ?>
                            <?php } else { ?>
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_ENABLED_NO_TEXT'); ?>
                            <?php } ?>
                        </div>
                    </div>
                    <!-- .control-group -->

                    <div class="control-group row">
                        <label class="col-sm-3 control-label" for="state">
                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_STATUS_LABEL'); ?>:
                        </label>

                        <div
                            class="col-sm-9 <?php echo(ApiPortalHelper::isPending($application) ? 'pending-approval' : ''); ?>"
                            id="state" name="state">
                            <?php if (ApiPortalHelper::isPending($application)) { ?>
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_STATUS_PENDING_LABEL'); ?>
                            <?php } else { ?>
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_STATUS_APPROVED_LABEL'); ?>
                            <?php } ?>
                        </div>
                    </div>
                    <!-- .control-group -->

                    <div class="control-group row">
                        <label class="col-sm-3 control-label" for="created-by">
                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_CREATED_BY_LABEL'); ?>:
                        </label>

                        <div class="col-sm-9" id="created-by"
                             name="created-by"><?php echo $this->escape($application->createdByName); ?></div>
                    </div>
                    <!-- .control-group -->

                    <div class="control-group row">
                        <label class="col-sm-3 control-label" for="created-on">
                            <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_GENERAL_CREATED_ON_LABEL'); ?>:
                        </label>

                        <div class="col-sm-9" id="created-on" name="created-on">
                           <!--   <script>displayDate(<?php echo $application->createdOn; ?>, 'D MMM YYYY');</script>-->
                            
                            <?php echo ApiPortalHelper::convertDateTime($application->createdOn,JText::_('COM_APIPORTAL_LOCAL_DATE_FORMAT')); ?>
                   
                         
                        </div>
                    </div>
                    <!-- .control-group -->
                </div>
                <!-- .col-sm-5 -->
            </div>
            <!-- .col-sm-12 -->

            <div id="selected-apis" class="panel panel-default">
                <h2><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_APIS_SECTION'); ?></h2>
                <!-- Check if there are actually any subscribed APIS's, and if not, don't just render table header -->
                <?php if (!$selectedApis) { ?>
                    <p class="no-apis-selected">
                        <em><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_APIS_NO_APIS_SELECTED'); ?></em>
                    </p>
                <?php } else { ?>
                    <table class="table table-striped table-bordered table-hover" id="apis-table">
                        <thead>
                        <tr>
                            <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_APIS_NAME_HEADER'); ?></th>
                            <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_APIS_DESCRIPTION_HEADER'); ?></th>
                            <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_APIS_ACTIONS_HEADER'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($discoveredApis as $discovered): ?>
                            <?php foreach ($selectedApis as $api): ?>
                                <?php if ($discovered->apiId == $api->apiId) { ?>
                                    <tr>
                                        <!-- Discovered APIs can be enabled/disabled - Selected APIs can be approved/pending:
                                            - If enabled and pending, apply pending icon and disable name/description fields
                                            - If enabled and approved, simply display the value
                                            - If disabled and pending, apply pending icon and disable name/description fields
                                            - If disabled and approved, apply disabled icon and disable name/description fields

                                            Quotas can be viewed regardless of the APIs state.
                                        -->
                                        <?php if (ApiPortalHelper::isEnabled($discovered)) { ?>
                                            <?php if (ApiPortalHelper::isPending($api)) { ?>
                                                <td class="disabled-text-effect col-lg-2">
                                                    <div class="api-tooltip api-pending" data-toggle="tooltip"
                                                         title="<?php echo JText::_('COM_APIPORTAL_APPLICATIONS_API_PENDING'); ?>">
                                                        <i class="fa fa-clock-o"></i> <?= $this->escape($discovered->name); ?>
                                                    </div>
                                                </td>
                                            <?php } else { ?>
                                                <td class="col-lg-2">
                                                    <?php
                                                    $apiUrl = JUri::base().'index.php?option=com_apiportal&view=apitester&usage=api&tab=tests&Itemid=0&apiName=' . rawurlencode($discovered->name) . '&apiId=' . $discovered->apiId;
                                                    ?>
                                                    <a href="<?= (string)$apiUrl ?>">
                                                        <?= $this->escape($discovered->name); ?>
                                                    </a>
                                                </td>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <?php if (ApiPortalHelper::isPending($api)) { ?>
                                                <td class="disabled-text-effect col-lg-2">
                                                    <div class="api-tooltip api-pending" data-toggle="tooltip"
                                                         title="<?php echo JText::_('COM_APIPORTAL_APPLICATIONS_API_PENDING'); ?>">
                                                        <i class="fa fa-clock-o"></i> <?= $this->escape($discovered->name); ?>
                                                    </div>
                                                </td>
                                            <?php } else { ?>
                                                <td class="disabled-text-effect col-lg-2">
                                                    <div class="api-tooltip api-disabled" data-toggle="tooltip"
                                                         title="<?php echo JText::_('COM_APIPORTAL_APPLICATIONS_API_DISABLED'); ?>">
                                                        <i class="fa fa-ban"></i> <?= $this->escape($discovered->name); ?>
                                                    </div>
                                                </td>
                                            <?php } ?>
                                        <?php } ?>

                                        <?php if (!ApiPortalHelper::isEnabled($discovered) || ApiPortalHelper::isPending($api)) { ?>
                                            <td class="hidden-xs disabled-text-effect description-api-<?= $discovered->id ?>">

                                            </td>
                                        <?php } else { ?>
                                            <td class="hidden-xs description-api-<?= $discovered->id ?>">

                                            </td>
                                        <?php } ?>

                                        <script>
                                            var apiDescription = <?= json_encode($discovered->description) ?>;
                                            jQuery('.description-api-<?= $discovered->id ?>').append(cleanMarkdownTxt(apiDescription));
                                        </script>

                                        <td class="col-lg-2">
                                            <div class="dropdown">
                                                <a class="btn dropdown-toggle icon chevron-down" data-toggle="dropdown" href="#">
                                                    <?php echo JText::_('COM_APIPORTAL_APPLICATIONS_API_ACTIONS'); ?>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <button type="button" class="btn btn-link"
                                                                onClick="showQuotasDialog('<?php echo formatQuota($application->quotas[$api->apiId]); ?>');"> <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_APIS_ACTIONS_VIEW_QUOTA'); ?> </button>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="btn btn-link"
                                                                onClick="showScopesDialog('<?php echo formatScopes($api->securityProfile, $api->methods); ?>')"> <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_APIS_ACTIONS_VIEW_SCOPES'); ?> </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>

            <!-- API Keys -->
            <div class="panel panel-default" id="api-keys-collapse">
                <div class="panel-heading">
                    <h2 class="panel-title">
                        <a data-toggle="collapse" data-parent="#api-keys-collapse" href="#collapse-api-keys-overview">
                            <i class="indicator fa fa-chevron-down"></i>

                            <strong><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_API_KEYS_SECTION'); ?></strong>
                        </a>
                    </h2>
                </div>
                <!-- .panel-heading -->

                <div class="panel-collapse collapse in" id="collapse-api-keys-overview">
                    <div class="panel-body" id="api-keys-overview">
                        <?php if (!$apiKeys) { ?>
                            <p class="no-api-keys-defined">
                                <em>
                                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_API_KEYS_NO_KEYS_DEFINED') . '.'; ?>
                                    <?php if (($application->state == 'approved' && $permissions == 'manage') || $this->delegateApplicationAdministration === true && $application->state == 'approved' && $permission != 'view'):?>
                                        <?php echo sprintf(JText::_('COM_APIPORTAL_APPLICATION_EDIT_APPLICATION_WITH_CRDENTIALS'), $appEditURLAuth);?>
                                    <?php endif;?>
                                </em>
                            </p>
                        <?php } else { ?>
                            <table class="table table-striped table-bordered table-hover" id="api-keys-table">
                                <thead>
                                <tr>
                                    <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_API_KEYS_NAME_HEADER'); ?></th>
                                    <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_API_KEYS_CORS_HEADER'); ?></th>
                                    <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_API_KEYS_CREATED_HEADER'); ?></th>
                                    <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_API_KEYS_ACTIONS_HEADER'); ?></th>
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
                                        <td class="hidden-xs <?php echo(ApiPortalHelper::isEnabled($apiKey) ? '' : 'disabled-text-effect'); ?>">
                                           <!--   <script>displayDate(<?php echo $apiKey->createdOn; ?>, 'D MMM YYYY, HH:mm');</script> -->
                                           <?php echo ApiPortalHelper::convertDateTime($apiKey->createdOn,JText::_('COM_APIPORTAL_LOCAL_DATE_TIME_FORMAT')); ?>
                                        </td>
                                        <td>
                                            <a href='#' onClick='showAPIKeySecret("<?php echo $apiKey->secret; ?>")'>
                                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_API_KEYS_ACTIONS_VIEW_SECRET'); ?>
                                            </a>
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
                            <i class="indicator fa fa-chevron-down"></i>
                            <strong><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_OAUTH_CLIENTS_SECTION'); ?></strong>
                        </a>
                    </h2>
                </div>
                <!-- .panel-heading -->

                <div class="panel-collapse collapse in" id="collapse-oauth-overview">
                    <div class="panel-body" id="oauth-overview">
                        <?php if (!$oauthClients) { ?>
                            <p class="no-oauth-clients-defined">
                                <em>
                                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_OAUTH_CLIENTS_NO_CLIENTS_DEFINED') . '.'; ?>
                                    <?php if (($application->state == 'approved' && $permissions == 'manage') || $this->delegateApplicationAdministration === true && $application->state == 'approved' && $permission != 'view'):?>
                                        <?php echo sprintf(JText::_('COM_APIPORTAL_APPLICATION_EDIT_APPLICATION_WITH_CRDENTIALS'), $appEditURLAuth);?>
                                    <?php endif;?>
                                </em>
                            </p>
                        <?php } else { ?>
                            <table class="table table-striped table-bordered table-hover" id="oauth-clients-table">
                                <thead>
                                <tr>
                                    <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_OAUTH_CLIENTS_ID_HEADER'); ?></th>
                                    <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_OAUTH_CLIENTS_TYPE_HEADER'); ?></th>
                                    <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_OAUTH_CLIENTS_CORS_HEADER'); ?></th>
                                    <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_OAUTH_CLIENTS_URLS_HEADER'); ?></th>
                                    <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_OAUTH_CLIENTS_CREATED_HEADER'); ?></th>
                                    <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_OAUTH_CLIENTS_ACTIONS_HEADER'); ?></th>
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
                                            <!-- TODO: externalize Confidential/Public -->
                                            <?php if ($oauthClient->type == 'confidential') { ?>
                                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_OAUTH_CLIENTS_TYPE_CONFIDENTIAL'); ?>
                                            <?php } else { ?>
                                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_OAUTH_CLIENTS_TYPE_PUBLIC'); ?>
                                            <?php } ?>
                                        </td>
                                        <td class="hidden-xs <?php echo(ApiPortalHelper::isEnabled($oauthClient) ? '' : 'disabled-text-effect'); ?>">
                                            <?= $this->escape(implode("<br/>", explode(",", htmlspecialchars(implode(",", $oauthClient->corsOrigins))))); ?>
                                        </td>
                                        <td class="hidden-xs <?php echo(ApiPortalHelper::isEnabled($oauthClient) ? '' : 'disabled-text-effect'); ?>">
                                            <?= $this->escape(implode(", ", $oauthClient->redirectUrls)); ?>
                                        </td>
                                        <td class="hidden-xs <?php echo(ApiPortalHelper::isEnabled($oauthClient) ? '' : 'disabled-text-effect'); ?>">
                                         <!--    <script>displayDate(<?php echo $oauthClient->createdOn; ?>, 'D MMM YYYY, HH:mm');</script> -->
                                            <?php echo ApiPortalHelper::convertDateTime($oauthClient->createdOn,JText::_('COM_APIPORTAL_LOCAL_DATE_TIME_FORMAT')); ?>
                                            
                                        </td>
                                        <td>
                                            <a href='#'
                                               onClick='showOAuthSecret("<?php echo $oauthClient->secret; ?>")'>
                                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_OAUTH_CLIENTS_ACTIONS_VIEW_SECRET'); ?>
                                            </a>
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

            <!-- External OAuth Credentials -->
            <div class="panel panel-default" id="ext-oauth-creds-collapse" <?php echo $publicApiAction;?>>
                <div class="panel-heading">
                    <h2 class="panel-title">
                        <a data-toggle="collapse" data-parent="#ext-oauth-creds-collapse" href="#ext-collapse-oauth-overview">
                            <i class="indicator fa fa-chevron-down"></i>
                            <strong><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_EXT_OAUTH_CLIENTS_SECTION'); ?></strong>
                        </a>
                    </h2>
                </div>
                <!-- .panel-heading -->
            <div class="panel-collapse collapse in" id="ext-collapse-oauth-overview">
                <div class="panel-body" id="ext-oauth-overview">
                    <?php if (!$isExtClients) { ?>
                        <p class="no-oauth-clients-defined">
                            <em>
                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_EXT_OAUTH_CLIENTS_NO_CLIENTS_DEFINED') . '.'; ?>
                                <?php if (($application->state == 'approved' && $permissions == 'manage') || $this->delegateApplicationAdministration === true && $application->state == 'approved' && $permission != 'view'):?>
                                    <?php echo sprintf(JText::_('COM_APIPORTAL_APPLICATION_EDIT_APPLICATION_WITH_CRDENTIALS'), $appEditURLAuth);?>
                                <?php endif;?>
                            </em>
                        </p>
                    <?php } else { ?>
                        <table class="table table-striped table-bordered table-hover" id="ext-oauth-clients-table">
                            <thead>
                            <tr>
                                <th><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_OAUTH_CLIENTS_ID_HEADER'); ?></th>
                                <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_EXT_OAUTH_CLIENTS_CORS_HEADER'); ?></th>
                                <th class="hidden-xs"><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_EXT_OAUTH_CLIENTS_CREATED_HEADER'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($application->extClients as $extClient): ?>
                                <tr>
                                    <?php if (ApiPortalHelper::isEnabled($extClient)) { ?>
                                        <td><?= $this->escape($extClient->clientId); ?></td>
                                    <?php } else { ?>
                                        <td class="disabled-text-effect"><i
                                                class="fa fa-ban"></i> <?= $this->escape($extClient->clientId); ?></td>
                                    <?php } ?>
                                    <td class="hidden-xs <?php echo(ApiPortalHelper::isEnabled($extClient) ? '' : 'disabled-text-effect'); ?>">
                                        <?= implode("<br/>", explode(",", htmlspecialchars(implode(",", $extClient->corsOrigins)))); ?>
                                    </td>
                                    <td class="hidden-xs <?= ApiPortalHelper::isEnabled($extClient) ? '' : 'disabled-text-effect'; ?>">
                                     <!--    <script>displayDate(<?= $extClient->createdOn; ?>, 'D MMM YYYY, HH:mm');</script> -->
                                     <?php echo ApiPortalHelper::convertDateTime($extClient->createdOn,JText::_('COM_APIPORTAL_LOCAL_DATE_TIME_FORMAT')); ?>
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

            <?php if (!ApiPortalHelper::isCommunity($application)) { ?>
                <div class="panel panel-default" id="sharing-collapse">
                    <div class="panel-heading">
                        <h2 class="panel-title">
                            <a data-toggle="collapse" data-parent="#sharing-collapse" href="#collapse-sharing-overview">
                                <i class="indicator fa fa-chevron-down"></i>
                                <strong><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SHARING_SECTION'); ?></strong>
                            </a>
                        </h2>
                    </div>
                    <!-- .panel-heading -->

                    <div class="panel-collapse collapse in" id="collapse-sharing-overview">
                        <div class="panel-body" id="sharing-overview">
                            <?php if (!$applicationUsers) { ?>
                                <p class="application-not-shared">
                                    <em><?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SHARING_NO_SHARED_USERS'); ?></em>
                                </p>
                            <?php } else { ?>
                                <?php foreach ($applicationUsers as $user): ?>
                                    <div class="col-sm-12 control-group row shared-user-view">
                                        <div class="col-sm-2">
                                            <?php
                                            $sharedUserName = ApiPortalHelper::cleanHtml($user->name);
                                            if ($currentUserId == $user->id) {
                                                $sharedUserName .= ' ';
                                                $sharedUserName .= JText::_('COM_APIPORTAL_APPLICATION_VIEW_SHARING_USER_IS_SELF');
                                            }
                                            ?>
                                            <span class="shared-user-name"><?php echo $sharedUserName; ?></span>
                                        </div>
                                        <div class="col-sm-2 shared-user-permission switch-toggle disabled">
                                            <input id="view-<?php echo $user->id; ?>" type="radio"
                                                <?php echo(isset($user->permission) && isset($user->permission->permission) && $user->permission->permission == 'view' ? 'checked' : ''); ?>
                                                   disabled>
                                            <label for="view-<?php echo $user->id; ?>">
                                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SHARING_SHARED_VIEW_ONLY'); ?>
                                            </label>
                                            <input id="edit-<?php echo $user->id; ?>" type="radio"
                                                <?php echo(isset($user->permission) && isset($user->permission->permission) && $user->permission->permission == 'manage' ? 'checked' : ''); ?>
                                                   disabled>
                                            <label for="edit<?php echo $user->id; ?>">
                                                <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SHARING_SHARED_MANAGE'); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php } ?>
                        </div>
                        <!-- .panel-body -->
                    </div>
                    <!-- .panel-collapse -->
                </div><!-- .panel -->
            <?php } ?>
        </div>
        <!-- .tab-pane -->

        <?php if (!ApiPortalHelper::isPending($application)) { ?>
            <!-- Metrics Tab -->
            <div
                class="tab-pane fade <?php echo($tab !== null && $tab !== '' && $tab !== 'details' ? 'in active' : ''); ?>"
                id="metrics">
                <?php if ($tab !== null && $tab !== '' && $tab !== 'details') { ?>
                    <?php
                    $appUsageTab = true;
                    include JPATH_COMPONENT . '/views/monitoring/tmpl/default.php';
                    ?>
                <?php } ?>
            </div><!-- .tab-pane -->
        <?php } ?>
    </div>
    <!-- .tab-content -->
</div><!-- .tabs -->
</div>
<!-- View API Key Secret Dialog -->
<div class="modal fade" id="api-key-secret-details" tabindex="-1" role="dialog"
     aria-labelledby="api-key-secret-dialog-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title" id="api-key-secret-dialog-title">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SECRET_KEY_DIALOG_TITLE'); ?>
                </h3>
            </div>
            <!-- .modal-header -->

            <div class="modal-body">
                <label class="col-sm-2 control-label hidden-xs" for="api-key-secret-key">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SECRET_KEY_DIALOG_LABEL'); ?>:
                </label>

                <p class="col-sm-10" id="api-key-secret-key"></p>
            </div>
            <!-- .modal-body -->

            <div class="modal-footer">
                <div class="dialog-actions">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">
                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SECRET_KEY_DIALOG_CLOSE_LABEL'); ?>
                    </button>
                </div>
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
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SECRET_KEY_DIALOG_TITLE'); ?>
                </h3>
            </div>
            <!-- .modal-header -->

            <div class="modal-body">
                <label class="col-sm-2 control-label hidden-xs" for="oauth-secret-key">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SECRET_KEY_DIALOG_LABEL'); ?>:
                </label>

                <p class="col-sm-10" id="oauth-secret-key"></p>
            </div>
            <!-- .modal-body -->

            <div class="modal-footer">
                <div class="dialog-actions">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">
                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SECRET_KEY_DIALOG_CLOSE_LABEL'); ?>
                    </button>
                </div>
            </div>
            <!-- .modal-footer -->

        </div>
        <!-- .modal-content -->
    </div>
    <!-- .modal-dialog -->
</div><!-- .modal -->

<!-- View Quota Dialog -->
<div class="modal fade" id="quota-modal-dialog" tabindex="-1" role="dialog" aria-labelledby="quota-modal-label"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title" id="quota-modal-label">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_QUOTA_DIALOG_TITLE'); ?>
                </h3>
            </div>
            <!-- .modal-header -->

            <div class="modal-body">
                <div class="quota-content" id="quota-modal-content"></div>
            </div>

            <div class="modal-footer">
                <div class="dialog-actions">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">
                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_QUOTA_CLOSE_LABEL'); ?>
                    </button>
                </div>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- View Scopes Dialog -->
<div class="modal fade" id="scopes-modal-dialog" tabindex="-1" role="dialog" aria-labelledby="scopes-modal-label"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h3 class="modal-title" id="quota-modal-label">
                    <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SCOPES_DIALOG_TITLE'); ?>
                </h3>
            </div>
            <!-- .modal-header -->

            <div class="modal-body">
                <div class="scopes-content" id="scopes-modal-content"></div>
            </div>

            <div class="modal-footer">
                <div class="dialog-actions">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">
                        <?php echo JText::_('COM_APIPORTAL_APPLICATION_VIEW_SCOPES_CLOSE_LABEL'); ?>
                    </button>
                </div>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->

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
        if ($('#apis-table')) {
            $('#apis-table').tablesorter({
                headers: {
                    2: {sorter: false}
                },
                sortList: [[0, 0]]
            })
        }

        if ($('#apis-keys-table')) {
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

        $('#api-keys-collapse').on('hide.bs.collapse', toggleChevron);
        $('#api-keys-collapse').on('show.bs.collapse', toggleChevron);
        $('#oauth-creds-collapse').on('hide.bs.collapse', toggleChevron);
        $('#oauth-creds-collapse').on('show.bs.collapse', toggleChevron);
        $('#ext-oauth-creds-collapse').on('hide.bs.collapse', toggleChevron);
        $('#ext-oauth-creds-collapse').on('show.bs.collapse', toggleChevron);

        <?php if (!ApiPortalHelper::isCommunity($application)) { ?>
        $('#sharing-collapse').on('hide.bs.collapse', toggleChevron);
        $('#sharing-collapse').on('show.bs.collapse', toggleChevron);
        <?php } ?>

        // Set quota content
        $('#quota-modal-dialog').on('show.bs.modal', function (e) {
            var relatedTarget = e.relatedTarget ? e.relatedTarget : e.fromElement;

            $("#quota-modal-content").html($(relatedTarget).data('content'));
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

        // Submit confirm delete form.
        $('#confirm-delete-button').on('click', function (e) {
            $(this).data('form').submit();
        });

        // Initialize tooltips
        $('.api-tooltip').tooltip({placement: 'bottom'});
    });

    function showAPIKeySecret(secret) {
        (function ($) {
            $("#api-key-secret-key").text(secret);
            $("#api-key-secret-details").modal({keyboard: true});
        })(jQuery);
    }

    function showOAuthSecret(secret) {
        (function ($) {
            $("#oauth-secret-key").text(secret);
            $("#oauth-secret-details").modal({keyboard: true});
        })(jQuery);
    }

    function showQuotasDialog(content) {
        (function ($) {
            if (content) {
                $('#quota-modal-content').empty();
                $('#quota-modal-content').append(content);
            }
            $("#quota-modal-dialog").modal({keyboard: true});
        })(jQuery);
    }

    function showScopesDialog(content) {
        (function ($) {
            if (content) {
                $('#scopes-modal-content').empty();
                $('#scopes-modal-content').append(content);
            }
            $("#scopes-modal-dialog").modal({keyboard: true});
        })(jQuery);
    }
</script>
