<?php
$itemId = JRequest::getString('Itemid', '');
$itemId = ApiPortalHelper::cleanHtml($itemId, false, true);

// Check Menu Id to get Menu Params Mastheadtitle, MastheadSlogan
$result = ApiPortalHelper::getMenuParamsValue($itemId);
if(!empty($result['masthead-title'])){
	$title =  $result['masthead-title'];
}else{
	$title =  JText::_('API_PORTAL_PRICING');
}
if(!empty($result['masthead-slogan'])){
	$slogan =  $result['masthead-slogan'];
}
?>

<div class="head" >
  <h1 class="auto"><?= $title; ?></h1>
  <p class="auto"><em><?= $slogan; ?><!-- placeholder --></em></p>
</div>
<div class="body auto">

<div class="pricing">
    <div class="tier1">
        <h2><?php echo JText::_('API_PORTAL_PRICING_BRONZE');?></h2>
          <p><span class="price"><?php echo JText::_('API_PORTAL_PRICING_BRONZE_PRICE'); ?></span><span style="permonth"><?php echo JText::_('API_PORTAL_PRICING_MONTH');?></span></p>
          <hr>
          <a href="urlofsignup" class="start"><?php echo JText::_('API_PORTAL_PRICING_GET_STARTED');?></a>
          <hr>
          <p><span class="numrequests"><?php echo JText::_('API_PORTAL_PRICING_BRONZE_NUMBER_OF_REQUESTS');?></span><br />
          <span class="requests"><?php echo JText::_('API_PORTAL_PRICING_BRONZE_REQ_PER_MON');?></span></p>
          <hr>
          <p><span class="additionaltext"><?php echo JText::_('API_PORTAL_PRICING_BRONZE_ADDITIONAL_REQUESTS');?></span><br />
          <span class="requests"><?php echo JText::_('API_PORTAL_PRICING_BRONZE_REQUEST_DURATION');?></span></p>
          <hr>
          <p><span class="additionaltext"><?php echo JText::_('API_PORTAL_PRICING_BRONZE_REQUEST_REJECTION');?></span><br />
          <span class="requests"><?php echo JText::_('API_PORTAL_PRICING_BRONZE_REQUEST_REJECTION_DURATION');?></span></p>
    </div>
    <div class="tier3">
        <h2><?= JText::_('API_PORTAL_PRICING_GOLD') ?></h2>
          <p><span class="price"><?= JText::_('API_PORTAL_PRICING_GOLD_PRICE') ?></span><span style="permonth"><?= JText::_('API_PORTAL_PRICING_MONTH') ?></span></p>
          <hr>
          <a href="urlofsignup" class="start"><?php echo JText::_('API_PORTAL_PRICING_GET_STARTED');?></a>
          <hr>
          <p><span class="numrequests"><?php echo JText::_('API_PORTAL_PRICING_GOLD_NUMBER_OF_REQUEST');?></span></p>
    </div>
    <div class="tier2">
          <h2><?php echo JText::_('API_PORTAL_PRICING_SILVER');?></h2>
          <p><span class="price"><?php echo JText::_('API_PORTAL_PRICING_SILVER_PRICE'); ?></span><span style="permonth"><?php echo JText::_('API_PORTAL_PRICING_MONTH');?></span></p>
          <hr>
          <a  href="urlofsignup" class="start"><?php echo JText::_('API_PORTAL_PRICING_GET_STARTED');?></a>
          <hr>
          <p><span class="numrequests"><?php echo JText::_('API_PORTAL_PRICING_SILVER_NUMBER_OF_REQUESTS');?></span><br />
          <span class="requests"><?php echo JText::_('API_PORTAL_PRICING_SILVER_REQ_PER_MON');?></span></p>
          <hr>
          <p><span class="additionaltext"><?php echo JText::_('API_PORTAL_PRICING_SILVER_ADDITIONAL_REQUESTS');?></span><br />
          <span class="requests"><?php echo JText::_('API_PORTAL_PRICING_SILVER_REQUEST_DURATION');?></span></p>
          <hr>
          <p><span class="additionaltext"><?php echo JText::_('API_PORTAL_PRICING_SILVER_REQUEST_OVER_LIMIT');?> </span><br />
          <?php echo JText::_('API_PORTAL_PRICING_SILVER_REQUEST_OVER_LIMIT_DURATION');?></p>
          <p>
          <span class="requests"><?php echo JText::_('API_PORTAL_PRICING_SILVER_PRICE_PER_ADDITIONAL_REQUEST');?></span></p>
    </div>
  </div>
</div>
