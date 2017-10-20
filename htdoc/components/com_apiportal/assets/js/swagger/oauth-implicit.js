/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var oauthImpl = function (opts){
    var o = (opts||{});
    var errors = [];
    
    this.appName = (o.appName||errors.push("missing appName"));
    this.clientId = (o.clientId||errors.push("missing client id"));
    this.scopes = (o.scopes|| []);
    this.loginEndPoint = (o.loginEndPoint || errors.push("missing login point"));
    this.redirectUri = (o.redirectUri || errors.push("missing redirect uri"));
    this.callback = o.callback;
    this.authorized=false;
    
    if(window.swaggerUi.api.authSchemes 
        && window.swaggerUi.api.authSchemes.oauth2
        && window.swaggerUi.api.authSchemes.oauth2.scopes) {
        scopes = window.swaggerUi.api.authSchemes.oauth2.scopes;
    }
    window.oauthImplObj = this;
}

oauthImpl.prototype.requestToken = function (){
    window.enabledScopes = []; // all scopes
    
    url = this.loginEndPoint;
    url += '?client_id=' + this.clientId;
    url += '&response_type=token';
    
    var requestScopes = "";
    for(var i = 0; i < this.scopes.length; i++){
        requestScopes += this.scopes[i] + ' ';
    }
    
    if(requestScopes != ""){
        requestScopes = requestScopes.slice(0, -1);
        url += '&scope=' + encodeURIComponent(requestScopes);
    }
    
    window.open(url);
}

oauthImpl.prototype.onOAuthComplete = function(qp){
    if(qp.token_type && qp.access_token){
        this.token_type = qp.token_type;
        this.access_token = qp.access_token;
        window.authorizations.add(oauthDevice.type, new OAuthSecurity(qp.token_type ,qp.access_token));
        $(".api-ic").removeClass("ic-error");
        $(".api-ic").addClass("ic-info");
        this.authorized=true;
    } else if (qp.error){
        console.log(qp.error);
        console.log(decodeURIComponent(qp.error_description).replace(new RegExp("\\+","g"),' '));
    }
    this.callback(decodeURIComponent(qp.error_description).replace(new RegExp("\\+","g"),' '));
}
