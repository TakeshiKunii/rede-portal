/**
 * This class is an extension of SwaggerApi which adds support for Axway API Gateway
 * Server security profiles. The security profile is specific API Gateway property
 * which correspondents to the swagger authorizations.
 * 
 * @param {type} _url
 * @returns {SwaggerApiExtension}
 */
SwaggerApiExtension = function (_options){
    this.options = _options;
    this.methodMapping = {};
    SwaggerApi.call(this, this.options);
}

SwaggerApiExtension.prototype = Object.create( SwaggerApi.prototype);
SwaggerApiExtension.prototype.constructor = SwaggerApiExtension;

/**
 * Overrides the SwaggerApi build function and adds the original response to this 
 * object.
 * @returns {SwaggerApiExtension.prototype}
 */
SwaggerApiExtension.prototype.build = function (){
    var _this = this;
    this.progress('fetching resource list: ' + this.url);
    var obj = {
      useJQuery: this.useJQuery,
      url: this.url,
      method: "get",
      headers: {
        accept: "application/json"
      },
      on: {
        error: function(response) {
          if (_this.url.substring(0, 4) !== 'http') { 
            return _this.fail('Please specify the protocol for ' + _this.url);
          } else if (response.status === 0) {
            return _this.fail('Can\'t read from server.  It may not have the appropriate access-control-origin settings.');
          } else {
              var message = response.status + ' ';
              var responseObj = response.obj || JSON.parse(response.data);
              if(responseObj && responseObj.message){
                  message += responseObj.message;
              } else if(responseObj.errors){ 
                  if(responseObj.errors[0].message){
                    message += responseObj.errors[0].message;
                  }
              }else if(response.statusText) {
                  message += response.statusText;
              } else {
                  message += "Internal Server error";
              }
            return _this.fail(message);
          }
        },
        response: function(resp) {
            _this.handleResponseHelper(resp);
        }
      }
    };
    if(this.options.staticFeed){
        var feed = new Object();
        feed.obj = this.options.staticFeed;
        _this.handleResponseHelper(feed);
    } else {
        var e = (typeof window !== 'undefined' ? window : exports);
        e.authorizations.apply(obj);
        new SwaggerHttp().execute(obj);
    }
    return this;
}

SwaggerApiExtension.prototype.handleResponseHelper = function (swaggerFeed){
    var _this = this;
    var responseObj = swaggerFeed.obj || JSON.parse(swaggerFeed.data);
    _this.swaggerVersion = responseObj.swaggerVersion;
    if (_this.swaggerVersion === "1.2") {
        _this.responseObj = responseObj;
      return _this.buildFromSpec(responseObj);
    } else {
      _this.responseObj = responseObj;
      responseObj.authorizations = {};
      if(responseObj.securityProfile){
          if(responseObj.securityProfile.devices){
              var devices = responseObj.securityProfile.devices;
              for(var i = 0; i < devices.length; i++){
                  if(devices[i].type){
                      responseObj.authorizations[devices[i].type] = devices[i]; 
                  } else {
                      responseObj.authorizations[devices[i].typeDisplayName] = devices[i];
                  }
              }
          }
      }
      for (k = 0; k < responseObj.apis.length; k++) {
          resource = responseObj.apis[k];
          // fix the resource path 
          if(responseObj.resourcePath && resource.path.indexOf(responseObj.resourcePath) !== 0 ){
              resource.path = responseObj.resourcePath + resource.path;
          }
          for(i = 0; i < resource.operations.length; i++){
              var operation = resource.operations[i];
              var securityProfile = operation.securityProfile;
              if(securityProfile){
                  if(securityProfile.devices == null){
                      if(operation.authorizations == undefined){
                        operation.authorizations = {};
                      }
                  } else { 
                      if(operation.authorizations == undefined){
                         operation.authorizations = {};
                      }
                      for(j = 0; j < securityProfile.devices.length; j++){
                          if(securityProfile.devices[j].type){
                              operation.authorizations[securityProfile.devices[j].type] = securityProfile.devices[j];
                          } else {
                              operation.authorizations[securityProfile.devices[j].typeDisplayName] = securityProfile.devices[j];
                          }
                      }
                  }
              } else {
                  operation.authorizations = responseObj.authorizations;
              } 
              // map API servre method id to the method nickname
              // According to the swagger 1.2 specification 
              // the nickname is:
              // Required. A unique id for the operation that can be used by tools reading the output for further and easier manipulation.
              // referense https://github.com/wordnik/swagger-spec/blob/master/versions/1.2.md
              _this.methodMapping[operation.nickname] = operation.id;
          }
        }
        
      return _this.buildFrom1_1Spec(responseObj);
    }
}

SwaggerApiExtension.prototype.buildFrom1_1Spec = function(response) {
  log("This API is using a deprecated version of Swagger!  Please see http://github.com/wordnik/swagger-core/wiki for more info");
  if (response.apiVersion != null)
    this.apiVersion = response.apiVersion;
  this.apis = {};
  this.apisArray = [];
  this.produces = response.produces;
  this.securityProfile = response.securityProfile;
  if (response.info != null) {
    this.info = response.info;
  }
  var isApi = false;
  for (var i = 0; i < response.apis.length; i++) {
    var api = response.apis[i];
    if (api.operations) {
      for (var j = 0; j < api.operations.length; j++) {
        operation = api.operations[j];
        isApi = true;
      }
    }
  }
  if (response.basePath) {
    this.basePath = response.basePath;
  } else if (this.url.indexOf('?') > 0) {
    this.basePath = this.url.substring(0, this.url.lastIndexOf('?'));
  } else {
    this.basePath = this.url;
  }
  if (isApi) {
    var newName = response.resourcePath.replace(/\//g, '');
    this.resourcePath = response.resourcePath;
    var res = new SwaggerResource(response, this);
    this.apis[newName] = res;
    this.apisArray.push(res);
  } else {
    for (k = 0; k < response.apis.length; k++) {
      resource = response.apis[k];
      res = new SwaggerResource(resource, this);
      this.apis[res.name] = res;
      this.apisArray.push(res);
    }
  }
  
  //CDG change
  /*
  if (this.success) {
    this.success();
  }*/
  return this;
};

SwaggerApiExtension.prototype.selfReflect = function() {
  var resource, resource_name, _ref;
  if (this.apis == null) {
    return false;
  }
  _ref = this.apis;
  for (resource_name in _ref) {
    resource = _ref[resource_name];
    if (resource.ready == null) {
      return false;
    }
  }
  this.setConsolidatedModels();
  this.ready = true;
  
  // CDG change 
  /*
  if (this.success != null) {
    return this.success();
  }*/
};

SwaggerApiExtension.prototype.fail = function (message){
    $('#message-bar').removeClass("hidden");
    $('#message-bar').text(message);
}
