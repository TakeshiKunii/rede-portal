<?php
defined('_JEXEC') or die;

if (!isset($this->error))
{
	$this->error = JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
	$this->debug = false;
}
//get language and direction
$doc = JFactory::getDocument();
$this->language = $doc->language;
$this->direction = $doc->direction;

$errorImage = '<div style="margin-bottom:50px">
					<img style="max-width: 250px;display: block;margin-left: auto;margin-right: auto" src="'.$this->baseurl.'/templates/purity_iii/images/error-page.png" alt="Page Not Found"/>
				</div>';

$debug = JFactory::getConfig()->get('debug_lang');
$reporting = JFactory::getConfig()->get('error_reporting');

if ($reporting != 'none') {
	$errorImage = null;
	$headerTitle = $this->error->getCode() . '-' . htmlspecialchars($this->error->getMessage());
	$status      = false;
	$mainTitle   = $this->error->getCode() . '-' . htmlspecialchars($this->error->getMessage());
	$body        = '<p><strong>'.JText::_('JERROR_LAYOUT_NOT_ABLE_TO_VISIT').'</strong></p>
	<ol>
		<li>'.JText::_('JERROR_LAYOUT_AN_OUT_OF_DATE_BOOKMARK_FAVOURITE').'</li>
		<li>'.JText::_('JERROR_LAYOUT_SEARCH_ENGINE_OUT_OF_DATE_LISTING').'</li>
		<li>'.JText::_('JERROR_LAYOUT_MIS_TYPED_ADDRESS').'</li>
		<li>'.JText::_('JERROR_LAYOUT_YOU_HAVE_NO_ACCESS_TO_THIS_PAGE').'</li>
		<li>'.JText::_('JERROR_LAYOUT_REQUESTED_RESOURCE_WAS_NOT_FOUND').'</li>
		<li>'.JText::_('JERROR_LAYOUT_ERROR_HAS_OCCURRED_WHILE_PROCESSING_YOUR_REQUEST').'</li>
	</ol>
	<p>
		<strong>'.JText::_('JERROR_LAYOUT_PLEASE_TRY_ONE_OF_THE_FOLLOWING_PAGES').'</strong>
		<a class="btn btn-primary" href="'.$this->baseurl.'/"
		   title="'.JText::_('JERROR_LAYOUT_GO_TO_THE_HOME_PAGE').'">'.JText::_('JERROR_LAYOUT_HOME_PAGE').'</a>
	</p>

	<p>'.JText::_('JERROR_LAYOUT_PLEASE_CONTACT_THE_SYSTEM_ADMINISTRATOR').'.</p>

	<div id="techinfo">
		<p>'. htmlspecialchars($this->error->getMessage()).'</p>
	
		<p>'.
			$this->debug ? $this->renderBacktrace() : null
		.'</p>
	</div>';
} elseif ($this->error->getCode() == 404) {
	$headerTitle = JText::_('COM_APIPORTAL_ERROR_PAGE_TITLE_NOT_FOUND');
	$status      = 404;
	$mainTitle   = JText::sprintf('COM_APIPORTAL_ERROR_PAGE_HEADER_MESSAGE', $this->error->getCode());
	$body        = '<p><strong>'. JText::_('JERROR_LAYOUT_PLEASE_TRY_ONE_OF_THE_FOLLOWING_PAGES') .'</strong>
			<a class="btn btn-primary" href="'.$this->baseurl.'/"
            title="'. JText::_('JERROR_LAYOUT_GO_TO_THE_HOME_PAGE').'">'. JText::_('JERROR_LAYOUT_HOME_PAGE').'</a>
			</p>';
} else {
	$headerTitle = JText::_('COM_APIPORTAL_ERROR_PAGE_TITLE_WENT_WRONG');
	$status      = false;
	$mainTitle   = JText::_('COM_APIPORTAL_ERROR_PAGE_TITLE_WENT_WRONG');
	$body        = '<p><strong>'. JText::_('JERROR_LAYOUT_PLEASE_TRY_ONE_OF_THE_FOLLOWING_PAGES') .'</strong>
			<a class="btn btn-primary" href="'.$this->baseurl.'/"
            title="'. JText::_('JERROR_LAYOUT_GO_TO_THE_HOME_PAGE').'">'. JText::_('JERROR_LAYOUT_HOME_PAGE').'</a>
			</p>';
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>"
      lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title><?= $headerTitle ?></title>
	<link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/purity_iii/css/error.css" type="text/css"/>
	<?php if ($this->direction == 'rtl') : ?>
		<link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/system/css/error_rtl.css"
		      type="text/css"/>
	<?php endif; ?>
	<?php
	if (JDEBUG || $debug)
	{
		?>
		<link rel="stylesheet" href="<?php echo $this->baseurl ?>/media/cms/css/debug.css" type="text/css"/>
		<?php
	}
	?>
</head>
<body>
<div class="error">
	<div id="outline">
		<div id="errorboxoutline">
			<?= $errorImage ?>
			<div id="errorboxheader">
				<?= $mainTitle ?>
			</div>
			<div id="errorboxbody">
				<?= $body ?>
			</div>
		</div>
	</div>
</div>
</body>
</html>