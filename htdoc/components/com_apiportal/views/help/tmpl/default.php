<?php
$itemId = JRequest::getString('Itemid', '');
$itemId = ApiPortalHelper::cleanHtml($itemId, false, true);

// Check Menu Id to get Menu Params Mastheadtitle, MastheadSlogan
$result = ApiPortalHelper::getMenuParamsValue($itemId);
if(!empty($result['masthead-title'])){
	$title =  $result['masthead-title'];
}else{
	$title =  JText::_('API_PORTAL_HELP_CENTER');
}
if(!empty($result['masthead-slogan'])){
	$slogan =  $result['masthead-slogan'];
}
?>
<div class="head" id="static_mainmenu">
  <h1 class="auto"><?=$title ?></h1>
  <p class="auto"><em><?=$slogan ?><!-- placeholder --></em></p>
</div>

<div class="body auto">
  <div class="container">
    <div class="row" style="margin-top:30px;">
      <div class="col-md-6">
        <div class="col-md-2">
          <i class="icon question-circle fa-4x"></i>
        </div>
        <div class="col-md-10">
          <h4><a href="<?= JRoute::_('index.php?option=com_apiportal&view=help&layout=faq', false); ?>"><?= JText::_('API_PORTAL_HELP_CENTER_TECHNICAL_FAQS') ?></a></h4>
        <p><?= JText::_('API_PORTAL_HELP_CENTER_FREQUENTLY_ASKED_QUESTIONS') ?></p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="col-md-2">
          <i class="icon document-text fa-4x"></i>
        </div>
        <div class="col-md-10">
          <h4><a href="/documentation"><?= JText::_('API_PORTAL_HELP_CENTER_DOCUMENTATION') ?></a></h4>
        <p><?= JText::_('API_PORTAL_HELP_CENTER_DOCUMENTATION_ACCESS_EVERYTHING_YOU_NEED_TO_INTEGRATE') ?></p>
        </div>
      </div>
    </div>

    <div class="row" style="padding:15px 0 40px 0">
      <div class="col-md-6">
        <div class="col-md-2">
          <i class="icon bubble-text fa-4x"></i>
        </div>
        <div class="col-md-10">
      <?php if (ApiPortalHelper::isComponentEnabled('com_easydiscuss')) { ?>
        <!-- EasyDiscuss needs to installed/enabled, and be present as an (un)published sub-menu item of 'Help Center' -->
          <h4><a href="<?php echo JRoute::_('index.php?option=com_easydiscuss&view=index', false); ?>"><?= JText::_('API_PORTAL_HELP_CENTER_DOCUMENTATION_DISCUSSION_FORUMS');?></a></h4>
      <?php } else { ?>
          <h4><a href="#Replace_with_a_link_to_your_discussion_forum"><?= JText::_('API_PORTAL_HELP_CENTER_DOCUMENTATION_DISCUSSION_FORUMS');?>s</a></h4>
      <?php } ?>
        <p><?= JText::_('API_PORTAL_HELP_CENTER_DOCUMENTATION_DISCUSSION_FORUMS_KNOWLEDGE_EXPERIENCE') ?></p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="col-md-2">
          <i class="icon earth fa-4x"></i>
        </div>
        <div class="col-md-10">
          <h4><a href="<?php echo JRoute::_('index.php?option=com_apiportal&view=help&layout=contact', false); ?>"><?= JText::_('API_PORTAL_HELP_CENTER_CONTACT_US') ?></a></h4>
        <p><?= JText::_('API_PORTAL_HELP_CENTER_CONTACT_US_CONTENT');?></p>
        </div>
      </div>
    </div>
  </div>
</div>
