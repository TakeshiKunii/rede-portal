var oauthClientCredentials = function (opts){
    var o = (opts||{});
    var errors = [];

    this.appName = (o.appName||errors.push("missing appName"));
    this.clientId = (o.clientId||errors.push("missing client id"));
    this.clientSecret = (o.clientSecret||errors.push("missing client secret"));
    this.scopes = (o.scopes|| []);
    this.loginEndPoint = (o.loginEndPoint || errors.push("missing login point"));
    this.ajaxRequestPoint = (o.ajaxRequestPoint || errors.push('missing ajax point'));
    //this.redirectUri = (o.redirectUri || errors.push("missing redirect uri"));
    this.callback = o.callback;
    this.authorized=false;
    this.csrfToken = (o.csrfToken || errors.push('missing token point'));

    if(window.swaggerUi.api.authSchemes
        && window.swaggerUi.api.authSchemes.oauth2
        && window.swaggerUi.api.authSchemes.oauth2.scopes) {
        scopes = window.swaggerUi.api.authSchemes.oauth2.scopes;
    }
    window.oauthImplObj = this;
};

oauthClientCredentials.prototype.requestToken = function (){
    window.enabledScopes = []; // all scopes

    var requestScopes = "";
    for(var i = 0; i < this.scopes.length; i++){
        requestScopes += this.scopes[i] + ' ';
    }

    var postData = {};
    postData[this.csrfToken] = 1;
    postData['grant_type'] = 'client_credentials';
    postData['client_id'] = this.clientId;
    postData['client_secret'] = this.clientSecret;
    postData['scope'] = requestScopes;
    postData['login_end_point'] = this.loginEndPoint;

    $.ajax({
        type: 'POST',
        url: this.ajaxRequestPoint,
        async: false,
        cache: false,
        timeout: 10000,
        data: postData
    }).done(function (e) {
        var qp = null;
        try {
            qp = JSON.parse(e);
        } catch (r) {
            qp = e;
        }
        if (qp == null || qp == '' || qp === 'undefined') {
            qp = {
                error: 'Error',
                error_description: 'No response from authorization server. Check settings or network connection.'
            }
        }
        window.oauthImplObj.onOAuthComplete(qp);
    }).fail(function (jqXHR, status, err) {
        var fail = {
            error: status,
            error_description: err
        };
        window.oauthImplObj.onOAuthComplete(fail);
    });

};

oauthClientCredentials.prototype.onOAuthComplete = function(qp){
    if(qp.token_type && qp.access_token){
        this.token_type = qp.token_type;
        this.access_token = qp.access_token;
        window.authorizations.add(oauthDevice.type, new OAuthSecurity(qp.token_type ,qp.access_token));
        //$(".api-ic").removeClass("ic-error");
        //$(".api-ic").addClass("ic-info");
        this.authorized=true;
        this.callback(null);
    } else {
        console.log(qp);
        this.callback(qp);
        //console.log(decodeURIComponent(qp.error_description).replace(new RegExp("\\+","g"),' '));
    }

};
