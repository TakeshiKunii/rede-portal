<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class APIPortalViewApiCatalog extends JViewLegacy
{
    //======================= Grained API list for static catalog =============================
    // Use this list to specify a comma separated API names that will be shown at first
    // as fine grained api list. Then there will be a button saying "Show all apis",
    // that will allow you to show all the APIS.
    // By default this list is empty which would mean to not show a fine grained API list
    // but immediately show the complete list.
    private static $API_FILTER_LIST = array(
        // "Library API",
        // "pet",
    );

    protected $items;
    protected $isFiltered;
    protected $allTags;

    public static function getGrainedApiList($apiArray, $filterList)
    {
        $listFilterInfo = new ApiListFilterInformation();

        if (!isset($apiArray)) { //|| gettype($apiarray) !== "array"
            $listFilterInfo->apiArray = array();
            $listFilterInfo->isFiltered = false;
        } else {
            $listFilterInfo->apiArray = $apiArray;
            $listFilterInfo->isFiltered = false;
        }


        $arrayGrained = array();
        if ($filterList === null || count($filterList) <= 0) {
            // No filter has been specified, so show all apis by default.
            return $listFilterInfo;
        } elseif ($apiArray !== null) {
            try {
                foreach ($apiArray as $apiItem) {
                    if (isset($apiItem["name"]) && in_array($apiItem["name"], $filterList)) {
                        array_push($arrayGrained, $apiItem);
                    } elseif (isset($apiItem["id"]) && in_array($apiItem["id"], $filterList)) {
                        array_push($arrayGrained, $apiItem);
                    }
                }
            } catch (Exception $e) {
                error_log("Caught Exception: " . $e->getMessage());
                return "Caught Exception: " . $e->getMessage(); //null;
            }
            $listFilterInfo->isFiltered = true;
        }
        $listFilterInfo->apiArray = $arrayGrained;
        return $listFilterInfo;
    }

    public static function sortApiList(array &$apiList, $sort)
    {
        $sortKey = 'name';
        $sortOrder = SORT_ASC;
        if ($sort == null) {
            $sort = "na";
        }
        switch ($sort) {
            default:
            case 'na':
                $sortKey = 'name';
                $sortOrder = SORT_ASC;
                break;
            case 'nd':
                $sortKey = 'name';
                $sortOrder = SORT_DESC;
                break;
        }

        $keys = array();
        // Obtain a list of columns
        foreach ($apiList as $key => $row) {
            $keys[$key] = $row[$sortKey];
        }
        array_multisort($keys, $sortOrder, $apiList);

    }

	/**
	 * This function is used to collect the the API tags and tag groups 
	 * and group them into one final array 
	 * @param array $apiItems
	 * @return array 
	 */
    public static function getAllTags($apiItems)
    {
       	$fullTagArray = array ();
		$tempAllTagArray = array ();
		
		//return full unique list for all APIs used in search catalog list by API
		if (isset ( $apiItems )) {
			foreach ( $apiItems as $itemApi ) {
				// produce array key->single_tag_alue
				if (isset ( $itemApi ['tags'] )) {
					foreach ( $itemApi ['tags'] as $key => $value ) {
						if (isset ( $tempAllTagArray [$key] )) {
							$tempAllTagArray [$key] = array_merge ( $tempAllTagArray [$key], $value );
						} else {
							$tempAllTagArray [$key] = $value;
						}
					}
				}
			}
		}
		
		foreach ( $tempAllTagArray as $groupName => $groupTags ) {
			$groupTagsString = "";
			if ($groupTags) {
				$groupTags = array_map('trim', $groupTags); 
				sort ( $groupTags );
				$groupTagsString = implode ( ":", array_unique ( $groupTags ) );
			}
			$fullTagArray [] = $groupName . ":" . $groupTagsString;
		}
		
		sort ( $fullTagArray );
		return array_values ( array_unique ( $fullTagArray ) );
    }

    public function display($tpl = null)
    {
        // Make sure the session is valid before displaying view
        ApiPortalHelper::checkSession();

        $filter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_SPECIAL_CHARS);
        $filter = ApiPortalHelper::cleanHtml($filter, false, true);
        $sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS);
        $sort = ApiPortalHelper::cleanHtml($sort, false, true);
        if ($filter !== null && $filter == "showall") {
            $filter = null;
        } else {
            $filter = self::$API_FILTER_LIST;
        }

        $apiArray = $this->get('Items');
        
        $apiArray = $this->showHideAPIs($apiArray);

        $listFilterInfo = self::getGrainedApiList($apiArray, $filter);
        $this->items = $listFilterInfo->apiArray;
        $this->isFiltered = $listFilterInfo->isFiltered;

        $this->allTags = self::getAllTags($this->items);

        self::sortApiList($this->items, $sort);

        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors));
        }

        parent::display($tpl);
    }
    
    public function showHideAPIs($apiArray)
    {
    	
    	$hideTagsArr = array();
    	$showTagsArr = array();
    	
    	$menuParam = JFactory::getApplication()->getParams();  	
    	$hideTags = $menuParam->get(hideTags);
    	if($hideTags){
    		$hideTagsArr = array_map('trim', explode(',', $hideTags));
    		$hideTagsArr = array_map('strtolower', $hideTagsArr);
    	}
    	$showTags = $menuParam->get(showTags);
    	if($showTags){
    		$showTagsArr = array_map('trim', explode(',', $showTags));
    		$showTagsArr = array_map('strtolower', $showTagsArr);
    	}
    	if(empty($hideTagsArr) && empty($showTagsArr)) {
    		return  $apiArray;
    	}
   	
    	
    	foreach ($apiArray as $key=>$api){
    		$apiTags =  $api['tags'];
    		//Collect GroupNames and Tags into one array to check for show / hide API based on Tags
    		$apiAllTags = $this->getAllTagsOfApi($apiTags);
    		$apiAllTags = array_map('strtolower', $apiAllTags);
    		
    		if(!empty($showTagsArr)){
    			$showTagsFound = (count(array_intersect($showTagsArr, $apiAllTags))) ? true : false;
    				
    			if(!$showTagsFound){
    				unset($apiArray[$key]);
    				continue;
    			}
    		}
    		
    		//look for one array element in other array
    		$hideTagsFound = (count(array_intersect($hideTagsArr, $apiAllTags))) ? true : false;
    		
    		//check for hide tags
    		if($hideTagsFound){
    			//look at Group Level tags
    			foreach ($apiTags as $groupKey => $groupValues){
    				if(in_array(strtolower($groupKey), $hideTagsArr)){
    					unset($apiTags[$groupKey]);
    					continue;
    				}
    				foreach ($groupValues as $groupValueKey => $groupValue){
    					if(in_array(strtolower($groupValue), $hideTagsArr)){
    						unset($apiTags[$groupKey][$groupValueKey]);
    					}
    				}
    				
    				if(count($apiTags[$groupKey]) <= 0){
    					unset($apiTags[$groupKey]);
    				}
    			}
    		}
    	
    		$apiArray[$key]['tags'] = $apiTags;
    	    	
    	}
    	
    	return $apiArray;
    }
    public function getAllTagsOfApi($apiTags)
    {
    	$apiAllTags = array();
    	if(!empty($apiTags)){
    		$groupsTags = array_keys($apiTags);
    		if(!empty($groupsTags)){
    			$apiAllTags = array_merge($apiAllTags, $groupsTags);
    			foreach ($apiTags as $key=>$tagsArr){
    				$tags = array();
    				$tags = array_values($tagsArr);
    				$apiAllTags = array_merge($apiAllTags, $tags);
    			}
    			
    		}
    		// trim the array values
    		$apiAllTags = array_map('trim', $apiAllTags);
    	}
    	return $apiAllTags;
    }
    
}

class ApiListFilterInformation
{
    public $apiArray;
    public $isFiltered;
}
