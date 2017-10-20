<?php
/**
 * @version 1.5 stable $Id: view.html.php 1901 2014-05-07 02:37:25Z ggppdk $
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

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('legacy.view.legacy');
use Joomla\String\StringHelper;

/**
 * View class for the FLEXIcontent categories screen
 *
 * @package Joomla
 * @subpackage FLEXIcontent
 * @since 1.0
 */
class FlexicontentViewFields extends JViewLegacy 
{
	function display( $tpl = null )
	{
		// ********************
		// Initialise variables
		// ********************

		$app     = JFactory::getApplication();
		$jinput  = $app->input;
		$option  = $jinput->get('option', '', 'cmd');
		$view    = $jinput->get('view', '', 'cmd');

		$cparams  = JComponentHelper::getParams( 'com_flexicontent' );
		$user     = JFactory::getUser();
		$db       = JFactory::getDBO();
		$document = JFactory::getDocument();
		
		// Get model
		$model = $this->getModel();

		$print_logging_info = $cparams->get('print_logging_info');
		if ( $print_logging_info )  global $fc_run_times;



		// ***********
		// Get filters
		// ***********

		$count_filters = 0;

		// Various filters
		$filter_fieldtype = $model->getState( 'filter_fieldtype' );
		$filter_assigned  = $model->getState( 'filter_assigned' );
		$filter_type      = $model->getState( 'filter_type' );
		$filter_state     = $model->getState( 'filter_state' );
		$filter_access    = $model->getState( 'filter_access' );

		if ($filter_fieldtype) $count_filters++;
		if ($filter_assigned) $count_filters++;
		if ($filter_type) $count_filters++;
		if ($filter_state) $count_filters++;
		if ($filter_access) $count_filters++;
		
		// Text search
		$search = $model->getState( 'search' );
		$search = $db->escape( StringHelper::trim(StringHelper::strtolower( $search ) ) );
		
		// Order and order direction
		$filter_order     = $model->getState( 'filter_order' );
		$filter_order_Dir = $model->getState( 'filter_order_Dir' );


		// ****************************
		// Important usability messages
		// ****************************

		if ( $cparams->get('show_usability_messages', 1) )
		{
			/*$conf_link = '<a href="index.php?option=com_config&view=component&component=com_flexicontent&path=" class="btn btn-info btn-small">'.JText::_("FLEXI_CONFIG").'</a>';
			$notice_content_type_order = $app->getUserStateFromRequest( $option.'.'.$view.'.notice_content_type_order',	'notice_content_type_order',	0, 'int' );
			if (!$notice_content_type_order)
			{
				$app->setUserState( $option.'.'.$view.'.notice_content_type_order', 1 );
				JFactory::getDocument()->addStyleDeclaration("#system-message-container .alert.alert-info > .alert-heading { display:none; }");
				
				$disable_use_notices = '<span class="fc-nowrap-box fc-disable-notices-box">'. JText::_('FLEXI_USABILITY_MESSAGES_TURN_OFF_IN').' '.$conf_link.'</span><div class="fcclear"></div>';
				$app->enqueueMessage(JText::_('FLEXI_FILTER_BY_TYPE_BEFORE_ACTIONS') .' '. $disable_use_notices, 'notice');
			}*/
		}
		
		$this->minihelp = '
			<div id="fc-mini-help" class="fc-mssg fc-info" style="display:none;">
				'.JText::_('FLEXI_FILTER_BY_TYPE_BEFORE_ACTIONS') .' <br/><br/>
				'.JText::_('FLEXI_FIELDS_ORDER_NO_TYPE_FILTER_ACTIVE').'
			</div>
		';
		
		
		// **************************
		// Add css and js to document
		// **************************
		
		flexicontent_html::loadFramework('select2');
		//JHTML::_('behavior.tooltip');
		
		!JFactory::getLanguage()->isRtl()
			? $document->addStyleSheetVersion(JURI::base(true).'/components/com_flexicontent/assets/css/flexicontentbackend.css', FLEXI_VHASH)
			: $document->addStyleSheetVersion(JURI::base(true).'/components/com_flexicontent/assets/css/flexicontentbackend_rtl.css', FLEXI_VHASH);
		!JFactory::getLanguage()->isRtl()
			? $document->addStyleSheetVersion(JURI::base(true).'/components/com_flexicontent/assets/css/j3x.css', FLEXI_VHASH)
			: $document->addStyleSheetVersion(JURI::base(true).'/components/com_flexicontent/assets/css/j3x_rtl.css', FLEXI_VHASH);
		
		
		
		// *****************************
		// Get user's global permissions
		// *****************************
		
		$perms = FlexicontentHelperPerm::getPerm();
		
		
		
		// ************************
		// Create Submenu & Toolbar
		// ************************
		
		// Create Submenu (and also check access to current view)
		FLEXIUtilities::ManagerSideMenu('CanFields');
		
		// Create document/toolbar titles
		$doc_title = JText::_( 'FLEXI_FIELDS' );
		$site_title = $document->getTitle();
		JToolBarHelper::title( $doc_title, 'fields' );
		$document->setTitle($doc_title .' - '. $site_title);

		// Create the toolbar
		$js = "jQuery(document).ready(function(){";

		$contrl = "fields.";

		if ($perms->CanEditField)
		{
			$ctrl_task = '&task=fields.selectsearchflag';
			$popup_load_url = JURI::base().'index.php?option=com_flexicontent'.$ctrl_task.'&tmpl=component';
			
			$btn_name = 'basicindex';
			$btn_task = '';
			$full_js  = ';';
			$extra_js = '';
			flexicontent_html::addToolBarButton(
				JText::_('FLEXI_TOGGLE_SEARCH_FLAG'), $btn_name, $full_js, $msg_alert=JText::_('FLEXI_SELECT_FIELDS_TO_TOGGLE_PROPERTY'), $msg_confirm='',
				$btn_task, $extra_js, $btn_list=true, $btn_menu=true, $btn_confirm=false, $btn_class="");
			
			$js .= "
				jQuery('#toolbar-basicindex a.toolbar, #toolbar-basicindex button')
					.attr('onclick', 'javascript:;')
					.attr('href', '".$popup_load_url."')
					.attr('rel', '{handler: \'iframe\', size: {x: 800, y: 340}, onClose: function() {}}');
			";
			JHtml::_('behavior.modal', '#toolbar-basicindex a.toolbar, #toolbar-basicindex button');
		}
		
		if ($perms->CanCopyFields)
		{
			JToolBarHelper::custom( $contrl.'copy', 'copy.png', 'copy_f2.png', 'FLEXI_COPY' );
			JToolBarHelper::custom( $contrl.'copy_wvalues', 'copy_wvalues.png', 'copy_f2.png', 'FLEXI_COPY_WITH_VALUES' );
			JToolBarHelper::divider();
		}

		JToolBarHelper::publishList($contrl.'publish');
		JToolBarHelper::unpublishList($contrl.'unpublish');
		if ($perms->CanAddField) {
			JToolBarHelper::addNew($contrl.'add');
		}
		if ($perms->CanEditField)
		{
			JToolBarHelper::editList($contrl.'edit');
		}
		if ($perms->CanDeleteField)
		{
			//JToolBarHelper::deleteList(JText::_('FLEXI_ARE_YOU_SURE'), $contrl.'remove');
			$msg_alert   = JText::sprintf('FLEXI_SELECT_LIST_ITEMS_TO', JText::_('FLEXI_DELETE'));
			$msg_confirm = JText::_('FLEXI_ITEMS_DELETE_CONFIRM');
			$btn_task    = $contrl.'remove';
			$extra_js    = "";
			flexicontent_html::addToolBarButton(
				'FLEXI_DELETE', 'delete', '', $msg_alert, $msg_confirm,
				$btn_task, $extra_js, $btn_list=true, $btn_menu=true, $btn_confirm=true);
		}
		JToolbarHelper::checkin($contrl.'checkin');

		$appsman_path = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_flexicontent'.DS.'views'.DS.'appsman';
		if (file_exists($appsman_path))
		{
			$btn_icon = 'icon-download';
			$btn_name = 'download';
			$btn_task    = 'appsman.exportxml';
			$extra_js    = " var f=document.getElementById('adminForm'); f.elements['view'].value='appsman'; jQuery('<input>').attr({type: 'hidden', name: 'table', value: 'flexicontent_fields'}).appendTo(jQuery(f));";
			flexicontent_html::addToolBarButton(
				'Export now',
				$btn_name, $full_js='', $msg_alert='', $msg_confirm=JText::_('FLEXI_EXPORT_NOW_AS_XML'),
				$btn_task, $extra_js, $btn_list=false, $btn_menu=true, $btn_confirm=true, $btn_class="btn-info", $btn_icon);
			
			$btn_icon = 'icon-box-add';
			$btn_name = 'box-add';
			$btn_task    = 'appsman.addtoexport';
			$extra_js    = " var f=document.getElementById('adminForm'); f.elements['view'].value='appsman'; jQuery('<input>').attr({type: 'hidden', name: 'table', value: 'flexicontent_fields'}).appendTo(jQuery(f));";
			flexicontent_html::addToolBarButton(
				'Add to export',
				$btn_name, $full_js='', $msg_alert='', $msg_confirm=JText::_('FLEXI_ADD_TO_EXPORT_LIST'),
				$btn_task, $extra_js, $btn_list=false, $btn_menu=true, $btn_confirm=true, $btn_class="btn-info", $btn_icon);
		}
		
		/*$btn_icon = 'icon-download';
		$btn_name = 'download';
		$btn_task    = 'fields.exportsql';
		$extra_js    = "";
		flexicontent_html::addToolBarButton(
			'Export SQL', $btn_name, $full_js='', $msg_alert='', $msg_confirm='Field\'s configuration will be exported as SQL',
			$btn_task, $extra_js, $btn_list=false, $btn_menu=true, $btn_confirm=true, $btn_class="btn-warning", $btn_icon);
		
		
		$btn_icon = 'icon-download';
		$btn_name = 'download';
		$btn_task    = 'fields.exportcsv';
		$extra_js    = "";
		flexicontent_html::addToolBarButton(
			'Export CSV', $btn_name, $full_js='', $msg_alert='', $msg_confirm='Field\'s configuration will be exported as CSV',
			$btn_task, $extra_js, $btn_list=false, $btn_menu=true, $btn_confirm=true, $btn_class="btn-warning", $btn_icon);*/
		
		
		if ($perms->CanConfig) {
			JToolBarHelper::divider(); JToolBarHelper::spacer();
			$session = JFactory::getSession();
			$fc_screen_width = (int) $session->get('fc_screen_width', 0, 'flexicontent');
			$_width  = ($fc_screen_width && $fc_screen_width-84 > 940 ) ? ($fc_screen_width-84 > 1400 ? 1400 : $fc_screen_width-84 ) : 940;
			$fc_screen_height = (int) $session->get('fc_screen_height', 0, 'flexicontent');
			$_height = ($fc_screen_height && $fc_screen_height-128 > 550 ) ? ($fc_screen_height-128 > 1000 ? 1000 : $fc_screen_height-128 ) : 550;
			JToolBarHelper::preferences('com_flexicontent', $_height, $_width, 'Configuration');
		}
		
		$js .= "});";
		$document->addScriptDeclaration($js);
		
		
		// Get data from the model
		if ( $print_logging_info )  $start_microtime = microtime(true);
		$rows       = $this->get( 'Items' );
		$allrows    = $this->get( 'AllItems' );
		if ( $print_logging_info ) @$fc_run_times['execute_main_query'] += round(1000000 * 10 * (microtime(true) - $start_microtime)) / 10;
		$pagination = $this->get( 'Pagination' ); // Pagination
		$types      = $this->get( 'Typeslist' );  // Content types
		$fieldTypes = flexicontent_db::getFieldTypes($_grouped = true, $_usage=true, $_published=false);  // Field types with content type ASSIGNMENT COUNTING
		
		
		$lists = array();
		
		// build item-type filter
		$lists['filter_type'] = ($filter_type|| 1 ? '<div class="add-on">'.JText::_('FLEXI_TYPE').'</div>' : '').
			flexicontent_html::buildtypesselect($types, 'filter_type', $filter_type, '-'/*2*/, 'class="use_select2_lib" size="1" onchange="document.adminForm.limitstart.value=0; Joomla.submitform()"', 'filter_type');
		
		
		// build orphaned/assigned filter
		$assigned 	= array();
		$assigned[] = JHTML::_('select.option',  '', '-'/*JText::_( 'FLEXI_ALL_FIELDS' )*/ );
		$assigned[] = JHTML::_('select.option',  'O', JText::_( 'FLEXI_ORPHANED' ) );
		$assigned[] = JHTML::_('select.option',  'A', JText::_( 'FLEXI_ASSIGNED' ) );
		
		$lists['assigned'] = ($filter_assigned || 1 ? '<div class="add-on">'.JText::_('FLEXI_ASSIGNED').'</div>' : '').
			JHTML::_('select.genericlist', $assigned, 'filter_assigned', 'class="use_select2_lib" size="1" onchange="document.adminForm.limitstart.value=0; Joomla.submitform()"', 'value', 'text', $filter_assigned );
		
		
		// build field-type filter
		$ALL = StringHelper::strtoupper(JText::_( 'FLEXI_ALL' )) . ' : ';
		$fftypes = array();
		$fftypes[] = array('value'=>'', 'text'=>'-'/*JText::_( 'FLEXI_ALL_FIELDS_TYPE' )*/ );
		$fftypes[] = array('value'=>'BV', 'text'=>$ALL . JText::_( 'FLEXI_BACKEND_FIELDS' ) );
		$fftypes[] = array('value'=>'C',  'text'=>$ALL . JText::_( 'FLEXI_CORE_FIELDS' ) );
		$fftypes[] = array('value'=>'NC', 'text'=>$ALL . JText::_( 'FLEXI_CUSTOM_NON_CORE_FIELDS' ));
		foreach ($fieldTypes as $field_group => $ft_types) {
			$fftypes[] = $field_group;
			foreach ($ft_types as $field_type => $ftdata) {
				$fftypes[] = array('value'=>$ftdata->field_type, 'text'=>'-'.$ftdata->assigned.'- '. $ftdata->friendly);
			}
			$fftypes[] = '';
		}
		$lists['fftype'] = ($filter_fieldtype || 1 ? '<div class="add-on">'.JText::_('FLEXI_FIELD_TYPE').'</div>' : '').
			flexicontent_html::buildfieldtypeslist($fftypes, 'filter_fieldtype', $filter_fieldtype, ($_grouped ? 1 : 0), 'class="use_select2_lib" size="1" onchange="document.adminForm.limitstart.value=0; Joomla.submitform()"');
		
		
		// build publication state filter
		$states 	= array();
		$states[] = JHTML::_('select.option',  '', '-'/*JText::_( 'FLEXI_SELECT_STATE' )*/ );
		$states[] = JHTML::_('select.option',  'P', JText::_( 'FLEXI_PUBLISHED' ) );
		$states[] = JHTML::_('select.option',  'U', JText::_( 'FLEXI_UNPUBLISHED' ) );
		//$states[] = JHTML::_('select.option',  '-2', JText::_( 'FLEXI_TRASHED' ) );
		
		$lists['state'] = ($filter_state || 1 ? '<div class="add-on">'.JText::_('FLEXI_STATE').'</div>' : '').
			JHTML::_('select.genericlist', $states, 'filter_state', 'class="use_select2_lib" size="1" onchange="document.adminForm.limitstart.value=0; Joomla.submitform()"', 'value', 'text', $filter_state );
			//JHTML::_('grid.state', $filter_state );
		
		
		// build access level filter
		$options = JHtml::_('access.assetgroups');
		array_unshift($options, JHtml::_('select.option', '', '-'/*JText::_('JOPTION_SELECT_ACCESS')*/) );
		$fieldname =  $elementid = 'filter_access';
		$attribs = 'class="use_select2_lib" onchange="document.adminForm.limitstart.value=0; Joomla.submitform()"';
		$lists['access'] = ($filter_access || 1 ? '<div class="add-on">'.JText::_('FLEXI_ACCESS').'</div>' : '').
			JHTML::_('select.genericlist', $options, $fieldname, $attribs, 'value', 'text', $filter_access, $elementid, $translate=true );
		
		
		// text search filter
		$lists['search']= $search;
		
		
		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;
		$ordering = ($filter_type == '' || $filter_type == 0)
			? ($lists['order'] == 't.ordering')
			: ($lists['order'] == 'typeordering');
		
		
		//assign data to template
		$this->count_filters = $count_filters;
		$this->permission = $perms;
		$this->filter_type = $filter_type;

		$this->lists = $lists;
		$this->rows = $rows;
		$this->allrows = $allrows;
		$this->types = $types;
		
		$this->ordering = $ordering;
		$this->pagination = $pagination;
		
		$this->option = $option;
		$this->view = $view;
		
		$this->sidebar = FLEXI_J30GE ? JHtmlSidebar::render() : null;
		parent::display($tpl);
	}
}
?>