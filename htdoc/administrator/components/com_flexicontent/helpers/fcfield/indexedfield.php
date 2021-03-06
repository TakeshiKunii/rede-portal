<?php
/**
 * @package         FLEXIcontent
 * @version         3.2
 * 
 * @author          Emmanuel Danan, Georgios Papadakis, Yannick Berges, others, see contributor page
 * @link            http://www.flexicontent.com
 * @copyright       Copyright � 2017, FLEXIcontent team, All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined( '_JEXEC' ) or die( 'Restricted access' );
JLoader::register('FCField', JPATH_ADMINISTRATOR . '/components/com_flexicontent/helpers/fcfield/parentfield.php');

class FCIndexedField extends FCField
{
	var $task_callable = array('getCascadedField');

	static $extra_props = array();
	static $valueIsArr = 0;
	static $isDropDown = 0;
	static $promptEnabled = 0;
	static $usesImages = 0;

	static $cascaded_values = array();

	// ***********
	// CONSTRUCTOR
	// ***********
	
	function __construct( &$subject, $params )
	{
		$fieldtype = str_replace('plgflexicontent_fields', '', strtolower(get_class($this)));
		static::$field_types = array($fieldtype);

		parent::__construct( $subject, $params );
	}
	
	
	
	// *******************************************
	// DISPLAY methods, item form & frontend views
	// *******************************************
	
	// Method to create field's HTML display for item form
	function onDisplayField(&$field, &$item)
	{
		if ( !in_array($field->field_type, static::$field_types) ) return;
		
		$field->label = JText::_($field->label);
		$use_ingroup = $field->parameters->get('use_ingroup', 0);
		$ajax = !empty($field->isAjax);
		if (!isset($field->formhidden_grp)) $field->formhidden_grp = $field->formhidden;
		if ($use_ingroup) $field->formhidden = 3;
		if ($use_ingroup && empty($field->ingroup) && !$ajax) return;
		
		// initialize framework objects and other variables
		$document = JFactory::getDocument();
		$cparams  = JComponentHelper::getParams( 'com_flexicontent' );
		
		$tooltip_class = 'hasTooltip';
		$add_on_class    = $cparams->get('bootstrap_ver', 2)==2  ?  'add-on' : 'input-group-addon';
		$input_grp_class = $cparams->get('bootstrap_ver', 2)==2  ?  'input-append input-prepend' : 'input-group';
		$form_font_icons = $cparams->get('form_font_icons', 1);
		$font_icon_class = $form_font_icons ? ' fcfont-icon' : '';

		// some parameter shortcuts
		$sql_mode				= $field->parameters->get( 'sql_mode', 0 ) ;
		$field_elements	= $field->parameters->get( 'field_elements' ) ;
		$cascade_after  = (int) $field->parameters->get('cascade_after', 0);
		$sortable       = static::$valueIsArr && (int) $field->parameters->get('sortable', 0);
		
		
		// ****************
		// Number of values
		// ****************
		$multiple     = $use_ingroup || (int) $field->parameters->get( 'allow_multiple', 0 ) ;
		$required     = $field->parameters->get( 'required', 0 ) ;
		$min_values   = $use_ingroup || !static::$valueIsArr ? 0 : (int) $field->parameters->get( 'min_values', 0 ) ;
		$max_values   = $use_ingroup ? 0 : (int) $field->parameters->get( 'max_values', 0 ) ;
		$exact_values	= $use_ingroup || !static::$valueIsArr ? 0 : (int) $field->parameters->get( 'exact_values', 0 ) ;
		$add_position = (int) $field->parameters->get( 'add_position', 3 ) ;

		// Sanitize limitations
		if ($required && !$min_values && static::$valueIsArr) $min_values = 1;  // Comment this to allow simpler 'required' validation
		if ($exact_values) $max_values = $min_values = $exact_values;


		// ***
		// *** Value handling
		// ***

		// Default value
		$default_values = !$ajax ? $this->getFormDefaultValues($field, $item) : array('');


		// ***
		// *** Input field configuration
		// ***

		$display_label_form = (int) $field->parameters->get( 'display_label_form', 1 ) ;
		$display_as_select = static::$isDropDown || (int) $field->parameters->get( 'display_as_select', 0 );

		if ($display_as_select)
		{
			// DISPLAY using select2 JS
			$use_jslib = $field->parameters->get( 'use_jslib', 1 ) ;
			$use_select2 = $use_jslib==1 || $use_jslib==2;
			if ($sortable && !$use_select2)
			{
				$use_select2 = true;
				$error_msg = '<div class="alert alert-warning fc-small fc-iblock">Sortable property enabled, please also enable using select2 JS (usage of it forced to ON)</div><div class="fcclear"></div>';
			}
			static $select2_added = null;
		  if ( $use_select2 && $select2_added === null ) $select2_added = flexicontent_html::loadFramework('select2');

			// Fields: select / selectmultiple and fields: radioimage / checkboximage displayed as drop-down select in item edit form
			$usefirstoption  = $field->parameters->get( 'usefirstoption', 1 ) ;
			$firstoptiontext = $field->parameters->get( 'firstoptiontext', 'FLEXI_SELECT' ) ;

			// Useful when displaying as multi-select without select2 JS
			$size = $field->parameters->get( 'size', 6 ) ;
		}
		else
		{
			// DISPLAY using prettyCheckable JS
			$use_jslib = $field->parameters->get( 'use_jslib', 2 ) ;
			$use_prettycheckable = $use_jslib==2;
			static $prettycheckable_added = null;
		  if ( $use_prettycheckable && $prettycheckable_added === null ) $prettycheckable_added = flexicontent_html::loadFramework('prettyCheckable');
			$placeInsideLabel = static::$usesImages && !($use_prettycheckable && $prettycheckable_added);
		}


		// Custom HTML placed before / after form field
		$opentag   = $field->parameters->get( 'opentag_form', '' ) ;
		$closetag  = $field->parameters->get( 'closetag_form', '' ) ;

		// For radio /checkbox display
		if (!$display_as_select)
		{
			$fftype = static::$valueIsArr ? 'checkbox' : 'radio';

			// Applicable only for radioimage/checkboximage fields, it allows a more compact display in item form
			$form_vals_display = static::$usesImages ? (int) $field->parameters->get( 'form_vals_display', 1 ) : 0 ;  // 0: label, 1: image, 2: both

			// Prefix - Suffix - Separator (item FORM) parameters, for the checkbox/radio elements
			$pretext   = $field->parameters->get( 'pretext_form', '' ) ;
			$posttext  = $field->parameters->get( 'posttext_form', '' ) ;
			$separator = $field->parameters->get( 'separator', 0 ) ;

			switch($separator)
			{
				case 0:
				$separator = '&nbsp;';
				break;

				case 1:
				$separator = '<br />';
				break;

				case 2:
				$separator = '&nbsp;|&nbsp;';
				break;

				case 3:
				$separator = ',&nbsp;';
				break;

				case 4:
				$separator = $closetag . $opentag;
				break;

				default:
				$separator = '&nbsp;';
				break;
			}
		}


		// Initialise property with default value
		if ( !$field->value || (count($field->value)==1 && $field->value[0] === null) )
		{
			$field->value = static::$valueIsArr && !empty($field->ingroup)
				? array($default_values)
				: $default_values;
		}
		
		// CSS classes of value container
		$value_classes  = 'fcfieldval_container valuebox fcfieldval_container_'.$field->id;
		
		// Field name and HTML TAG id
		$valueholder_nm = 'custom[_fcfield_valueholder_]['.$field->name.']';
		$valueholder_id = 'custom__fcfield_valueholder__'.$field->name;
		$fieldname = 'custom['.$field->name.']';
		$elementid = 'custom_'.$field->name;

		// Create the attributes of the form field
		$input_classes = array();
		$attribs = '';

		// Extra attributes for multi-value field
		if (static::$valueIsArr)
		{
			if ($exact_values)
				$attribs .= ' data-exact_values="'.$exact_values.'" ';
			else
			{
				if ($max_values)    $attribs .= ' data-max_values="'.$max_values.'" ';
				if ($min_values)    $attribs .= ' data-min_values="'.$min_values.'" ';
			}
		}

		// Attributes if displaying as radio / checkbox set
		if (!$display_as_select)
		{
			if ($use_prettycheckable && $prettycheckable_added)
			{
				$input_classes[] = 'use_prettycheckable';
				$attribs .= static::$usesImages ? ' data-customClass="fcradiocheckimage"' : ' data-customClass="fcradiocheck"';
			}
			if (static::$valueIsArr)
			{
				if ($max_values || $min_values || $exact_values)
					$input_classes[] = 'validate-cboxlimitations';
				else if ($required)
					$input_classes[] = 'required validate-checkbox';  // do basic checkbox-required validation
			}
			else if ($required)
				$input_classes[] = 'required validate-radio';  // do basic radio-required validation

			// Attributes for input-labels
			$label_class = 'fccheckradio_lbl ' . ($form_vals_display==1 ? $tooltip_class : '');
			$label_style = static::$usesImages ? 'vertical-align: unset!important;' : '';  // fix for image placement inside label
		}

		// Attributes if displaying as select
		else
		{
			$input_classes[] = 'fcfield_textselval';
			if ($use_jslib && $select2_added) $input_classes[] = 'use_select2_lib';
			if ($required) $input_classes[] = 'required';

			// Attributes multi-select field
			if (static::$valueIsArr)
			{
				$add_placeholder = $display_label_form==-1 ? 1 : $field->parameters->get( 'usefirstoption', 1 );
				$placeholder = $display_label_form==-1 ? $field->label : JText::_($field->parameters->get( 'firstoptiontext', 'FLEXI_SELECT' ));

				$attribs .=
					' multiple="multiple"' . ($size ? ' size="'.$size.'"' : '')
					. ($add_placeholder ? ' data-placeholder="'.$placeholder.'" ' : '');
			}

			// Attribute for default value(s)
			if (!empty($default_values))
			{
				$attribs .= ' data-defvals="'.implode('|||', $default_values).'" ';
			}

			// Client-side Validation and sortable class
			if (static::$valueIsArr)
			{
				if ($max_values || $min_values || $exact_values)
				{
					$input_classes[] = 'validate-sellimitations';
				}
				if ($sortable)
				{
					$input_classes[] = 'fc_select2_sortable';
				}
			}
		}

		// Form element classes
		$input_classes = implode(' ', $input_classes);



		// *********************************************************************************************
		// Handle adding the needed JS code to CASCADE (listen to) changes of the dependent master field
		// *********************************************************************************************

		JText::script('FLEXI_PLEASE_WAIT',true);
		$js = "";
		$css = "";

		if ($cascade_after && !$ajax)
		{
			$byIds = FlexicontentFields::indexFieldsByIds($item->fields, $item);
			
			if ( isset($byIds[$cascade_after]) )
			{
				$master_field = $byIds[$cascade_after];
				$cascade_prompt = $field->parameters->get('cascade_prompt', '');
				$cascade_prompt = $cascade_prompt ? JText::_($cascade_prompt) : JText::_('FLEXI_PLEASE_SELECT') . ': ' . JText::_($master_field->label);
				
				$srcELid = 'custom_' . $master_field->name;
				$trgELid = $elementid;
				$single_master = ! $master_field->parameters->get('use_ingroup', 0) && !$master_field->parameters->get('multiple', 0);
				
				// Get values of cascade (on) source field
				$field->valgrps = $master_field->value ?:
					(isset(static::$cascaded_values[$master_field->id]) ? static::$cascaded_values[$master_field->id] : array());
					// array();
					// $this->getFormDefaultValues($master_field, $item);
				//echo ' SLAVE: ' . $field->label . ' with master values : ' . print_r($field->valgrps, true) . '<br/>';
				foreach($field->valgrps as & $vg)
				{
					if (!is_array($vg))
					{
						$vg = $this->unserialize_array($vg, $force_array=true, $force_value=true);
					}
				}
				unset($vg);
			}
			else
			{
				foreach($field->value as $value)
				{
					$field->html[] = '<div class="alert alert-error fc-small fc-iblock">Error, master field no: '.$cascade_after.' is not assigned to current item type or was unpublished</div><br/>';
				}
				$cascade_after = 0;
				return;
			}
		}

		else if ($cascade_after && $ajax)
		{
			$field->valgrps = isset($field->valgrps) ? $field->valgrps : array();
			$field->valgrps = is_array($field->valgrps) ? $field->valgrps : preg_split("/\s*,\s*/u", trim($field->valgrps));
		}


		// ***
		// *** Handle multiple records
		// ***
		
		if ($multiple && !$ajax)
		{
			// Add the drag and drop sorting feature
			if (!$use_ingroup) $js .= "
			jQuery(document).ready(function(){
				jQuery('#sortables_".$field->id."').sortable({
					handle: '.fcfield-drag-handle',
					/*containment: 'parent',*/
					tolerance: 'pointer'
					".($field->parameters->get('fields_box_placing', 1) ? "
					,start: function(e) {
						//jQuery(e.target).children().css('float', 'left');
						//fc_setEqualHeights(jQuery(e.target), 0);
					}
					,stop: function(e) {
						//jQuery(e.target).children().css({'float': 'none', 'min-height': '', 'height': ''});
					}
					" : '')."
				});
			});
			";
			
			if ($max_values) JText::script("FLEXI_FIELD_MAX_ALLOWED_VALUES_REACHED", true);
			$js .= "
			function addField".$field->id."(el, groupval_box, fieldval_box, params)
			{
				var insert_before   = (typeof params!== 'undefined' && typeof params.insert_before   !== 'undefined') ? params.insert_before   : 0;
				var remove_previous = (typeof params!== 'undefined' && typeof params.remove_previous !== 'undefined') ? params.remove_previous : 0;
				var scroll_visible  = (typeof params!== 'undefined' && typeof params.scroll_visible  !== 'undefined') ? params.scroll_visible  : 1;
				var animate_visible = (typeof params!== 'undefined' && typeof params.animate_visible !== 'undefined') ? params.animate_visible : 1;
				
				if(!remove_previous && (rowCount".$field->id." >= maxValues".$field->id.") && (maxValues".$field->id." != 0)) {
					alert(Joomla.JText._('FLEXI_FIELD_MAX_ALLOWED_VALUES_REACHED') + maxValues".$field->id.");
					return 'cancel';
				}
				
				// Find last container of fields and clone it to create a new container of fields
				var lastField = fieldval_box ? fieldval_box : jQuery(el).prev().children().last();
				var newField  = lastField.clone();
				
			".(!$display_as_select ? "
				// Remove HTML added by prettyCheckable JS, from the dupicate new INPUT SET
				var prettyContainers = newField.find('.prettyradio, .prettycheckbox');
				prettyContainers.find('input, label').each(function() {
					var el = jQuery(this);
					el.insertAfter(el.parent());
				});
				prettyContainers.remove();
				
				// Update INPUT SET container id
				newField.find('.fc_input_set').attr('id', '".$elementid."_'+uniqueRowNum".$field->id.");
				
				// Update the new INPUT SET
				var theSet = newField.find('input:radio, input:checkbox');
				//if(window.console) window.console.log('theSet.length: ' + theSet.length);
				var nr = 0;
				theSet.each(function() {
					var elem = jQuery(this);
					elem.attr('name', '".$fieldname."['+uniqueRowNum".$field->id."+']".(static::$valueIsArr ? '[]' : '')."');
					elem.attr('id', '".$elementid."_'+uniqueRowNum".$field->id."+'_'+nr);
					elem.attr('class', '".$elementid."_'+uniqueRowNum".$field->id." + ' " . $input_classes . "');
					elem.attr('data-is-defval') ?
						elem.attr('checked', 'checked') :
						elem.removeAttr('checked') ;

					".($use_prettycheckable && $prettycheckable_added ?
						"elem.attr('data-element-grpid', '".$elementid."_'+uniqueRowNum".$field->id.");" :
						"elem.attr('data-element-grpid', '".$elementid."_'+uniqueRowNum".$field->id.");" )."

					".(!$placeInsideLabel ?
						"elem.next('label').attr('for', '".$elementid."_'+uniqueRowNum".$field->id."+'_'+nr);" :
						"elem.closest('label').attr('for', '".$elementid."_'+uniqueRowNum".$field->id."+'_'+nr);" )."   // special case for field with image place input and image inside labels
					nr++;
				});
				
				// Reapply prettyCheckable JS 
				newField.find('.use_prettycheckable').each(function() {
					var elem = jQuery(this);
					var lbl = elem.prev('label');
					var lbl_html = lbl.html();
					lbl.remove();
					elem.prettyCheckable({ label: lbl_html });
				});

			" : "

				// Update the new select field
				var elem= newField.find('select.fcfield_textselval').first();
				var defvals = elem.attr('data-defvals');
				if ( defvals && defvals.length )
				{
					jQuery.each(defvals.split('|||'), function(i, val){
						elem.find('option[value=\"' + val + '\"]').attr('selected', 'selected');
					});
				}
				else elem.val('');
				elem.attr('name', '".$fieldname."['+uniqueRowNum".$field->id."+']".(static::$valueIsArr ? '[]' : '')."');
				elem.attr('id', '".$elementid."_'+uniqueRowNum".$field->id.");
				elem.attr('data-uniqueRowNum', uniqueRowNum".$field->id.");
				
				// Re-init any select2 elements
				var has_select2 = newField.find('div.select2-container').length != 0;
				if (has_select2) {
					newField.find('div.select2-container').remove();
					newField.find('select.use_select2_lib').select2('destroy').show();
					fc_attachSelect2(newField);
				}
			")."

				// Update value holder
				newField.find('.fcfield_value_holder')
					.attr('id', '".$valueholder_id."_'+uniqueRowNum".$field->id.")
					.attr('name', '".$valueholder_nm."['+uniqueRowNum".$field->id."+']');
				";
			
			// Add new field to DOM
			$js .= "
				lastField ?
					(insert_before ? newField.insertBefore( lastField ) : newField.insertAfter( lastField ) ) :
					newField.appendTo( jQuery('#sortables_".$field->id."') ) ;
				if (remove_previous) lastField.remove();
				";
			
			// Listen to the changes of depends-on-master field
			if ($cascade_after)
			{
				$mvno = $single_master ? "'0'" : "rowNo";
				$js .= "
				fc_cascade_field_funcs['".$srcELid."_'+uniqueRowNum".$field->id."] = function(rowNo){
					return function () {
						fcCascadedField(".$field->id.", '".$item->id."', '".$field->field_type."', 'select#".$srcELid."_'+".$mvno."+', input.".$srcELid."_'+".$mvno.", '".$trgELid."_'+rowNo, '".htmlspecialchars( $cascade_prompt, ENT_COMPAT, 'UTF-8' )."', ".static::$promptEnabled.", rowNo);
					}
				}(uniqueRowNum".$field->id.");
				fc_cascade_field_funcs['".$srcELid."_'+uniqueRowNum".$field->id."]();
				";
			}

			else
			{
				$js .= "
				jQuery('#".$elementid."_'+uniqueRowNum".$field->id.").each(function() {
					var el = jQuery(this);
					setTimeout(function(){
						el.trigger('change');
					}, 20); // >0 is enough
				});
				";
			}

			// Add new element to sortable objects (if field not in group)
			if (!$use_ingroup) $js .= "
				//jQuery('#sortables_".$field->id."').sortable('refresh');  // Refresh was done appendTo ?
				";
			
			// Show new field, increment counters
			$js .="
				//newField.fadeOut({ duration: 400, easing: 'swing' }).fadeIn({ duration: 200, easing: 'swing' });
				if (scroll_visible) fc_scrollIntoView(newField, 1);
				if (animate_visible) newField.css({opacity: 0.1}).animate({ opacity: 1 }, 800, function() { jQuery(this).css('opacity', ''); });
				
				// Enable tooltips on new element
				newField.find('.hasTooltip').tooltip({html: true, container: newField});
				newField.find('.hasPopover').popover({html: true, container: newField, trigger : 'hover focus'});

				// Attach form validation on new element
				fc_validationAttach(newField);
				
				rowCount".$field->id."++;       // incremented / decremented
				uniqueRowNum".$field->id."++;   // incremented only
			}

			function deleteField".$field->id."(el, groupval_box, fieldval_box)
			{
				// Disable clicks on remove button, so that it is not reclicked, while we do the field value hide effect (before DOM removal of field value)
				var btn = fieldval_box ? false : jQuery(el);
				if (btn && rowCount".$field->id." > 1) btn.css('pointer-events', 'none').off('click');

				// Find field value container
				var row = fieldval_box ? fieldval_box : jQuery(el).closest('li');
				
				// Add empty container if last element, instantly removing the given field value container
				if(rowCount".$field->id." == 1)
					addField".$field->id."(null, groupval_box, row, {remove_previous: 1, scroll_visible: 0, animate_visible: 0});
				
				// Remove if not last one, if it is last one, we issued a replace (copy,empty new,delete old) above
				if (rowCount".$field->id." > 1)
				{
					// Destroy the remove/add/etc buttons, so that they are not reclicked, while we do the field value hide effect (before DOM removal of field value)
					row.find('.fcfield-delvalue').remove();
					row.find('.fcfield-expand-view').remove();
					row.find('.fcfield-insertvalue').remove();
					row.find('.fcfield-drag-handle').remove();
					// Do hide effect then remove from DOM
					row.slideUp(400, function(){ jQuery(this).remove(); });
					rowCount".$field->id."--;
				}
			}
			";
			
			$css .= '';
			
			$remove_button = '<span class="' . $add_on_class . ' fcfield-delvalue ' . $font_icon_class . '" title="'.JText::_( 'FLEXI_REMOVE_VALUE' ).'" onclick="deleteField'.$field->id.'(this);"></span>';
			$move2 = '<span class="' . $add_on_class . ' fcfield-drag-handle ' . $font_icon_class . '" title="'.JText::_( 'FLEXI_CLICK_TO_DRAG' ).'"></span>';
			$add_here = '';
			$add_here .= $add_position==2 || $add_position==3 ? '<span class="' . $add_on_class . ' fcfield-insertvalue fc_before ' . $font_icon_class . '" onclick="addField'.$field->id.'(null, jQuery(this).closest(\'ul\'), jQuery(this).closest(\'li\'), {insert_before: 1});" title="'.JText::_( 'FLEXI_ADD_BEFORE' ).'"></span> ' : '';
			$add_here .= $add_position==1 || $add_position==3 ? '<span class="' . $add_on_class . ' fcfield-insertvalue fc_after ' . $font_icon_class . '"  onclick="addField'.$field->id.'(null, jQuery(this).closest(\'ul\'), jQuery(this).closest(\'li\'), {insert_before: 0});" title="'.JText::_( 'FLEXI_ADD_AFTER' ).'"></span> ' : '';
		} else {
			$remove_button = '';
			$move2 = '';
			$add_here = '';
			$js .= '';
			$css .= '';
		}


		// Added field's custom CSS / JS
		if ($multiple) $js .= "
			var uniqueRowNum".$field->id."	= ".count($field->value).";  // Unique row number incremented only
			var rowCount".$field->id."	= ".count($field->value).";      // Counts existing rows to be able to limit a max number of values
			var maxValues".$field->id." = ".$max_values.";
		";
		if (!$ajax && $js)  $document->addScriptDeclaration($js);
		if (!$ajax && $css) $document->addStyleDeclaration($css);
		
		
		
		// *****************************************
		// Create field's HTML display for item form
		// *****************************************

		// Handle case of FORM fields that each value is an array of values
		// (e.g. selectmultiple, checkbox), and that multi-value input is also enabled
		$is_array_already = is_array($field->value) ? is_array(reset($field->value)) : false;
		$values = static::$valueIsArr && !$multiple && !$is_array_already ? array($field->value) : $field->value;
		
		
		// *****************************************
		// Create field's HTML display for item form
		// *****************************************
		
		$field->html = array();
		$n = $ajax ? $field->valindex : 0;
		$per_val_js = '';
		foreach ($values as $value)
		{
			// Compatibility for serialized values
			if ( static::$valueIsArr )
			{
				if (!is_array($value))
				{
					$value = $this->unserialize_array($value, $force_array=true, $force_value=true);
				}
			}

			// Make sure value is an array
			if (!is_array($value))
			{
				$value = strlen($value) ? array($value) : array();
			}

			// Skip empty if not in field group, and at least one value was added
			if (!count($value) && !$use_ingroup && $n)  continue;

			// Only if needed ...
			if (!$ajax || !$display_as_select)
			{
				$fieldname_n = $fieldname.'['.$n.']'. (static::$valueIsArr ? '[]' : '');
				$elementid_n = $elementid.'_'.$n;
			}

			// Get options according to cascading and according to per element state, this is here so that it works with field grouping too
			$elements = $cascade_after
				? $this->getLimitedProps($field, $item, !$ajax ? $cascade_prompt : null, $ajax, $n, $value)
				: $this->getLimitedProps($field, $item, null, $ajax, $n, $value);

			if ($display_as_select)
			{
				$options = & $elements;
			}
			else
			{
				// Create form field options
				$i = 0;
				$options = array();
				foreach ($elements as $element)
				{
					if ( !empty($element->isprompt) )
					{
						$options[] = '<span style="float: left;" class="'.$element->isprompt.'">'.$element->text.'</span>';
						continue;
					}
					$checked  = (in_array($element->value, $value)  ?  ' checked="checked"'  :  '') . (in_array($element->value, $default_values)  ?  ' data-is-defval="1"'  :  '');
					$elementid_no = $elementid_n.'_'.$i;
					//echo " &nbsp; &nbsp; $elementid_n , $elementid_no , $fieldname_n  , &nbsp; value: {$element->value} <br/>\n";
					$input_attribs  = '';  //$use_prettycheckable && $prettycheckable_added ? ' data-labelPosition="right" data-labeltext="'.$element->text.'"' : '';
					if ( !empty($element->disable) )
					{
						$input_attribs  .= ' disabled ';
					}
					$input_attribs .= ' class="' . $input_classes .' '. $elementid_n . '" ';
					$input_fld = ' <input type="'.$fftype.'" id="'.$elementid_no.'" data-element-grpid="'.$elementid_n.'" name="'.$fieldname_n.'" '.$attribs.$input_attribs.' value="'.$element->value.'" '.$checked.' />';
					$options[] = ''
						.$pretext
						.(!$placeInsideLabel ? $input_fld : '')
						.'<label for="'.$elementid_no.'" class="'.$label_class.'" style="'.$label_style.'" '.($form_vals_display==1 ? 'title="'.@ $element->label_tip.'"' : '').'>'
							. ($placeInsideLabel ? $input_fld : '')
							.($form_vals_display!=1 ? $element->text : '')
							.($form_vals_display==2 ? ' <br/>' : '')
							.($form_vals_display >0 ? $element->image_html : '')
						.'</label>'
						.$posttext
						;
					$i++;
				}

				// Apply (item form) separator and open/close tags to create the radio field
				$form_field = $opentag . implode($separator, $options) . $closetag;
			}

			// Rendering field during initial form loading (non-AJAX)
			if (!$ajax)
			{
				// Set order of selected values for the case that field is sortable
				if ($sortable && $display_as_select)
				{
					$d = array();
					foreach($value as $v)
					{
						if ( isset($options[$v]) && $v != '_field_selection_prompt_' )
						{
							$d[] = (object) array('id' => $options[$v]->value, 'text' => $options[$v]->text);
						}
					}
					//$per_val_js .= 'jQuery("#'.$elementid_n.'").select2("data", '.json_encode($d).');';
					$attribs .= ' data-select2-initdata = "' . htmlentities(json_encode($d), ENT_QUOTES, 'UTF-8') . '"';
				}

				$field->html[] = '
					'.($display_as_select ?
						$opentag . JHTML::_('select.genericlist', $options, $fieldname_n, $attribs.' class="'.$input_classes.'" data-uniqueRowNum="'.$n.'"', 'value', 'text', $value, $elementid_n) . $closetag :
						'<div id="'.$elementid_n.'" class="fc_input_set">'.$form_field.'</div>'
					).'
					'.($cascade_after ? '<span class="field_cascade_loading"></span>' : '').'
					'.($use_ingroup   ? '<input type="hidden" class="fcfield_value_holder" name="'.$valueholder_nm.'['.$n.']" id="'.$valueholder_id.'_'.$n.'" value="-">' : '').'
				'.($use_ingroup || !$multiple ? '' : '
				<div class="'.$input_grp_class.' fc-xpended-btns">
					'.$move2.'
					'.$remove_button.'
					'.(!$add_position ? '' : $add_here).'
				</div>
				');
				
				// Listen to the changes of depends-on-master field (non-AJAX)
				// NOTE: Add listening code only for value 0 if listening to single master value
				if ($cascade_after)
				{
					$mvno = $single_master ? '0' : $n;
					$per_val_js .= "
						fc_cascade_field_funcs['".$srcELid.'_'.$n."'] = function(){
							fcCascadedField(".$field->id.", '".$item->id."', '".$field->field_type."', 'select#".$srcELid.'_'.$mvno.", input.".$srcELid.'_'.$mvno."', '".$trgELid.'_'.$n."', '".$cascade_prompt."', ".static::$promptEnabled.", ".$n.");
						}
						fc_cascade_field_funcs['".$srcELid.'_'.$n."']();
					";
				}
			}

			// Rendering field via AJAX call
			else
			{
				$field->html = !$display_as_select ? $form_field : JHTML::_('select.options', $options, 'value', 'text', $value, $translate = false);
			}
			
			$n++;
			if (!$multiple) break;  // multiple values disabled, break out of the loop, not adding further values even if the exist
		}
		if ($per_val_js)
			$document->addScriptDeclaration('
				jQuery(document).ready(function(){
					'.$per_val_js.'
				});
			');


		if ($ajax) {
			return; // Done
		} else if ($use_ingroup) { // do not convert the array to string if field is in a group
		} else if ($multiple) { // handle multiple records
			$field->html = !count($field->html) ? '' :
				'<li class="'.$value_classes.'">'.
					implode('</li><li class="'.$value_classes.'">', $field->html).
				'</li>';
			$field->html = '<ul class="fcfield-sortables" id="sortables_'.$field->id.'">' .$field->html. '</ul>';
			if (!$add_position) $field->html .= '
				<div class="input-append input-prepend fc-xpended-btns">
					<span class="fcfield-addvalue ' . $font_icon_class . ' fccleared" onclick="addField'.$field->id.'(jQuery(this).closest(\'.fc-xpended-btns\').get(0));" title="'.JText::_( 'FLEXI_ADD_TO_BOTTOM' ).'">
						'.JText::_( 'FLEXI_ADD_VALUE' ).'
					</span>
				</div>';
		} else {  // handle single values
			$field->html = '<div class="fcfieldval_container valuebox fcfieldval_container_'.$field->id.'">' . $field->html[0] .'</div>';
		}


		// For multi-value fields add configured limits
		if (static::$valueIsArr)
		{
			// Add message box about allowed # values
			if ($exact_values) {
				$values_msg = '<div class="alert alert-info fc-small fc-iblock">'.JText::sprintf('FLEXI_FIELD_NUM_VALUES_EXACTLY', $exact_values) .'</div><div class="fcclear"></div>';
			} else if ($max_values || $min_values > 1) {
				$values_msg = '<div class="alert alert-info fc-small fc-iblock">'.JText::sprintf('FLEXI_FIELD_NUM_VALUES_BETWEEN', $min_values, $max_values) .'</div><div class="fcclear"></div>';
			}
		
			// Add VALUE message to every value if inside field group
			if ( !empty($values_msg) )
			{
				if (!$use_ingroup) {
					$field->html = $values_msg . $field->html;
				} else {
					foreach($field->html as & $html) $html = $values_msg . $html;
					unset($html);
				}
			}
		}

		// Add ERROR message to every value if inside field group
		if ( !empty($error_msg) )
		{
			if (!$use_ingroup) {
				$field->html = $error_msg . $field->html;
			} else {
				foreach($field->html as & $html) $html = $error_msg . $html;
				unset($html);
			}
		}

		// Add sort message to every value if inside field group
		$sortable_msg = $sortable ? '<div style="display: inline-block; vertical-align: middle; padding: 0px 4px 0px 8px;"><span class="icon-info hasTooltip" title="'.JText::_('FLEXI_FIELD_ALLOW_SORTABLE_INFO').'"></span>' . JText::_('FLEXI_ORDER') . '</div> ' : '';
		if ( !empty($sortable_msg) )
		{
			if (!$use_ingroup) {
				$field->html = $sortable_msg . $field->html;
			} else {
				foreach($field->html as & $html) $html = $sortable_msg . $html;
				unset($html);
			}
		}
	}


	function & getLimitedProps($field, $item, $cascade_prompt=null, $ajax=false, $i=0, $row_values = array())
	{
		// some parameter shortcuts
		$sql_mode				= $field->parameters->get( 'sql_mode', 0 ) ;
		$field_elements	= $field->parameters->get( 'field_elements' ) ;
		$cascade_after  = (int)$field->parameters->get('cascade_after', 0);
		$display_as_select = static::$isDropDown || (int) $field->parameters->get( 'display_as_select', 0 );

		if ($cascade_after)
		{
			$_fields = FlexicontentFields::getFieldsByIds(array($cascade_after), array($item->id));
			if (!isset($_fields[$cascade_after]))
			{
				return array('Master field not found');
			}
			$master_field = $_fields[$cascade_after];
			FlexicontentFields::loadFieldConfig($master_field, $item);

			$cascade_prompt = $cascade_prompt ?: JText::_('FLEXI_PLEASE_SELECT') . ' ' . JText::_('FLEXI_ABOVE');

			$single_master = ! $master_field->parameters->get('use_ingroup', 0) && !$master_field->parameters->get('multiple', 0);
			$valgrps = $ajax || $single_master ? $field->valgrps : (isset($field->valgrps[$i]) ? $field->valgrps[$i] : null);

			// If using a multi-value per value field (checkbox / multi-select) then account for the fact that every of its values can be an array
			if ($valgrps)
			{
				$_valgrps = array();
				foreach($valgrps as $vg)
				{
					$_valgrps[] = is_array($vg) ? reset($vg) : $vg;
				}
				if ($_valgrps && !strlen($_valgrps[0]))
				{
					unset($_valgrps[0]);
				}
				$valgrps = $_valgrps;
			}

			if (empty($valgrps))
			{
				$elements = array();
				if (!$ajax)
				{
					//$prompt = JHTML::_('select.option', (static::$valueIsArr ? '_field_selection_prompt_' : ''), $cascade_prompt, 'value', 'text', (static::$valueIsArr ? 'disabled' : null));
					$prompt = (object) array(
						'value' => (static::$valueIsArr ? '_field_selection_prompt_' : ''),
						'text' => $cascade_prompt,
						'disable' => (static::$valueIsArr ? true : null),
						'isprompt' => 'fcpadded alert alert-info'
					);
					$elements = array('_field_selection_prompt_' => $prompt);
				}
				return $elements;
			}
		}


		// ***
		// *** Get elements, either via SQL query or via parsing the elements string
		// ***
		if ($sql_mode)  // SQL query mode
		{
			$and_clause = false;
			if ($cascade_after)
			{
				// Filter out values not in the the value group, this is done by modifying the SQL query
				$db = JFactory::getDBO();
				$_elements = array();
				foreach($valgrps as & $vg) $vg = $db->Quote($vg);
				unset($vg);

				// Parse query to find column expressions used to create field's elements
				$element_cols = FlexicontentFields::indexedField_getColsExprs($field, $item, $field_elements);
				$and_clause = !isset($element_cols['valgrp'])
					? ' 0 '
					: $element_cols['valgrp'] . ' IN ('.implode(',', $valgrps).')';
			}

			$item_pros = true;
			$elements = FlexicontentFields::indexedField_getElements($field, $item, static::$extra_props, $item_pros, false, $and_clause);
			if ( !is_array($elements) )
			{
				//$prompt = JHTML::_('select.option', (static::$valueIsArr ? '_field_selection_prompt_' : ''), JText::_('FLEXI_FIELD_INVALID_QUERY'), 'value', 'text', (static::$valueIsArr ? 'disabled' : null));
				$prompt = (object) array(
					'value' => (static::$valueIsArr ? '_field_selection_prompt_' : ''),
					'text' => JText::_('FLEXI_FIELD_INVALID_QUERY'),
					'disable' => (static::$valueIsArr ? true : null),
					'isprompt' => 'fcpadded alert alert-important'
				);
				$elements = array('_field_selection_prompt_' => $prompt);
				return $elements;
			}
		}

		else  // Elements mode
		{
			$elements = FlexicontentFields::indexedField_getElements($field, $item, static::$extra_props);
			if ( !is_array($elements) )
			{
				//$prompt = JHTML::_('select.option', (static::$valueIsArr ? '_field_selection_prompt_' : ''), JText::_('FLEXI_FIELD_INVALID_ELEMENTS'), 'value', 'text', (static::$valueIsArr ? 'disabled' : null));
				$prompt = (object) array(
					'value' => (static::$valueIsArr ? '_field_selection_prompt_' : ''),
					'text' => JText::_('FLEXI_FIELD_INVALID_ELEMENTS'),
					'disable' => (static::$valueIsArr ? true : null),
					'isprompt' => 'fcpadded alert alert-important'
				);
				$elements = array('_field_selection_prompt_' => $prompt);
				return $elements;
			}
			if ($cascade_after)
			{
				// Filter out values not in the the value group, this is done after the elements text is parsed
				$_elements = array();
				$_valgrps = array_flip($valgrps);
				foreach($elements as $element)
				{
					if (isset($_valgrps[$element->valgrp]))
					{
						$_elements[$element->value] = $element;
					}
				}
				$elements = $_elements;
			}
		}

		// Filter field values according to visible elements and store them to calculate the "default" cascaded values
		foreach($field->value as $v)
		{
			$v = is_array($v) ? reset($v) : $v;
			if (isset($elements[$v]))
			{
				static::$cascaded_values[$field->id][] = $v;
			}
		}


		// ***
		// *** Handle element states, cloning the element objects if setting 'disable flag'
		// ***
		if ($field->parameters->get('use_elements_state', 0) && !empty($elements))
		{
			$values = array_flip($row_values);
			$_elements = array();
			foreach($elements as $i => $element)
			{
				$element->state = isset($element->state) ? $element->state : 1;
				switch ($element->state)
				{
					case -2:  // Trashed
						break;
					case 0:   // Unpublished
					case 2:   // Archived
						if (isset($values[$element->value]))
						{
							$_elements[$i] = $element;
						}
						break;
					case 9:   // Expired
						if (!isset($values[$element->value]))
						{
							$_elements[$i] = clone($element);
							$_elements[$i]->disable = true;
							break;
						}
					case 1:   // Published
					default:
						$_elements[$i] = $element;
						break;
				}
			}
			$elements = $_elements;
		}


		// ***
		// *** Terminate if no useable values found
		// ***
		if (empty($elements))
		{
			//$prompt = JHTML::_('select.option', (static::$valueIsArr ? '_field_selection_prompt_' : ''), 'No data found', 'value', 'text', (static::$valueIsArr ? 'disabled' : null));
			$prompt = (object) array(
				'value' => (static::$valueIsArr ? '_field_selection_prompt_' : ''),
				'text' => JText::_('FLEXI_FIELD_NO_DATA_FOUND'),
				'disable' => (static::$valueIsArr ? true : null),
				'isprompt' => 'fcpadded alert alert-warning'
			);
			$elements = array('_field_selection_prompt_' => $prompt);
			return $elements;
		}


		// ***
		// *** Add prompt / empty option prompt
		// ***
		$display_label_form = (int) $field->parameters->get( 'display_label_form', 1 ) ;
		$usefirstoption  = $display_label_form==-1 ? 1 : $field->parameters->get( 'usefirstoption', $display_as_select ? 1 : 0 );
		$firstoptiontext = $display_label_form==-1 ? $field->label : JText::_($field->parameters->get( 'firstoptiontext', 'FLEXI_SELECT' ));

		if ($usefirstoption)   // Add selection prompt
		{
			//prompt = JHTML::_('select.option', (static::$valueIsArr ? '_field_selection_prompt_' : ''), $firstoptiontext, 'value', 'text', (static::$valueIsArr ? 'disabled' : null));
			$prompt = (object) array(
				'value' => (static::$valueIsArr ? '_field_selection_prompt_' : ''),
				'text' => $firstoptiontext,
				'disable' => (static::$valueIsArr ? true : null),
				'isprompt' => 'fcpadded alert alert-info'
			);
			$elements = array('_field_selection_prompt_' => $prompt) + $elements;
		}


		// ***
		// *** Handle fields that use images, like radioimage / checkboximage
		// ***
		if (static::$usesImages)
		{
			// image specific variables
			$form_vals_display = $field->parameters->get( 'form_vals_display', 1 ) ;  // this field includes image but it can be more convenient/compact not to be display image in item form
			$image_type = (int)$field->parameters->get( 'image_type', 0 );
			
			if ($image_type==0)
			{
				$imagedir = preg_replace('#^(/)*#', '', $field->parameters->get( 'imagedir' ) );
				$imgpath  = JURI::root(true) .'/'. $imagedir;
				$imgfolder = JPATH_SITE .DS. $imagedir;
			}
			else {
				$icon_size = (int)$field->parameters->get( 'icon_size_form' ) ;
				$icon_color = $field->parameters->get( 'icon_color_form' ) ;
			}
			
			foreach ($elements as $element)
			{
				if ($form_vals_display > 0 && !isset($element->image_html) && empty($element->isprompt))
				{
					if (!$image_type)
						$element->image_html = file_exists($imgfolder . $element->image) ?
							'<img style="vertical-align:unset!important;" src="'.$imgpath . $element->image .'"  alt="'.$element->text.'" />' :
							'[NOT found]: '. $imgpath . $element->image;
					else
						$element->image_html = '<span style="vertical-align:unset!important; '.($icon_color ? 'color: '.$icon_color.';' : '').'" class="fcfield_radiocheck_icon '. $element->image . ($icon_size ? ' fc-icon-'.$icon_size : '').'"></span>';
				}
				if (!isset($element->label_tip))
					$element->label_tip = flexicontent_html::getToolTip(null, $element->text, 0, 1);
			}
		}

		return $elements;
	}
	
	
	// Method called via AJAX to get dependent values
	function getCascadedField()
	{
		$field_id = JRequest::getInt('field_id', 0);
		$item_id  = JRequest::getInt('item_id', 0);
		$valgrps  = JRequest::getVar('valgrps', '');
		$valindex = JRequest::getVar('valindex', 0);
		
		// Load field
		$_fields = FlexicontentFields::getFieldsByIds(array($field_id), array($item_id));
		$field = $_fields[$field_id];
		$field->item_id = $item_id;
		$field->valgrps = $valgrps;
		$field->valindex = $valindex;
		
		// Load item
		$item = JTable::getInstance( $_type = 'flexicontent_items', $_prefix = '', $_config = array() );
		$item->load( $item_id );
		
		// Get field configuration
		FlexicontentFields::loadFieldConfig($field, $item);
		
		// Get field values
		//$_fieldvalues = FlexicontentFields::getFieldValsById(array($field_id), array($item_id));
		$field->value = null; //isset($_fieldvalues[$item_id][$field_id]) ? $_fieldvalues[$item_id][$field_id] : array();
		
		// Render field
		$field->isAjax = 1;
		$this->onDisplayField($field, $item);
		
		// Output the field
		echo $field->html;
		exit;
	}
	
	
	// Method to create field's HTML display for frontend views
	function onDisplayFieldValue(&$field, $item, $values=null, $prop='display')
	{
		if ( !in_array($field->field_type, static::$field_types) ) return;
		
		$field->label = JText::_($field->label);
		
		// Some variables
		$is_ingroup  = !empty($field->ingroup);
		$use_ingroup = $field->parameters->get('use_ingroup', 0);
		$multiple    = $use_ingroup || (int) $field->parameters->get( 'allow_multiple', 0 ) ;
		
		// Get field values
		$values = $values ? $values : $field->value;
		
		// Check for no values and not displaying ALL elements
    $display_all = $field->parameters->get( 'display_all', 0 ) && !$is_ingroup;  // NOT supported inside fielgroup yet
    $display_all = $prop=='csv_export' ? 0 : $display_all;
		if ( empty($values) && !$display_all ) {
			if (!$is_ingroup) {
				$field->{$prop} = ''; $field->display_index = '';
			} else {
				$field->{$prop} = array(); $field->display_index = array();
			}
			return;
		}
		
		
		// Prefix - Suffix - Separator parameters, replacing other field values if found
		$remove_space = $field->parameters->get( 'remove_space', 0 ) ;
		$pretext		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'pretext', '' ), 'pretext' );
		$posttext		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'posttext', '' ), 'posttext' );
		$separatorf	= $field->parameters->get( 'separatorf', 1 ) ;
		$opentag		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'opentag', '' ), 'opentag' );
		$closetag		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'closetag', '' ), 'closetag' );
		
		// Microdata (classify the field values for search engines)
		$itemprop    = $field->parameters->get('microdata_itemprop');
		
		if($pretext)  { $pretext  = $remove_space ? $pretext : $pretext . ' '; }
		if($posttext) { $posttext = $remove_space ? $posttext : ' ' . $posttext; }
		
		// Value creation
		$sql_mode = $field->parameters->get( 'sql_mode', 0 ) ;
		$field_elements = $field->parameters->get( 'field_elements', '' ) ;
		$text_or_value  = (int) $field->parameters->get( 'text_or_value', (static::$usesImages ? 2 : 1) ) ;
		$tooltip_class = 'hasTooltip';

		// image specific or image related variables
		if (static::$usesImages)
		{
			$imagedir = preg_replace('#^(/)*#', '', $field->parameters->get( 'imagedir' ) );
			$imgpath  = JURI::root(true) .'/'. $imagedir;

			$image_type = (int)$field->parameters->get( 'image_type', 0 ) ;
			$icon_size = (int)$field->parameters->get( 'icon_size', $field->parameters->get( 'icon_size_form') ) ;
			$icon_color = $field->parameters->get( 'icon_color', $field->parameters->get( 'icon_color_form') ) ;
		}

		switch($separatorf)
		{
			case 0:
			$separatorf = '&nbsp;';
			break;

			case 1:
			$separatorf = '<br class="fcclear" />';
			break;

			case 2:
			$separatorf = '&nbsp;|&nbsp;';
			break;

			case 3:
			$separatorf = ',&nbsp;';
			break;

			case 4:
			$separatorf = $closetag . $opentag;
			break;

			case 5:
			$separatorf = '';
			break;

			default:
			$separatorf = '&nbsp;';
			break;
		}
		if ($prop == 'csv_export') $separatorf = ', ';
		
		
		// Get indexed element values
		$elements = FlexicontentFields::indexedField_getElements($field, $item, static::$extra_props);
		if ( !$elements ) {
			if ($sql_mode)
				$field->{$prop} = JText::_('FLEXI_FIELD_INVALID_QUERY');
			else
				$field->{$prop} = JText::_('FLEXI_FIELD_INVALID_ELEMENTS');
			return;
		}
		// Check for no elements found
		if ( empty($elements) )  { $field->{$prop} = ''; $field->display_index = ''; return; }
		
		
		// Handle case of FORM fields that each value is an array of values
		// (e.g. selectmultiple, checkbox), and that multi-value input is also enabled
		// we make sure that values should be an array of arrays
		$is_2lvl_arr = false;
		if (is_array($values))
		{
			$v = reset($values);
			$is_2lvl_arr = is_array($v);
		}
		$values = ($multiple && static::$valueIsArr) || $is_2lvl_arr ? $values : array($values);
		if ( !$values ) $values = array();
		
		
		// Get layout name
		$viewlayout = $field->parameters->get('viewlayout', '');
		$viewlayout = $viewlayout ? 'value_'.$viewlayout : 'value_default';
		
		// Create field's HTML, using layout file
		$field->{$prop} = array();
		$display_index = array();
		
		include(self::getViewPath($this->fieldtypes[0], $viewlayout));


		// Add microdata to every group of values if field -- is -- in a field group
		if ($is_ingroup && $itemprop && $prop!='csv_export') {
			foreach($field->{$prop} as $n => $disp_html) {
				$field->{$prop}[$n] = '<div style="display:inline" itemprop="'.$itemprop.'" >' .$field->{$prop}[$n]. '</div>';
			}
		}
		
		
		// Do not convert the array to string if field is in a group, and do not add: FIELD's opentag, closetag, value separator
		if (!$is_ingroup)
		{
			if ($multiple && static::$valueIsArr) {
				// Values separator, field 's opening / closing texts, were already applied for every array of values
				$sep = $prop!='csv_export' ? '' : ' -- ';
				$field->{$prop} = implode($sep, $field->{$prop});
				$field->display_index = implode($sep, $display_index);
			} else {
				// Apply values separator, and field 's opening / closing texts
				$field->{$prop} = !count($field->{$prop}) ? '' : $opentag . implode($separatorf, $field->{$prop}) . $closetag;
				$field->display_index = !count($field->{$prop}) ? '' : $opentag . implode($separatorf, $display_index) . $closetag;
			}
			
			// Add microdata once for all values, if field -- is NOT -- in a field group
			if ( $field->{$prop}!=='' && $itemprop )
			{
				$field->{$prop} = '<div style="display:inline" itemprop="'.$itemprop.'" >' .$field->{$prop}. '</div>';
			}
		}
	}
	
	
	
	// **************************************************************
	// METHODS HANDLING before & after saving / deleting field events
	// **************************************************************
	
	// Method to handle field's values before they are saved into the DB
	function onBeforeSaveField( &$field, &$post, &$file, &$item )
	{
		if ( !in_array($field->field_type, static::$field_types) ) return;
		
		$use_ingroup = $field->parameters->get('use_ingroup', 0);
		if ( !is_array($post) && !strlen($post) && !$use_ingroup ) return;
		
		$max_values = $use_ingroup ? 0 : (int) $field->parameters->get( 'max_values', 0 ) ;
		$multiple   = $use_ingroup || (int) $field->parameters->get( 'allow_multiple', 0 ) ;
		$is_importcsv = JFactory::getApplication()->get('task', '', 'cmd') == 'importcsv';
		$field->use_suborder = $multiple && static::$valueIsArr;
		
		// Make sure posted data is an array 
		$post = !is_array($post) ? array($post) : $post;

		// Make sure every value is an array (for multi-value per value fields)
		if (static::$valueIsArr)
		{
			$v = reset($post);  // Get first value to examine it below (by attempting unserialize) and forcing an array
			if (!is_array($v))
			{
				// An array of arrays
				$array = $this->unserialize_array($v, $force_array=false, $force_value=false);
				$post = $array===false ? array($post) : $post;
			}
		}
		
		// Account for fact that ARRAY form elements are not submitted if they do not have a value
		if ( $use_ingroup )
		{
			$empty_value = static::$valueIsArr ? array() : null;
			$custom = JFactory::getApplication()->input->get('custom', array(), 'array');
			if ( isset($custom['_fcfield_valueholder_'][$field->name]) ) 
			{
				$holders = $custom['_fcfield_valueholder_'][$field->name];
				$vals = array();
				foreach($holders as $i => $v)  $vals[]  =  isset($post[(int)$i]) ? $post[(int)$i] : $empty_value;
				$post = $vals;
			}
		}
		
		// Reformat the posted data
		$newpost = array();
		$new = 0;
		$elements = FlexicontentFields::indexedField_getElements($field, $item, static::$extra_props);
		
		foreach ($post as $n => $v)
		{
			// Non multi-value per value fields, have only 1 value, convert it to single record array to use same code below
			if (!static::$valueIsArr)
			{
				$v = array($v);
			}
			// Support for serialized user data, e.g. basic CSV import / export. (Safety concern: objects code will abort unserialization!)
			else if ( $is_importcsv && !is_array($v) )
			{
				$v = $this->unserialize_array($v, $force_array=true, $force_value=true);
			}

			// Do server-side validation and skip empty/invalid values
			$vals = array();
			foreach ($v as $i => $nv)
			{
				$element = !strlen($nv) ? false : @$elements[$nv];
				if ( $element ) $vals[] = $nv;  // include only valid value
			}
			
			// Skip empty value, but if in group increment the value position
			if (!count($vals))
			{
				if ($use_ingroup) $newpost[$new++] = static::$valueIsArr ? array() : null;
				continue;
			}
			
			// If multiple disabled, use 1st value ARRAY only (for multi-value per value fields)
			if (static::$valueIsArr && !$multiple)
			{
				$newpost = $vals;
				break;
			}
			
			$newpost[$new] = static::$valueIsArr ? $vals : reset($vals);
			$new++;
			
			// If multiple disabled, do not add more values
			if (!static::$valueIsArr && !$multiple) break;
			
			// max values limitation (*if in group, this was zeroed above)
			if ($max_values && $new >= $max_values) continue;
		}
		$post = $newpost;
		/*if ($use_ingroup) {
			$app = JFactory::getApplication();
			$app->enqueueMessage( print_r($post, true), 'warning');
		}*/
	}
	
	
	// Method to take any actions/cleanups needed after field's values are saved into the DB
	function onAfterSaveField( &$field, &$post, &$file, &$item ) {
	}
	
	
	// Method called just before the item is deleted to remove custom item data related to the field
	function onBeforeDeleteField(&$field, &$item) {
	}
	
	
	
	// *********************************
	// CATEGORY/SEARCH FILTERING METHODS
	// *********************************
	
	// Method to display a search filter for the advanced search view
	function onAdvSearchDisplayFilter(&$filter, $value='', $formName='searchForm')
	{
		if ( !in_array($filter->field_type, static::$field_types) ) return;
		
		$this->onDisplayFilter($filter, $value, $formName, $isSearchView=1);
	}
	
	
	// Method to display a category filter for the category view
	function onDisplayFilter(&$filter, $value='', $formName='adminForm', $isSearchView=0)
	{
		if ( !in_array($filter->field_type, static::$field_types) ) return;
		
		// Get indexed element values
		$item_pros = false;
		$elements = FlexicontentFields::indexedField_getElements($filter, $item=null, static::$extra_props, $item_pros, $create_filter=true);
		

		// ***
		// *** Handle display for fields that use images, like radioimage / checkboximage
		// ***
		if (static::$usesImages)
		{
			$_s = $isSearchView ? '_s' : '';
			$filter_vals_display = $filter->parameters->get( 'filter_vals_display'.$_s, 0 );
			$filter_as_images = in_array($filter_vals_display, array(1,2)) ;
			$image_type = (int)$filter->parameters->get( 'image_type', 0 );
		
			if ($filter_as_images && $elements && !$image_type)
			{
				// image specific variables
				$imagedir = preg_replace('#^(/)*#', '', $filter->parameters->get( 'imagedir' ) );
				$imgpath  = JURI::root(true) .'/'. $imagedir;
				foreach($elements as $element) {
					$element->image_url = $imgpath . $element->image;
				}
			}
		}

		// Check for error during getting indexed field elements
		if ( !$elements ) {
			$filter->html = '';
			$sql_mode = $filter->parameters->get( 'sql_mode', 0 );  // must retrieve variable here, and not before retrieving elements !
			if ($sql_mode && $item_pros > 0)
				$filter->html = sprintf( JText::_('FLEXI_FIELD_ITEM_SPECIFIC_AS_FILTERABLE'), $filter->label );
			else if ($sql_mode)
				$filter->html = JText::_('FLEXI_FIELD_INVALID_QUERY');
			else
				$filter->html = JText::_('FLEXI_FIELD_INVALID_ELEMENTS');
			return;
		}
		
		FlexicontentFields::createFilter($filter, $value, $formName, $elements);
	}
	
	
 	// Method to get the active filter result (an array of item ids matching field filter, or subquery returning item ids)
	// This is for content lists e.g. category view, and not for search view
	function getFiltered(&$filter, $value, $return_sql=true)
	{
		if ( !in_array($filter->field_type, static::$field_types) ) return;
		
		return FlexicontentFields::getFiltered($filter, $value, $return_sql);
	}
	
	
 	// Method to get the active filter result (an array of item ids matching field filter, or subquery returning item ids)
	// This is for search view
	function getFilteredSearch(&$filter, $value, $return_sql=true)
	{
		if ( !in_array($filter->field_type, static::$field_types) ) return;
		
		$filter->isindexed = true;
		return FlexicontentFields::getFilteredSearch($filter, $value, $return_sql);
	}
	
	
	
	// *************************
	// SEARCH / INDEXING METHODS
	// *************************
	
	// Method to create (insert) advanced search index DB records for the field values
	function onIndexAdvSearch(&$field, &$post, &$item)
	{
		if ( !in_array($field->field_type, static::$field_types) ) return;
		if ( !$field->isadvsearch && !$field->isadvfilter ) return;
		
		$field->isindexed = true;
		$field->extra_props = static::$extra_props;
		FlexicontentFields::onIndexAdvSearch($field, $post, $item, $required_properties=array(), $search_properties=array('text'), $properties_spacer=' ', $filter_func=null);
		return true;
	}
	
	
	// Method to create basic search index (added as the property field->search)
	function onIndexSearch(&$field, &$post, &$item)
	{
		if ( !in_array($field->field_type, static::$field_types) ) return;
		if ( !$field->issearch ) return;
		
		$field->isindexed = true;
		$field->extra_props = static::$extra_props;
		FlexicontentFields::onIndexSearch($field, $post, $item, $required_properties=array(), $search_properties=array('text'), $properties_spacer=' ', $filter_func=null);
		return true;
	}



	// ***
	// *** VARIOUS HELPER METHODS
	// ***
	
	// Method to get default values of an indexed field
	function getFormDefaultValues($field, $item)
	{
		$class_name = 'plgflexicontent_fields' . ucfirst($field->field_type);
		if (!class_exists($class_name))
		{
			JPluginHelper::getPlugin('flexicontent_fields', $field->field_type);
		}

		$value_usage = $field->parameters->get( 'default_value_use', 0 ) ;
		if ($class_name::$valueIsArr)
		{
			$default_values = ($item->version == 0 || $value_usage > 0) ? trim($field->parameters->get( 'default_values', '' )) : '';
			$default_values = preg_split("/\s*,\s*/u", $default_values);
		}
		else
		{
			$default_value = ($item->version == 0 || $value_usage > 0) ? trim($field->parameters->get( 'default_value', '' )) : '';
			$default_values= array($default_value);
		}

		return $default_values;
	}
}
