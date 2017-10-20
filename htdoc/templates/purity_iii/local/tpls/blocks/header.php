<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 * Override the existing header file of Purity_!!! template
 */

defined('_JEXEC') or die;

// get params
$sitename  = $this->params->get('sitename');
$slogan    = $this->params->get('slogan', '');
$logotype  = $this->params->get('logotype', 'text');
$logoimage = $logotype == 'image' ? $this->params->get('logoimage', 'templates/' . T3_TEMPLATE . '/images/logo.png') : '';
$logoimgsm = ($logotype == 'image' && $this->params->get('enable_logoimage_sm', 0)) ? $this->params->get('logoimage_sm', '') : false;

if (!$sitename) {
	$sitename = JFactory::getConfig()->get('sitename');
}

//require header files
require_once JPATH_SITE.'/components/com_apiportal/helpers/apiportal.php';

/* Implemented custom plugin for PUBLIC API to display few menu, if application is in PUBLIC API mode
 * else
 * The Original
 **/
$result = JPluginHelper::importPlugin('menu');
$dispatcher	= JEventDispatcher::getInstance();

$menus  = $dispatcher->trigger('onContentMainMenu', array());

if(JFactory::getSession()->get('PublicAPIMode',0) ==1){
	JHtml::_('behavior.keepalive');
}
?>

<!-- MAIN NAVIGATION -->
<header id="t3-mainnav" class="wrap navbar navbar-default navbar-fixed-top t3-mainnav">

	<!-- OFF-CANVAS -->
	<?php if ($this->getParam('addon_offcanvas_enable')) : ?>
		<?php $this->loadBlock ('off-canvas') ?>
	<?php endif ?>
	<!-- //OFF-CANVAS -->

	<div class="container">

		<!-- NAVBAR HEADER -->
		<div class="navbar-header">

			<!-- LOGO -->
			<div class="logo logo-<?php echo $logotype ?>">
				<div class="logo-<?php echo $logotype, ($logoimgsm ? ' logo-control' : '') ?>">
					<a href="<?php echo JURI::base(true) ?>" title="<?php echo strip_tags($sitename) ?>">
						<?php if($logotype == 'image'): ?>
							<img class="logo-img" src="<?php echo JURI::base(true) . '/' . $logoimage ?>" alt="<?php echo strip_tags($sitename) ?>" />
						<?php endif ?>

						<?php if($logoimgsm) : ?>
							<img class="logo-img-sm" src="<?php echo JURI::base(true) . '/' . $logoimgsm ?>" alt="<?php echo strip_tags($sitename) ?>" />
						<?php endif ?>
						
						<span><?php echo $sitename ?></span>
					</a>
				</div>
			</div>
			<!-- //LOGO -->

			<?php if ($this->getParam('navigation_collapse_enable', 1) && $this->getParam('responsive', 1)) : ?>
				<?php $this->addScript(T3_URL.'/js/nav-collapse.js'); ?>
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".t3-navbar-collapse">
					<i class="fa fa-bars"></i>
				</button>
			<?php endif ?>

	    <?php if ($this->countModules('head-search')) : ?>
	    <!-- HEAD SEARCH -->
	    <div class="head-search<?php $this->_c('head-search')?>">     
	      <jdoc:include type="modules" name="<?php $this->_p('head-search') ?>" style="raw" />
	    </div>
	    <!-- //HEAD SEARCH -->
	    <?php endif ?>

		</div>
		<!-- //NAVBAR HEADER -->

		<!-- NAVBAR MAIN -->
		<?php if ($this->getParam('navigation_collapse_enable')) : ?>
		<nav class="t3-navbar-collapse navbar-collapse collapse"></nav>
		<?php endif ?>

		<nav class="t3-navbar navbar-collapse collapse">
		<?php if(JFactory::getSession()->get('PublicAPIMode',0) ==1){?>
			<div class="t3-megamenu animate fading" data-duration="200" data-responsive="true">
				<ul class="nav navbar-nav level0">
				 <?php  $fullURL = JUri::current();
				 foreach($menus[0] as $key => $menu)
				 { 
				 	$menulink = $menu['link'];
				 	
				 	$isMenuAllowed = ApiportalHelper::isMenuAllowedInPublicMode($menulink);
				 	
				 	$parameter ="/home/";
				 	$paramURL = JUri::base(). $menu['path'];
				 	
				 	if($fullURL == $paramURL)
				 	{
				 		$active = 'active';
				 	}else{
				 		$active = '';
				 	}
				 	//Menu should be allowed for 1 - Public, 5-Guest
				 	$menu_access = array(1,5);
				 	if((!empty($isMenuAllowed)) || (in_array($menu['access'], $menu_access))){
					?>
						<li class="current <?php echo $active;?>" data-id="<?php echo $menu['id']?>" data-level="<?php echo $menu['access']; ?>">
							<a class="" href="<?php echo $menu['path'];?>" data-target="#">
								<?php if(preg_match($parameter, $menu['alias'], $matches)){?>
									<img src="<?php echo JUri::base()?>components/com_apiportal/assets/img/menu/axway-logo-top.svg" alt="Home">
								<?php }else{  echo $menu['title']; } ?> 
							</a>
						</li>
					<?php }
				 }?>
				</ul>
			</div>
			<?php }else{?>
			<jdoc:include type="<?php echo $this->getParam('navigation_type', 'megamenu') ?>" name="<?php echo $this->getParam('mm_type', 'mainmenu') ?>" />
		   <?php } ?>
		</nav>
    <!-- //NAVBAR MAIN -->

	</div>
</header>
<!-- //MAIN NAVIGATION -->

<?php $this->loadBlock ('masthead') ?>