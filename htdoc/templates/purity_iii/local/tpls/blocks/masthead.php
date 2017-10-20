<?php
/**
 * @package   T3 Blank
 * @copyright Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
$input = JFactory::getApplication()->input;
$layout = $input->get('layout'); 
$menu = JFactory::getApplication()->getMenu();
$activemenu = $menu->getActive() ? $menu->getActive() : $menu->getDefault();
$query = $activemenu->query;
$mast_title = $mast_slogan = '';
if ((!isset ($query['option']) || $query['option'] == $input->get ('option'))
		&& (!isset ($query['view']) || $query['view'] == $input->get ('view'))
		&& (!isset ($query['id']) || $query['id'] == $input->get ('id'))) {
	$mast_title = $activemenu->params->get ('masthead-title');
	$mast_slogan = $activemenu->params->get ('masthead-slogan');
}

$masthead_position = 'masthead';

?>

<?php if ($mast_title || $this->countModules($masthead_position)) : ?>
<div class="page-masthead" style="display:none;">
	<?php  if ($mast_title || $mast_slogan) : ?>
	<div class="jumbotron jumbotron-primary">
		<div class="head" id="dynamic_mainmenu">
			<h1 class="auto"><?php echo $mast_title ?></h1>
			<p class="auto"><em><?php echo $mast_slogan ?></em></p>
		</div>
	</div>
	<?php endif ?>

	<?php if ($this->countModules ($masthead_position)): ?>
		<jdoc:include type="modules" name="<?php echo $masthead_position ?>" style="FeatureRow" />
	<?php endif ?>
</div>
<?php endif ?>

