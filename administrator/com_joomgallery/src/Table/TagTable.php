<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;
 
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Access\Access;
use \Joomla\CMS\Table\Asset;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Versioning\VersionableTableInterface;
use \Joomla\Database\DatabaseDriver;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\Registry\Registry;

/**
 * Tag table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class TagTable extends Table implements VersionableTableInterface
{
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = _JOOM_OPTION.'.tag';

		parent::__construct(_JOOM_TABLE_TAGS, 'id', $db);

		$this->setColumnAlias('published', 'published');

	}

  /**
	 * Check if a field is unique
	 *
	 * @param   string   $field    Name of the field
	 *
	 * @return  bool    True if unique
	 */
	private function isUnique ($field)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->quoteName($field))
			->from($db->quoteName($this->_tbl))
			->where($db->quoteName($field) . ' = ' . $db->quote($this->$field))
			->where($db->quoteName('id') . ' <> ' . (int) $this->{$this->_tbl_key});

		$db->setQuery($query);
		$db->execute();

		return ($db->getNumRows() == 0) ? true : false;
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
	 * Method to return the title to use for the asset table.
	 *
	 * @return  string
	 *
	 * @since   1.6
	 */
	protected function _getAssetTitle()
	{
		return $this->title;
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
    $assetTable = new Asset(Factory::getContainer()->get(DatabaseInterface::class));

		// The item has the component as asset-parent
		$assetTable->loadByName(_JOOM_OPTION);

		// Return the found asset-parent-id
		if($assetTable->id)
		{
			$assetParentId = $assetTable->id;
		}
		else
		{
			// If no asset-parent can be found we take the global asset
			$assetParentId = $assetTable->getRootId();
		}

		return $assetParentId;
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
		$task = Factory::getApplication()->input->get('task', '', 'cmd');

    // Support for alias field: alias
		if(empty($array['alias']))
		{
			if(empty($array['title']))
			{
				$array['alias'] = OutputFilter::stringURLSafe(date('Y-m-d H:i:s'));
			}
			else
			{
				if(Factory::getConfig()->get('unicodeslugs') == 1)
				{
					$array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['title']));
				}
				else
				{
					$array['alias'] = OutputFilter::stringURLSafe(trim($array['title']));
				}
			}
		}
    else
    {
      if(Factory::getConfig()->get('unicodeslugs') == 1)
      {
        $array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['alias']));
      }
      else
      {
        $array['alias'] = OutputFilter::stringURLSafe(trim($array['alias']));
      }
    }

		if($array['id'] == 0)
		{
			$array['created_time'] = $date->toSql();
		}

		if($array['id'] == 0 && empty($array['created_by']))
		{
			$array['created_by'] = Factory::getUser()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_time'] = $date->toSql();
		}

		if($array['id'] == 0 && empty($array['modified_by']))
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_by'] = Factory::getUser()->id;
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

		if(!Factory::getUser()->authorise('core.admin', _JOOM_OPTION.'.tag.' . $array['id']))
		{
			$actions         = Access::getActionsFromFile(_JOOM_PATH_ADMIN.'/access.xml',	"/access/section[@name='tag']/");
			$default_actions = Access::getAssetRules(_JOOM_OPTION.'.tag.'.$array['id'])->getData();
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

    // Check if alias is unique
		if(!$this->isUnique('alias'))
		{
			$count = 2;
			$currentAlias =  $this->alias;

			while(!$this->isUnique('alias'))
      {
				$this->alias = $currentAlias . '-' . $count++;
			}
		}

    // Check if title is unique inside this category
		if(!$this->isUnique('title'))
		{
			$count = 2;
			$currentTitle =  $this->title;

			while(!$this->isUnique('title'))
      {
				$this->title = $currentTitle . ' (' . $count++ . ')';
			}
		}

		return parent::check();
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
}
