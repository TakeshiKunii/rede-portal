<?php
/**
 * @version 1.5 stable $Id: view.html.php 1869 2014-03-12 12:18:40Z ggppdk $
 * @package Joomla
 * @subpackage FLEXIcontent
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 * 
 * FLEXIcontent is a derivative work of the excellent QuickFAQ component
 * @copyright (C) 2008 Christoph Lukes
 * see www.schlu.net for more information
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('legacy.view.legacy');

/**
 * HTML View class for the Stats View
 *
 * @package Joomla
 * @subpackage FLEXIcontent
 * @since 1.0
 */
class FlexicontentViewStats extends JViewLegacy
{
	/**
	 * Creates the Entrypage
	 *
	 * @since 1.0
	 */
	function display( $tpl = null )
	{
		//initialise variables
		$document = JFactory::getDocument();
		$user     = JFactory::getUser();
		
		// Get data from the model
		$genstats   = $this->get( 'Generalstats' );
		$popular    = $this->get( 'Popular' );
		$rating     = $this->get( 'Rating' );
		$worstrating= $this->get( 'WorstRating' );
		$favoured   = $this->get( 'Favoured' );
		$statestats = $this->get( 'Statestats' );
		$votesstats	= $this->get( 'Votesstats' );
		$creators   = $this->get( 'Creators' );
		$editors    = $this->get( 'Editors' );

		// ************************************************** New data*********************************************************************************************************************//
		$itemsgraph  			   = $this->get('Itemsgraph');
		$unpopular   			   = $this->get('Unpopular');
		$totalitemspublish         = $this->get('Itemspublish');
		$totalitemsunpublish       = $this->get('Itemsunpublish');
		$totalitemswaiting         = $this->get('Itemswaiting');
		$totalitemsprogress        = $this->get('Itemsprogress');
		$metadescription           = $this->get('Itemsmetadescription');
		$metakeywords              = $this->get('Itemsmetakeywords');
		
		// ************************************************** New data*********************************************************************************************************************//
		
		
		// **************************
		// Add css and js to document
		// **************************
		
		!JFactory::getLanguage()->isRtl()
			? $document->addStyleSheetVersion(JURI::base(true).'/components/com_flexicontent/assets/css/flexicontentbackend.css', FLEXI_VHASH)
			: $document->addStyleSheetVersion(JURI::base(true).'/components/com_flexicontent/assets/css/flexicontentbackend_rtl.css', FLEXI_VHASH);
		!JFactory::getLanguage()->isRtl()
			? $document->addStyleSheetVersion(JURI::base(true).'/components/com_flexicontent/assets/css/j3x.css', FLEXI_VHASH)
			: $document->addStyleSheetVersion(JURI::base(true).'/components/com_flexicontent/assets/css/j3x_rtl.css', FLEXI_VHASH);



		//*****************************************************************Adicionar as biblitecas*******************************************************************************************//
		$document->addStyleSheet('//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css');
		$document->addScript(JURI::root(true).'/components/com_flexicontent/librairies/esl/esl.js');
		//*****************************************************************Adicionar as biblitecas*******************************************************************************************//
		
		
		
		// *****************************
		// Get user's global permissions
		// *****************************
		
		$perms = FlexicontentHelperPerm::getPerm();
		
		
		
		// ************************
		// Create Submenu & Toolbar
		// ************************
		
		// Create Submenu (and also check access to current view)
		FLEXIUtilities::ManagerSideMenu('CanStats');
		
		// Create document/toolbar titles
		$doc_title = JText::_( 'FLEXI_STATISTICS' );
		$site_title = $document->getTitle();
		JToolBarHelper::title( $doc_title, 'stats' );
		$document->setTitle($doc_title .' - '. $site_title);
		
		// Create the toolbar
		//JToolBarHelper::Back();
		if ($perms->CanConfig) {
			//JToolBarHelper::divider(); JToolBarHelper::spacer();
			$session = JFactory::getSession();
			$fc_screen_width = (int) $session->get('fc_screen_width', 0, 'flexicontent');
			$_width  = ($fc_screen_width && $fc_screen_width-84 > 940 ) ? ($fc_screen_width-84 > 1400 ? 1400 : $fc_screen_width-84 ) : 940;
			$fc_screen_height = (int) $session->get('fc_screen_height', 0, 'flexicontent');
			$_height = ($fc_screen_height && $fc_screen_height-128 > 550 ) ? ($fc_screen_height-128 > 1000 ? 1000 : $fc_screen_height-128 ) : 550;
			JToolBarHelper::preferences('com_flexicontent', $_height, $_width, 'Configuration');
		}
		
		$this->assignRef('genstats'		, $genstats);
		$this->assignRef('popular'		, $popular);
		$this->assignRef('rating'			, $rating);
		$this->assignRef('worstrating', $worstrating);
		$this->assignRef('favoured'		, $favoured);
		$this->assignRef('statestats'	, $statestats);
		$this->assignRef('votesstats'	, $votesstats);
		$this->assignRef('creators'		, $creators);
		$this->assignRef('editors'		, $editors);

		// ************************************************** New data*********************************************************************************************************************//
		$this->assignRef('itemsgraph'		  , $itemsgraph);
		$this->assignRef('unpopular'		  , $unpopular);
		$this->assignRef('totalitemspublish'  , $totalitemspublish);
		$this->assignRef('totalitemsunpublish', $totalitemsunpublish);
		$this->assignRef('totalitemswaiting'  , $totalitemswaiting);
		$this->assignRef('totalitemsprogress' , $totalitemsprogress);
		$this->assignRef('metadescription'    , $metadescription);
		$this->assignRef('metakeywords'    , $metakeywords);
		
		// ************************************************** New data*********************************************************************************************************************//

		$this->sidebar = FLEXI_J30GE ? JHtmlSidebar::render() : null;
		parent::display($tpl);
	}
}