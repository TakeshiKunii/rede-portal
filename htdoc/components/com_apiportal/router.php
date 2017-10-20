<?php

defined('_JEXEC') or die('Restricted access');
JLoader::register('ApiPortalValidator', dirname(__FILE__) . DS . 'helpers' . DS . 'validator.php');

function ApiPortalBuildRoute(&$query) {
    $segments = array();

    if (isset($query['view'])) {
        $segments[] = $query['view'];
        unset($query['view']);
    };

    if (isset($query['layout'])) {
        $segments[] = $query['layout'];
        unset($query['layout']);
    };

    if (isset($query['tab'])) {
        $segments[] = $query['tab'];
        unset($query['tab']);
    };

    if (isset($query['applicationId'])) {
        $segments[] = $query['applicationId'];
        unset($query['applicationId']);
    }

    if (isset($query['userId'])) {
        $segments[] = $query['userId'];
        unset($query['userId']);
    }

    if (isset($query['organizationId'])) {
        $segments[] = $query['organizationId'];
        unset($query['organizationId']);
    }

    if (isset($query['id'])) {
        $segments[] = $query['id'];
        unset($query['id']);
    };

    if (isset($query['apiId'])) {
        $segments[] = $query['apiId'];
        unset($query['apiId']);
    };

    if (isset($query['apiName'])) {
        $segments[] = $query['apiName'];
        unset($query['apiName']);
    };

    if (isset($query['usage'])) {
        $segments[] = $query['usage'];
        unset($query['usage']);
    };
    
    return $segments;
}

function ApiPortalParseRoute($segments) {
    $vars = array();
    $count = count($segments);
	// SSO path is configurable through the admin panel
	// So we need to take it from the DB
	$sso = getSSOPath();

    switch($segments[0]) {
        case 'apicatalog':
            $id = explode(':', $segments[$count-1]);
            $vars['id'] = (int) $id[0];
            $vars['view'] = 'apicatalog';
            break;

       case 'apitester':
            $vars['view'] = $segments[$count-4];
            $vars['tab'] = $segments[$count-3]; //tabs
            $vars['apiName'] =  $segments[$count-2]; //apiName
            $vars['usage'] =  $segments[$count-1]; //usage
            break;

       case 'monitoring':
            $vars['view'] = 'monitoring';
            $vars['usage'] =  $count>2?$segments[$count-1]:$count>1?$segments[$count-1]:null ; //tab (last element)
            $vars['tab'] =  $count>2?$segments[$count-2]:null; //usage (last element)
            break;

        case 'applications':
            $vars['view'] = 'applications';
            $vars['layout'] =  $count>2?$segments[$count-1]:$count>1?$segments[$count-1]:null ; //tab (last element)
            break;

        /**
         * Application URLS have one of the following four formats, always in the same order:
         *
         * ?view=xxx&layout=yyy [length: 2]
         * ?view=xxx&layout=yyy&applicationId=123 [length: 3]
         * ?view=xxx&layout=yyy&tab=zzz&applicationId=123 [length: 4]
         *
         * NOTE: Create will never have an applicationId, so this is unambigous
         * ?view=xxx&layout=create&organizationId=123 [length: 3]
         */
        case 'application':
            $vars['view'] = 'application';
            if ($count > 1) {
                $vars['layout'] = $segments[1];
            }
            if ($count > 2 && $count < 4) {
                if ($vars['layout'] == 'create') {
                    $vars['organizationId'] = $segments[2];
                } else {
                    $vars['applicationId'] = $segments[2];
                }
            }
            if ($count > 3) {
                $vars['tab'] = $segments[2];
                $vars['applicationId'] = $segments[3];
            }
            break;

        /**
         * Registration URLS only ever have view=registration set in the query string.
         */
        case 'registration':
            $vars['view'] = 'registration';
            break;

        /**
         * Reset URLS only ever have view=reset set in the query string.
         */
        case 'reset':
            $vars['view'] = 'reset';
            break;

        case 'help':
            $vars['view'] = 'help';
            if ($count > 1) {
                $vars['layout'] = $segments[1];
            }
            break;

        case 'users':
            $vars['view'] = 'users';
            $vars['layout'] =  $count>2?$segments[$count-1]:$count>1?$segments[$count-1]:null ; //tab (last element)
            break;

        /**
         * User URLs have one of the following four formats, always in the same order:
         *
         * ?view=xxx&layout=yyy [length: 2]
         * ?view=xxx&layout=yyy&userId=123 [length: 3]
         *
         * NOTE: Create will never have an applicationId, so this is unambigous
         * ?view=xxx&layout=create&organizationId=123 [length: 3]
         */
        case 'user':
            $vars['view'] = 'user';
            if ($count > 1) {
                if (ApiPortalValidator::isValidGuid($segments[1])) {
                    $vars['userId'] = $segments[1];
                } else {
                    $vars['layout'] = $segments[1];
                }
            }
            if ($count > 2) {
                if (key_exists('layout', $vars) && $vars['layout'] == 'create') {
                    $vars['organizationId'] = $segments[2];
                } else {
                    $vars['userId'] = $segments[2];
                }
            }
            break;
	    case $sso:
	    	if ($count > 1) {
			    $seg = implode('/', $segments);
			    if ($seg == 'sso/externallogin/post') {
				    $vars['task'] = 'ssologin.ssoExternalLoginPost';
			    }
		    } else {
			    $vars['task'] = 'ssologin.sso';
		    }
	    	break;
	    case 'api':
		    if ($count > 1) {
			    $seg = implode('/', $segments);
			    if ($seg == 'api/portal/v1.3/sso/externallogin/post') {
				    $vars['task'] = 'ssologin.ssoExternalLoginPost';
			    }

			    if ($seg == 'api/portal/v1.3/sso/externallogout/post') {
				    $vars['task'] = 'ssologin.ssoExternalLogoutPost';
			    }
		    }
		    break;
	    case 'getting-started':
		    $vars['view'] = 'started';
	    	break;
	    case 'documentation':
		    $vars['view'] = 'documentation';
		    break;
    }

    return $vars;
}

/**
 * A helper function for getting the SSO Path from the DB
 * Did not add it in the ApiPortalHelper class because on this stage
 * the helper is not loaded yet. And I did not want to load it here.
 * If we need this function in other places we will refactor it.
 * @return string
 * @since 7.5.3
 */
function getSSOPath()
{
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->select($db->quoteName(['property', 'value']));
	$query->from($db->quoteName('#__apiportal_configuration'));
	$query->where($db->quoteName('property') . ' = '. $db->quote('ssoPath'));
	$db->setQuery($query);

	$result = $db->loadObject();
	if ($result) {
		return $result->value;
	}

	return 'sso';
}

/**
 * Default routing class for Apiportal component routers
 *
 * @package     ApiPortal
 * @subpackage  Component
 * @since       3.3
 */
class ApiportalRouter implements JComponentRouterInterface
{
	/**
	 * Name of the component
	 *
	 * @var    string
	 * @since  3.3
	 */
	protected $component;

	/**
	 * Constructor
	 *
	 * @param   string  $component  Component name without the com_ prefix this router should react upon
	 *
	 * @since   3.3
	 */
	public function __construct()
	{
		$this->component = "ApiPortal";
	}

	/**
	 * Generic preprocess function for missing or legacy component router
	 *
	 * @param   array  $query  An associative array of URL arguments
	 *
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 *
	 * @since   3.3
	 */
	public function preprocess($query)
	{
		return $query;
	}

	/**
	 * Generic build function for missing or legacy component router
	 *
	 * @param   array  &$query  An array of URL arguments
	 *
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 *
	 * @since   3.3
	 */
	public function build(&$query)
	{
		$function = $this->component . 'BuildRoute';

		if (function_exists($function))
		{
			$segments = $function($query);
			return $segments;
		}

		return array();
	}

	/**
	 * Generic parse function for missing or legacy component router
	 *
	 * @param   array  &$segments  The segments of the URL to parse.
	 *
	 * @return  array  The URL attributes to be used by the application.
	 *
	 * @since   3.3
	 */
	public function parse(&$segments)
	{
		$function = $this->component . 'ParseRoute';

		if (function_exists($function))
		{
			return $function($segments);
		}

		return array();
	}
}
