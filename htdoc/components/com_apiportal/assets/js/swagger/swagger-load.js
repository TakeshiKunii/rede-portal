/**
 * Make ajax call to ajaxrequset.php controller for swagger definition
 * @param _url
 */


function swaggerLoadAjax(_url, generateAPIKeySelectorOptions, generateOAuthClientSelectorOptions, enableInlineTryIt) {
// START AJAX FOR SWAGGER DEFINITION
// Box for errors
	var alertBox = $('#custom-message-container');
    var mainContainer = $('#main-container');
    var tabs = $('#tabs');
// Load Swagger definition with async request
    $.ajax({
        url: URIBase + 'index.php?option=com_apiportal&task=ajaxrequest.swaggerLoad&apiName=' + apiNameEncoded + '&' + CSRFToken,
        method: 'GET',
        //dataType: 'json',
        timeout: 61000,
        beforeSend: function (xhr) {
            // Add opacity to tabs
            $('#tabs').css('opacity', '0.5');
            // Display the loading gif
            $('#swagger-load').css('display', 'block');
        }
    }).done(function (response) {
        // Check for errorMsg and display it if exist
        // This response normally is set in the API Portal service for loading the definition
        // It will contains errors from curl execution
        if (response && response.errorMsg != null && response.errorMsg != '') {
            alertBox.find('p').html(response.errorMsg);
            alertBox.toggleClass('hidden');
        } else if (response && response.id && response.id != null && response.id != '') {
            var useSameCred = $('#useSameCredentials');

            // This is global variable used in swagger-ui.js around 666 line
            // Needed for SOAP APIs
            apiType = response.type;
            
            // prepare the swaggerUi object
            window.swaggerUi = new SwaggerUi({
                tryItProxy: tryItProxyHost,
                url: _url,
                dom_id: "swagger-ui-container",
                supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
                staticFeed: response,
                highlightSizeThreshold: 1024,
                docExpansion: "none",
                sorter: "alpha",
                onAPIKeySelectorGenerated: function (selectField, methodName, swaggerUi) {
                    generateAPIKeySelectorOptions(selectField, methodName, swaggerUi);
                },
                onOAuthClientSelectorGenerated: function (selectField, methodName, swaggerUi) {
                    generateOAuthClientSelectorOptions(selectField, methodName, swaggerUi);
                },
                onComplete: function (swaggerApi, swaggerUi) {
                    $('pre code').each(function (i, e) {
                        hljs.highlightBlock(e)
                    });

                    populateCommonAuth();

                    useSameCred.off('change');

                    // This check is necessary because when there is a pass through for global auth and
                    // some local auth with method-override exist it won't show the local auth

                    //Customização para que o api tester posso usar o token64 da read_exif_data

                    if ($('#globalRedeTokenLabel').length || $('#globalOauthClientId').length || $('#globalApiKey').length || $('#globalUser').length || $('#globalInvokePolicyClientIdLabel').length) {
                        useSameCred.on('change', function () {
                            if ($(this).is(":checked")) {
                                $(".authenticationArea").addClass("hidden");
                                $("#globalAuth").removeClass('hidden');
                                jQuery('select').filter('.chzn-done').chosen({
                                    "disable_search_threshold": 1,
                                    "allow_single_deselect": true,
                                    "placeholder_text_multiple": "Select some options",
                                    "placeholder_text_single": "Select an option",
                                    "no_results_text": "No results match"
                                });
                            } else {
                                $(".authenticationArea").removeClass("hidden");
                                $("#globalAuth").addClass('hidden');
                            }
                        });

                        useSameCred.change();
                    } else {
                        // show the local auth
                        $(".authenticationArea").removeClass("hidden");
                    }

                    //fim customização

                    // fix the loading icon location
                    $(".response_throbber").attr("src", URIBase + "components/com_apiportal/assets/img/throbber.gif");

                    if (window.swaggerUi && window.swaggerUi.api && window.swaggerUi.api.apis) {
                        var keys = Object.keys(window.swaggerUi.api.apis);
                        if (keys.length > 0) {
                            var resource = keys[0];
                            var id = window.swaggerUi.api.apis[resource].id;
                            Docs.toggleEndpointListForResource(id);
                        }
                    }
                },
                onFailure: function (data) {
                    log("Unable to Load SwaggerUI");
                }
            });

            // Fill all necessary string in the page - like version, sdk etc
            // Description. It could be a url or normal text description
            // If it's text description it's parsed from markdown parser
            var description = (response.documentationUrl != undefined && response.documentationUrl != '' && validateURL(response.documentationUrl)) ? "<a href='"
            + response.documentationUrl + "' target='_blank'>" + response.documentationUrl
            + "</a>" : (response.description ? marked(escapeHTML(response.description)) : '');
            $('#api-description').append(description);

            // Image
            // The image is retrieved from rest call
            // If no image use default one
            var apiImage = $('#api-image');
            if (response.image && response.id) {
                var imageUrl = URIBase + 'index.php?option=com_apiportal&view=image&format=raw&apiId=' + encodeURI(response.id);
                apiImage.attr('style', 'background-image: url(' + imageUrl + ');');
            } else {
                apiImage.attr('style', 'background-image: url(' + JRoot + 'components/com_apiportal/assets/img/no_image.png);');
            }

            // Version
            var version = $('#version');
            if (response.apiVersion) {
                version.html(response.apiVersion);
            }

            // BasePath
            // In the original code basePaths was search for first
            // and if not exist basePath was used
            // Kept the same behaviour
            var basePaths = '';
            if (response.basePaths && response.basePaths != null) {
                if ($.isArray(response.basePaths)) {
                    $.each(response.basePaths, function (key, value) {
                        basePaths += value + '\n';
                    });
                } else {
                    basePaths = response.basePaths;
                }
            } else if (response.basePath && response.basePath != null) {
                basePaths = response.basePath;
            }
            $('#basepath').html(basePaths);

            // CORS
            var apiCors = '(unknown)';
            if (response.cors) {
                if (response.cors == true) {
                    apiCors = "Enabled";
                } else {
                    apiCors = "Disabled";
                }
            }
            $('#cors').html(apiCors);

            // Deprecated
            // Yeah, I put it in the version's
            // Text + icon
            if (response.deprecated && response.deprecated == true) {
                if (response.retirementDate && response.retirementDate > 0) {
                    var dateObj = new Date(response.retirementDate);
                    version.html(version.text() +
                        ' <img style="margin-left: 2%; margin-top: -10px;" src="' + JRoot + 'components/com_apiportal/assets/img/warning.png" alt="" /> Deprecated on '
                        + moment(dateObj).format('MMMM D, YYYY'));
                } else {
                    version.html(version.text() +
                        '<img style="margin-left: 2%; margin-top: -10px;" src="' + JRoot + 'components/com_apiportal/assets/img/warning.png" alt="" /> Deprecated');
                }
            }

            // Tags
            var tags = $('#tags');
            var arrTags = [];
            if (response.tags) {
                // Get the values from the tags groups
                for (var key in response.tags) {
                    if (response.tags.hasOwnProperty(key)) {
                        var obj = response.tags[key];
                        // Add each tag values into an array
                        for (var prop in obj) {
                            if (obj.hasOwnProperty(prop)) {
                                // We don't want to repeat values
                                if (arrTags.indexOf(obj[prop].trim()) == -1) {
                                    // Push to array and trim
                                    arrTags.push(obj[prop].trim());
                                }
                            }
                        }
                    }
                }


                // Count the tag values we collect
                var tagCount = arrTags.length;
                // If we have items iterate
                if (tagCount) {
                    // Append each tag value in DOM
                    for (var i = 0; i < tagCount; i++) {
                        // in the last one we don't want comma
                        if (i == tagCount-1) {
                            tags.append(escapeHTML(arrTags[i]));
                        } else {
                            tags.append(escapeHTML(arrTags[i] + ', '));
                        }
                    }
                }
            }

            // Type and API Definition
            if (response.type) {
                // The type
                $('#api-type').html(response.type == 'wsdl' ? 'SOAP' : response.type.toUpperCase());

                // The definition
                // Support two types - wsdl and rest
                var definition = $('#api-definition');
                var defArr = [];
                // If WSDL download link for the definition is one
                if (response.type == 'wsdl') {
                    definition.html('<a class="api-download-button" href="' + URIBase + 'index.php?option=com_apiportal&view=download&format=raw&apiType=wsdl&apiName=' + encodeURIComponent(response.name) + '&apiID='
                        + encodeURI(response.id) + '">' + downloadWsdl + '</a>');
                } else if (response.availableApiDefinitions && !jQuery.isEmptyObject(response.availableApiDefinitions)) {
                    // And download link for rest is another

                    // Convert to array so it could be sorted
                    for (var apiDef in response.availableApiDefinitions) {
                        if (response.availableApiDefinitions.hasOwnProperty(apiDef)) {
                            defArr[apiDef] = response.availableApiDefinitions[apiDef];
                        }
                    }

                    // Create array from keys
                    function keys(obj)
                    {
                        var keys = [];

                        for(var key in obj)
                        {
                            if(obj.hasOwnProperty(key))
                            {
                                keys.push(key);
                            }
                        }

                        return keys;
                    }

                    // Sort array with keys only and reverse;
                    var apiDefKeysSorted = keys(defArr).sort().reverse();

                    // display the last version of swagger definition
                    for (var def in apiDefKeysSorted) {
                        if (apiDefKeysSorted.hasOwnProperty(def) && defArr[apiDefKeysSorted[def]]) {
                            definition.html('<a class="api-download-button" href="' + URIBase + 'index.php?option=com_apiportal&view=definition&format=raw&stateReturn=' + returnUri + '&path='
                                + encodeURIComponent(defArr[apiDefKeysSorted[def]]) + '">' + apiDefKeysSorted[def] + '</a> ');
                            break;
                        }
                    }
                }
            }

            // SDKs
            if (response.availableSDK && !jQuery.isEmptyObject(response.availableSDK) && clientSdkCheck==true ) {
                // These are the names for SDKs from the API Manager
                // the properties/keys. But we don't want them so define nice ones.
                var sdkType = {
                    NODEJS: 'Node.JS',
                    ANGULARJS: 'Angular.JS',
                    ANDROID: 'Android',
                    JAVA: 'Java',
                    IOS: 'iOS'
                };
                // There are SDKs - add the block for them
                $('#sdk-group').css('display', 'block');

                var sortable = [];
                // Convert to array to sort them
                for (var vehicle in response.availableSDK) {
                    if (response.availableSDK.hasOwnProperty(vehicle)) {
                        sortable.push([vehicle, response.availableSDK[vehicle]]);
                    }
                }
                sortable.sort();

                // Display each SDK
                sortable.forEach(function (value, key) {
                    var name;
                    if (sdkType[value[0]]) {
                        name = sdkType[value[0]];
                    } else {
                        name = value[0].toString().toLocaleLowerCase();
                        name = name.charAt(0).toUpperCase() + name.slice(1);
                    }
                    $('#api-sdks').append('<a class="api-download-button" href="' + URIBase + 'index.php?option=com_apiportal&view=definition&format=raw&successRequestFirst=true&stateReturn=' + returnUri + '&path='
                        + encodeURIComponent(value[1]) + '">' + name + '</a> ');
                });
            } else {
                // If no SDKs remove the block
                $('#sdk-group').css('display', 'none');
            }

            if (response.authorizations) {
                window.InitialSwaggerAuth = response.authorizations;
            }

            // After all it's done display the page content
            $('#tabs-content').css('display', 'block');
            $('#tabs').css('opacity', '1');

            // Remove overlay (transparent gradient)
            $('.overlay').css('display', 'none');

            // initiate the swaggerUi
            window.swaggerUi.load();
            window.swaggerUi.render();
            changeTriIti18n();
            if(!enableInlineTryIt) {
           	 var isTryitbox = document.getElementsByClassName('sandbox_header');
    				if (isTryitbox.length > 0) {
    					$(".fullwidth thead tr th:nth-child(2)").remove();
     					$(".fullwidth tbody tr td:nth-child(2)").remove();
    					$('.submit, .body-textarea, .parameter, #globalAuth, .checkbox').remove();
    				} 
           }

            /**
             * Go to Auths when link from error box is clicked
             */
            $('.sandbox').on('click', '#goToAuth', function(e) {
                e.preventDefault();

                // If global Auths are hidden scroll to local auths
                if ($('#globalAuth').hasClass('hidden')) {
                    $('html, body').stop().animate({
                        'scrollTop': $(this).parents('.content').offset().top
                    }, 700, 'swing');
                } else {
                    // Scroll to global auths
                    var target = $('.tab-content');
                    $('html, body').stop().animate({
                        'scrollTop': target.offset().top
                    }, 700, 'swing');
                }
            });

        } else {
            // If no response.id is detected this is not swagger definition
            // display what it's returned
            alertBox.attr('class', 'show');
            alertBox.find('p').html(tryItErrorMsg + JSON.stringify(response));
        }
    }).fail(function (jqXHR, textStatus) {
        alertBox.toggleClass('hidden');
        alertBox.find('p').html(textStatus);
    }).always(function () {
        // stop loading gif here
        $('#swagger-load').css('display', 'none');
    });
// END AJAX FOR SWAGGER DEFINITION
}
