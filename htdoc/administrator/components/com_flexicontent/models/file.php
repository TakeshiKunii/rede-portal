<?php
/**
 * @version 1.5 stable $Id: file.php 1577 2012-12-02 15:10:44Z ggppdk $
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
defined('_JEXEC') or die('Restricted access');

jimport('legacy.model.admin');
use Joomla\String\StringHelper;
require_once('base.php');

/**
 * FLEXIcontent Component File Model
 *
 * @package Joomla
 * @subpackage FLEXIcontent
 * @since		1.0
 */
class FlexicontentModelFile extends FCModelAdmin
{
	/**
	 * Record name
	 *
	 * @var string
	 */
	var $record_name = 'file';

	/**
	 * Record database table 
	 *
	 * @var string
	 */
	var $records_dbtbl = null;

	/**
	 * Record jtable name
	 *
	 * @var string
	 */
	var $records_jtable = null;

	/**
	 * Record primary key
	 *
	 * @var int
	 */
	var $_id = null;

	/**
	 * Record data
	 *
	 * @var object
	 */
	var $_record = null;

	/**
	 * Events context to use during model FORM events triggering
	 *
	 * @var object
	 */
	var $events_context = null;

	/**
	 * Flag to indicate adding new records with next available ordering (at the end),
	 * this is ignored if this record DB model does not have 'ordering'
	 *
	 * @var boolean
	 */
	var $useLastOrdering = false;

	/**
	 * Plugin group used to trigger events
	 *
	 * @var boolean
	 */
	var $plugins_group = null;

	/**
	 * Records real extension
	 *
	 * @var string
	 */
	var $extension_proxy = null;

	/**
	 * Use language associations
	 *
	 * @var string
	 */
	var $associations_context = false;

	/**
	 * Various record specific properties
	 *
	 */
	// ...

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	function __construct()
	{
		parent::__construct();
	}


	/**
	 * Legacy method to get the record
	 *
	 * @access	public
	 * @return	object
	 * @since	1.0
	 */
	function & getFile($pk = null)
	{
		return parent::getRecord($pk);
	}


	/**
	 * Method to initialise the record data
	 *
	 * @access	protected
	 * @return	boolean	True on success
	 * @since	1.0
	 */
	protected function _initRecord(&$record = null, $initOnly = false)
	{
		parent::_initRecord($record, $initOnly);

		// Set some new record specific properties, note most properties already have proper values
		// Either the DB default values (set by getTable() method) or the values set by _afterLoad() method
		$record->id							= 0;
		$record->checked_out		= 0;
		$record->checked_out_time	= '';

		$this->_record = $record;

		return true;
	}


	/**
	 * Method to store the record
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	function store($data)
	{
		return parent::store($data);
	}


	/**
	 * Method to change the title & alias.
	 *
	 * @param   integer  $parent_id  If applicable, the id of the parent (e.g. assigned category)
	 * @param   string   $alias      The alias / name.
	 * @param   string   $title      The title / label.
	 *
	 * @return  array    Contains the modified title and alias / name.
	 *
	 * @since   1.7
	 */
	protected function generateNewTitle($parent_id, $alias, $title)
	{
		// Alter the title & alias
		$table = $this->getTable();

		while ($table->load(array('filename' => $title)))
		{
			$title = StringHelper::increment($title);
		}

		return array($title, null);
	}


	/**
	 * Method to check if the user can edit the record
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	3.2.0
	 */
	function canEdit($record=null)
	{
		$record = $record ?: $this->_record;
		$user = JFactory::getUser();

		$canupload = $user->authorise('flexicontent.uploadfiles', 'com_flexicontent');
		$canedit = $user->authorise('flexicontent.editfile', 'com_flexicontent');
		$caneditown = $user->authorise('flexicontent.editownfile', 'com_flexicontent') && $user->get('id') && $record->uploaded_by == $user->get('id');
		return !$record || !$record->id
			? $canupload
			: $canedit || $caneditown;
	}


	/**
	 * Method to check if the user can edit record 's state
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	3.2.0
	 */
	function canEditState($record=null)
	{
		$record = $record ?: $this->_record;
		$user = JFactory::getUser();

		$canpublish = $user->authorise('flexicontent.publishfile', 'com_flexicontent');
		$canpublishown = $user->authorise('flexicontent.publishownfile', 'com_flexicontent') && $user->get('id') && $record->uploaded_by == $user->get('id');
		return !$record || !$record->id
			? false
			: $canpublish || $canpublishown;
	}


	/**
	 * Method to check if the user can delete the record
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	3.2.0
	 */
	function canDelete($record=null)
	{
		$record = $record ?: $this->_record;
		$user = JFactory::getUser();

		$candelete = $user->authorise('flexicontent.deletefile', 'com_flexicontent');
		$candeleteown = $user->authorise('flexicontent.deleteownfile', 'com_flexicontent') && $user->get('id') && $record->uploaded_by == $user->get('id');
		return !$record || !$record->id
			? false
			: $candelete || $candeleteown;
	}


	/**
	 * Method to do some record / data preprocessing before call JTable::bind()
	 *
	 * Note. Typically called inside this MODEL 's store()
	 *
	 * @since	3.2.0
	 */
	protected function _prepareBind($record, & $data)
	{
		parent::_prepareBind($record, $data);
	}


	/**
	 * Method to do some work after record has been stored
	 *
	 * Note. Typically called inside this MODEL 's store()
	 *
	 * @since	3.2.0
	 */
	protected function _afterStore($record, & $data)
	{
		parent::_afterStore($record, $data);
	}


	/**
	 * Method to do some work after record has been loaded via JTable::load()
	 *
	 * Note. Typically called inside this MODEL 's store()
	 *
	 * @since	3.2.0
	 */
	protected function _afterLoad($record)
	{
		parent::_afterLoad($record);
	}
}