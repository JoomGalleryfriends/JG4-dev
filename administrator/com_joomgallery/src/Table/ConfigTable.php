<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Access\Access;
use \Joomla\CMS\Table\Table as Table;
use \Joomla\CMS\Versioning\VersionableTableInterface;
use \Joomla\Database\DatabaseDriver;
use \Joomla\Registry\Registry;

/**
 * Config table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigTable extends Table implements VersionableTableInterface
{
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = _JOOM_OPTION.'.config';

		parent::__construct(_JOOM_TABLE_CONFIGS, 'id', $db);

		$this->setColumnAlias('published', 'state');
	}

	/**
	 * Get the type alias for the history table
	 *
	 * @return  string  The alias as described above
	 *
	 * @since   4.0.0
	 */
	public function getTypeAlias()
	{
		return $this->typeAlias;
	}

	/**
	 * Overloaded bind function to pre-process the params.
	 *
	 * @param   array  $array   Named array
	 * @param   mixed  $ignore  Optional array or list of parameters to ignore
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     Table:bind
	 * @since   4.0.0
	 * @throws  \InvalidArgumentException
	 */
	public function bind($array, $ignore = '')
	{
		$date = Factory::getDate();
		$task = Factory::getApplication()->input->get('task');

		if($array['id'] == 0 && empty($array['created_by']))
		{
			$array['created_by'] = Factory::getUser()->id;
		}

		if($array['id'] == 0 && empty($array['modified_by']))
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

		if($task == 'apply' || $task == 'save')
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

		// Support for multiple field: jg_uploadorder
    $array['jg_uploadorder'] = $this->multipleFieldSupport($array['jg_uploadorder']);

		// Support for multiple field: jg_delete_original
		$array['jg_delete_original'] = $this->multipleFieldSupport($array['jg_delete_original']);

		// Support for multiple field: jg_imgprocessor
		$array['jg_imgprocessor'] = $this->multipleFieldSupport($array['jg_imgprocessor']);

		// Support for multiple field: jg_msg_upload_type
		$array['jg_msg_upload_type'] = $this->multipleFieldSupport($array['jg_msg_upload_type']);

		// Support for multiple field: jg_msg_upload_recipients
		$array['jg_msg_upload_recipients'] = $this->multipleFieldSupport($array['jg_msg_upload_recipients']);

		// Support for multiple field: jg_msg_download_type
		$array['jg_msg_download_type'] = $this->multipleFieldSupport($array['jg_msg_download_type']);

		// Support for multiple field: jg_msg_download_recipients
		$array['jg_msg_download_recipients'] = $this->multipleFieldSupport($array['jg_msg_download_recipients']);

		// Support for multiple field: jg_msg_comment_type
		$array['jg_msg_comment_type'] = $this->multipleFieldSupport($array['jg_msg_comment_type']);

		// Support for multiple field: jg_msg_comment_recipients
		$array['jg_msg_comment_recipients'] = $this->multipleFieldSupport($array['jg_msg_comment_recipients']);

		// Support for multiple field: jg_msg_report_type
		$array['jg_msg_report_type'] = $this->multipleFieldSupport($array['jg_msg_report_type']);

		// Support for multiple field: jg_msg_report_recipients
		$array['jg_msg_report_recipients'] = $this->multipleFieldSupport($array['jg_msg_report_recipients']);

		// Support for multiple field: jg_msg_rejectimg_type
		$array['jg_msg_rejectimg_type'] = $this->multipleFieldSupport($array['jg_msg_rejectimg_type']);

		// Support for multiple field: group_id
		$array['group_id'] = $this->multipleFieldSupport($array['group_id']);

		// Support for multiple field: jg_uploaddefaultcat
		$array['jg_uploaddefaultcat'] = $this->multipleFieldSupport($array['jg_uploaddefaultcat']);		

		// Support for multiple field: jg_redirect_after_upload
		$array['jg_redirect_after_upload'] = $this->multipleFieldSupport($array['jg_redirect_after_upload']);

		// Support for multiple field: jg_downloadfile
		$array['jg_downloadfile'] = $this->multipleFieldSupport($array['jg_downloadfile']);
    
    // Support for multiple field: jg_ratingcalctype
		$array['jg_ratingcalctype'] = $this->multipleFieldSupport($array['jg_ratingcalctype']);   
    
    if($array['jg_maxusercat'] === '')
		{
			$array['jg_maxusercat'] = NULL;
			$this->jg_maxusercat = NULL;
		}

		if($array['jg_maxuserimage'] === '')
		{
			$array['jg_maxuserimage'] = NULL;
			$this->jg_maxuserimage = NULL;
		}

		if($array['jg_maxuserimage_timespan'] === '')
		{
			$array['jg_maxuserimage_timespan'] = NULL;
			$this->jg_maxuserimage_timespan = NULL;
		}

		if($array['jg_maxfilesize'] === '')
		{
			$array['jg_maxfilesize'] = NULL;
			$this->jg_maxfilesize = NULL;
		}

    if($array['jg_maxuploadfields'] === '')
		{
			$array['jg_maxuploadfields'] = NULL;
			$this->jg_maxuploadfields = NULL;
		}

		if($array['jg_maxvoting'] === '')
		{
			$array['jg_maxvoting'] = NULL;
			$this->jg_maxvoting = NULL;
		}

		if(isset($array['params']) && is_array($array['params']))
		{
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

		if(isset($array['metadata']) && is_array($array['metadata']))
		{
			$registry = new Registry;
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string) $registry;
		}

		if(!Factory::getUser()->authorise('core.admin', _JOOM_OPTION.'.config.' . $array['id']))
		{
			$actions         = Access::getActionsFromFile(_JOOM_PATH_ADMIN.'/access.xml',	"/access/section[@name='config']/");
			$default_actions = Access::getAssetRules(_JOOM_OPTION.'.config.' . $array['id'])->getData();
			$array_jaccess   = array();

			foreach($actions as $action)
			{
				if(key_exists($action->name, $default_actions))
				{
					$array_jaccess[$action->name] = $default_actions[$action->name];
				}
			}

			$array['rules'] = $this->JAccessRulestoArray($array_jaccess);
		}

		// Bind the rules for ACL where supported.
		if(isset($array['rules']) && is_array($array['rules']))
		{
			$this->setRules($array['rules']);
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * Method to store a row in the database from the Table instance properties.
	 *
	 * If a primary key value is set the row with that primary key value will be updated with the instance property values.
	 * If no primary key value is set a new row will be inserted into the database with the properties from the Table instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   4.0.0
	 */
	public function store($updateNulls = true)
	{
		return parent::store($updateNulls);
	}

	/**
	 * This function convert an array of Access objects into an rules array.
	 *
	 * @param   array  $jaccessrules  An array of Access objects.
	 *
	 * @return  array
	 */
	private function JAccessRulestoArray($jaccessrules)
	{
		$rules = array();

		foreach($jaccessrules as $action => $jaccess)
		{
			$actions = array();

			if($jaccess)
			{
				foreach($jaccess->getData() as $group => $allow)
				{
					$actions[$group] = ((bool)$allow);
				}
			}

			$rules[$action] = $actions;
		}

		return $rules;
	}

	/**
	 * Overloaded check function
	 *
	 * @return bool
	 */
	public function check()
	{
		// If there is an ordering column and this is a new row then get the next ordering value
		if(property_exists($this, 'ordering') && $this->id == 0)
		{
			$this->ordering = self::getNextOrder();
		}

		// Support for subform field jg_replaceinfo
		if(is_array($this->jg_replaceinfo))
		{
			$this->jg_replaceinfo = json_encode($this->jg_replaceinfo, JSON_UNESCAPED_UNICODE);
		}

		// Support for subform field jg_staticprocessing
		if(is_array($this->jg_staticprocessing))
		{
			$this->jg_staticprocessing = json_encode($this->jg_staticprocessing, JSON_UNESCAPED_UNICODE);
		}

		// Support for subform field jg_dynamicprocessing
		if(is_array($this->jg_dynamicprocessing))
		{
			$this->jg_dynamicprocessing = json_encode($this->jg_dynamicprocessing, JSON_UNESCAPED_UNICODE);
		}

		return parent::check();
	}

	/**
	 * Define a namespaced asset name for inclusion in the #__assets table
	 *
	 * @return string The asset name
	 *
	 * @see Table::_getAssetName
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;

		return $this->typeAlias . '.' . (int) $this->$k;
	}

	/**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
	 *
	 * @param   Table   $table  Table name
	 * @param   integer  $id     Id
	 *
	 * @see Table::_getAssetParentId
	 *
	 * @return mixed The id on success, false on failure.
	 */
	protected function _getAssetParentId($table = null, $id = null)
	{
		// We will retrieve the parent-asset from the Asset-table
		$assetParent = Table::getInstance('Asset');

		// Default: if no asset-parent can be found we take the global asset
		$assetParentId = $assetParent->getRootId();

		// The item has the component as asset-parent
		$assetParent->loadByName(_JOOM_OPTION);

		// Return the found asset-parent-id
		if($assetParent->id)
		{
			$assetParentId = $assetParent->id;
		}

		return $assetParentId;
	}

  /**
   * Delete a record by id
   *
   * @param   mixed  $pk  Primary key value to delete. Optional
   *
   * @return bool
   */
  public function delete($pk = null)
  {
    $this->load($pk);
    $result = parent::delete($pk);

    return $result;
  }

  /**
   * Support for multiple field
   *
   * @param   mixed  $fieldData  Field data
   *
   * @return  mixed
   */
  protected function multipleFieldSupport($fieldData)
  {
    if(isset($fieldData))
		{
			if(is_array($fieldData))
			{
				$fieldData = implode(',',$fieldData);
			}
			elseif(strpos($fieldData, ',') != false)
			{
				$fieldData = explode(',',$fieldData);
			}
			elseif(strlen($fieldData) == 0)
			{
				$fieldData = '';
			}
		}
		else
		{
			$fieldData = '';
		}

    return $fieldData;
  }
}
