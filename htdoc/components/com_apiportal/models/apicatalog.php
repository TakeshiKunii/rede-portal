<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modellist');

class APIPortalModelApiCatalog extends JModelList
{
    protected $items;

    public function getItems()
    {
        //if (!isset($this->items)) {

        $path = (ApiPortalHelper::getVersionedBaseFolder()) . '/discovery/apis';
        $body = ApiPortalHelper::doGet($path, array(), true);

        $array = array();

        if ($body !== null && gettype($body) != "array") {
            $array = json_decode($body, true);
        } else {
            $array = $body;
        }
        $itemsResult = array();
        if (is_array($array)) {
            foreach ($array as $apiElement) {
                if (isset($apiElement["name"])) {
                    $path = (ApiPortalHelper::getVersionedBaseFolder()) . "/discovery/swagger/api" . '/' . rawurlencode($apiElement["name"]);
                    $details = ApiPortalHelper::doGet($path, array(), true);

                    if ($details !== null && gettype($details) != "array") {
                        $details = json_decode($details, true);
                    }

                    if ($details !== null && is_array($details)) {
                        foreach ($details as $key => $detail) {
                            $apiElement[$key] = $detail;
                        }
                    }
                }
                array_push($itemsResult, $apiElement);
            }
        } else {
            $itemsResult = $array;
        }
        $this->items = $itemsResult;
        //}
        return $this->items;
    }

    //================================ Show API tags PHP =================================
    //
    /*
     *  getApiTagsList() -  returns comma separated list of tags or '', similar to Test page.
     * 
     */
    public static function getApiTagsList($fldVal, $plusTagGroups)
    {
        //debug: return 'tag7, tag0';
        //$plustaggroups - if false exclude tag groups
        if (isset($fldVal) && $fldVal != null) {
            if (is_array($fldVal)) {
                //tags listing
                if ($fldVal !== null && is_array($fldVal)) {
                    $tagsArr = array();
                    //array_push($itemsresult, $apielement);
                    foreach ($fldVal as $key => $val) {
                        //tags listing, $key - tag group, val - tags array
                        if ($plusTagGroups) {
                            array_push($tagsArr, $key);//consider search by group name too!
                        } else {
                            //print tags, exclude group names
                        }
                        if ($val !== null && is_array($val)) {
                            foreach ($val as $valItem) {
                                array_push($tagsArr, $valItem);
                            }
                        }
                    }
                    //echo tags here
                    $tagsArr = array_unique($tagsArr);
                    sort($tagsArr);
                    $tmp1 = '';
                    $ctr = 0;
                    foreach ($tagsArr as $tagsArrItem) {
                        $tmp1 = $tmp1 . $tagsArrItem;
                        $ctr += 1;
                        if (count($tagsArr) > $ctr) {
                            $tmp1 = $tmp1 . ', ';
                        }
                    }
                    return $tmp1;
                }
            } else {
                return $fldVal;
            }
        } elseif (isset($fldVal) && $fldVal != null) {
            return $fldVal;
        } else {
            return '';
        }

        return '';
    }

    /*
     *  getItemFromList() -  returns array of group and tags in it, facilitates presentetaion in UI.
     * 
     */
    public static function getItemFromList($allTagItem)
    {
        if (isset($allTagItem) && $allTagItem != null) {
            $tmpArr = explode(":", $allTagItem);
            return $tmpArr;
        }
        return '';
    }
    //================================ //Show API tags PHP =================================  
}