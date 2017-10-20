<main>
  <section id="enterSection">
  <h1><?= JText::_('COM_APIPORTAL_HOME_AXWAY_API_PORTAL') ?></h1>
  <p><?= JText::_('COM_APIPORTAL_HOME_EXPLORE_TEST') ?></p>
    <?php
    if (JFactory::getUser()->guest) {
    ?>
    <a href="/sign-in"><?= JText::_('COM_USERS_SIGN_IN_TITLE') ?></a> </section>
    <?php } else { ?>
      <a href="api-catalog/"><?= JText::_('COM_APIPORTAL_HOME_EXPLORE_CLEAR') ?></a> </section>
    <?php } ?>
  <section id="featuresSection">
  <div class="auto">
    <ul>
    <li onclick="location.href='api-catalog/'">
      <div role="region"><img src="/desenvolvedores/components/com_apiportal/assets/img/home/expolore.svg" alt="compass icon" class="svg" />
      <h2><a href="api-catalog/"><?= JText::_('COM_APIPORTAL_HOME_EXPLORE') ?></a></h2>
      <p><?= JText::_('COM_APIPORTAL_HOME_EXPLORE_INFO') ?></p>
      </div>
    </li>
    <li onclick="location.href='apps'">
      <div role="region"><img src="/desenvolvedores/components/com_apiportal/assets/img/home/create.svg" alt="pencil icon" class="svg" />
      <h2><a href="apps/"><?= JText::_('COM_APIPORTAL_HOME_CREATE') ?></a></h2>
      <p><?= JText::_('COM_APIPORTAL_HOME_CREATE_INFO') ?></p>
      </div>
    </li>
    <li onclick="location.href='monitoring/'">
      <div role="region"><img src="/desenvolvedores/components/com_apiportal/assets/img/home/manage.svg" alt="gears icon" class="svg" />
      <h2><a href="monitoring/"><?= JText::_('COM_APIPORTAL_HOME_ANALYZE') ?></a></h2>
      <p><?= JText::_('COM_APIPORTAL_HOME_ANALYZE_INFO') ?></p>
      </div>
    </li>
    </ul>
  </div>
  </section>
  <section id="communitySection">
  <div class="auto">
    <ul role="presentation">
    <li role="presentation">
      <div role="region"><img src="/desenvolvedores/components/com_apiportal/assets/img/home/community.svg"
                  alt="chat question and answer icon" class="svg" />
      <h2><a href="blog/"><?= JText::_('COM_APIPORTAL_HOME_CONNECT') ?></a></h2>
      <p><?= JText::_('COM_APIPORTAL_HOME_CONNECT_INFO') ?></p>
      <a href="blog/"><?= JText::_('COM_APIPORTAL_HOME_JOIN_COMMUNITY_LINK') ?></a></div>
    </li>
    </ul>
  </div>
  </section>
</main>
<script>
  function embedSVG(img) {
    var imgID = img.attr('id'),
        imgClass = img.attr('class'),
        imgURL = img.attr('src'),
        imgGet = jQuery.get(imgURL);

    jQuery.when(imgGet).done(function(data) {
      var svg = jQuery(data).find('svg');

      if (typeof imgID !== 'undefined') {
        svg.attr('id', imgID);
        }

      if (typeof imgClass !== 'undefined') {
        svg = svg.attr('class', imgClass + ' replaced-svg');
    }

      img.replaceWith(svg);
    }).fail(function() {
      console.log("Failed to convert SVG.");
    });
    }

  jQuery('main').find('img.svg').each(function() {
  embedSVG(jQuery(this));
  });
</script>
