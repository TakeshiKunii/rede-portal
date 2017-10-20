var currentOptionIndex = 1;
var currentAuthenticationType = "";
var optionIndexes = new Array();
var options = new Array();
var optionsArr = new Array();
var invokePolicyDesc = "";
var invokePolicyName = "";

//global API test submit validation variables
var globalAPIKeyValidate = false;
var globalOAuthValidate = false;
var globalBasicValidate = false;


$(function () {

    // Helper function for vertically aligning DOM elements
    // http://www.seodenver.com/simple-vertical-align-plugin-for-jquery/
    $.fn.vAlign = function () {
        return this.each(function (i) {
            var ah = $(this).height();
            var ph = $(this).parent().height();
            var mh = (ph - ah) / 2;
            $(this).css('margin-top', mh);
        });
    };

    $.fn.stretchFormtasticInputWidthToParent = function () {
        return this.each(function (i) {
            var p_width = $(this).closest("form").innerWidth();
            var p_padding = parseInt($(this).closest("form").css('padding-left'), 10) + parseInt($(this).closest("form").css('padding-right'), 10);
            var this_padding = parseInt($(this).css('padding-left'), 10) + parseInt($(this).css('padding-right'), 10);
            $(this).css('width', p_width - p_padding - this_padding);
        });
    };

    $('form.formtastic li.string input, form.formtastic textarea').stretchFormtasticInputWidthToParent();

    // Vertically center these paragraphs
    // Parent may need a min-height for this to work..
    $('ul.downplayed li div.content p').vAlign();

    // When a sandbox form is submitted..
    $("form.sandbox").submit(function () {

        var error_free = true;

        // Cycle through the forms required inputs
        $(this).find("input.required").each(function () {

            // Remove any existing error styles from the input
            $(this).removeClass('error');

            // Tack the error style on if the input is empty..
            if ($(this).val() == '') {
                $(this).addClass('error');
                $(this).wiggle();
                error_free = false;
            }

        });

        return error_free;
    });

});

function clippyCopiedCallback(a) {
    $('#api_key_copied').fadeIn().delay(1000).fadeOut();

    // var b = $("#clippy_tooltip_" + a);
    // b.length != 0 && (b.attr("title", "copied!").trigger("tipsy.reload"), setTimeout(function() {
    //   b.attr("title", "copy to clipboard")
    // },
    // 500))
}

// Logging function that accounts for browsers that don't have window.console
log = function () {
    log.history = log.history || [];
    log.history.push(arguments);
    if (this.console) {
        console.log(Array.prototype.slice.call(arguments));
    }
};

// Handle browsers that do console incorrectly (IE9 and below, see http://stackoverflow.com/a/5539378/7913)
// This 'fix' doesn't work. IE9 don't recognize console object with this.
// Remove it and there will be no log (without dev console on) for IE9 and below.
//if (Function.prototype.bind && console && typeof console.log == "object") {
//    [
//        "log", "info", "warn", "error", "assert", "dir", "clear", "profile", "profileEnd"
//    ].forEach(function (method) {
//            console[method] = this.bind(console[method], console);
//        }, Function.prototype.call);
//}

var Docs = {

    shebang: function () {

        // If shebang has an operation nickname in it..
        // e.g. /docs/#!/words/get_search
        var fragments = $.param.fragment().split('/');
        fragments.shift(); // get rid of the bang

        switch (fragments.length) {
            case 1:
                // Expand all operations for the resource and scroll to it
                log('shebang resource:' + fragments[0]);
                var dom_id = 'resource_' + fragments[0];

                Docs.expandEndpointListForResource(fragments[0]);
                $("#" + dom_id).slideto({highlight: false});
                break;
            case 2:
                // Refer to the endpoint DOM element, e.g. #words_get_search
                log('shebang endpoint: ' + fragments.join('_'));

                // Expand Resource
                Docs.expandEndpointListForResource(fragments[0]);
                $("#" + dom_id).slideto({highlight: false});

                // Expand operation
                var li_dom_id = fragments.join('_');
                var li_content_dom_id = li_dom_id + "_content";

                log("li_dom_id " + li_dom_id);
                log("li_content_dom_id " + li_content_dom_id);

                Docs.expandOperation($('#' + li_content_dom_id));
                $('#' + li_dom_id).slideto({highlight: false});
                break;
        }

    },

    toggleEndpointListForResource: function (resource) {
        var elem = $('li#resource_' + Docs.escapeResourceName(resource) + ' ul.endpoints');
        if (elem.is(':visible')) {
            Docs.collapseEndpointListForResource(resource);
        } else {
            Docs.expandEndpointListForResource(resource);
        }
    },

    // Expand resource
    expandEndpointListForResource: function (resource) {
        var resource = Docs.escapeResourceName(resource);
        if (resource == '') {
            $('.resource ul.endpoints').slideDown();
            return;
        }

        $('li#resource_' + resource).addClass('active');

        var elem = $('li#resource_' + resource + ' ul.endpoints');
        elem.slideDown();
    },

    // Collapse resource and mark as explicitly closed
    collapseEndpointListForResource: function (resource) {
        var resource = Docs.escapeResourceName(resource);
        $('li#resource_' + resource).removeClass('active');

        var elem = $('li#resource_' + resource + ' ul.endpoints');
        elem.slideUp();
    },

    expandOperationsForResource: function (resource) {
        // Make sure the resource container is open..
        Docs.expandEndpointListForResource(resource);

        if (resource == '') {
            $('.resource ul.endpoints li.operation div.content').slideDown();
            return;
        }

        $('li#resource_' + Docs.escapeResourceName(resource) + ' li.operation div.content').each(function () {
            Docs.expandOperation($(this));
        });
    },

    collapseOperationsForResource: function (resource) {
        // Make sure the resource container is open..
        Docs.expandEndpointListForResource(resource);

        $('li#resource_' + Docs.escapeResourceName(resource) + ' li.operation div.content').each(function () {
            Docs.collapseOperation($(this));
        });
    },

    escapeResourceName: function (resource) {
        return resource.replace(/[!"#$%\s&'()*+,.\/:;<=>?@\[\\\]\^`{|}~]/g, "\\$&");
    },

    expandOperation: function (elem) {
        elem.slideDown();
    },

    collapseOperation: function (elem) {
        elem.slideUp();
    }
};
(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['content_type'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, functionType = "function", self = this;

        function program1(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n  ";
            stack1 = helpers.each.call(depth0, depth0.produces, {
                hash: {},
                inverse: self.noop,
                fn: self.program(2, program2, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n";
            return buffer;
        }

        function program2(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n	<option value=\"";
            stack1 = (typeof depth0 === functionType ? depth0.apply(depth0) : depth0);
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\">";
            stack1 = (typeof depth0 === functionType ? depth0.apply(depth0) : depth0);
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "</option>\n	";
            return buffer;
        }

        function program4(depth0, data) {


            return "\n  <option value=\"application/json\">application/json</option>\n";
        }

        buffer += "<label for=\"contentType\"></label>\n<select name=\"contentType\">\n";
        stack1 = helpers['if'].call(depth0, depth0.produces, {
            hash: {},
            inverse: self.program(4, program4, data),
            fn: self.program(1, program1, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n</select>\n";
        return buffer;
    });
})();

(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['main'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, functionType = "function", escapeExpression = this.escapeExpression, self = this;

        function program1(depth0, data) {

            var buffer = "", stack1, stack2;
            buffer += "\n    <div class=\"info_title\">"
            + escapeExpression(((stack1 = ((stack1 = depth0.info), stack1 == null || stack1 === false ? stack1 : stack1.title)), typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
            + "</div>\n    <div class=\"info_description\">";
            stack2 = ((stack1 = ((stack1 = depth0.info), stack1 == null || stack1 === false ? stack1 : stack1.description)), typeof stack1 === functionType ? stack1.apply(depth0) : stack1);
            if (stack2 || stack2 === 0) {
                buffer += stack2;
            }
            buffer += "</div>\n    ";
            stack2 = helpers['if'].call(depth0, ((stack1 = depth0.info), stack1 == null || stack1 === false ? stack1 : stack1.termsOfServiceUrl), {
                hash: {},
                inverse: self.noop,
                fn: self.program(2, program2, data),
                data: data
            });
            if (stack2 || stack2 === 0) {
                buffer += stack2;
            }
            buffer += "\n    ";
            stack2 = helpers['if'].call(depth0, ((stack1 = depth0.info), stack1 == null || stack1 === false ? stack1 : stack1.contact), {
                hash: {},
                inverse: self.noop,
                fn: self.program(4, program4, data),
                data: data
            });
            if (stack2 || stack2 === 0) {
                buffer += stack2;
            }
            buffer += "\n    ";
            stack2 = helpers['if'].call(depth0, ((stack1 = depth0.info), stack1 == null || stack1 === false ? stack1 : stack1.license), {
                hash: {},
                inverse: self.noop,
                fn: self.program(6, program6, data),
                data: data
            });
            if (stack2 || stack2 === 0) {
                buffer += stack2;
            }
            buffer += "\n  ";
            return buffer;
        }

        function program2(depth0, data) {

            var buffer = "", stack1;
            buffer += "<div class=\"info_tos\"><a href=\""
            + escapeExpression(((stack1 = ((stack1 = depth0.info), stack1 == null || stack1 === false ? stack1 : stack1.termsOfServiceUrl)), typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
            + "\">Terms of service</a></div>";
            return buffer;
        }

        function program4(depth0, data) {

            var buffer = "", stack1;
            buffer += "<div class='info_contact'><a href=\"mailto:"
            + escapeExpression(((stack1 = ((stack1 = depth0.info), stack1 == null || stack1 === false ? stack1 : stack1.contact)), typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
            + "\">Contact the developer</a></div>";
            return buffer;
        }

        function program6(depth0, data) {

            var buffer = "", stack1;
            buffer += "<div class='info_license'><a href='"
            + escapeExpression(((stack1 = ((stack1 = depth0.info), stack1 == null || stack1 === false ? stack1 : stack1.licenseUrl)), typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
            + "'>"
            + escapeExpression(((stack1 = ((stack1 = depth0.info), stack1 == null || stack1 === false ? stack1 : stack1.license)), typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
            + "</a></div>";
            return buffer;
        }

        function program8(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n        , <span style=\"font-variant: small-caps\">api version</span>: ";
            if (stack1 = helpers.apiVersion) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.apiVersion;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "\n        ";
            return buffer;
        }

        buffer += "<div class='info' id='api_info'>\n  ";
        stack1 = helpers['if'].call(depth0, depth0.info, {
            hash: {},
            inverse: self.noop,
            fn: self.program(1, program1, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n</div>\n<div class='_container' id='resources_container'>\n    <ul id='resources'>\n    </ul>\n\n    <div class=\"footer\">\n        <br>\n        <br>\n        ";
        buffer += "   </div>\n</div>\n";
        return buffer;
    });
})();

(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['operation'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, options, functionType = "function", escapeExpression = this.escapeExpression, self = this, blockHelperMissing = helpers.blockHelperMissing;

        function program1(depth0, data) {

            var buffer = "", stack1, stack2;
            buffer += "\n        <h4>Implementation Notes</h4>\n        ";
            if (stack1 = helpers.notes) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.notes;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            if (stack1 || stack1 === 0) {
                buffer += "<div class='markdown-reset'>" + marked(escapeHTML(stack1)) + "</div>";
            }
            else {
                if (stack2 = helpers.documentationUrl) {
                    stack2 = stack2.call(depth0, {hash: {}, data: data});
                }
                else {
                    stack2 = depth0.documentationUrl;
                    stack2 = typeof stack2 === functionType ? stack2.apply(depth0) : stack2;
                }

                if ((stack2 || stack2 === 0) && /^(http|https):\/\/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/i.test(stack2)) {
                    buffer += "<a class='description-link' href='" + stack2 + "' target='_blank'>" + stack2 + "</a>";
                }
            }
            buffer += "\n        ";
            return buffer;
        }

        function program3(depth0, data) {


            return "\n        <div class=\"auth\">\n        <span class=\"api-ic ic-error\"></span>";
        }

        function program5(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n          <div id=\"api_information_panel\" style=\"top: 526px; left: 776px; display: none;\">\n          ";
            stack1 = helpers.each.call(depth0, depth0, {
                hash: {},
                inverse: self.noop,
                fn: self.program(6, program6, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n          </div>\n        ";
            return buffer;
        }

        function program6(depth0, data) {

            var buffer = "", stack1, stack2;
            buffer += "\n            <div title='";
            stack2 = ((stack1 = depth0.description), typeof stack1 === functionType ? stack1.apply(depth0) : stack1);
            if (stack2 || stack2 === 0) {
                buffer += stack2;
            }
            buffer += "'>"
            + escapeExpression(((stack1 = depth0.scope), typeof stack1 === functionType ? stack1.apply(depth0) : stack1))
            + "</div>\n          ";
            return buffer;
        }

        function program8(depth0, data) {


            return "</div>";
        }

        function setDescription(obj)
        {
            if (obj.descriptionType == "manual") {
                return (obj.description && obj.description != '') ? obj.description : "";
            } else if (obj.descriptionType == "url") {
                return (obj.descriptionUrl && obj.descriptionUrl != '') ? "<a href='" + obj.descriptionUrl + "'>" + obj.descriptionUrl + "</a>" : "";
            } else if (obj.descriptionType = "original") {
                return (obj.description && obj.description != '') ? obj.description : "";
            } else if (obj.descriptionType = "markdown") {
                return (obj.descriptionMarkdown && obj.descriptionMarkdown != '') ? obj.descriptionMarkdown : "";
            } else {
                return (obj.description && obj.description != '') ? obj.description : "";
            }
        }

        function program10(depth0, data) {
            var authorizations = depth0.authorizations;
            var nickname = depth0.nickname;
            var keys = Object.keys(authorizations);
            // display auth area if the method has keys
            if (keys.length > 0) {
                var buffer = "\n        <div class='authenticationArea clearfix'><h4>Authentication</h4>\n";

                buffer += "\n<div class=\"auth-area\">"
                buffer += "\n<select id=\"authselect_" + nickname + "\"" + " class=\"authselect\">"
                buffer += "\n        <option name=\"authselectopnoauth\" value=\"authselectopnoauth\" class=\"authselect\">";
                buffer += "\n        No Authentication";
                buffer += "\n        </option>";

                for (var i = 0; i < keys.length; i++) {
                    var auth = authorizations[keys[i]];
                    var option;
                    if (auth.type == apiKeyDevice.type) {
                        var option = document.createElement('option');
                        option.text = "API Key Only";
                        option.value = keys[i];
                        option.id = "option" + currentOptionIndex++;
                        option.selected = false;
                        optionIndexes.push(option.id);
                        optionsArr.push(option);
                        buffer += option.outerHTML;
                    } else if (auth.type == "APIKeyAndSecretSecurityDevice") {
                        var option = document.createElement('option');
                        option.text = "API Key and Secret";
                        option.value = keys[i];
                        option.id = "option" + currentOptionIndex++;
                        option.selected = false;
                        optionIndexes.push(option.id);
                        optionsArr.push(option);
                        buffer += option.outerHTML;
                    } else if (auth.type == basicDevice.type) {
                        var option = document.createElement('option');
                        option.text = "Basic Authentication";
                        option.value = keys[i];
                        option.id = "option" + currentOptionIndex++;
                        option.selected = true;
                        optionIndexes.push(option.id);
                        optionsArr.push(option);
                        buffer += option.outerHTML;
                    } else if (auth.type == oauthDevice.type) {
                        var option = document.createElement('option');
                        option.text = "OAuth Authentication";
                        option.value = keys[i];
                        option.id = "option" + currentOptionIndex++;
                        option.selected = false;
                        optionIndexes.push(option.id);
                        optionsArr.push(option);
                        buffer += option.outerHTML;
                    } else if (auth.type == invokePolicyDevice.type) {
                        var option = document.createElement('option');
                        option.text = auth.name;
                        option.value = keys[i];
                        invokePolicyDesc = setDescription(auth);
                        invokePolicyName = auth.name;
                        option.id = "option" + currentOptionIndex++;
                        option.selected = false;
                        optionIndexes.push(option.id);
                        optionsArr.push(option);
                        buffer += option.outerHTML;
                    }
                }
                buffer += "\n</select>";
                buffer += "\n</div></div>";

                return buffer;
            }
            //return "\n        <div class='access'>\n          <span class=\"api-ic ic-off\" title=\"click to authenticate\"></span>\n        </div>\n        ";
        }

        function program12(depth0, data) {


            return "\n          <h4>Response Class</h4>\n          <p><span class=\"model-signature\" /></p>\n          <br/>\n          <div class=\"response-content-type\" />\n        ";
        }

        function program14(depth0, data) {


            return "\n          <h4>Parameters</h4>\n          <table class='fullwidth'>\n          <thead>\n            <tr>\n            <th style=\"width: 100px; max-width: 100px\">Parameter</th>\n            <th style=\"width: 310px; max-width: 310px\">Value</th>\n            <th style=\"width: 200px; max-width: 200px\">Description</th>\n            <th style=\"width: 100px; max-width: 100px\">Parameter Type</th>\n            <th style=\"width: 220px; max-width: 230px\">Data Type</th>\n            </tr>\n          </thead>\n          <tbody class=\"operation-params\">\n\n          </tbody>\n          </table>\n          ";
        }

        function program16(depth0, data) {


            return "\n          <div style='margin:0;padding:0;display:inline'></div>\n          <h4>Error Status Codes</h4>\n          <table class='fullwidth'>\n            <thead>\n            <tr>\n              <th>HTTP Status Code</th>\n              <th>Reason</th>\n            </tr>\n            </thead>\n            <tbody class=\"operation-status\">\n            \n            </tbody>\n          </table>\n          ";
        }

        function program18(depth0, data) {


            return "\n          ";
        }

        function program20(depth0, data) {
            return "\n<div  style='display: none;' id='error_box_auth_" + depth0.nickname + "'><div class='swagger-ui-wrap alert alert-warning' id='inner_auth" + depth0.nickname + "'>Warning: The request resource requires local authentication.</div></div>\n<div  style='visibility: hidden' id='error_box_" + depth0.nickname + "'><div class='swagger-ui-wrap alert alert-warning' id='inner_" + depth0.nickname + "'>Warning: The request did not include any credentials. <a id='goToAuth' href='javascript:void(0);'>Please, specify.</a></div></div><div class='sandbox_header'>\n            <input class='submit' name='commit' type='button' value='Try it out!'/>\n            <a href='#' class='response_hider' style='display:none'>Hide Response</a>\n            <img alt='Throbber' class='response_throbber' src='components/com_apiportal/assets/img/throbber.gif' style='display:none' />\n          </div>\n          ";
        }

        /**
         * Adds info about OAuth authorization
         * @param depth0 object
         * @param authDevice object
         * @param authDeviceName string
         * @returns {string}
         */
        function addOAuth(depth0, authDevice, authDeviceName) {
            // for collecting all the info
            var localStack = '';
            // use operation nickname for unique id
            var nickname = depth0.nickname.replace(/[\s]/g, '');
            // the title
            localStack += '<div class="padding-left-3 margin-bot-8">' +
                '<a style="text-decoration: none; margin-bottom: 3px;display: inline-block;" data-toggle="collapse" href="#' + authDevice.type + nickname + 'Auth" ' +
                'aria-expanded="true" aria-controls="' + authDevice.type + nickname + 'Auth">' + authDevice.typeDisplayName + '</a>' +
                '<div id="' + authDevice.type + nickname + 'Auth" class="collapse padding-left-3 margin-bot-8" aria-expanded="false">';
            // the body
            localStack += '<table class="auth-details">';
            // for the OAuth info we use details in the global authorizations object from the swagger definition
            // we set this object when we retrieve the swagger definition
            if (window && window.InitialSwaggerAuth) {
                // save a state if we found what we looking for
                var foundOAuth = false;
                // iterate over the global authorizations
                for (var initAuth in window.InitialSwaggerAuth) {
                    if (window.InitialSwaggerAuth.hasOwnProperty(initAuth)) {
                        // important here is that we search not only by the type (oauth2) but with the object property value
                        // because if we go only with the type there can be another authorization with oauth2 type
                        // and the result will be with wrong information
                        if (window.InitialSwaggerAuth[initAuth].type && window.InitialSwaggerAuth[initAuth].type == 'oauth2' && initAuth == authDeviceName) {
                            // if we found it save it
                            foundOAuth = true;
                            // in this statement we go through the grantTypes object and collect the needed information
                            if (window.InitialSwaggerAuth[initAuth].grantTypes) {
                                for (var gType in window.InitialSwaggerAuth[initAuth].grantTypes) {
                                    if (window.InitialSwaggerAuth[initAuth].grantTypes.hasOwnProperty(gType)) {
                                        switch (gType) {
                                            case 'authorization_code':
                                                localStack +=
                                                    '<tr>' +
                                                    '<td>Request endpoint URL</td>' +
                                                    '<td>' + escapeHTML(window.InitialSwaggerAuth[initAuth].grantTypes[gType]['tokenRequestEndpoint'].url) + '</td>' +
                                                    '</tr>' +
                                                    '<tr>' +
                                                    '<td>Request client ID name</td>' +
                                                    '<td>' + escapeHTML(window.InitialSwaggerAuth[initAuth].grantTypes[gType]['tokenRequestEndpoint'].clientIdName) + '</td>' +
                                                    '</tr>' +
                                                    '<tr>' +
                                                    '<td>Request client secret name</td>' +
                                                    '<td>' + escapeHTML(window.InitialSwaggerAuth[initAuth].grantTypes[gType]['tokenRequestEndpoint'].clientSecretName) + '</td>' +
                                                    '</tr>' +
                                                    '<tr>' +
                                                    '<td>Token URL</td>' +
                                                    '<td>' + escapeHTML(window.InitialSwaggerAuth[initAuth].grantTypes[gType]['tokenEndpoint'].url) + '</td>' +
                                                    '</tr>' +
                                                    '<tr>' +
                                                    '<td>Token name</td>' +
                                                    '<td>' + escapeHTML(window.InitialSwaggerAuth[initAuth].grantTypes[gType]['tokenEndpoint'].tokenName) + '</td>' +
                                                    '</tr>';
                                                break;
                                            case 'implicit':
                                                localStack += '<tr>' +
                                                    '<td>Login endpoint URL</td>' +
                                                    '<td>' + escapeHTML(window.InitialSwaggerAuth[initAuth].grantTypes[gType]["loginEndpoint"].url) + '</td>' +
                                                    '</tr>' +
                                                    '<tr>' +
                                                    '<td>Login token name</td>' +
                                                    '<td>' + escapeHTML(window.InitialSwaggerAuth[initAuth].grantTypes[gType].tokenName) + '</td>' +
                                                    '</tr>';
                                                break;
                                        }
                                    }
                                }
                            }

                            // get the scopes
                            if (window.InitialSwaggerAuth[initAuth].scopes) {
                                if ($.isArray(window.InitialSwaggerAuth[initAuth].scopes)) {
                                    var scopes = '';
                                    $.each(window.InitialSwaggerAuth[initAuth].scopes, function (key, value) {
                                        scopes += '<li>' + escapeHTML(value.scope) + '</li>';
                                    });
                                    localStack += '<tr>' +
                                        '<td>Scopes</td>' +
                                        '<td><ul>' + scopes + '</ul></td>' +
                                        '</tr>';
                                }
                            }
                            localStack += '</table>';
                            localStack += '</div></div>';
                        }
                    }
                }

                // if we didn't find the searched object we can only display the information about the scopes
                // and this info is from the local authorization object, not from the global one
                if (!foundOAuth) {
                    var localScopes = '';
                    $.each(authDevice.scopes, function (key, value) {
                        localScopes += '<li>' + escapeHTML(value) + '</li>';
                    });
                    localStack += '<tr>' +
                        '<td>Scopes</td>' +
                        '<td><ul>' + localScopes + '</ul></td>' +
                        '</tr>';
                    localStack += '</table>';
                    localStack += '</div></div>';
                }
            } else {
                localStack += '</table>';
                localStack += '</div></div>';
            }

            return localStack;
        }

        /**
         * Adds info about Basic authentication
         * @param depth0 object
         * @param authDevice object
         * @returns {string}
         */
        function addBasicAuth(depth0, authDevice) {
            var localStack = '';
            // use operation nickname for unique id
            var nickname = depth0.nickname.replace(/[\s]/g, '');
            // the title
            localStack += '<div class="padding-left-3 margin-bot-8">' +
                '<a style="text-decoration: none; margin-bottom: 3px;display: inline-block;" data-toggle="collapse" href="#' + authDevice.type + nickname + 'Auth" ' +
                'aria-expanded="true" aria-controls="' + authDevice.type + nickname + 'Auth">' + authDevice.name + '</a>' +
                '<div id="' + authDevice.type + nickname + 'Auth" class="collapse padding-left-3 margin-bot-8" aria-expanded="false">';
            // the body
            localStack += '<table class="auth-details">' +
                '<tr>' +
                '<td>' + 'Realm' + '</td>' +
                '<td>' + escapeHTML(authDevice.realm) + '</td>' +
                '</tr></table>';
            localStack += '</div></div>';

            return localStack;
        }

        /**
         * Adds info about API Key authentication
         * @param depth0 object
         * @param authDevice object
         * @returns {string}
         */
        function addAPIKeyAuth(depth0, authDevice) {
            var localStack = '';
            // use operation nickname for unique id
            var nickname = depth0.nickname.replace(/[\s]/g, '');
            // the title
            localStack += '<div class="padding-left-3 margin-bot-8">' +
                '<a style="text-decoration: none; margin-bottom: 3px;display: inline-block;" data-toggle="collapse" href="#' + authDevice.type + nickname + 'Auth" ' +
                'aria-expanded="true" aria-controls="' + authDevice.type + nickname + 'Auth">' + authDevice.name + '</a>' +
                '<div id="' + authDevice.type + nickname + 'Auth" class="collapse padding-left-3 margin-bot-8" aria-expanded="false">';
            // the body
            localStack += '<table class="auth-details">' +
                '<tr>' +
                '<td>API Key Field</td>' +
                '<td>' + escapeHTML(authDevice.keyField) + '</td>' +
                '</tr>' +
                '<tr>' +
                '<td>Pass as</td>' +
                '<td>' + escapeHTML(authDevice.takeFrom) + '</td>' +
                '</tr>' +
                '</table>';
            localStack += '</div></div>';

            return localStack;
        }

        /**
         * Adds info about Invoke Policy authentication
         * @param depth0 object
         * @param authDevice object
         * @returns {string}
         */
        function addAuthPolicy(depth0, authDevice) {
            var localStack = '';
            // use operation nickname for unique id
            var nickname = depth0.nickname.replace(/[\s]/g, '');
            // the title
            localStack += '<div class="padding-left-3 margin-bot-8">' +
                '<a style="text-decoration: none; margin-bottom: 3px;display: inline-block;" data-toggle="collapse" href="#' + authDevice.type + nickname + 'Auth" ' +
                'aria-expanded="true" aria-controls="' + authDevice.type + nickname + 'Auth">' + authDevice.name + '</a>' +
                '<div id="' + authDevice.type + nickname + 'Auth" class="collapse padding-left-3 margin-bot-8" aria-expanded="false">';
            // the body
            localStack += '<table class="auth-details">' +
                '<tr>' +
                '<td>Description</td>' +
                '<td>' + setMultiDescription(authDevice) + '</td>' +
                '</tr>' +
                '</table>';
            localStack += '</div></div>';

            return localStack;
        }

        /**
         * For displaying the Invoke Policy description
         * three types of description - normal, markdown and url
         * @param descriptionObj object
         * @returns string
         */
        function setMultiDescription(descriptionObj) {
            if (descriptionObj.description) {
                return escapeHTML(descriptionObj.description);
            } else if (descriptionObj.descriptionUrl) {
                return '<a href="' + encodeURIComponent(descriptionObj.descriptionUrl) + '" target="_blank">' + escapeHTML(descriptionObj.descriptionUrl) + '</a>';
            } else if (descriptionObj.descriptionMarkdown) {
                return marked(escapeHTML(descriptionObj.descriptionMarkdown));
            } else {
                return '';
            }
        }

        /**
         * Return the auth info according the the type
         * @param depth0 object
         * @param auth object
         * @param authDeviceName string
         * @returns {string}
         */
        function switchByAuthorizationType(depth0, auth, authDeviceName) {
            switch (auth.type) {
                case 'basic':
                    return addBasicAuth(depth0, auth);
                case 'apiKey':
                    return addAPIKeyAuth(depth0, auth);
                case 'oauth':
                    // when is normal authorization we search the OAuth info form the _default object
                    // and when it's security profile we search by property name
                    return addOAuth(depth0, auth, authDeviceName);
                case 'authPolicy':
                    return addAuthPolicy(depth0, auth);
            }
        }

        /**
         * This function adds Authentication support area in the Swagger UI method's box.
         * Authentication support is information about the authorization mechanisms.
         * @param depth0 object
         * @param data object
         * @returns {string}
         */
        function program22(depth0, data) {
            // collects all the output we want to display
            var localStack = '';
            // save the state if security profile is found in the authorizations object
            // if it is security profile it doesn't have type property
            var noTypeSecurityProfileDetected = false;
            // save the authorizations property name - needed when searching the auth info
            var securityProfileName = '';

            // iterate over the authorizations if exists
            if (depth0.authorizations && !jQuery.isEmptyObject(depth0.authorizations)) {
                // add the swagger UI section title
                localStack = "\n<h4 style='margin-bottom: 10px;'>Authentication Supported</h4>\n\n";
                // go through every authorization and take the info for it
                for (var auth in depth0.authorizations) {
                    if (depth0.authorizations.hasOwnProperty(auth)) {
                        // according to the authorization type we act different
                        // important here is the state of noTypeSecurityProfileDetected
                        // if the authorization is a security profile not normal authorization this state is true
                        // and will goes in the else statement, this is because we handle the oauth info different
                        if (depth0.authorizations[auth].type && !noTypeSecurityProfileDetected) {
                            // this is main reason for the if-else statement
                            // when we have normal authorization we sesrch for _default object
                            localStack += switchByAuthorizationType(depth0, depth0.authorizations[auth], '_default');
                        } else {
                            // we come here when security profile is detected
                            // the first time we are here noTypeSecurityProfileDetected will be false
                            // and if is false we change it to true save the authorization property name
                            // and go to the next element in the authorizations, there is a logic which fills the
                            // available authorizations in the operations authorizations object - it is not only the security profile;
                            // also in the security profile there is a bug - the authorizations are not filled right in the
                            // object, it is not an array the values are overwritten.
                            if (!noTypeSecurityProfileDetected) {
                                noTypeSecurityProfileDetected = true;
                                securityProfileName = auth;
                                continue;
                            }

                            // and again according to the type we act as expected
                            // but this time we search the info in the proper security profile object
                            localStack += switchByAuthorizationType(depth0, depth0.authorizations[auth], securityProfileName);
                        }
                    }
                }
            }
            // return the result
            return localStack + "<br />";
        }

        buffer += "\n  <ul class='operations' >\n    <li class='";
        if (stack1 = helpers.method) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.method;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }

        //Swagger original, replaced bellow to handle '*' method
        //buffer += escapeExpression(stack1)
        //  + " operation' id='";

        if (stack1 != "*") {
            buffer += escapeExpression(stack1)
            + " operation' id='";
        } else {
            buffer += "star"
            + " operation' id='";
        }

        if (stack1 = helpers.parentId) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.parentId;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        stack1 = stack1.replace(/[\s]/g, '');
        buffer += escapeExpression(stack1)
        + "_";
        if (stack1 = helpers.nickname) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.nickname;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "'>\n      <div class='heading'>\n        <h3>\n          <span class='http_method'>\n          <a href='" + window.location.search + "#!/";
        if (stack1 = helpers.parentId) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.parentId;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "/";
        if (stack1 = helpers.nickname) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.nickname;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "' class=\"toggleOperation\">";
        if (stack1 = helpers.method) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.method;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(decodeURIComponent(stack1))
        + "</a>\n          </span>\n          <span class='path'>\n          <a href='" + window.location.search + "#!/";
        if (stack1 = helpers.parentId) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.parentId;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "/";
        if (stack1 = helpers.nickname) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.nickname;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "' class=\"toggleOperation\">";
        if (stack1 = helpers.path) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.path;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (apiType === 'wsdl' && typeof depth0.nickname === 'string') {
            stack1 = depth0.nickname;
        }
        buffer += escapeExpression(decodeURIComponent(stack1))
        + "</a>\n          </span>\n        </h3>\n        <ul class='options'>\n          <li>\n          <a href='" + window.location.search + "#!/";
        if (stack1 = helpers.parentId) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.parentId;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "/";
        if (stack1 = helpers.nickname) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.nickname;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(decodeURIComponent(stack1))
        + "' class=\"toggleOperation\">";
        if (stack1 = helpers.summary) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.summary;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (stack1 || stack1 === 0) {
            buffer += escapeExpression(stack1);
        }
        buffer += "</a>\n          </li>\n        </ul>\n      </div>\n      <div class='content' id='";
        if (stack1 = helpers.parentId) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.parentId;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "_";
        if (stack1 = helpers.nickname) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.nickname;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "_content' style='display:none'>\n        ";
        stack1 = helpers['if'].call(depth0, depth0.notes, {
            hash: {},
            inverse: self.noop,
            fn: self.program(1, program1, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        else {
            var stack2 = helpers['if'].call(depth0, depth0.documentationUrl, {
                hash: {},
                inverse: self.noop,
                fn: self.program(1, program1, data),
                data: data
            });
            if (stack2 || stack2 === 0) {
                buffer += stack2;
            }
        }
        buffer += "\n        ";
        options = {hash: {}, inverse: self.noop, fn: self.program(3, program3, data), data: data};
        if (stack1 = helpers.oauth) {
            stack1 = stack1.call(depth0, options);
        }
        else {
            stack1 = depth0.oauth;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (!helpers.oauth) {
            stack1 = blockHelperMissing.call(depth0, stack1, options);
        }
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n        ";
        stack1 = helpers.each.call(depth0, depth0.oauth, {
            hash: {},
            inverse: self.noop,
            fn: self.program(5, program5, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n        ";
        options = {hash: {}, inverse: self.noop, fn: self.program(8, program8, data), data: data};
        if (stack1 = helpers.oauth) {
            stack1 = stack1.call(depth0, options);
        }
        else {
            stack1 = depth0.oauth;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (!helpers.oauth) {
            stack1 = blockHelperMissing.call(depth0, stack1, options);
        }
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n        ";
        options = {hash: {}, inverse: self.noop, fn: self.program(10, program10, data), data: data};
        if (stack1 = helpers.oauth) {
            stack1 = stack1.call(depth0, options);
        }
        else {
            stack1 = depth0.oauth;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (!helpers.oauth) { //stack1 = blockHelperMissing.call(depth0, stack1, options); 
            if (stack1 === "" || stack1 === null) {
                if (helpers.authorizations) {
                    stack1 = helpers;
                    stack1 = blockHelperMissing.call(depth0, stack1, options)
                } else {
                    if (depth0.authorizations) {
                        stack1 = depth0;
                        stack1 = blockHelperMissing.call(depth0, stack1, options)
                    }
                }
            } else if (stack1 && stack1.scopes) {
                stack1 = depth0;
                stack1 = blockHelperMissing.call(depth0, stack1, options)
            }
        }
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n        ";
        stack1 = helpers['if'].call(depth0, depth0.type, {
            hash: {},
            inverse: self.noop,
            fn: self.program(12, program12, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n        <form accept-charset='UTF-8' class='sandbox'>\n          <div style='margin:0;padding:0;display:inline'></div>\n          ";
        stack1 = helpers['if'].call(depth0, depth0.parameters, {
            hash: {},
            inverse: self.noop,
            fn: self.program(14, program14, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n          ";
        stack1 = helpers['if'].call(depth0, depth0.responseMessages, {
            hash: {},
            inverse: self.noop,
            fn: self.program(16, program16, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n";
        // Add info about the auths
        stack1 = helpers['if'].call(depth0, depth0.authorizations, {
            hash: {},
            inverse: self.noop,
            fn: self.program(22, program22, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n          ";
        stack1 = helpers['if'].call(depth0, depth0.isReadOnly, {
            hash: {},
            inverse: self.program(20, program20, data),
            fn: self.program(18, program18, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n        </form>\n        <div class='response' style='display:none'>\n          <h4>Request URL</h4>\n          <div class='block request_url'></div>\n          <h4>Response Body</h4>\n          <div class='block response_body'></div>\n          <h4>Response Code</h4>\n          <div class='block response_code'></div>\n          <h4>Response Headers</h4>\n          <div class='block response_headers'></div>\n        </div>\n      </div>\n    </li>\n  </ul>\n";
        return buffer;
    });
})();

(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['param'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, functionType = "function", escapeExpression = this.escapeExpression, self = this;

        function program1(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n		";
            stack1 = helpers['if'].call(depth0, depth0.isFile, {
                hash: {},
                inverse: self.program(4, program4, data),
                fn: self.program(2, program2, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n	";
            return buffer;
        }

        function program2(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n			<input type=\"file\" name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "'/>\n			<div class=\"parameter-content-type\" />\n		";
            return buffer;
        }

        function program4(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n			";
            stack1 = helpers['if'].call(depth0, depth0.defaultValue, {
                hash: {},
                inverse: self.program(7, program7, data),
                fn: self.program(5, program5, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n		";
            return buffer;
        }

        function program5(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n				<textarea class='body-textarea' name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "'>";
            if (stack1 = helpers.defaultValue) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.defaultValue;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "</textarea>\n			";
            return buffer;
        }

        function program7(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n				<textarea class='body-textarea' name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "'></textarea>\n				<br />\n				<div class=\"parameter-content-type\" />\n			";
            return buffer;
        }

        function program9(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n		";
            stack1 = helpers['if'].call(depth0, depth0.isFile, {
                hash: {},
                inverse: self.program(12, program12, data),
                fn: self.program(10, program10, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n	";
            return buffer;
        }

        function program10(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n			<input class='parameter' class='' type='file' name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
                + "'/>\n		";
            return buffer;
        }

        function program12(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n			";
            stack1 = helpers['if'].call(depth0, depth0.defaultValue, {
                hash: {},
                inverse: self.program(15, program15, data),
                fn: self.program(13, program13, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n		";
            return buffer;
        }

        function program13(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n				<input class='parameter' name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
                + "' placeholder='' type='text' value='";
            if (stack1 = helpers.defaultValue) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.defaultValue;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
                + "'/>\n			";
            return buffer;
        }

        function program15(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n				<input class='parameter' name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
                + "' placeholder='' type='text' value=''/>\n			";
            return buffer;
        }

        buffer += "<td class='code'>";
        if (stack1 = helpers.name) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.name;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "</td>\n<td>\n\n	";
        stack1 = helpers['if'].call(depth0, depth0.isBody, {
            hash: {},
            inverse: self.program(9, program9, data),
            fn: self.program(1, program1, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n\n</td>\n<td>";
        if (stack1 = helpers.description) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.description;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (stack1 || stack1 === 0) {
            buffer += escapeExpression(stack1);
        }
        buffer += "</td>\n<td>";
        if (stack1 = helpers.paramType) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.paramType;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (stack1 || stack1 === 0) {
            buffer += escapeExpression(stack1);
        }
        buffer += "</td>\n<td>\n	<span class=\"model-signature\"></span>\n</td>\n";
        return buffer;
    });
})();

(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['param_list'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, stack2, options, self = this, helperMissing = helpers.helperMissing, functionType = "function", escapeExpression = this.escapeExpression;

        function program1(depth0, data) {


            return " multiple='multiple'";
        }

        function program3(depth0, data) {


            return "\n    ";
        }

        function program5(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n      ";
            stack1 = helpers['if'].call(depth0, depth0.defaultValue, {
                hash: {},
                inverse: self.program(8, program8, data),
                fn: self.program(6, program6, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n    ";
            return buffer;
        }

        function program6(depth0, data) {


            return "\n      ";
        }

        function program8(depth0, data) {

            var buffer = "", stack1, stack2, options;
            buffer += "\n        ";
            options = {
                hash: {},
                inverse: self.program(11, program11, data),
                fn: self.program(9, program9, data),
                data: data
            };
            stack2 = ((stack1 = helpers.isArray || depth0.isArray), stack1 ? stack1.call(depth0, depth0, options) : helperMissing.call(depth0, "isArray", depth0, options));
            if (stack2 || stack2 === 0) {
                buffer += stack2;
            }
            buffer += "\n      ";
            return buffer;
        }

        function program9(depth0, data) {


            return "\n        ";
        }

        function program11(depth0, data) {


            return "\n          <option selected=\"\" value=''></option>\n        ";
        }

        function program13(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n      ";
            stack1 = helpers['if'].call(depth0, depth0.isDefault, {
                hash: {},
                inverse: self.program(16, program16, data),
                fn: self.program(14, program14, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n    ";
            return buffer;
        }

        function program14(depth0, data) {
            var buffer = "", stack1;
            buffer += "\n        <option selected=\"\" value='";
            if (stack1 = helpers.value) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.value;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "'>";
            if (stack1 = helpers.value) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.value;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + " (default)</option>\n      ";
            return buffer;
        }

        function program16(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n        <option value='";
            if (stack1 = helpers.value) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.value;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "'>";
            if (stack1 = helpers.value) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.value;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "</option>\n      ";
            return buffer;
        }

        buffer += "<td class='code'>";
        if (stack1 = helpers.name) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.name;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "</td>\n<td>\n  <select ";
        options = {hash: {}, inverse: self.noop, fn: self.program(1, program1, data), data: data};
        stack2 = ((stack1 = helpers.isArray || depth0.isArray), stack1 ? stack1.call(depth0, depth0, options) : helperMissing.call(depth0, "isArray", depth0, options));
        if (stack2 || stack2 === 0) {
            buffer += stack2;
        }
        buffer += " class='parameter' name='";
        if (stack2 = helpers.name) {
            stack2 = stack2.call(depth0, {hash: {}, data: data});
        }
        else {
            stack2 = depth0.name;
            stack2 = typeof stack2 === functionType ? stack2.apply(depth0) : stack2;
        }
        buffer += escapeExpression(stack2)
        + "'>\n    ";
        stack2 = helpers['if'].call(depth0, depth0.required, {
            hash: {},
            inverse: self.program(5, program5, data),
            fn: self.program(3, program3, data),
            data: data
        });
        if (stack2 || stack2 === 0) {
            buffer += stack2;
        }
        buffer += "\n    ";
        stack2 = helpers.each.call(depth0, ((stack1 = depth0.allowableValues), stack1 == null || stack1 === false ? stack1 : stack1.descriptiveValues), {
            hash: {},
            inverse: self.noop,
            fn: self.program(13, program13, data),
            data: data
        });
        if (stack2 || stack2 === 0) {
            buffer += stack2;
        }
        buffer += "\n  </select>\n</td>\n<td>";
        if (stack2 = helpers.description) {
            stack2 = stack2.call(depth0, {hash: {}, data: data});
        }
        else {
            stack2 = depth0.description;
            stack2 = typeof stack2 === functionType ? stack2.apply(depth0) : stack2;
        }
        if (stack2 || stack2 === 0) {
            buffer += escapeExpression(stack2);
        }
        buffer += "</td>\n<td>";
        if (stack2 = helpers.paramType) {
            stack2 = stack2.call(depth0, {hash: {}, data: data});
        }
        else {
            stack2 = depth0.paramType;
            stack2 = typeof stack2 === functionType ? stack2.apply(depth0) : stack2;
        }
        if (stack2 || stack2 === 0) {
            buffer += escapeExpression(stack2);
        }
        buffer += "</td>\n<td><span class=\"model-signature\"></span></td>";
        return buffer;
    });
})();

(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['param_readonly'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, functionType = "function", escapeExpression = this.escapeExpression, self = this;

        function program1(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n        <textarea class='body-textarea' readonly='readonly' name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "'>";
            if (stack1 = helpers.defaultValue) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.defaultValue;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "</textarea>\n    ";
            return buffer;
        }

        function program3(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n        ";
            stack1 = helpers['if'].call(depth0, depth0.defaultValue, {
                hash: {},
                inverse: self.program(6, program6, data),
                fn: self.program(4, program4, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n    ";
            return buffer;
        }

        function program4(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n            ";
            if (stack1 = helpers.defaultValue) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.defaultValue;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "\n        ";
            return buffer;
        }

        function program6(depth0, data) {


            return "\n            (empty)\n        ";
        }

        buffer += "<td class='code'>";
        if (stack1 = helpers.name) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.name;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "</td>\n<td>\n    ";
        stack1 = helpers['if'].call(depth0, depth0.isBody, {
            hash: {},
            inverse: self.program(3, program3, data),
            fn: self.program(1, program1, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n</td>\n<td>";
        if (stack1 = helpers.description) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.description;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (stack1 || stack1 === 0) {
            buffer += escapeExpression(stack1);
        }
        buffer += "</td>\n<td>";
        if (stack1 = helpers.paramType) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.paramType;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (stack1 || stack1 === 0) {
            buffer += escapeExpression(stack1);
        }
        buffer += "</td>\n<td><span class=\"model-signature\"></span></td>\n";
        return buffer;
    });
})();

(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['param_readonly_required'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, functionType = "function", escapeExpression = this.escapeExpression, self = this;

        function program1(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n        <textarea class='body-textarea'  readonly='readonly' placeholder='(required)' name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "'>";
            if (stack1 = helpers.defaultValue) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.defaultValue;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "</textarea>\n    ";
            return buffer;
        }

        function program3(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n        ";
            stack1 = helpers['if'].call(depth0, depth0.defaultValue, {
                hash: {},
                inverse: self.program(6, program6, data),
                fn: self.program(4, program4, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n    ";
            return buffer;
        }

        function program4(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n            ";
            if (stack1 = helpers.defaultValue) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.defaultValue;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "\n        ";
            return buffer;
        }

        function program6(depth0, data) {


            return "\n            (empty)\n        ";
        }

        buffer += "<td class='code required'>";
        if (stack1 = helpers.name) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.name;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "</td>\n<td>\n    ";
        stack1 = helpers['if'].call(depth0, depth0.isBody, {
            hash: {},
            inverse: self.program(3, program3, data),
            fn: self.program(1, program1, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n</td>\n<td>";
        if (stack1 = helpers.description) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.description;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (stack1 || stack1 === 0) {
            buffer += escapeExpression(stack1);
        }
        buffer += "</td>\n<td>";
        if (stack1 = helpers.paramType) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.paramType;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (stack1 || stack1 === 0) {
            buffer += escapeExpression(stack1);
        }
        buffer += "</td>\n<td><span class=\"model-signature\"></span></td>\n";
        return buffer;
    });
})();

(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['param_required'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, functionType = "function", escapeExpression = this.escapeExpression, self = this;

        function program1(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n		";
            stack1 = helpers['if'].call(depth0, depth0.isFile, {
                hash: {},
                inverse: self.program(4, program4, data),
                fn: self.program(2, program2, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n	";
            return buffer;
        }

        function program2(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n			<input type=\"file\" name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "'/>\n		";
            return buffer;
        }

        function program4(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n			";
            stack1 = helpers['if'].call(depth0, depth0.defaultValue, {
                hash: {},
                inverse: self.program(7, program7, data),
                fn: self.program(5, program5, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n		";
            return buffer;
        }

        function program5(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n				<textarea class='body-textarea' placeholder='(required)' name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "'>";
            if (stack1 = helpers.defaultValue) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.defaultValue;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "</textarea>\n			";
            return buffer;
        }

        function program7(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n				<textarea class='body-textarea' placeholder='(required)' name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "'></textarea>\n				<br />\n				<div class=\"parameter-content-type\" />\n			";
            return buffer;
        }

        function program9(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n		";
            stack1 = helpers['if'].call(depth0, depth0.isFile, {
                hash: {},
                inverse: self.program(12, program12, data),
                fn: self.program(10, program10, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n	";
            return buffer;
        }

        function program10(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n			<input class='parameter' class='required' type='file' name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "'/>\n		";
            return buffer;
        }

        function program12(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n			";
            stack1 = helpers['if'].call(depth0, depth0.defaultValue, {
                hash: {},
                inverse: self.program(15, program15, data),
                fn: self.program(13, program13, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n		";
            return buffer;
        }

        function program13(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n				<input class='parameter required' minlength='1' name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "' placeholder='(required)' type='text' value='";
            if (stack1 = helpers.defaultValue) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.defaultValue;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "'/>\n			";
            return buffer;
        }

        function program15(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n				<input class='parameter required' minlength='1' name='";
            if (stack1 = helpers.name) {
                stack1 = stack1.call(depth0, {hash: {}, data: data});
            }
            else {
                stack1 = depth0.name;
                stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
            }
            buffer += escapeExpression(stack1)
            + "' placeholder='(required)' type='text' value=''/>\n			";
            return buffer;
        }

        buffer += "<td class='code required'>";
        if (stack1 = helpers.name) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.name;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "</td>\n<td>\n	";
        stack1 = helpers['if'].call(depth0, depth0.isBody, {
            hash: {},
            inverse: self.program(9, program9, data),
            fn: self.program(1, program1, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n</td>\n<td>\n	<strong>";
        if (stack1 = helpers.description) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.description;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (stack1 || stack1 === 0) {
            buffer += escapeExpression(stack1);
        }
        buffer += "</strong>\n</td>\n<td>";
        if (stack1 = helpers.paramType) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.paramType;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (stack1 || stack1 === 0) {
            buffer += escapeExpression(stack1);
        }
        buffer += "</td>\n<td><span class=\"model-signature\"></span></td>\n";
        return buffer;
    });
})();

(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['parameter_content_type'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, functionType = "function", self = this, escapeExpression = this.escapeExpression;

        function program1(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n  ";
            stack1 = helpers.each.call(depth0, depth0.consumes, {
                hash: {},
                inverse: self.noop,
                fn: self.program(2, program2, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n";
            return buffer;
        }

        function program2(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n  <option value=\"";
            stack1 = (typeof depth0 === functionType ? depth0.apply(depth0) : depth0);
            if (stack1 || stack1 === 0) {
                buffer += escapeExpression(stack1);
            }
            buffer += "\">";
            stack1 = (typeof depth0 === functionType ? depth0.apply(depth0) : depth0);
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "</option>\n  ";
            return buffer;
        }

        function program4(depth0, data) {


            return "\n  <option value=\"application/json\">application/json</option>\n";
        }

        buffer += "<label for=\"parameterContentType\"></label>\n<select name=\"parameterContentType\">\n";
        stack1 = helpers['if'].call(depth0, depth0.consumes, {
            hash: {},
            inverse: self.program(4, program4, data),
            fn: self.program(1, program1, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n</select>\n";
        return buffer;
    });
})();

(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['resource'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, options, functionType = "function", escapeExpression = this.escapeExpression, self = this, blockHelperMissing = helpers.blockHelperMissing;

        function program1(depth0, data) {


            return "";
        }

        buffer += "<div class='heading'>\n  <h2>\n    <a href='" + window.location.search + "#!/";
        if (stack1 = helpers.id) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.id;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "' onclick=\"Docs.toggleEndpointListForResource('";
        if (stack1 = helpers.id) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.id;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "');\">";
        if (stack1 = helpers.path) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.path;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "<a> </h2> \n";
        buffer += "\n  <ul class='options'>\n    <li>\n      <a href='#' onclick=\"Docs.collapseOperationsForResource('";
        if (stack1 = helpers.id) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.id;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "'); return false;\">\n        Listar Métodos\n      </a>\n    </li>\n    <li>\n      <a href='#' onclick=\"Docs.expandOperationsForResource('";
        if (stack1 = helpers.id) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.id;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "'); return false;\">\n        Expandir Métodos\n      </a>\n    </li>\n  ";
        if (stack1 = helpers.url) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.url;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + " </ul>\n</div>\n<ul class='endpoints' id='";
        if (stack1 = helpers.id) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.id;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "_endpoint_list' style='display:none'>\n\n</ul>\n";
        return buffer;
    });
})();

(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['response_content_type'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, functionType = "function", self = this, escapeExpression = this.escapeExpression;

        function program1(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n  ";
            stack1 = helpers.each.call(depth0, depth0.produces, {
                hash: {},
                inverse: self.noop,
                fn: self.program(2, program2, data),
                data: data
            });
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "\n";
            return buffer;
        }

        function program2(depth0, data) {

            var buffer = "", stack1;
            buffer += "\n  <option value=\"";
            stack1 = (typeof depth0 === functionType ? depth0.apply(depth0) : depth0);
            if (stack1 || stack1 === 0) {
                buffer += escapeExpression(stack1);
            }
            buffer += "\">";
            stack1 = (typeof depth0 === functionType ? depth0.apply(depth0) : depth0);
            if (stack1 || stack1 === 0) {
                buffer += stack1;
            }
            buffer += "</option>\n  ";
            return buffer;
        }

        function program4(depth0, data) {


            return "\n  <option value=\"application/json\">application/json</option>\n";
        }

        buffer += "<label for=\"responseContentType\"></label>\n<select name=\"responseContentType\">\n";
        stack1 = helpers['if'].call(depth0, depth0.produces, {
            hash: {},
            inverse: self.program(4, program4, data),
            fn: self.program(1, program1, data),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n</select>\n";
        return buffer;
    });
})();

(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['signature'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, functionType = "function", escapeExpression = this.escapeExpression;


        buffer += "<div>\n<ul class=\"signature-nav\">\n    <li><a class=\"description-link\" href=\"#\">Model</a></li>\n    <li><a class=\"snippet-link\" href=\"#\">Model Schema</a></li>\n</ul>\n<div>\n\n<div class=\"signature-container\">\n    <div class=\"description\">\n        ";
        if (stack1 = helpers.signature) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.signature;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n    </div>\n\n    <div class=\"snippet\">\n        <pre><code>";
        if (stack1 = helpers.sampleJSON) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.sampleJSON;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "</code></pre>\n        <small class=\"notice\"></small>\n    </div>\n</div>\n\n";
        return buffer;
    });
})();

(function () {
    var template = Handlebars.template, templates = Handlebars.templates = Handlebars.templates || {};
    templates['status_code'] = template(function (Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [4, '>= 1.0.0'];
        helpers = this.merge(helpers, Handlebars.helpers);
        data = data || {};
        var buffer = "", stack1, functionType = "function", escapeExpression = this.escapeExpression;


        buffer += "<td width='15%' class='code'>";
        if (stack1 = helpers.code) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.code;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1)
        + "</td>\n<td>";
        if (stack1 = helpers.message) {
            stack1 = stack1.call(depth0, {hash: {}, data: data});
        }
        else {
            stack1 = depth0.message;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        if (stack1 || stack1 === 0) {
            buffer += escapeExpression(stack1);
        }
        buffer += "</td>\n";
        return buffer;
    });
})();


// Generated by CoffeeScript 1.6.3
(function () {
    var ContentTypeView, HeaderView, MainView, OperationView, ParameterContentTypeView, ParameterView, ResourceView, ResponseContentTypeView, SignatureView, StatusCodeView, SwaggerUi, _ref, _ref1, _ref10, _ref2, _ref3, _ref4, _ref5, _ref6, _ref7, _ref8, _ref9,
        __hasProp = {}.hasOwnProperty,
        __extends = function (child, parent) {
            for (var key in parent) {
                if (__hasProp.call(parent, key)) child[key] = parent[key];
            }
            function ctor() {
                this.constructor = child;
            }

            ctor.prototype = parent.prototype;
            child.prototype = new ctor();
            child.__super__ = parent.prototype;
            return child;
        };

    SwaggerUi = (function (_super) {
        __extends(SwaggerUi, _super);

        function SwaggerUi() {
            _ref = SwaggerUi.__super__.constructor.apply(this, arguments);
            return _ref;
        }

        SwaggerUi.prototype.dom_id = "swagger_ui";

        SwaggerUi.prototype.options = null;

        SwaggerUi.prototype.api = null;

        SwaggerUi.prototype.headerView = null;

        SwaggerUi.prototype.mainView = null;

        SwaggerUi.prototype.initialize = function (options) {
            var _this = this;
            if (options == null) {
                options = {};
            }
            if (options.dom_id != null) {
                this.dom_id = options.dom_id;
                delete options.dom_id;
            }
            if ($('#' + this.dom_id) == null) {
                $('body').append('<div id="' + this.dom_id + '"></div>');
            }
            this.options = options;
            this.options.success = function () {
                return _this.render();
            };
            this.options.progress = function (d) {
                return _this.showMessage(d);
            };
            this.options.failure = function (d) {
                return _this.onLoadFailure(d);
            };
            this.headerView = new HeaderView({
                el: $('#header')
            });
            return this.headerView.on('update-swagger-ui', function (data) {
                return _this.updateSwaggerUi(data);
            });
        };

        SwaggerUi.prototype.updateSwaggerUi = function (data) {
            this.options.url = data.url;
            return this.load();
        };

        SwaggerUi.prototype.load = function () {
            var url, _ref1;
            if ((_ref1 = this.mainView) != null) {
                _ref1.clear();
            }
            url = this.options.url;
            if (url.indexOf("http") !== 0) {
                url = this.buildUrl(window.location.href.toString(), url);
            }
            this.options.url = url;
            this.headerView.update(url);
            this.api = new SwaggerApiExtension(this.options);
            return this.api;
        };

        SwaggerUi.prototype.render = function () {
            var _this = this;
            this.showMessage('Finished Loading Resource Information. Rendering Swagger UI...');
            this.mainView = new MainView({
                model: this.api,
                el: $('#' + this.dom_id)
            }).render();
            this.showMessage();
            switch (this.options.docExpansion) {
                case "full":
                    Docs.expandOperationsForResource('');
                    break;
                case "list":
                    Docs.collapseOperationsForResource('');
            }
            if (this.options.onComplete) {
                this.options.onComplete(this.api, this);
            }
            return setTimeout(function () {
                return Docs.shebang();
            }, 400);
        };

        SwaggerUi.prototype.buildUrl = function (base, url) {
            var endOfPath, parts;
            log("base is " + base);
            if (url.indexOf("/") === 0) {
                parts = base.split("/");

                base = parts[0] + "//" + parts[2];
                return base + url;
            } else {
                endOfPath = base.length;
                if (base.indexOf("?") > -1) {
                    endOfPath = Math.min(endOfPath, base.indexOf("?"));
                }
                if (base.indexOf("#") > -1) {
                    endOfPath = Math.min(endOfPath, base.indexOf("#"));
                }
                base = base.substring(0, endOfPath);
                if (base.indexOf("/", base.length - 1) !== -1) {
                    return base + url;
                }
                return base + "/" + url;
            }
        };

        SwaggerUi.prototype.showMessage = function (data) {
            if (data == null) {
                data = '';
            }
            $('#message-bar').removeClass('message-fail');
            $('#message-bar').addClass('message-success');
            return $('#message-bar').html(data);
        };

        SwaggerUi.prototype.onLoadFailure = function (data) {
            var val;
            if (data == null) {
                data = '';
            }
            $('#message-bar').removeClass('message-success');
            $('#message-bar').addClass('message-fail');
            val = $('#message-bar').html(data);
            if (this.options.onFailure != null) {
                this.options.onFailure(data);
            }
            return val;
        };

        return SwaggerUi;

    })(Backbone.Router);

    window.SwaggerUi = SwaggerUi;

    HeaderView = (function (_super) {
        __extends(HeaderView, _super);

        function HeaderView() {
            _ref1 = HeaderView.__super__.constructor.apply(this, arguments);
            return _ref1;
        }

        HeaderView.prototype.events = {
            'click #show-pet-store-icon': 'showPetStore',
            'click #show-wordnik-dev-icon': 'showWordnikDev',
            'click #explore': 'showCustom',
            'keyup #input_baseUrl': 'showCustomOnKeyup',
            'keyup #input_apiKey': 'showCustomOnKeyup'
        };

        HeaderView.prototype.initialize = function () {
        };

        HeaderView.prototype.showPetStore = function (e) {
            return this.trigger('update-swagger-ui', {
                url: "http://petstore.swagger.wordnik.com/api/api-docs"
            });
        };

        HeaderView.prototype.showWordnikDev = function (e) {
            return this.trigger('update-swagger-ui', {
                url: "http://api.wordnik.com/v4/resources.json"
            });
        };

        HeaderView.prototype.showCustomOnKeyup = function (e) {
            if (e.keyCode === 13) {
                return this.showCustom();
            }
        };

        HeaderView.prototype.showCustom = function (e) {
            if (e != null) {
                e.preventDefault();
            }
            return this.trigger('update-swagger-ui', {
                url: $('#input_baseUrl').val(),
                apiKey: $('#input_apiKey').val()
            });
        };

        HeaderView.prototype.update = function (url, apiKey, trigger) {
            if (trigger == null) {
                trigger = false;
            }
            $('#input_baseUrl').val(url);
            if (trigger) {
                return this.trigger('update-swagger-ui', {
                    url: url
                });
            }
        };

        return HeaderView;

    })(Backbone.View);

    MainView = (function (_super) {
        __extends(MainView, _super);

        function MainView() {
            _ref2 = MainView.__super__.constructor.apply(this, arguments);
            return _ref2;
        }

        sorters = {
            'alpha': function (a, b) {
                return a.nickname.localeCompare(b.nickname);
            },
            'method': function (a, b) {
                return a.method.localeCompare(b.method);
            }
        };

        MainView.prototype.initialize = function () {
            var route, sorter, sorterName, _i, _len, _ref3;
            var opts = swaggerUi.options;
            if (opts == null) {
                opts = {};
            }
            if (opts.sorter) {
                sorterName = opts.sorter;
                sorter = sorters[sorterName];
                _ref3 = this.model.apisArray;
                for (_i = 0, _len = _ref3.length; _i < _len; _i++) {
                    route = _ref3[_i];
                    route.operationsArray.sort(sorter);
                }
                if (sorterName === "alpha") {
                    return this.model.apisArray.sort(sorter);
                }
            }
        };

        MainView.prototype.render = function () {
            var counter, id, resource, resources, _i, _len, _ref3;
            $(this.el).html(Handlebars.templates.main(this.model));
            resources = {};
            counter = 0;
            _ref3 = this.model.apisArray;
            for (_i = 0, _len = _ref3.length; _i < _len; _i++) {
                resource = _ref3[_i];
                id = resource.name;
                while (typeof resources[id] !== 'undefined') {
                    id = id + "_" + counter;
                    counter += 1;
                }
                resource.id = id;
                resources[id] = resource;
                this.addResource(resource);
            }
            return this;
        };

        MainView.prototype.addResource = function (resource) {
            var resourceView;
            resourceView = new ResourceView({
                model: resource,
                tagName: 'li',
                id: 'resource_' + resource.id,
                className: 'resource'
            });
            return $('#resources').append(resourceView.render().el);
        };

        MainView.prototype.clear = function () {
            return $(this.el).html('');
        };

        return MainView;

    })(Backbone.View);

    ResourceView = (function (_super) {
        __extends(ResourceView, _super);

        function ResourceView() {
            _ref3 = ResourceView.__super__.constructor.apply(this, arguments);
            return _ref3;
        }

        ResourceView.prototype.initialize = function () {
        };

        ResourceView.prototype.render = function () {
            var counter, id, methods, operation, _i, _len, _ref4;
            $(this.el).html(Handlebars.templates.resource(this.model));
            methods = {};
            _ref4 = this.model.operationsArray;
            for (_i = 0, _len = _ref4.length; _i < _len; _i++) {
                operation = _ref4[_i];
                counter = 0;
                id = operation.nickname;
                while (typeof methods[id] !== 'undefined') {
                    id = id + "_" + counter;
                    counter += 1;
                }
                methods[id] = operation;
                operation.nickname = id;
                operation.parentId = this.model.id;
                this.addOperation(operation);
            }
            return this;
        };

        ResourceView.prototype.addOperation = function (operation) {
            var operationView;
            operation.number = this.number;
            operationView = new OperationView({
                model: operation,
                tagName: 'li',
                className: 'endpoint'
            });
            $('.endpoints', $(this.el)).append(operationView.render().el);
            return this.number++;
        };

        return ResourceView;

    })(Backbone.View);

    OperationView = (function (_super) {
        __extends(OperationView, _super);

        function OperationView() {
            _ref4 = OperationView.__super__.constructor.apply(this, arguments);
            return _ref4;
        }

        OperationView.prototype.invocationUrl = null;

        OperationView.prototype.events = {
            'submit .sandbox': 'submitOperation',
            'click .submit': 'submitOperation',
            'click .response_hider': 'hideResponse',
            'click .toggleOperation': 'toggleOperationContent',
            'change .authselect': 'changeSelectedAuthentication',
            'mouseenter .api-ic': 'mouseEnter',
            'mouseout .api-ic': 'mouseExit'
        };

        OperationView.prototype.initialize = function () {
        };

        OperationView.prototype.createCredentialsHelper = function (currentAuthenticationType, parentNode, methodName) {
            var credentials;
            var inputField;
            var selectField;
            var cbSelectorGenerated = null;
            if (currentAuthenticationType === basicDevice.type) {
                credentials = $(document.createElement('div'));
                credentials.addClass("auth-credentials-div");

                inputField = $(document.createElement('input'));
                inputField.attr("type", "text");
                inputField.addClass("auth-credentials");
                inputField.attr("placeholder", "username");
                inputField.attr("id", "auth_username_" + methodName);
                credentials.append(inputField);

                inputField = $(document.createElement('input'));
                inputField.attr("type", "password");
                inputField.attr("id", "auth_password_" + methodName);
                inputField.addClass("auth-credentials");
                inputField.attr("placeholder", "password");
                credentials.append(inputField);
            } else if (currentAuthenticationType === apiKeyDevice.type) {
                credentials = $(document.createElement('div'));
                credentials.addClass("auth-credentials-div");

                selectField = $('<select/>');
                selectField.attr("placeholder", "api key");
                selectField.attr("name", "auth_apikey_" + methodName);
                selectField.attr("id", "auth_apikey_" + methodName);
                selectField.addClass("auth-credentials");
                selectField.addClass("auth-credentials");
                credentials.append(selectField);

                if (window.swaggerUi.options.onAPIKeySelectorGenerated) {
                    cbSelectorGenerated = window.swaggerUi.options.onAPIKeySelectorGenerated;
                }
            } else if (currentAuthenticationType === oauthDevice.type) {
                credentials = $(document.createElement('div'));
                credentials.addClass("auth-credentials-div");

                selectField = $('<select/>');
                selectField.attr("placeholder", "client id");
                selectField.attr("name", "oauthid_" + methodName);
                selectField.attr("id", "oauthid_" + methodName);
                var api = Object.keys(swaggerUi.api.apis)[0]; // we always have only one api
                selectField.data(swaggerUi.api.apis[api].operations[methodName].oauth);
                selectField.addClass("auth-credentials");
                credentials.append(selectField);

                var tokenDiv = $(document.createElement('div'));
                tokenDiv.addClass('col-sm-12');
                credentials.append(tokenDiv);

                var tokenStatus = $(document.createElement('span'));
                tokenStatus.addClass('token-status');
                tokenStatus.text('Unauthorized:');
                tokenDiv.append(tokenStatus);

                var tokenKey = $(document.createElement('span'));
                tokenKey.addClass('token-key');
                tokenDiv.append(tokenKey);

                var btn = $(document.createElement('button'));
                btn.attr('type', 'button');
                btn.attr('class', 'btn btn-link');
                btn.attr('id', 'request-token-' + methodName);
                btn.attr('onclick', 'toggleAuthDialog(true, this)');
                $(btn).text('Request token');
                tokenDiv.append(btn);

                if (window.swaggerUi.options.onOAuthClientSelectorGenerated) {
                    cbSelectorGenerated = window.swaggerUi.options.onOAuthClientSelectorGenerated;
                }
            } else if (currentAuthenticationType === invokePolicyDevice.type) {
                credentials = $(document.createElement('div'));
                credentials.addClass("auth-credentials-div");
                credentials.append('<div class="invoke-policy-div markdown-reset">' + marked(invokePolicyDesc) + '</div>');
            }

            parentNode.find(".auth-credentials-div").remove();
            parentNode.parent().find(":button").remove();

            if (credentials) {
                parentNode.append(credentials);
                if (cbSelectorGenerated) {
                    cbSelectorGenerated(selectField, methodName, this);
                }
            }

            return credentials;
        };

        OperationView.prototype.changeSelectedAuthentication = function (e) {
            var currentAuthenticationType = e.currentTarget.value;
            var parentNode = e.currentTarget.parentNode;
            var method = e.currentTarget.id.substring("authselect_".length);
            this.createCredentialsHelper(currentAuthenticationType, $(parentNode), method);
        };

        OperationView.prototype.mouseEnter = function (e) {
            var elem, hgh, pos, scMaxX, scMaxY, scX, scY, wd, x, y;
            elem = $(e.currentTarget.parentNode).find('#api_information_panel');
            x = event.pageX;
            y = event.pageY;
            scX = $(window).scrollLeft();
            scY = $(window).scrollTop();
            scMaxX = scX + $(window).width();
            scMaxY = scY + $(window).height();
            wd = elem.width();
            hgh = elem.height();
            if (x + wd > scMaxX) {
                x = scMaxX - wd;
            }
            if (x < scX) {
                x = scX;
            }
            if (y + hgh > scMaxY) {
                y = scMaxY - hgh;
            }
            if (y < scY) {
                y = scY;
            }
            pos = {};
            pos.top = y;
            pos.left = x;
            elem.css(pos);
            return $(e.currentTarget.parentNode).find('#api_information_panel').show();
        };

        OperationView.prototype.mouseExit = function (e) {
            return $(e.currentTarget.parentNode).find('#api_information_panel').hide();
        };

        OperationView.prototype.render = function () {
            var contentTypeModel, isMethodSubmissionSupported, k, o, param, responseContentTypeView, responseSignatureView, signatureModel, statusCode, type, v, _i, _j, _k, _l, _len, _len1, _len2, _len3, _ref5, _ref6, _ref7, _ref8;
            isMethodSubmissionSupported = true;
            if (!isMethodSubmissionSupported) {
                this.model.isReadOnly = true;
            }
            this.model.oauth = null;
            if (this.model.authorizations) {
                _ref5 = this.model.authorizations;
                for (k in _ref5) {
                    v = _ref5[k];
                    if (k === "oauth2") {
                        if (this.model.oauth === null) {
                            this.model.oauth = {};
                        }
                        if (this.model.oauth.scopes === void 0) {
                            this.model.oauth.scopes = [];
                        }
                        for (_i = 0, _len = v.length; _i < _len; _i++) {
                            o = v[_i];
                            this.model.oauth.scopes.push(o);
                        }
                    }
                }
            }
            $(this.el).html(Handlebars.templates.operation(this.model));
            if (this.model.responseClassSignature && this.model.responseClassSignature !== 'string') {
                signatureModel = {
                    sampleJSON: this.model.responseSampleJSON,
                    isParam: false,
                    signature: this.model.responseClassSignature
                };
                responseSignatureView = new SignatureView({
                    model: signatureModel,
                    tagName: 'div'
                });
                $('.model-signature', $(this.el)).append(responseSignatureView.render().el);
            } else {
                $('.model-signature', $(this.el)).html(this.model.type);
            }
            contentTypeModel = {
                isParam: false
            };
            contentTypeModel.consumes = this.model.consumes;
            contentTypeModel.produces = this.model.produces;
            _ref6 = this.model.parameters;
            for (_j = 0, _len1 = _ref6.length; _j < _len1; _j++) {
                param = _ref6[_j];
                type = param.type || param.dataType;
                if (type.toLowerCase() === 'file') {
                    if (!contentTypeModel.consumes) {
                        log("set content type ");
                        contentTypeModel.consumes = 'multipart/form-data';
                    }
                }
            }
            responseContentTypeView = new ResponseContentTypeView({
                model: contentTypeModel
            });
            $('.response-content-type', $(this.el)).append(responseContentTypeView.render().el);
            _ref7 = this.model.parameters;
            for (_k = 0, _len2 = _ref7.length; _k < _len2; _k++) {
                param = _ref7[_k];
                this.addParameter(param, contentTypeModel.consumes);
            }
            _ref8 = this.model.responseMessages;
            for (_l = 0, _len3 = _ref8.length; _l < _len3; _l++) {
                statusCode = _ref8[_l];
                this.addStatusCode(statusCode);
            }
            return this;
        };

        OperationView.prototype.addParameter = function (param, consumes) {
            var paramView;
            param.consumes = consumes;
            paramView = new ParameterView({
                model: param,
                tagName: 'tr',
                readOnly: this.model.isReadOnly
            });
            return $('.operation-params', $(this.el)).append(paramView.render().el);
        };

        OperationView.prototype.addStatusCode = function (statusCode) {
            var statusCodeView;
            statusCodeView = new StatusCodeView({
                model: statusCode,
                tagName: 'tr'
            });
            return $('.operation-status', $(this.el)).append(statusCodeView.render().el);
        };


        OperationView.prototype.submitOperation = function (e) {
            var error_free, form, isFileUpload, map, o, opts, val, _i, _j, _k, _len, _len1, _len2, _ref5, _ref6, _ref7;
            if (e != null) {
                e.preventDefault();
            }
            form = $('.sandbox', $(this.el));
            error_free = true;
            form.find("input.required").each(function () {
                var _this = this;
                $(this).removeClass("error");
                if (jQuery.trim($(this).val()) === "") {
                    $(this).addClass("error");
                    $(this).wiggle({
                        callback: function () {
                            return $(_this).focus();
                        }
                    });
                    return error_free = false;
                }
            });
            if (error_free) {
                map = {};
                opts = {
                    parent: this
                };
                isFileUpload = false;
                _ref5 = form.find("input");
                for (_i = 0, _len = _ref5.length; _i < _len; _i++) {
                    o = _ref5[_i];
                    if ((o.value != null) && jQuery.trim(o.value).length > 0) {
                        map[o.name] = o.value;
                    }
                    if (o.type === "file") {
                        isFileUpload = true;
                    }
                }
                _ref6 = form.find("textarea");
                for (_j = 0, _len1 = _ref6.length; _j < _len1; _j++) {
                    o = _ref6[_j];
                    if ((o.value != null) && jQuery.trim(o.value).length > 0) {
                        map["body"] = o.value;
                    }
                }
                _ref7 = form.find("select");
                for (_k = 0, _len2 = _ref7.length; _k < _len2; _k++) {
                    o = _ref7[_k];
                    val = this.getSelectedValue(o);
                    if ((val != null) && jQuery.trim(val).length > 0) {
                        map[o.name] = val;
                    }
                }
                $("message-bar").addClass("hidden");
                opts.responseContentType = $("div select[name=responseContentType]", $(this.el)).val();
                opts.requestContentType = $("div select[name=parameterContentType]", $(this.el)).val();
                $(".response_throbber", $(this.el)).show();

                //Check if user is with real browser or not :)
                //If it's <= IE9 stop and display an error
                $(this.el).find('.browser-fail').remove();
                if ((document.all && !window.atob) || !('withCredentials' in new XMLHttpRequest())) {
                    $(this.el).find('.sandbox_header').after("<div class='browser-fail'><br /><div class='swagger-ui-wrap alert alert-warning'>Warning! This browser may not support CORS. Errors may occur. Check your browser settings.</div></div>");
                }

                var method = this.model.nickname;
                var selected = $("#authselect_" + method);
                var checkbox = $("#useSameCredentials");
                var errBoxId = "error_box_" + method;
                var errBox = $("#" + errBoxId);
                errBox.attr("style", "visibility: hidden");
                if (checkbox.is(":checked")) {
                    $.each(window.authorizations.authz, function (key) {
                        window.authorizations.remove(key);
                    });

                    var keyField = $("#globalApiKey");
                    var oauthField = $("#globalOauthClientId");


                    if (window.oauthImplObj) {
                        window.authorizations.add(oauthDevice.type, new OAuthSecurity(window.oauthImplObj.token_type, window.oauthImplObj.access_token));
                    }
                    if (oauthField && oauthField.val()) {
                        globalOAuthValidate = false;
                    }
                    if (oauthField && oauthField.val() && oauthField.val() !== '') {
                        globalOAuthValidate = true;
                    }

                    if (oauthField && oauthField.val() === '') {
                        globalOAuthValidate = false;
                    }

                    if (keyField && keyField.val() === '') {
                        globalAPIKeyValidate = false;
                    }

                    if (keyField && keyField.val() && keyField.val() !== '') {
                        window.authorizations.add(apiKeyDevice.type, new APIKeySecurityDeviceAuthorization(keyField.val()));
                        globalAPIKeyValidate = true;
                    }

                    var userField = $("#globalUser");
                    var user = '';
                    var userPassField = $("#globalPass");
                    var pass = '';
                    var addBasicAuth = false;
                    if (userField && userField.val()) {
                        user = userField.val();
                        addBasicAuth = true;
                    }

                    if (userPassField && userPassField.val()) {
                        pass = userPassField.val();
                    }

                    if (addBasicAuth) {
                        var name = basicDevice.type === undefined ? basicDevice.typeDisplayName : basicDevice.type;
                        window.authorizations.add(name, new PasswordAuthorization(basicDevice, user, pass));
                    }

                    //For custom authentication policy we only display the description of the custom policy. When custom authentication policy exists, we should allow the ‘Try out’ functionality
                    var invokePolicyExists = $("#globalInvokePolicyClientIdLabel").length;

                    var selectedAuth;
                    var errBoxAuth = $('#error_box_auth_' + method);
                    errBoxAuth.attr("style", "display: none");

                    // Get the selected global auth
                    if (addBasicAuth) {
                        selectedAuth = basicDevice.type;
                    } else if (globalAPIKeyValidate) {
                        selectedAuth = apiKeyDevice.type;
                    } else if (globalOAuthValidate) {
                        selectedAuth = oauthDevice.type;
                    } else if (invokePolicyExists) {
                        selectedAuth = invokePolicyDevice.type;
                    }

                    // This section is for method-override support
                    // If there is/are local auth(s) make some checks
                    // When there is global auth and method-override local auth:
                    // - there is no difference for API Key and HTTP Basic, they work
                    // - but for OAuth settings have to be equal to work, and now we check only for
                    // scopes (length and compare), the worst that can happen is the user to be able to send the request
                    // and receive a 401 status
                    if (this.model.authorizations.length > 0 || true) {
                        // If no global auth is selected continue as normal
                        if (selectedAuth) {
                            // Track if we have found a local auth
                            var foundLocalAuth = false;
                            // Try to found the selected global auth in the local ones
                            $.each(this.model.authorizations, function (key, value) {
                                // If we have a match enter the checking
                                if (value.type == selectedAuth) {
                                    // Now find the selected global auth
                                    // At this point we need this only for OAuth
                                    if (selectedAuth == oauthDevice.type) {
                                        $.each(window.swaggerUi.api.securityProfile.devices, function (index, row) {
                                            if (row.type == selectedAuth) {
                                                // For this auth we need to check the scopes length
                                                // and show a message if they are not equal
                                                if (value.scopes.length != row.scopes.length) {
                                                    errBoxAuth.attr("style", "display: block");
                                                    $(".response_throbber", $(this.el)).hide();
                                                    return;
                                                }
                                                // Also check if local scopes exists in the global ones
                                                $.each(value.scopes, function (ind, val) {
                                                    // If some doesn't exist stop and show message
                                                    if ($.inArray(val, row.scopes) === -1) {
                                                        errBoxAuth.attr("style", "display: block");
                                                        $(".response_throbber", $(this.el)).hide();
                                                        return;
                                                    }
                                                });
                                            }
                                        });
                                    }
                                    // TODO Will need this if we have to make checks not only for OAuth
                                    // $.each(window.swaggerUi.api.securityProfile.devices, function (index, row) {
                                    //     if (row.type == selectedAuth) {
                                    //         switch (row.type) {
                                    //             case apiKeyDevice.type :
                                    //                 // can continue
                                    //                 break;
                                    //             case basicDevice.type :
                                    //                 // can continue
                                    //                 break;
                                    //             case invokePolicyDevice.type :
                                    //                 // can continue
                                    //                 break;
                                    //             case oauthDevice.type :
                                    //                 // For this auth we need to check the scopes length
                                    //                 // and show a message if they are not equal
                                    //                 if (value.scopes.length != row.scopes.length) {
                                    //                     errBoxAuth.attr("style", "display: block");
                                    //                     $(".response_throbber", $(this.el)).hide();
                                    //                     return;
                                    //                 }
                                    //                 // Also check if local scopes exists in the global ones
                                    //                 $.each(value.scopes, function (ind, val) {
                                    //                     // If some doesn't exist stop and show message
                                    //                     if ($.inArray(val, row.scopes) === -1) {
                                    //                         errBoxAuth.attr("style", "display: block");
                                    //                         $(".response_throbber", $(this.el)).hide();
                                    //                         return;
                                    //                     }
                                    //                 });
                                    //                 break;
                                    //         }
                                    //     }
                                    // });
                                    // Set the flag to true - we have local - global match
                                    foundLocalAuth = true;
                                }
                            });

                            // If we don't have a match, the auth are different
                            // and we need local auth for this method
                            // stop it and display a message
                            if (!foundLocalAuth) {
                                errBoxAuth.attr("style", "display: block");
                                $(".response_throbber", $(this.el)).hide();
                                return;
                            }
                        }
                    }

                    /*
                     Check if some authentication is detected.
                     If there is an authentication ensure proper values are set and continue.
                     Other wise display msg box and stop.
                     EXCEPTION!!!
                     If Invoke Policy is detected always pass the request!
                     */
//kunii

                    if ($('#globalRedeToken option:selected').val() == ""){

                        if ((keyField || userField || oauthField) && !(globalAPIKeyValidate || globalOAuthValidate || addBasicAuth || invokePolicyExists)) {
                            errBox.attr("style", "visibility: visible");
                            $(".response_throbber", $(this.el)).hide();
                            return;
                        } //else if fields not present at all - just continue with no common authentication
                    }
                } else {
                    errBoxAuth = $('#error_box_auth_' + method);
                    errBoxAuth.attr("style", "display: none");
                    if (selected) {
                        // clean up the previous auth objects
                        $.each(window.authorizations.authz, function (key) {
                            window.authorizations.remove(key);
                        });
                        // If we have a auth dropdown (with options) then execute the checking
                        // Without this local auth wouldn't be displayed correctly
                        if (selected.length) {
                            var selectedAuthOpt = selected.find(":selected");

                            var selectedAnyEmpty = false;
                            if (selectedAuthOpt[0].value === apiKeyDevice.type || selectedAuthOpt[0].value === apiKeyDevice.typeDisplayName) {
                                var keyField = $("#auth_apikey_" + method);
                                if (keyField.val().trim() === '') {
                                    selectedAnyEmpty = true;
                                }
                                window.authorizations.add(apiKeyDevice.type, new APIKeySecurityDeviceAuthorization(keyField.val()));
                            } else if (selectedAuthOpt[0].value === basicDevice.type || selectedAuthOpt[0].value === basicDevice.typeDisplayName) {
                                var user = $("#auth_username_" + method).val();
                                var pass = $("#auth_password_" + method).val();
                                if (user.trim() === '' && pass.trim() === '') {
                                    selectedAnyEmpty = true;
                                }
                                var name = basicDevice.type === undefined ? basicDevice.typeDisplayName : basicDevice.type;
                                window.authorizations.add(name, new PasswordAuthorization(basicDevice, user, pass));
                            } else if (selectedAuthOpt[0].value === oauthDevice.type || selectedAuthOpt[0].value === oauthDevice.typeDisplayName) {
                                var clientId = $("#oauthid_" + method).val();
                                if (clientId.trim() === '') {
                                    selectedAnyEmpty = true;
                                }
                                if (window.oauthImplObj) {
                                    window.authorizations.add(oauthDevice.type, new OAuthSecurity(window.oauthImplObj.token_type, window.oauthImplObj.access_token));
                                }

                                /*
                                 If selected authentication type is no Invoke Policy but there is authentication type(s) display error
                                 */
                            } else if (selectedAuthOpt[0].value !== invokePolicyDevice.type) {
                                errBox.attr("style", "visibility: visible");
                                $(".response_throbber", $(this.el)).hide();
                                return;
                            }

                            if (selectedAuthOpt[0].value !== 'authselectopnoauth' && selectedAnyEmpty) {
                                errBox.attr("style", "visibility: visible");
                                $(".response_throbber", $(this.el)).hide();
                                return;
                            }
                        }
                    } else {
                        errBox.attr("style", "visibility: visible");
                        $(".response_throbber", $(this.el)).hide();
                        return;
                    }
                }

                if (isFileUpload) {
                    return this.handleFileUpload(map, form);
                } else {
                    return this.model["do"](map, opts, this.showCompleteStatus, this.showErrorStatus, this);
                }

            }
        };

        OperationView.prototype.success = function (response, parent) {
            return parent.showCompleteStatus(response);
        };

        OperationView.prototype.handleFileUpload = function (map, form) {
            var bodyParam, el, headerParams, o, obj, param, _i, _j, _k, _l, _len, _len1, _len2, _len3, _ref5, _ref6, _ref7, _ref8,
                _this = this;
            log("it's a file upload");
            _ref5 = form.serializeArray();
            for (_i = 0, _len = _ref5.length; _i < _len; _i++) {
                o = _ref5[_i];
                if ((o.value != null) && jQuery.trim(o.value).length > 0) {
                    map[o.name] = o.value;
                }
            }
            bodyParam = new FormData();
            _ref6 = this.model.parameters;
            for (_j = 0, _len1 = _ref6.length; _j < _len1; _j++) {
                param = _ref6[_j];
                if (param.paramType === 'form') {
                    if (map[param.name] !== void 0) {
                        bodyParam.append(param.name, map[param.name]);
                    }
                }
            }
            headerParams = {};
            _ref7 = this.model.parameters;
            for (_k = 0, _len2 = _ref7.length; _k < _len2; _k++) {
                param = _ref7[_k];
                if (param.paramType === 'header') {
                    headerParams[param.name] = map[param.name];
                }
            }

            _ref8 = form.find('input[type~="file"]');
            for (_l = 0, _len3 = _ref8.length; _l < _len3; _l++) {
                el = _ref8[_l];
                bodyParam.append($(el).attr('name'), el.files[0]);
            }

            this.args = jQuery.extend({}, map);
            var pathified = this.model.pathify(this.args);

            this.invocationUrl = this.model.supportHeaderParams() ? (headerParams = this.model.getHeaderParams(map), this.model.urlify(map, false)) : this.model.urlify(map, true);
            $(".request_url", $(this.el)).html("<pre>" + this.invocationUrl + "</pre>");
            var that = this;
            obj = {
                type: this.model.method,
                url: this.invocationUrl,
                headers: headerParams,
                data: bodyParam,
                dataType: 'json',
                contentType: false,
                processData: false,
                error: function (data, textStatus, error) {
                    if (textStatus == 'timeout'){
                        var idPathSplitted = that.model.pathify(that.params).split('/');
                        idPathSplitted.shift();
                        var selector = 'li#' + idPathSplitted[0] + '_' + that.model.nickname;
                        var html = '<div class ="clearfix"></div><div id="system-message-container" style ="margin-top: 10px;"><div id="system-message"><div class="alert alert-error"><a data-dismiss="alert" class="close">×</a><h4 class="alert-heading">Error</h4><div><p><strong>The request has timed out !</strong></p></div></div></div></div>'
                        jQuery(selector + ' .sandbox_header').append(html)
                    }
                    return _this.showErrorStatus(_this.wrap(data), _this);
                },
                success: function (data, status) {
                    return _this.showResponse(data, _this);
                },
                complete: function (data) {
                    return _this.showCompleteStatus(_this.wrap(data), _this);
                }
            };

            if (window.swaggerUi.options.tryItProxy) {
                // save real url to use it later
                window.APIPortal = {};
                window.APIPortal.tryItUrl = obj.url;
                // change destination to the API Portal proxy
                obj.url = window.swaggerUi.options.tryItProxy + '&path=' + encodeURIComponent(pathified);
            }

            if (window.authorizations && this.model.authorizations) {
                window.authorizations.apply(obj, this.model.authorizations);
            }

            jQuery.ajax(obj);
            return false;
        };

        OperationView.prototype.wrap = function (data) {
            var h, headerArray, headers, i, o, _i, _len;
            headers = {};
            headerArray = data.getAllResponseHeaders().split("\r");
            for (_i = 0, _len = headerArray.length; _i < _len; _i++) {
                i = headerArray[_i];
                h = i.split(':');
                if (h[0] !== void 0 && h[1] !== void 0) {
                    headers[h[0].trim()] = h[1].trim();
                }
            }
            o = {};
            o.content = {};
            o.content.data = data.responseText;
            o.headers = headers;
            o.request = {};
            o.request.url = this.invocationUrl;
            o.status = data.status;
            return o;
        };

        OperationView.prototype.getSelectedValue = function (select) {
            var opt, options, _i, _len, _ref5;
            if (!select.multiple) {
                return select.value;
            } else {
                options = [];
                _ref5 = select.options;
                for (_i = 0, _len = _ref5.length; _i < _len; _i++) {
                    opt = _ref5[_i];
                    if (opt.selected) {
                        options.push(opt.value);
                    }
                }
                if (options.length > 0) {
                    return options.join(",");
                } else {
                    return null;
                }
            }
        };

        OperationView.prototype.hideResponse = function (e) {
            if (e != null) {
                e.preventDefault();
            }
            $(".response", $(this.el)).slideUp();
            return $(".response_hider", $(this.el)).fadeOut();
        };

        OperationView.prototype.showResponse = function (response) {
            var prettyJson;
            prettyJson = JSON.stringify(response, null, "\t").replace(/\n/g, "<br>");
            return $(".response_body", $(this.el)).html(escape(prettyJson));
        };

        OperationView.prototype.showErrorStatus = function (data, parent) {
            return parent.showStatus(data);
        };

        OperationView.prototype.showCompleteStatus = function (data, parent) {
            return parent.showStatus(data);
        };

        OperationView.prototype.formatXml = function (xml) {
            var contexp, formatted, indent, lastType, lines, ln, pad, reg, transitions, wsexp, _fn, _i, _len;
            reg = /(>)(<)(\/*)/g;
            wsexp = /[ ]*(.*)[ ]+\n/g;
            contexp = /(<.+>)(.+\n)/g;
            xml = xml.replace(reg, '$1\n$2$3').replace(wsexp, '$1\n').replace(contexp, '$1\n$2');
            //pad = 0;
            formatted = '';
            lines = xml.split('\n');
            //indent = 0;
            lastType = 'other';
            /*transitions = {
             'single->single': 0,
             'single->closing': -1,
             'single->opening': 0,
             'single->other': 0,
             'closing->single': 0,
             'closing->closing': -1,
             'closing->opening': 0,
             'closing->other': 0,
             'opening->single': 1,
             'opening->closing': 0,
             'opening->opening': 1,
             'opening->other': 1,
             'other->single': 0,
             'other->closing': -1,
             'other->opening': 0,
             'other->other': 0
             };*/
            _fn = function (ln) {
                var fromTo, j, key, padding, type, types, value;
                types = {
                    single: Boolean(ln.match(/<.+\/>/)),
                    closing: Boolean(ln.match(/<\/.+>/)),
                    opening: Boolean(ln.match(/<[^!?].*>/))
                };
                type = ((function () {
                    var _results;
                    _results = [];
                    for (key in types) {
                        value = types[key];
                        if (value) {
                            _results.push(key);
                        }
                    }
                    return _results;
                })())[0];
                type = type === void 0 ? 'other' : type;
                fromTo = lastType + '->' + type;
                //lastType = type;
                //padding = '';
                //indent += transitions[fromTo];
                /*padding = ((function() {
                 var _j, _ref5, _results;
                 _results = [];
                 for (j = _j = 0, _ref5 = indent; 0 <= _ref5 ? _j < _ref5 : _j > _ref5; j = 0 <= _ref5 ? ++_j : --_j) {
                 _results.push('  ');
                 }
                 return _results;
                 })()).join('');*/
                if (fromTo === 'opening->closing') {
                    return formatted = formatted.substr(0, formatted.length - 1) + ln + '\n';
                } else {
                    return formatted += /*padding +*/ ln + '\n';
                }
            };
            for (_i = 0, _len = lines.length; _i < _len; _i++) {
                ln = lines[_i];
                _fn(ln);
            }
            return formatted;
        };

        OperationView.prototype.showStatus = function (response) {
            $('.browser-fail').remove();
            var code, content, contentType, e, headers, json, opts, pre, response_body, response_body_el, url;
            if (response.content === void 0) {
                content = response.data;
                url = response.url;
            } else {
                content = response.content.data;
                url = response.request.url;
            }

            // Before rendering check for redirect
            this.redirectFromResponse(response.status, response.headers);

            contentType = null;
            if (response.headers) {
                contentType = response.headers["Content-Type"] || response.headers["content-type"];
                if (contentType) {
                    contentType = contentType.split(";")[0].trim();
                }
            }

            if (!content) {
                code = $('<code />').text("no content");
                pre = $('<pre class="json" />').append(code);
            } else if (contentType === "application/json" || /\+json$/.test(contentType)) {
                json = null;
                try {
                    json = JSON.stringify(JSON.parse(content), null, "  ");
                } catch (_error) {
                    e = _error;
                    json = "can't parse JSON.  Raw result:\n\n" + content;
                }
                code = $('<code />').text(json);
                pre = $('<pre class="json" />').append(code);
            } else if (contentType === "application/xml" || /\+xml$/.test(contentType)) {
                code = $('<code />').text(this.formatXml(content));
                pre = $('<pre class="xml" />').append(code);
            } else if (contentType === "text/html") {
                code = $('<code />').html(_.escape(content));
                pre = $('<pre class="xml" />').append(code);
            } else if (/^image\//.test(contentType)) {
                pre = $('<img>').attr('src', url);
            } else {
                code = $('<code />').text(content);
                pre = $('<pre class="json" />').append(code);
            }
            response_body = pre;
            $(".request_url", $(this.el)).html("<pre></pre>");
            // we have to display the original uri to the resource
            var resUrl = window.APIPortal ? (window.APIPortal.tryItUrl ? window.APIPortal.tryItUrl : url) : url;
            $(".request_url pre", $(this.el)).text(resUrl);
            $(".response_code", $(this.el)).html("<pre>" + response.status + "</pre>");
            $(".response_body", $(this.el)).html(response_body);
            $(".response_headers", $(this.el)).html("<pre>" + _.escape(JSON.stringify(response.headers, null, "  ")).replace(/\n/g, "<br>") + "</pre>");
            $(".response", $(this.el)).slideDown();
            $(".response_hider", $(this.el)).show();
            $(".response_throbber", $(this.el)).hide();
            //return hljs.highlightBlock($('.response_body', $(this.el))[0]);
        };

        /**
         * This method is used for redirect.
         * If status is 308 reload the page and it will redirect the client to login page.
         * Basic usage: When user session in APIPortal is expired we have to redirect the user.
         * @param response
         */
        OperationView.prototype.redirectFromResponse = function (status, headers) {
            if (status == 308) {
                if (headers) {
                    var portalHeader = headers["X-Apiportal-Auto"] || headers["x-apiportal-auto"];
                    if (portalHeader == 'redirect') {
                        location.reload();
                    }
                }
            }
        };

        OperationView.prototype.toggleOperationContent = function () {
            var elem;
            elem = $('#' + Docs.escapeResourceName(this.model.parentId) + "_" + this.model.nickname + "_content");
            if (elem.is(':visible')) {
                return Docs.collapseOperation(elem);
            } else {
                return Docs.expandOperation(elem);
            }
        };

        return OperationView;

    })(Backbone.View);

    StatusCodeView = (function (_super) {
        __extends(StatusCodeView, _super);

        function StatusCodeView() {
            _ref5 = StatusCodeView.__super__.constructor.apply(this, arguments);
            return _ref5;
        }

        StatusCodeView.prototype.initialize = function () {
        };

        StatusCodeView.prototype.render = function () {
            var template;
            template = this.template();
            $(this.el).html(template(this.model));
            return this;
        };

        StatusCodeView.prototype.template = function () {
            return Handlebars.templates.status_code;
        };

        return StatusCodeView;

    })(Backbone.View);

    ParameterView = (function (_super) {
        __extends(ParameterView, _super);

        function ParameterView() {
            _ref6 = ParameterView.__super__.constructor.apply(this, arguments);
            return _ref6;
        }

        ParameterView.prototype.initialize = function () {
            return Handlebars.registerHelper('isArray', function (param, opts) {
                if (param.type.toLowerCase() === 'array' || param.allowMultiple) {
                    return opts.fn(this);
                } else {
                    return opts.inverse(this);
                }
            });
        };

        ParameterView.prototype.render = function () {
            var contentTypeModel, isParam, parameterContentTypeView, responseContentTypeView, signatureModel, signatureView, template, type;
            type = this.model.type || this.model.dataType;
            if (this.model.paramType === 'body') {
                this.model.isBody = true;
            }
            if (type.toLowerCase() === 'file') {
                this.model.isFile = true;
            }
            template = this.template();
            $(this.el).html(template(this.model));
            signatureModel = {
                sampleJSON: this.model.sampleJSON,
                isParam: true,
                signature: this.model.signature
            };
            if (this.model.sampleJSON) {
                signatureView = new SignatureView({
                    model: signatureModel,
                    tagName: 'div'
                });
                $('.model-signature', $(this.el)).append(signatureView.render().el);
            } else {
                $('.model-signature', $(this.el)).html(this.model.signature);
            }
            isParam = false;
            if (this.model.isBody) {
                isParam = true;
            }
            contentTypeModel = {
                isParam: isParam
            };
            contentTypeModel.consumes = this.model.consumes;
            if (isParam) {
                parameterContentTypeView = new ParameterContentTypeView({
                    model: contentTypeModel
                });
                $('.parameter-content-type', $(this.el)).append(parameterContentTypeView.render().el);
            } else {
                responseContentTypeView = new ResponseContentTypeView({
                    model: contentTypeModel
                });
                $('.response-content-type', $(this.el)).append(responseContentTypeView.render().el);
            }
            return this;
        };

        ParameterView.prototype.template = function () {
            if (this.model.isList) {
                return Handlebars.templates.param_list;
            } else {
                if (this.options.readOnly) {
                    if (this.model.required) {
                        return Handlebars.templates.param_readonly_required;
                    } else {
                        return Handlebars.templates.param_readonly;
                    }
                } else {
                    if (this.model.required) {
                        return Handlebars.templates.param_required;
                    } else {
                        return Handlebars.templates.param;
                    }
                }
            }
        };

        return ParameterView;

    })(Backbone.View);

    SignatureView = (function (_super) {
        __extends(SignatureView, _super);

        function SignatureView() {
            _ref7 = SignatureView.__super__.constructor.apply(this, arguments);
            return _ref7;
        }

        SignatureView.prototype.events = {
            'click a.description-link': 'switchToDescription',
            'click a.snippet-link': 'switchToSnippet',
            'mousedown .snippet': 'snippetToTextArea'
        };

        SignatureView.prototype.initialize = function () {
        };

        SignatureView.prototype.render = function () {
            var template;
            template = this.template();
            $(this.el).html(template(this.model));
            this.switchToDescription();
            this.isParam = this.model.isParam;
            if (this.isParam) {
                $('.notice', $(this.el)).text('Click to set as parameter value');
            }
            return this;
        };

        SignatureView.prototype.template = function () {
            return Handlebars.templates.signature;
        };

        SignatureView.prototype.switchToDescription = function (e) {
            if (e != null) {
                e.preventDefault();
            }
            $(".snippet", $(this.el)).hide();
            $(".description", $(this.el)).show();
            $('.description-link', $(this.el)).addClass('selected');
            return $('.snippet-link', $(this.el)).removeClass('selected');
        };

        SignatureView.prototype.switchToSnippet = function (e) {
            if (e != null) {
                e.preventDefault();
            }
            $(".description", $(this.el)).hide();
            $(".snippet", $(this.el)).show();
            $('.snippet-link', $(this.el)).addClass('selected');
            return $('.description-link', $(this.el)).removeClass('selected');
        };

        SignatureView.prototype.snippetToTextArea = function (e) {
            var textArea;
            if (this.isParam) {
                if (e != null) {
                    e.preventDefault();
                }
                textArea = $('textarea', $(this.el.parentNode.parentNode.parentNode));
                if ($.trim(textArea.val()) === '') {
                    return textArea.val(this.model.sampleJSON);
                }
            }
        };

        return SignatureView;

    })(Backbone.View);

    ContentTypeView = (function (_super) {
        __extends(ContentTypeView, _super);

        function ContentTypeView() {
            _ref8 = ContentTypeView.__super__.constructor.apply(this, arguments);
            return _ref8;
        }

        ContentTypeView.prototype.initialize = function () {
        };

        ContentTypeView.prototype.render = function () {
            var template;
            template = this.template();
            $(this.el).html(template(this.model));
            $('label[for=contentType]', $(this.el)).text('Response Content Type');
            return this;
        };

        ContentTypeView.prototype.template = function () {
            return Handlebars.templates.content_type;
        };

        return ContentTypeView;

    })(Backbone.View);

    ResponseContentTypeView = (function (_super) {
        __extends(ResponseContentTypeView, _super);

        function ResponseContentTypeView() {
            _ref9 = ResponseContentTypeView.__super__.constructor.apply(this, arguments);
            return _ref9;
        }

        ResponseContentTypeView.prototype.initialize = function () {
        };

        ResponseContentTypeView.prototype.render = function () {
            var template;
            template = this.template();
            $(this.el).html(template(this.model));
            $('label[for=responseContentType]', $(this.el)).text('Response Content Type');
            return this;
        };

        ResponseContentTypeView.prototype.template = function () {
            return Handlebars.templates.response_content_type;
        };

        return ResponseContentTypeView;

    })(Backbone.View);

    ParameterContentTypeView = (function (_super) {
        __extends(ParameterContentTypeView, _super);

        function ParameterContentTypeView() {
            _ref10 = ParameterContentTypeView.__super__.constructor.apply(this, arguments);
            return _ref10;
        }

        ParameterContentTypeView.prototype.initialize = function () {
        };

        ParameterContentTypeView.prototype.render = function () {
            var template;
            template = this.template();
            $(this.el).html(template(this.model));
            $('label[for=parameterContentType]', $(this.el)).text('Parameter content type:');
            return this;
        };

        ParameterContentTypeView.prototype.template = function () {
            return Handlebars.templates.parameter_content_type;
        };

        return ParameterContentTypeView;

    })(Backbone.View);

}).call(this);
