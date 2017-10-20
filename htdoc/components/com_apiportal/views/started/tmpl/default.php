<div class="head">
  <h1 class="auto"><?= JText::_('API_PORTAL_GETTING_STARTED') ?></h1>
  <p class="auto"><em><?= JText::_('API_PORTAL_TO_HELP_YOU_DECIDE') ?></em></p>
</div>

<div class="body auto">
  <h2><?= JText::_('API_PORTAL_EXPLORE_AND_TEST_API') ?></h2>

  <p><?= JText::_('API_PORTAL_EXPLORE_FIRST') ?></p>

  <ol>
    <li><?= JText::_('API_PORTAL_START_IF_DONT_HAVE_APP') ?> </li>
    <li><?= JText::_('API_PORTAL_START_TEST_AND_METRICS_APP') ?></li>
    <li><?= JText::_('API_PORTAL_START_TEST_TAB') ?></li>
    <li><?= JText::_('API_PORTAL_START_SAME_CREDENTIALS') ?></li>
    <li><?= JText::_('API_PORTAL_START_REQUIRE_DIFFERENT_CRED') ?></li>
    <li><?= JText::_('API_PORTAL_START_PARAMETER_SECTION') ?></li>
    <li><?= JText::_('API_PORTAL_START_NEXT') ?></li>
    <li><?= JText::_('API_PORTAL_START_SUCCESSES_TAB') ?></li>
  </ol>

  <p>
  <?php
  	$text = JText::_('API_PORTAL_START_YOUTUBE');
  	echo JHtml::_('content.prepare', $text);
  ?>
  </p>

  <h2><?= JText::_('API_PORTAL_START_APPLICATION') ?></h2>
  <p><?= JText::_('API_PORTAL_START_APPLICATION_QUICKLY_CREATE') ?></p>

  <ol>
    <li><?= JText::_('API_PORTAL_START_APPLICATION_DIRECTED_TO_API_CATALOG_PAGE') ?></li>
    <li><?= JText::_('API_PORTAL_START_APPLICATION_CREATE') ?></li>
    <li><?= JText::_('API_PORTAL_START_APPLICATION_ENTER_INFO') ?></li>
    <li><?= JText::_('API_PORTAL_START_APPLICATION_SAVE_AND_AUTHENTICATE') ?></li>
    <li><?= JText::_('API_PORTAL_START_APPLICATION_IF_API_DOESNOT_REQUIRE_APP') ?></li>
  </ol>
  
    <p><?= JText::_('API_PORTAL_START_APPLICATION_FIRST') ?></p>
  
  <p>
   <?php
  	$text = JText::_('API_PORTAL_START_APPLICATION_YOUTUBE');
  	echo JHtml::_('content.prepare', $text);
  ?>
  </p>
</div>
