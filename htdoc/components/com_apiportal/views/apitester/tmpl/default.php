<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.helper');
require_once JPATH_COMPONENT . '/views/apicatalog/view.html.php';

// Make sure the session is valid before displaying view
ApiPortalHelper::checkSession();

// Manage hidden tab for public API user
$publicApiAction = ApiPortalHelper::hasHiddenTabforPublicUser();

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_apiportal' . DS . 'helpers' . DS . 'apiconfiguration.php';
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_apiportal' . DS . 'models' . DS . 'apiportal.php';

$config = new ApiportalModelapiportal();
$api_manager_addr = $config->getHost();
$api_manager_port = $config->getPort();
$api_manager_oauth_port = $config->getOauthPort();
$api_manager_oauth_path = $config->getOauthPath();
$host = $api_manager_addr . ':' . $api_manager_port;
$oauthEndpoint = "https://" . $api_manager_addr . ":" . $api_manager_oauth_port . $api_manager_oauth_path;
$ajaxEndpointCall = JURI::base(false) . 'index.php?option=com_apiportal&task=oauth.requestToken';
$tryItProxy = JURI::base(false) . 'index.php?option=com_apiportal&task=proxy.tryIt&ajax=1&' . JSession::getFormToken() . '=1';

$session = JFactory::getSession();
$jInput = JFactory::getApplication()->input;
$apiName = $jInput->get('apiName', null, 'RAW');
$basePath = ApiPortalHelper::getVersionedBaseFolder();

$currentApiId = $jInput->get('apiId', null, 'RAW');
$currentApiId = ApiPortalHelper::cleanHtml($currentApiId, false, true);
$tab = JRequest::getString('tab', null);
$tab = ApiPortalHelper::cleanHtml($tab, false, true);
$itemId = JRequest::getString('Itemid', '');
$itemId = ApiPortalHelper::cleanHtml($itemId, false, true);
$usage = JRequest::getString('usage', '');
$usage = ApiPortalHelper::cleanHtml($usage, false, true);

$menuId = $jInput->get('menuId', null, 'INT');
$clientSdk = ApiPortalHelper::getClientSdkValue($menuId);
$swagger = ApiPortalHelper::getSwaggerValue($menuId);
$enableInlineTryIt = ApiPortalHelper::getEnableInlineTryIt($menuId);

if ($tab == null) {
    $tab = 'tests';
}

?>
<script type="text/javascript">
    // Initialization

    var apiManagerHost = "<?= $api_manager_addr; ?>";
    var oauthEndpoint = "<?= $oauthEndpoint; ?>";
    var apiNameEncoded = encodeURIComponent("<?= addslashes($apiName) ?>");
    var CSRFToken = "<?= JSession::getFormToken() ?>=1";
    var host = "<?= $host; ?>";
    var URIBase = "<?= JUri::base();?>";
    var protocol = "https:";
    var apiId = "<?= $currentApiId; ?>";
    var baseFolder = "<?= $basePath;?>";
    //var clientSdkCheck = "<?= $api_manager_sdk; ?>";
	var clientSdkCheck = "<?= $clientSdk; ?>";
	var hasApiKey = false;
    // For OAuth call
    var ajaxEndpointCall = '<?= $ajaxEndpointCall ?>';
    // For try it proxy call
    var tryItProxyHost = '<?= $tryItProxy ?>';
    // API type for swagger-ui.js
    var apiType = null;
    // Used in image path
    var JRoot = '<?= JUri::root() ?>';
    // Used in definition/sdk download
    var returnUri = '<?= $this->generateReturnUri() ?>';
    // Text for WSDL Download
    var downloadWsdl= '<?= JText::_('COM_APIPORTAL_APITEST_DOWNLOAD_WSDL') ?>';
    // Message for Try It error
    var tryItErrorMsg = '<?= JText::_('COM_APIPORTAL_APITEST_TRY_IT_ERROR_MESSAGE') ?>';
	
	function displayError(message) {
        var error =
            '<div id="system-message">' +
            '<div class="alert alert-error auto">' +
            '<a class="close" data-dismiss="alert">×</a>' +
            '<h4 class="alert-heading">Error</h4>' +
            '<div>' +
            '<p>' + message + '</p>' +
            '</div>' +
            '</div>' +
            '</div>';
        $('#system-message-container').empty();
        $('#system-message-container').append(error);
        window.scroll(0, 0);
    }

    function oauthCallback(error) {
        var modal = $("#auth-modal-dialog");
        modal.modal('hide');
        if (window.authorizations.authz && window.authorizations.authz.oauth) {
            var requestToken = modal.data().trigger;
            requestToken.attr("onClick", "toggleAuthDialog(false, this)");
            requestToken.text('<?php echo JText::_('COM_APIPORTAL_APITEST_AUTHORIZE_DIALOG_DISMISS_TOKEN');?>');
            var tokenKey = requestToken.prev('span');
            tokenKey.html(window.authorizations.authz.oauth.token);
            tokenKey.prev('span').text('<?= JText::_('COM_APIPORTAL_APITEST_AUTHORIZATION_TOKEN') ?>');
        }

        if (error && error !== "undefined") {
            if (typeof error === 'object') {
                displayError(error.error_description);
            } else if (typeof error === 'string') {
                displayError(error);
            } else {
                console.log(error);
            }
        }
    }

    function requestToken() {
        var scopes = [];
        var modal = $("#auth-modal-dialog");
        var device = modal.data(oauthDevice.type);
        var checkboxes = $("input[name='oauth-scope']:checked");
        for (var i = 0; i < checkboxes.size(); i++) {
            scopes.push(checkboxes[i].value);

        }
        if (device !== undefined) {
            if (device.scopesMatching.toLowerCase() === "any") {
                var found = false;
                for (var i = 0; i < scopes.length; i++) {
                    if ($.inArray(scopes[i], device.scopes) > -1) {
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    modal.modal('hide');
                    displayError('<?= JText::_('COM_APIPORTAL_APITEST_SCOPE_SELECT_ERROR') ?>');
                    return;
                }
            } else if (device.scopesMatching.toLowerCase() === "all") {
                var notFound = false;
                for (var i = 0; i < scopes.length; i++) {
                    if ($.inArray(scopes[i], device.scopes) === -1) {
                        notFound = true;
                        break;
                    }
                }
                if (notFound) {
                    modal.modal('hide');
                    displayError('<?= JText::_('COM_APIPORTAL_APITEST_ALL_SCOPES_REQUIRED') ?>');
                    return;
                }
            }
        }

        if (modal.data().clientId === undefined || modal.data().clientId === "") {
            modal.modal('hide');
            displayError('<?= JText::_('COM_APIPORTAL_APITEST_OAUTH_REQUIRED') ?>');
            return;
        }

        if (modal.data().clientSecret === undefined || modal.data().clientSecret === "") {
            modal.modal('hide');
            displayError('<?= JText::_('COM_APIPORTAL_APITEST_OAUTH_SECRET_REQUIRED') ?>');
            return;
        }

        //OAuth client credentials flow
        var oauthObj = new oauthClientCredentials({
            clientId: modal.data().clientId, // client id
            clientSecret: modal.data().clientSecret, // client secret
            appName: "<?= $this->escape($apiName);?>",
            loginEndPoint: oauthEndpoint,
            callback: oauthCallback,
            scopes: scopes,
            ajaxRequestPoint: ajaxEndpointCall,
            csrfToken: "<?= JSession::getFormToken() ?>"
        });
        oauthObj.requestToken();
        /*
         var oauthObj = new oauthImpl({
         clientId: modal.data().clientId, // client id
         appName: apiName,
         loginEndPoint: oauthEndpoint,
         callback: oauthCallback,
         scopes: scopes
         });
         oauthObj.requestToken();
         */
        return;
    }

    function toggleAuthDialog(show, elementTrigger) {
        var trigger = $(elementTrigger);

        //Check for empty oauth client id when requesting token
        if (show && elementTrigger.id == "request-token" && !($("#globalOauthClientId").val())) {
            var modal = $("#auth-modal-dialog2");
            modal.data("trigger", trigger);
            var content = "<br><div class='alert alert-error'>" + "<?= JText::_('COM_APIPORTAL_APITEST_AUTHORIZE_DIALOG_REQUEST_TOKEN_NO_CLIENT_ID') ?>" + "</div>";
            content += '<center><button id="oauth-dismiss" type="button" class="btn btn-primary" data-dismiss="modal">';
            content += '<?= JText::_('COM_APIPORTAL_APITEST_AUTHORIZE_DIALOG_REQUEST_TOKEN_OK_BUTTON') ?>';
            content += '</button></center><br/>';
            $("#scopes-content2").empty();
            $("#scopes-content2").append(content);
            modal.modal({keyboard: true});

            return;

        }

        var modal = $("#auth-modal-dialog");
        modal.data("trigger", trigger);
        if (show) {
            var apiOauthScopes;
            var device = "";
            modal.data("clientId", $("#globalOauthClientId").val());
            if (trigger.attr("id") === "request-token") {
                if (window.swaggerUi.api.securityProfile) {
                    var devices = window.swaggerUi.api.securityProfile.devices;
                    if (devices && devices.length > 0) {
                        for (var i = 0; i < devices.length; i++) {
                            if (devices[i].type === oauthDevice.type) {
                                modal.data('clientSecret', $("#globalOauthClientId").find(':selected').data('secret'));
                                apiOauthScopes = devices[i].scopes;
                                device = devices[i];
                                break;
                            }
                        }
                    } else {
                        var availableScopesSet = {};
                        var operations = Object.keys(swaggerUi.api.apisArray[0].operations);
                        for (var i = 0; i < operations.length; i++) {
                            var auths = swaggerUi.api.apisArray[0].operations[operations[i]].authorizations;
                            var authKeys = Object.keys(auths);
                            for (var j = 0; j < authKeys.length; j++) {
                                if (authKeys[j] === oauthDevice.type) {
                                    modal.data('clientSecret', $("#globalOauthClientId").find(':selected').data('secret'));
                                    var scopes = auths[authKeys[j]].scopes;
                                    for (var k = 0; k < scopes.length; k++) {
                                        if (!(scopes[k] in availableScopesSet)) {
                                            availableScopesSet[scopes[k]] = scopes[k];
                                        }
                                    }

                                }
                            }
                        }
                        apiOauthScopes = Object.keys(availableScopesSet);
                    }
                }
            } else {
                var id = trigger.attr("id");
                var method = id.substring("request-token".length + 1, id.length);
                apiOauthScopes = swaggerUi.api.apisArray[0].operations[method].authorizations[oauthDevice.type].scopes;
                device = swaggerUi.api.apisArray[0].operations[method].authorizations[oauthDevice.type];
                try {
                    modal.data("clientSecret", $("#oauthid_" + method).find(':selected').data('secret'));
                } catch (e) {
                }
                modal.data("clientId", $("#oauthid_" + method).val());
            }

            modal.data(oauthDevice.type, device);
            var content = "";
            for (var i = 0; i < apiOauthScopes.length; i++) {
                content += '<div class="col-sm-11">' +
                    '<div class="radio-inline">' +
                    '<input type="checkbox" checked id="oauth-scope-' + i + '" name="oauth-scope" value="' + apiOauthScopes[i] + '"/>' +
                    '&nbsp;<label for="oauth-scope-' + i + '">' +
                    apiOauthScopes[i] +
                    '</label>' +
                    '</div>' +
                    '</div>';
            }
            $("#scopes-content").append(content);

            modal.modal({keyboard: true});
        } else {
            window.authorizations.remove(oauthDevice.type);
            var tokenKey = trigger.prev('span');
            tokenKey.prev('span').text('<?= JText::_('COM_APIPORTAL_APITEST_UNAUTHORIZED_TOKEN') ?>');
            tokenKey.empty();
            trigger.attr("onClick", "toggleAuthDialog(true, this)");
            trigger.text('<?= JText::_('COM_APIPORTAL_APITEST_AUTHORIZE_DIALOG_REQUEST_TOKEN');?>');
            $(".api-ic").removeClass("ic-info");
            $(".api-ic").addClass("ic-error");
            window.oauthImplObj = undefined;
        }
    }

    function populateCommonAuth() {
        hasApiKey = false;
        var hasBasic = false;
        var hasOauth = false;
        var hasInvoke = false;
        var invokeDescription = '';
//        $(".authselect option").each(function (index, element) {
//            var val = $(element).val();
//            if (val === basicDevice.type || val === basicDevice.typeDisplayName) {
//                hasBasic = true;
//            } else if (val === apiKeyDevice.type || val === apiKeyDevice.typeDisplayName) {
//                hasApiKey = true;
//            } else if (val === oauthDevice.type || val === oauthDevice.typeDisplayName) {
//                hasOauth = true;
//            } else if (val === invokePolicyDevice.type || val === invokePolicyDevice.typeDisplayName) {
//                hasInvoke = true;
//                invokeDescription = invokePolicyDesc;
//            }
//        });

	    /**
         * Take the global auth from the right place and then
         * assign the necessary statuses
         */
        if (window.swaggerUi.api.securityProfile.devices.length > 0) {
            $.each(window.swaggerUi.api.securityProfile.devices, function (key, val) {
                if (val.type == apiKeyDevice.type) {
                    hasApiKey = true;
                } else if (val.type == oauthDevice.type) {
                    hasOauth = true;
                } else if(val.type == basicDevice.type) {
                    hasBasic = true;
                } else if(val.type == invokePolicyDevice.type) {
                    hasInvoke = true;
                    invokeDescription = invokePolicyDesc;
                }
            })
        }

        if (!hasApiKey && !hasBasic && !hasOauth && !hasInvoke) {
            var checkboxDiv = $("#useSameCredentials").parents(".checkbox");
            checkboxDiv.remove();
            $("<div><?= JText::_('COM_APIPORTAL_APITEST_API_NOT_SUPPORT_AUTH') ?></div>").insertAfter("#message-bar");
            $(".authenticationArea").addClass("hidden");
            return;
        }

        var form = $("#globalAuth");

        if (hasInvoke) {
            var globalInvokePolicySet = $('<fieldset>' +
                '<div class="form-group">' +
                '<label class="col-sm-2 control-label" id="globalInvokePolicyClientIdLabel" name="globalInvokePolicyClientId">' + escapeHTML(invokePolicyName) + ':</label>' +
                '<div class="col-sm-4 markdown-reset" style="padding-top: 7px;">' +
                marked(escapeHTML(invokeDescription)) +
                '</div>' +
                '</div>' +
                '</fieldset>');

            form.append(globalInvokePolicySet);
        }

        if (hasOauth) {
            var requestTokenButton = "";
            var authorizationText = "";
            if (window.authorizations.authz && window.authorizations.authz.oauth) {
                authorizationText = '<span class="form-control-static" id="authorize-status"><?= JText::_('COM_APIPORTAL_APITEST_AUTHORIZATION_TOKEN') ?></span>';
                requestTokenButton = '<button type="button" class="btn btn-link" id="request-token" onClick="toggleAuthDialog(false, this);" ><?php echo JText::_('COM_APIPORTAL_APITEST_AUTHORIZE_DIALOG_DISMISS_TOKEN');?></button>';
            } else {
                authorizationText = '<span class="form-control-static" id="authorize-status"><?= JText::_('COM_APIPORTAL_APITEST_UNAUTHORIZED_TOKEN') ?></span>';
                requestTokenButton = '<button type="button" class="btn btn-link" id="request-token" onClick="toggleAuthDialog(true, this);" ><?php echo JText::_('COM_APIPORTAL_APITEST_AUTHORIZE_DIALOG_REQUEST_TOKEN');?></button>';
            }

            <?php
                $listOAuthClients  = "";
                // Get applications:
                $path1 = $basePath."/applications";
                $apps1 = ApiPortalHelper::doGet($path1, array(), true);

                if (ApiPortalHelper::isHttpError()) {
                        $listOAuthClients  = $listOAuthClients  ."<option>" . JText::_('COM_APIPORTAL_APITEST_ERROR_LISTING_APPS') . "</option>";

                }
                // Get the APIs and OAuth client ids, sort the multidimensional array
                $appsAndIds = 0;
                $tmp = array();
                foreach($apps1 as &$ma)
                $tmp[] = &$ma["name"];
                array_multisort($tmp, $apps1);
                foreach($apps1 as $apps1Item){
                     //Use addslashes() to handle handles Cyrillic and other letters
                     $isPending = ApiPortalHelper::isPending($apps1Item);
                     //$isenabled = ApiPortalHelper::isEnabled($apps1Item);
                     if(!$isPending) {
                         //Get the list of APIs that the application can access
                         $pathApiAccess = $basePath.'/applications/'.$apps1Item['id'].'/apis';
                         $apisAccess = ApiPortalHelper::doGet($pathApiAccess);

                         if (!empty($apisAccess)) {
                             foreach ($apisAccess as $apiAccess) {
                                 if ($apiAccess->apiId == $currentApiId) {
                                     //===get client ids
                                     $appsAndIds = $appsAndIds + 1;
                                     $path3 = $basePath."/applications/".$apps1Item['id']."/oauth";
                                     $oauthClients = ApiPortalHelper::doGet($path3);

                                     if (ApiPortalHelper::isHttpError()) {
                                         $listOAuthClients  = $listOAuthClients  ."<option>" . JText::_('COM_APIPORTAL_APITEST_ERROR_LISTING_OATUH') . "</option>";
                                     }else{
                                         foreach($oauthClients as $oauthClientsItem){
                                             $listOAuthClients  = $listOAuthClients . '<option value="' . $oauthClientsItem->id . '" data-secret="' . $oauthClientsItem->secret . '">' . addslashes($this->escape($apps1Item['name'])) . " - " . $oauthClientsItem->id . "</option>";
                                         }
                                     }
                                     //===end get client ids
                                 }
                             }
                         }
                     }
                }
             ?>
            var oAuthFieldSet = $('<fieldset>' +
                '<div class="form-group">' +
                '<label class="col-sm-2 control-label" id="globalOauthClientIdLabel" name="globalOauthClientIdLabel"  for="globalOauthClientId"><?= JText::_('COM_APIPORTAL_APITEST_OAUTH_CLIENT') ?></label>' +
                '<div class="col-sm-4">' +
                '<select id="globalOauthClientId" name="globalOauthClientId" class="chzn-done" style="display: none; ">' +
                '<option value="" selected="selected"><?= JText::_('COM_APIPORTAL_APITEST_SELECT_CLIENT_ID') ?></option>' +
                '<?php echo $listOAuthClients; ?>' +
                '</select>' +
                '</div>' +
                '<div class="col-sm-offset-2 col-sm-10">' +
                authorizationText +
                '<span class="form-control-static" id="tokenKey"></span>' +
                requestTokenButton +
                '</div>' +
                '</div>' +
                '</fieldset>');
            form.append(oAuthFieldSet);

        }

        if (hasApiKey) {
            // ================================= build select app and key list =================
            <?php
                $listApiKeys = "";
                // Get applications:
                $path1 = $basePath."/applications";
                $apps1 = ApiPortalHelper::doGet($path1, array(), true);

                if (ApiPortalHelper::isHttpError()) {
                        $listApiKeys = $listApiKeys."<option>" . JText::_('COM_APIPORTAL_APITEST_ERROR_LISTING_APPS'). "</option>";
                }
                $listApiKeys = "";
                // Get the APIs and their keys, Sort the multidimensional array
                $appsAndKeys = 0;
                $tmp = array();
                foreach($apps1 as &$ma)
                $tmp[] = &$ma["name"];
                array_multisort($tmp, $apps1);

                foreach($apps1 as $apps1Item){
                     //Use addslashes() to handle handles Cyrillic and other letters
                     $isPending = ApiPortalHelper::isPending($apps1Item);
                     //$isenabled = ApiPortalHelper::isEnabled($apps1Item);
                     if(!$isPending) {
                         //Get the list of APIs that the application can access
                         $pathApiAccess = $basePath.'/applications/'.$apps1Item['id'].'/apis';
                         $apisAccess = ApiPortalHelper::doGet($pathApiAccess);

                         if (!empty($apisAccess)) {
                             foreach ($apisAccess as $apiAccess) {
                                 if ($apiAccess->apiId == $currentApiId) {
                                     //$listApiKeys = $listApiKeys.'<optgroup label="'.addslashes($apps1Item['name']).'">'; //addslashes() handles Cyrillic letters
                                     //===get keys
                                     $appsAndKeys = $appsAndKeys + 1;
                                     $path2 = $basePath . "/applications/" . $apps1Item['id'] . "/apikeys";
                                     $apiKeys2 = ApiPortalHelper::doGet($path2);
                                     if (ApiPortalHelper::isHttpError()) {
                                         $listApiKeys = $listApiKeys . "<option>" . JText::_('COM_APIPORTAL_APITEST_ERROR_LISTING_KEYS') . "</option>";
                                     } else {
                                         foreach ($apiKeys2 as $apiKeys2Item) {
                                             // $listApiKeys = $listApiKeys.'<option value="'.$apps1Item['id']."/".$apiKeys2Item->id.'">'.$apiKeys2Item->id;
                                             $listApiKeys = $listApiKeys . '<option value="' . $apiKeys2Item->id . '">' . addslashes($this->escape($apps1Item['name'])) . " - " . $apiKeys2Item->id.'</option>'; //addslashes() handles Cyrillic letters
                                         }
                                     }
                                     //===end get keys
                                     //$listApiKeys = $listApiKeys.'</optgroup>';
                                 }
                             }
                         }
                     }
                }
             ?>

            var createApplicationOrKey = "<br><?= JText::_('COM_APIPORTAL_APITEST_NO_KEYS_FOR_APP') ?>";
            if (<?php echo $appsAndKeys; ?> !=
            0
        )
            {
                createApplicationOrKey = "";
            }

            var globalKeySet = $('<fieldset>' +
                '<div class="form-group">' +
                '<label class="col-sm-2 control-label" id="globalApiKeyLabel" name="globalApiKeyLabel" for="globalApiKey"><?= JText::_('COM_APIPORTAL_APITEST_API_KEY') ?></label>' +
                '<div class="col-sm-4">' +
                '<select id="globalApiKey" name="globalApiKey" class="chzn-done" style="display: none; ">' +
                '<option value="" selected="selected"><?= JText::_('COM_APIPORTAL_APITEST_SELECT_API_KEY') ?></option>' +
                '<?php echo $listApiKeys; ?>' +
                '</select>' + createApplicationOrKey +
                '</div>' +
                '</div>' +
                '</fieldset>');

            form.append(globalKeySet);

            // ================================= //build select app and key list =================
        }

        if (hasBasic) {
            var basicUserFieldSet = $('<fieldset>' +
                '<div class="form-group">' +
                '<label class="col-sm-2 control-label" for="globalUser"><?= JText::_('COM_APIPORTAL_APITEST_BASIC') ?></label>' +
                '<div class="col-sm-3">' +
                '<input type="text" id="globalUser" name="name" value="" placeholder="<?= JText::_('COM_APIPORTAL_APITEST_BASIC_USERNAME') ?>">' +
                '</div>' +
                '</div>' +
                '</fieldset>');
            var basicPassFieldSet = $('<fieldset>' +
                '<div class="form-group">' +
                '<label class="col-sm-2 control-label" for="globalUser">&nbsp;</label>' +
                '<div class="col-sm-3">' +
                '<input type="password" id="globalPass" name="name" value="" placeholder="<?= JText::_('COM_APIPORTAL_APITEST_BASIC_PASSWORD') ?>">' +
                '</div>' +
                '</div>' +
                '</fieldset>');
            form.append(basicUserFieldSet);
            form.append(basicPassFieldSet);
        }
    }

    $(document).ready(function () {
        var _url = protocol + "//" + host + baseFolder + "/discovery/swagger/api/" + apiNameEncoded;

        $("#api-nav").click(function () {
            var parent = $(this).parent();
            if (parent && !parent.hasClass("open")) {
                parent.addClass("open");
                return false;
            } else if (parent && parent.hasClass("open")) {
                parent.removeClass("open");
                return false;
            }
        });

        $('#auth-modal-dialog').on('hide.bs.modal', function (e) {
            $("#scopes-content").empty();
        });

        var generateAPIKeySelectorOptions = function (selectField, methodName, swaggerUi) {
            selectField.addClass("chzn-done");
            selectField.html(
                '<option value="" selected="selected"><?= JText::_('COM_APIPORTAL_APITEST_SELECT_API_KEY') ?></option>' +
                '<?php echo $listApiKeys; ?>'
            );
            selectField.chosen({
                "disable_search_threshold": 1,
                "allow_single_deselect": true,
                "placeholder_text_multiple": "<?= JText::_('COM_APIPORTAL_APITEST_SELECT_SOME_OPTIONS') ?>",
                "placeholder_text_single": "<?= JText::_('COM_APIPORTAL_APITEST_SELECT_OPTION') ?>",
                "no_results_text": "<?= JText::_('COM_APIPORTAL_APITEST_NO_RESULT_MATCH') ?>"
            });
        };

        var generateOAuthClientSelectorOptions = function (selectField, methodName, swaggerUi) {
            selectField.addClass("chzn-done");
            selectField.html(
                '<option value="" selected="selected"><?= JText::_('COM_APIPORTAL_APITEST_SELECT_CLIENT_ID') ?></option>' +
                '<?php echo $listOAuthClients; ?>'
            );
            selectField.chosen({
                "disable_search_threshold": 1,
                "allow_single_deselect": true,
                "placeholder_text_multiple": "<?= JText::_('COM_APIPORTAL_APITEST_SELECT_SOME_OPTIONS') ?>",
                "placeholder_text_single": "<?= JText::_('COM_APIPORTAL_APITEST_SELECT_OPTION') ?>",
                "no_results_text": "<?= JText::_('COM_APIPORTAL_APITEST_NO_RESULT_MATCH') ?>"
            });
        };

        // Run ajax call to retrieve swagger definition
        var enableInlineTryIt = <?php echo json_encode($enableInlineTryIt); ?>;
        swaggerLoadAjax(_url, generateAPIKeySelectorOptions, generateOAuthClientSelectorOptions, enableInlineTryIt);
        window.proxyTimeout = <?php echo $this->proxyTimeout;?>;
    });

    function changeTriIti18n()
	{
		$(".submit").prop('value', '<?= JText::_('COM_APIPORTAL_APITEST_API_TRY_IT_LABEL') ?>'); 
	}
	
</script>
<?php
$path = $basePath . "/discovery/apis";
$apiList = ApiPortalHelper::doGet($path, array(), true);
APIPortalViewApiCatalog::sortApiList($apiList, SORT_ASC);
?>
<div class="head">
  <h1 class="auto"><?= $this->escape($apiName); ?></h1>
  <p class="auto"><em><!-- placeholer --></em></p>
</div>

<div class="btn-toolbar" role="toolbar">
    <div class="auto">
<div class="action-group object-chooser">
    <div class="dropdown sort-dropdown">
        <button id="api-nav" type="button" class="btn btn-default dropdown-toggle icon chevron-down" data-toggle="dropdown">
            <?= $this->escape($apiName); ?>
        </button>
        <ul class="dropdown-menu" role="menu">
            <?php foreach ($apiList as $apiItemSort) : ?>
            <li>
                <a href="<?= JUri::base() ?>index.php?option=com_apiportal&view=apitester&usage=<?= $usage; ?>&apiName=<?= rawurlencode($apiItemSort['name']); ?>&sn=<?= rawurlencode($apiItemSort['name']); ?>&Itemid=<?= $itemId; ?>&tab=<?= $tab; ?>&apiId=<?= $apiItemSort['id'] ?>&menuId=<?php echo $menuId;?>"
                   class="btn btn-primary"><?php echo $this->escape($apiItemSort['name']); ?></a>
            </li>
            <?php endforeach ?>
        </ul>
    </div>
</div>
</div>
</div>

<div class="body auto">
<div id="custom-message-container" class="hidden">
    <div id="system-message">
        <div class="alert alert-error">
            <a class="close" data-dismiss="alert">×</a>

            <h4 class="alert-heading"><?= JText::_('COM_APIPORTAL_APITEST_ERROR') ?></h4>
            <div>
                <p id="system-message-text"></p>
            </div>
        </div>
    </div>
</div>

<!-- Loading gif - definition download ajax -->
<div style="width: 100%;">
    <img id="swagger-load" src="<?= JURI::root() ?>components/com_apiportal/assets/img/loading.gif" alt="Loading..." style="display:none;width: 10%;margin: 0 auto;position: absolute; right: 50%; z-index: 99999;" />
</div>

<!-- Main container - do not display until the ajax call for swagger definition is finished -->
<div id="main-container" style="position: relative;">
    <div class="description markdown-reset" id="api-description"></div>

    <div class="col-sm-12 row entry-details">

        <div class="col-sm-2">
            <div class="logo" id="api-image" style="background-image: url('<?= JURI::root() ?>components/com_apiportal/assets/img/no_image_loading.png');"></div>
        </div>
        <!-- .col-sm-2 -->

        <div class="col-sm-10 content">
            <div class="row control-group">
                <label class="col-sm-2 control-label" for="version"><?= JText::_('COM_APIPORTAL_APITEST_VERSION') ?></label>
                <!-- Version and deprecated label - if exist -->
                <div class="col-sm-8" id="version"></div>
            </div>
            <div class="row control-group">
                <label class="col-sm-2 control-label" for="basepath"><?= JText::_('COM_APIPORTAL_APITEST_BASE_PATH') ?></label>
                <div class="col-sm-8" id="basepath"></div>
            </div>
            <div class="row control-group">
                <label class="col-sm-2 control-label" for="cors"><?= JText::_('COM_APIPORTAL_APITEST_CORS') ?></label>
                <div class="col-sm-8" id="cors"></div>
            </div>
            <div class="row control-group">
                <label class="col-sm-2 control-label" for="tags"><?= JText::_('COM_APIPORTAL_APITEST_TAGS') ?></label>
                <div class="col-sm-8" id="tags"></div>
            </div>
            <div class="row control-group">
                <label class="col-sm-2 control-label" for="api-type"><?= JText::_('COM_APIPORTAL_APITEST_TYPE') ?></label>
                <div class="col-sm-8" id="api-type"></div>
            </div>
            <?php if($swagger==true){?>
            <div class="row control-group">
                <label class="col-sm-2 control-label"
                       for="api-definition"><?= JText::_('COM_APIPORTAL_APITEST_DOWNLOAD') ?></label>
                <div class="col-sm-8" id="api-definition"></div>
            </div>
            <?php } ?>
            <div class="row control-group" id="sdk-group">
                <label class="col-sm-2 control-label"
                       for="api-sdks"><?= JText::_('COM_APIPORTAL_APITEST_SDK') ?></label>

                <div class="col-sm-8" id="api-sdks"></div>
            </div>
        </div>
    </div>
    <div class="overlay"></div>
    <div class="clearfix"></div>
<!-- This is closing from another page -->
</div>
<div class="clearfix"></div>
<div class="tabs" id="tabs">
    <ul class="nav nav-tabs">
        <?php
        $encodedApiName = rawurlencode($apiName);
        ?>
        <li <?php echo($tab === 'tests' ? 'class="active"' : ''); ?>><a
                href="<?= JUri::base() ?>index.php?option=com_apiportal&view=apitester&usage=api&tab=tests&apiName=<?= $encodedApiName ?>&sn=<?= $encodedApiName ?>&Itemid=<?= $itemId; ?>&apiId=<?= $currentApiId ?>&menuId=<?=$menuId?>"
                data-toggle="TODO"><?= JText::_('COM_APIPORTAL_APITEST_TAB_TEST') ?></a></li>
        
        <li <?php echo($tab !== null && $tab !== '' && $tab !== 'tests' ? 'class="active"' : ''); echo $publicApiAction;   ?>><a
                href="<?= JUri::base() ?>index.php?option=com_apiportal&view=apitester&usage=api&tab=messages&apiName=<?= $encodedApiName ?>&sn=<?= $encodedApiName ?>&Itemid=<?= $itemId; ?>&apiId=<?= $currentApiId ?>&menuId=<?php echo $menuId; ?>"
                data-toggle="TODO"><?= JText::_('COM_APIPORTAL_APITEST_TAB_USAGE'); ?></a></li>
    </ul>

    <div class="tab-content" id="tabs-content" style="display: none;">
        <!-- API Tester Tab -->

        <div class="tab-pane fade <?php echo($tab === null || $tab === '' || $tab === 'tests' ? 'in active' : ''); ?>"
             id="tests">
            <div id="message-bar" class="swagger-ui-wrap alert alert-danger alert-dismissable hidden"></div>
            <div class="checkbox"><label><input type='checkbox' id='useSameCredentials' checked> <?= JText::_('COM_APIPORTAL_APITEST_SAME_CREDENTIALS') ?></input></label></div>

            <form id="globalAuth" class="form-horizontal" role="form"></form>


            <div id="swagger-ui-container" class="swagger-ui-wrap"></div>

            <!-- Authorization Dialog -->
            <div class="modal fade" id="auth-modal-dialog" tabindex="-1" role="dialog"
                 aria-labelledby="auth-modal-label" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h3 class="modal-title" id="auth-modal-label">
                                <?php echo JText::_('COM_APIPORTAL_APITEST_AUTHORIZE_DIALOG_TITLE'); ?>
                            </h3>
                        </div>
                        <!-- .modal-header -->

                        <div class="modal-body">
                            <form id="oauth-client-form" class="form-horizontal" role="form" method="post">
                                <fieldset>
                                    <div class="col-sm-11">
                                        <span
                                            class="auth-info-message"><?php echo JText::_('COM_APIPORTAL_APITEST_AUTHORIZE_DIALOG_MESSAGE'); ?></span>
                                    </div>
                                    <div id="scopes-content">

                                    </div>
                                </fieldset>
                            </form>
                        </div>

                        <div class="modal-footer">
                            <div class="dialog-actions">
                                <button type="button" class="btn btn-primary" onclick="requestToken();">
                                    <?php echo JText::_('COM_APIPORTAL_APITEST_AUTHORIZE_DIALOG_AUTH'); ?>
                                </button>
                                <button id="oauth-dismiss" type="button" class="btn btn-primary" data-dismiss="modal">
                                    <?php echo JText::_('COM_APIPORTAL_APITEST_AUTHORIZE_DIALOG_CANCEL'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
            <!-- /.modal -->

            <!-- Empty OAauth client id propmt -->
            <div class="modal fade" id="auth-modal-dialog2" tabindex="-1" role="dialog"
                 aria-labelledby="auth-modal-label" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        </div>
                        <!-- .modal-header -->
                        <div class="modal-body">
                            <div id="scopes-content2">

                            </div>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
            <!-- /.modal -->
            <!-- //Empty OAauth client id propmt -->

        </div>
        <!-- Metrics Tab -->
        <div class="tab-pane fade <?php echo($tab !== null && $tab !== '' && $tab !== 'tests' ? 'in active' : ''); ?>"
             id="metrics">
            <?php if ($tab !== null && $tab !== '' && $tab !== 'tests') { ?>
                <?php include JPATH_COMPONENT . '/views/monitoring/tmpl/default.php'; ?>
            <?php } ?>
        </div>
        <!-- .tab-pane -->
    </div>
    <!-- .tab-content -->
</div><!-- .tabs -->
</div>
