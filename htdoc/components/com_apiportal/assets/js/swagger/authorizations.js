/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function Device(name, type, typeDisplayName){
    this.name = name;
    this.type = type;
    this.typeDisplayName = typeDisplayName;
}

var ApiKeyDevice = function (name, type, typeDisplayName){
    this.header = "HEADER";
    this.query = "QUERY";
    Device.call(this, name, type, typeDisplayName);
};

ApiKeyDevice.prototype = Object.create( Device.prototype);
ApiKeyDevice.prototype.constructor = ApiKeyDevice;

// Define global variables to hold API Gateway device names for easy access.
// The names may change through versions
var apiKeyDevice = new ApiKeyDevice("API Key", "apiKey", "API Key only");
var oauthDevice = new Device("OAuth", "oauth", "OAuth 2.0");
var basicDevice = new Device("HTTP Basic", "basic", "HTTP Basic Authentication");
var invokePolicyDevice = new Device('Invoke Policy', 'authPolicy', 'Invoke Policy');

var APIKeyAndSecretSecurityDeviceAuthorization = function (key, secret) {
    this.key = key;
    this.secret = secret;

};

APIKeyAndSecretSecurityDeviceAuthorization.prototype.apply = function (obj, authorizations){
    if(authorizations && authorizations["API Key and Secret Device"]){
        var keyName = authorizations["API Key and Secret Device"].keyField;
        var secretName = authorizations["API Key and Secret Device"].secretField;
        obj.headers[proxyPrefix + keyName] = this.key;
        obj.headers[proxyPrefix + secretName] = this.secret;
    }  if(authorizations && authorizations["API Key and Secret"]){
        var keyName = authorizations["API Key and Secret"].keyField;
        var secretName = authorizations["API Key and Secret"].secretField;
        obj.headers[proxyPrefix + keyName] = this.key;
        obj.headers[proxyPrefix + secretName] = this.secret;
    }

    return true;
};

var APIKeySecurityDeviceAuthorization = function (key) {
    this.key = key;
};

APIKeySecurityDeviceAuthorization.prototype.apply = function (obj, authorizations){

    if(authorizations){
  
        var auth = authorizations[apiKeyDevice.type] !== undefined ?
            authorizations[apiKeyDevice.type] : authorizations[apiKeyDevice.typeDisplayName];

        if(auth){

            if(auth.takeFrom === apiKeyDevice.header){
                var keyName = auth.keyField;
                obj.headers[keyName] = this.key;
                //console.log("key name: " + keyName);
                return true;
            } else if (auth.takeFrom === apiKeyDevice.query){
                if (obj.url.indexOf('?') > 0){
                    obj.url = obj.url + "&" + auth.keyField + "=" + this.key;
                } else{                    
                    obj.url = obj.url + "?" + auth.keyField + "=" + this.key;
                    window.swaggerUi.options.apiKeyValue = this.key;
                }
            }
        }
    }
};

var OAuthSecurity = function (token_name, token){
    this.token_name = token_name;
    this.token = token;
    return true;
};

OAuthSecurity.prototype.apply = function (obj, auth){
    if(auth !== undefined && auth[oauthDevice.type] !== undefined){
        var oauth = auth[oauthDevice.type];
        if(oauth.accessTokenLocation.toUpperCase() === "HEADER"){
            var prefix = oauth.authorizationHeaderPrefix;
            obj.headers["Authorization"] = prefix + " " + this.token;
        } else if (oauth.accessTokenLocation.toUpperCase() === "QUERYSTRING"){
            var queryKeyName = oauth.accessTokenLocationQueryString;

            if (obj.url.indexOf('?') > 0){
                obj.url = obj.url + "&" + queryKeyName + "=" + this.token;
            } else{
                obj.url = obj.url + "?" + queryKeyName + "=" + this.token;
            }
        }
    }

    return true;
};
