<?php
    function addURLParameters ($url, $newparams, $entrypoint=null) {
        $url_data = parse_url($url);
        $query = parse_url($url, PHP_URL_QUERY);
        $params=array();
        parse_str($query, $params);

        // Add params from array
        foreach ($newparams as $k => $v) {
            $params[$k] = $v;
        }

        // Add entrypoint
        if ($entrypoint!=null && $entrypoint!="") {
            $params['ep'] = $entrypoint;
        }
        $url_data["query"] = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        return $url_data["path"]."?".$url_data["query"];
    }

    function stripURLParameter ($url, $parameters) {
        foreach ($parameters as $k => $v) {
            $url = preg_replace('~(\?|&)'.$v.'=[^&]*~','$1',$url);
        }
        return $url;
    }

?>

<script language="javascript">
    function updateQueryStringParameter(uri, key, value) {
        if (uri===null) {
            uri = window.location.href;
        }
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
          return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
          return uri + separator + key + "=" + value;
        }
    }

    function removeQueryStringParameter(url, parameter) {
        var urlparts= url.split('?');

        if (urlparts.length>=2) {
            var urlBase=urlparts.shift();
            var queryString=urlparts.join("?");

            var prefix = encodeURIComponent(parameter)+'=';
            var pars = queryString.split(/[&;]/g);
            for (var i= pars.length; i-->0;) {
              if (pars[i].lastIndexOf(prefix, 0)!==-1) {
                  pars.splice(i, 1);
              }
            }
            url = urlBase+'?'+pars.join('&');
        }
        return url;
    }
</script>
