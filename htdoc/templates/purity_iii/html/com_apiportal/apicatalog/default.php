<?php
defined('_JEXEC') or die('Restricted access');

// Make sure the session is valid before displaying view
ApiPortalHelper::checkSession();

// Manage hidden tab for public API user
$publicApiAction = ApiPortalHelper::hasHiddenTabforPublicUser();

$itemId = JRequest::getString('Itemid', '');
$itemId = ApiPortalHelper::cleanHtml($itemId, false, true);
$document = JFactory::getDocument();

// Check Menu Id to get Menu Params Mastheadtitle, MastheadSlogan
$result = ApiPortalHelper::getMenuParamsValue($itemId);
if(!empty($result['masthead-title'])){
	$title =  $result['masthead-title'];
}else{
	$title =  JText::_('COM_APIPORTAL_APICATALOG_TITLE');
}
if(!empty($result['masthead-slogan'])){
	$slogan =  $result['masthead-slogan'];
}else{
	$slogan =  JText::_('COM_APIPORTAL_APICATALOG_GENERAL_SECTION');
}


// For ellipsis truncation of 'description' field
$document->addScript('components/com_apiportal/assets/js/jquery.dotdotdot.js');
$document->addScript('components/com_apiportal/assets/js/moment.min.js');
$document->addScript('components/com_apiportal/assets/js/marked/lib/marked.js');
$document->addScript('components/com_apiportal/assets/js/jquery.cookie.js');


$menuParam = JFactory::getApplication()->getParams();
//$menuParam['apiCatlogDefaultView']->'list';

$apiCatelogDefaultView = $menuParam->get(apiCatlogDefaultView);
if($apiCatelogDefaultView == 'tiles'){
	$apiCatelogDefaultView = 'list';
}

$hideTags = $menuParam->get(hideTags);

//Set AppUseid in session to avaoid the 403 error in phblic mode when regular user session expires
JFactory::getSession()->set('appUserId',JFactory::getSession()->get('user')->get('id'));

$enableInlineTryIt = ApiPortalHelper::getEnableInlineTryIt($itemId);

?>

<div id="tabs">
    <div id="tab-contents">
        <div class="head" >
          <h1 class="auto"><?php echo $title; ?></h1>
          <p class="auto"><em><?php echo $slogan; ?></em></p>
        </div>
        <?php
        $tagLabel = JText::_('COM_APIPORTAL_APICATALOG_TAGS');
        $selectedTagRaw = trim(filter_input(INPUT_GET, 'tag'));
        $selectedTag = $this->escape($selectedTagRaw);
        $selectedTagUrlEncoded = rawurlencode($selectedTag);
        $notSelected = "";
        ?>
            <!-- Single button -->
            <?php
            //start:do not hide filtering
            $sortOrder = 'na';
            $apiTagList = '';
            $filteredItemCount = 0;
            if ($this !== null && $this->items !== null && count($this->items) > 0){
                foreach ($this->items as $item) {
                    $apiTagList = '';
                    if(isset($item['tags'])) {
                        $apiTagList = APIPortalModelApiCatalog::getApiTagsList($item['tags'],true);//included group name!
                    }
                    //Start tag filter check. Show selected APIs per tag or Any tag API
                    if ($selectedTag == null || $selectedTag == '' || strpos($apiTagList, $selectedTag) !== false) {
                        $filteredItemCount += 1;
                    }
                }
            }
            ?>
            <script language=JavaScript>
                function onFilterClick(){
                    document.getElementById("loading").innerHTML = '<?= JText::_('COM_APIPORTAL_APICATALOG_LOADING') ?>';
                }
            </script>
            <div class="btn-toolbar">
              <div class="auto">
                <div class="action-group">
                    <div id="loading" class="status message loading">&nbsp;</div>
                    <div role="search">
                        <label><span>Pesquisar:</span>
                          <input id="findApis" type="text" size="50" placeholder="<?= JText::_('COM_APIPORTAL_APICATALOG_PLACEHOLDER') ?>">
                        </label>
                    </div>
                    <?php
                    //Show/Hide sorting select box
                    if ($this !== null && $this->items !== null && count($this->items) > 1 && $filteredItemCount > 1){
                        ?>
                        <div class="dropdown sort-dropdown">
                            <button type="button" class="btn btn-default dropdown-toggle icon chevron-down" data-toggle="dropdown">
 								<?php echo filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS) == 'nd'? JText::_('COM_APIPORTAL_APICATALOG_NAME_DESC') :  JText::_('COM_APIPORTAL_APICATALOG_NAME_ASC') ;?>
                                <?php
                                if (filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS) == 'nd' ) {
                                    $sortOrder = 'nd';
                                }
                                ?>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                <li><a onclick="javascript:onFilterClick()" href="index.php?option=com_apiportal&view=apicatalog<?php echo $this->isFiltered ? "" : "&filter=showall";?>&sort=na&tag=<?php echo $selectedTagUrlEncoded ?>&Itemid=<?php echo $itemId; ?>" class="btn btn-primary"><?= JText::_('COM_APIPORTAL_APICATALOG_NAME_ASC') ?></a></li>
                                <li><a onclick="javascript:onFilterClick()" href="index.php?option=com_apiportal&view=apicatalog<?php echo $this->isFiltered ? "" : "&filter=showall";?>&sort=nd&tag=<?php echo $selectedTagUrlEncoded ?>&Itemid=<?php echo $itemId; ?>" class="btn btn-primary"><?= JText::_('COM_APIPORTAL_APICATALOG_NAME_DESC') ?></a></li>
                            </ul>
                        </div>
                    <?php }; ?>
                    <button id="changeApisView" class="btn btn-default icon tiles" aria-pressed="<?php echo $apiCatelogDefaultView =='list' ? 'false':'true';?>"><?php echo JText::_('COM_APIPORTAL_APICATALOG_CHANGE_VIEW') ?></button>
                    <div class="dropdown tag-dropdown">
                        <?php if ($this !== null && $this->allTags !== null && count($this->allTags) > 0 ) { ?>
                            <button type="button" class="btn btn-default dropdown-toggle icon chevron-down" data-toggle="dropdown">
                                <?php echo ($selectedTag == null || $selectedTag  == "")? $tagLabel : $selectedTag ;?>
                            </button>
                            <ul class="dropdown-menu tags" role="menu">
                                <li><a onclick="javascript:onFilterClick()" href="index.php?option=com_apiportal&view=apicatalog<?php echo $this->isFiltered ? "": "&filter=showall";?>&sort=<?php echo $sortOrder;?>&tag=&Itemid=<?php echo $itemId; ?>" class="clear-tags"><?= JText::_('COM_APIPORTAL_APICATALOG_CLEAR_TAGS') ?></a></li>
                                <li class="col-sm-12">
                                    <div class="col-sm-4"><?= JText::_('COM_APIPORTAL_APICATALOG_TAG_GROUPS') ?></div>
                                    <div class="col-sm-8"><?= JText::_('COM_APIPORTAL_APICATALOG_TAGS') ?></div>
                                </li>
                                <?php foreach ($this->allTags as $allTagItem) { ?>
                                    <li class="col-sm-12">
                                        <?php
                                        $tagArr = APIPortalModelApiCatalog::getItemFromList($allTagItem);//tags after 0-element
                                        $ctr=0;
                                        if(count($tagArr)>0) {
                                            foreach ($tagArr as $tagArrItem) {
                                                if( $ctr == 0 ) {//group
                                                    ?>
                                                    <div class="col-sm-4<?= trim($tagArrItem) == trim($selectedTagRaw) ? ' active': ''; ?>"><a onclick="javascript:onFilterClick()" href="index.php?option=com_apiportal&view=apicatalog<?php echo $this->isFiltered ? "": "&filter=showall";?>&sort=<?php echo $sortOrder;?>&tag=<?= rawurlencode($tagArrItem); ?>&Itemid=<?php echo $itemId; ?>" ><?php echo $this->escape($tagArrItem); ?></a>
                                                    </div>
                                                    <div class="col-sm-8">
                                                <?php
                                                } else {
                                                    ?>
                                                    <a onclick="javascript:onFilterClick()" <?= trim($tagArrItem) == trim($selectedTagRaw) ? 'class="active" ': ''; ?>href="index.php?option=com_apiportal&view=apicatalog<?php echo $this->isFiltered ? "": "&filter=showall";?>&sort=<?php echo $sortOrder;?>&tag=<?= rawurlencode($tagArrItem); ?>&Itemid=<?php echo $itemId; ?>" ><?php echo $this->escape($tagArrItem); ?></a>
                                                <?php
                                                }
                                                $ctr += 1;
                                            }
                                            ?>
                                            </div>
                                        <?php
                                        }?>
                                    </li>
                                <?php
                                }
                                ?>
                            </ul>
                        <?php
                        };
                        ?>
                    </div>
                </div>
            </div>
            </div>
            <!-- ************************************************************************ -->

            <div class="body auto">
            <?php
            if (!defined('DS')) {
                define('DS', DIRECTORY_SEPARATOR);
            }
            ?>
            <h3 id="apisSearch" aria-hidden="true">APIs found <em><span>0</span> items</em></h3>
            <ul id="apisList" class="apis" data-view="list"> <!-- API list -->
                <?php if ($this !== null && $this->items !== null && count($this->items) > 0) : ?>

                    <?php foreach ($this->items as $item) { ?>
                        <?php
                        $apiTagListFilter = '';
                        $apiTagListPrint = '';
                        if(  isset($item['tags']) ) {
                            $apiTagListFilter = APIPortalModelApiCatalog::getApiTagsList($item['tags'], true);//included group name!
                            $apiTagListPrint = APIPortalModelApiCatalog::getApiTagsList($item['tags'], false);//included group name!
                        }
                        //Start tag filter check. Show selected APIs per tag or Any tag API
                        if ($selectedTag == null || $selectedTag == '' || strpos($apiTagListFilter, $selectedTagRaw) !== false) {
                            ?>
                            <?php
                            $tmpId = ApiPortalHelper::cleanHtml($item['id'], false, true);
                            $tmpName = ApiPortalHelper::cleanHtml($item['name'], false, true);
                            $apiType = "";
                            $apiState = "";
                            if(isset($item['type'])){
                                $apiType = $item['type'] == 'wsdl' ? 'SOAP' : strtoupper(ApiPortalHelper::cleanHtml($item['type'], false, true));
                            }

                            if(isset($item['deprecated'])){
                                $apiState = $item['deprecated'] ? JText::_('COM_APIPORTAL_APICATALOG_APISTATE_DEPRICATED') : '';
                            }

                            $staticPathId = JPATH_SITE.DS.'components'.DS.'com_apiportal'.DS.'views'.DS.'apicatalog'.DS.'include'.DS.$tmpId.DS;
                            $staticPathName = JPATH_SITE.DS.'components'.DS.'com_apiportal'.DS.'views'.DS.'apicatalog'.DS.'include'.DS.$tmpName.DS;
                            $noImagePath = 'components/com_apiportal/assets/img/no_image.png';
                            $staticFile = 'api_info'.'.php';
                            $relativeId = "components/com_apiportal/views/apicatalog/include/$tmpId/";
                            $relativeName = "components/com_apiportal/views/apicatalog/include/$tmpName/";
                            ?>

                            <?php
                                $imageUrl = $noImagePath;
                                $staticFile = 'apilogo.png';
                                $nameUrlEncoded = rawurlencode($item['name']);
                                $apiURL           = JURI::base() . 'index.php?option=com_apiportal&view=apitester&usage=api&tab=tests&apiName=' . $nameUrlEncoded . '&apiId=' . $item['id'].'&menuId='.$itemId;
                                $apiMetricsURL    = JURI::base() . 'index.php?option=com_apiportal&view=apitester&usage=api&tab=messages&apiName=' . $nameUrlEncoded . '&sn=' .$nameUrlEncoded . '&Itemid='. $itemId . '&apiId=' . $item['id'].'&menuId='.$itemId;

                                if (file_exists($staticPathName.$staticFile)) {
                                    $imageUrl = $relativeName.$staticFile;
                                } elseif (file_exists($staticPathId.$staticFile)) {
                                    $imageUrl = $relativeId.$staticFile;
                                } elseif (isset($item["image"]) && $item["image"] !== null && $item["image"] != "" && isset($item['id'])) {
                                    $imageUrl = JURI::base(false) . 'index.php?option=com_apiportal&view=image&format=raw&apiId=' . $item['id'];
                                }
                            ?>
                            <!-- API list item -->
                            <li>
                                <h2><a href="<?php echo $apiURL;?>" ><?php echo isset($item['name']) ? $this->escape($item['name'], false, true) : ""; ?></a></h2>
                                <div class="api-icon">
                                    <?php if (empty($imageUrl)) { ?>
                                    <a href="<?php echo $apiURL;?>" role="presentation"></a>
                                    <?php } else { ?>
                                    <img src="<?php echo $imageUrl;?>" onclick="location.href='<?php echo $apiURL;?>';" alt="api icon" role="presentation">
                                    <?php } ?>
                                </div>
                                <p class="markdown-reset" id="<?php echo 'api-description-'. $item['id']; ?>">
                                    <?php
                                        // If we have a docUrl validate it and display it
                                        // this approach is used in ajaxrequest controller - DRY on refactoring
                                        if (isset($item['documentationUrl'])) {
                                            $url = false;
                                            $regex = '(?xi)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';
                                            preg_match("#$regex#i", $item['documentationUrl'], $url);
                                            echo ($url ? "<a target='_blank' href='" . $item['documentationUrl'] . "'>" . $item['documentationUrl'] . "</a>" : null);
                                        }
                                    ?>
                                </p>
                                <script>
                                    var descriptionTxt = "<?= isset($item['description']) ? preg_replace(array('/\n/', '/\t/'), array('\\\\n', '\\\\t'), htmlentities($item['description'])) : '' ?>";
                                    if (descriptionTxt) {
                                        jQuery('#<?php echo 'api-description-' . $item['id']; ?>').append(jQuery(marked(descriptionTxt)).html());
                                    }
                                </script>
                                <dl>
                                    <?php if (isset($item["deprecated"]) && $item["deprecated"]){ ?>
                                        <dt><i class="fa fa-warning"></i><?php echo $apiState; ?></dt>
                                        <dd>
                                            <?php
                                            $timestamp = $item['retirementDate'];
                                            if($timestamp > 0 ){
                                                echo ApiPortalHelper::convertDateTime($timestamp,JText::_('COM_APIPORTAL_LOCAL_DATE_FORMAT'));
                                            }
                                            ?>
                                        </dd>
                                    <?php }?>
                                    <!-- IRU: Hide this for now. Original file does not print version here.
                                    <dt>Version</dt>
                                    <dd>1.4</dd> -->
                                    <dt><?= JText::_('COM_APIPORTAL_APICATALOG_TYPE') ?></dt>
                                    <dd><?php echo $apiType; ?></dd>
                                    <dt><?= JText::_('COM_APIPORTAL_APICATALOG_VERSION') ?></dt>
                                    <dd><?= $item['apiVersion']; ?></dd>
                                    <?php if (!empty($apiTagListPrint)) { ?>
                                    <dt><?= JText::_('COM_APIPORTAL_APICATALOG_TAGS') ?></dt>
                                    <dd>
                                        <?php echo $this->escape($apiTagListPrint); ?>
                                    </dd>
                                    <?php }?>
                                </dl>
                                <div role="toolbar">
                                <?php 
                                
                                $nomeProduto = preg_replace('/\s+/', '-', $item['name']);
                                
                                ?>
                                    <a class = "btn-default" href="/produto/<?php echo $nomeProduto ?>"><?= JText::_('Saiba Mais') ?></a>
                                   
                                <?php if($enableInlineTryIt){?>
                                    <a class="btn btn-default btn-primary icon gear" href="<?php echo $apiURL;?>"><?= JText::_('COM_APIPORTAL_APICATALOG_TEST') ?></a>
                                <?php }?>
                                    <a class="btn btn-default icon metrics" <?php echo $publicApiAction; ?> href="<?php echo $apiMetricsURL;?>"><?= JText::_('COM_APIPORTAL_APICATALOG_VIEW_METRICS') ?></a>
                                </div>

                            </li>
                        <?php     } //end tag filter check; ?>
                    <?php     } //endforeach; ?>
                <?php else : ?>
                    <li class="noentries">
                        <p><?= JText::_('COM_APIPORTAL_APICATALOG_EMPTY_API_LIST') ?></p>
                    </li>
                <?php endif; ?>
            </ul>

            <?php if ($this->isFiltered) : ?>
                <div>
                    <a href="index.php?option=com_apiportal&view=apicatalog&filter=showall&Itemid=<?php echo $itemId; ?>" class="btn btn-primary" ><?= JText::_('COM_APIPORTAL_APICATALOG_SHOW_ALL_APIS') ?></a>
                </div>
            <?php else : ?>
                <!-- All available APIs for currently logged in user (<?php echo JFactory::getUser()->name;?>) have been shown. -->
            <?php endif; ?>
        </div>
    </div> <!-- tab-contents -->
</div> <!-- tabs -->

<script type="text/javascript">
    // jQuery is loaded in 'noconflict' mode.
    jQuery(document).ready(function($) {

        // Add ellipsis to 'description' field
        $('.content .description').dotdotdot({
            watch: true
        });

        /**
         * Case insenstive selector.
         */

        jQuery.expr[':'].icontains = function(a, i, m) {
            return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
        };

        function findApi($input) {
            var $this = $input;
            var $apis = $('#apisList > li');
            var $apisSearch = $('#apisSearch');

            if ($this.val().length > 0) {
              $match = $apis.filter(':icontains(' + $this.val() + ')');

              $apis.not($match).hide();

                    $sort = $apis.sort(function (a, b) {
                        var aTitle = $(a).find('h2 a').text().toLowerCase();
                        var bTitle = $(b).find('h2 a').text().toLowerCase();
                        var aVisibility = $(a).is(':visible');
                        var bVisibility = $(b).is(':visible');

                        return aTitle === bTitle ? 0 : (aTitle < bTitle ? -1 : 1);
                    });

              $match.show();

              $apisSearch.attr('aria-hidden', false);
              $apisSearch.find('em span').text($match.length);
            } else {
              $apisSearch.attr('aria-hidden', true);
                    $apis.show();
            }
      }

        function changeApisView($toggle) {
            var pressed = $toggle.attr('aria-pressed') == 'true' ? true : false;
            var view = pressed ? 'list' : 'tiles';
			//set the cookie values of current state
            $.cookie("cookie_pressed_<?php echo $itemId ?>", pressed, { expires : 365 });
            $.cookie("cookie_view_<?php echo $itemId ?>", view, { expires : 365 });
            
            var $apis = $('#apisList');

            $toggle.attr('aria-pressed', !pressed);
            $apis.attr('data-view', view);
        }

        var cookiePressedValue = $.cookie("cookie_pressed_<?php echo $itemId ?>");
        var cookieViewValue = $.cookie("cookie_view_<?php echo $itemId ?>");

         if(typeof cookieViewValue != "undefined"){
              var $apisss = $('#apisList');
              var cookiePressedValue = (cookiePressedValue == 'true');  
              $("#changeApisView").attr('aria-pressed', !cookiePressedValue);
              $apisss.attr('data-view', cookieViewValue);
        }
        

      $('body').on('keyup', '#findApis', function(e) {
            findApi($(this));
      });

        $('body').on('click', '#changeApisView', function(e) {
            changeApisView($(this));
        });
    });
</script>