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
use \Joomla\CMS\Table\Nested as Table;
use \Joomla\CMS\Versioning\VersionableTableInterface;
use \Joomla\Database\DatabaseDriver;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Category table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryTable extends Table implements VersionableTableInterface
{ 
  use JoomTableTrait;
  
  /**
   * Object property to hold the path of the new location reference node.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $_new_location_path = null;

  /**
   * Object property to hold the path of the old location reference node.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $_old_location_path = null;

	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = _JOOM_OPTION.'.category';

		parent::__construct(_JOOM_TABLE_CATEGORIES, 'id', $db);

		$this->setColumnAlias('published', 'published');
		$this->getRootId();
	}

  /**
	 * Check if a field is unique
	 *
	 * @param   string   $field    Name of the field
   * @param   integer  $parent   Parent category id (default=null)
	 *
	 * @return  bool    True if unique
	 */
	private function isUnique ($field, $parent=null)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->quoteName($field))
			->from($db->quoteName($this->_tbl))
			->where($db->quoteName($field) . ' = ' . $db->quote($this->$field))
			->where($db->quoteName('id') . ' <> ' . (int) $this->{$this->_tbl_key});
    
    if($parent > 0)
    {
      $query->where($db->quoteName('parent_id') . ' = ' . $db->quote($parent));
    }    

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
	 * @since   4.0.0
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

		if($this->parent_id && \intval($this->parent_id) >= 1)
		{
			// The item has a category as asset-parent
			$parent_id = \intval($this->parent_id);
			$assetTable->loadByName(_JOOM_OPTION.'.category.'.$parent_id);
		}
		else
		{
			// The item has the component as asset-parent
			$assetTable->loadByName(_JOOM_OPTION);
		}

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
   * Method to load a row from the database by primary key and bind the fields to the Table instance properties.
   *
   * @param   mixed    $keys   An optional primary key value to load the row by, or an array of fields to match.
   *                           If not set the instance property value is used.
   * @param   boolean  $reset  True to reset the default values before loading the new row.
   *
   * @return  boolean  True if successful. False if row not found.
   *
   * @since   4.0.0
   */
  public function load($keys = null, $reset = true)
  {
    $res = parent::load($keys, $reset);

    if(isset($this->path))
    {
      $this->_old_location_path = $this->path;
    }

    return $res;
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

		if($array['id'] == 0)
		{
			$array['created_time'] = $date->toSql();
		}

		if($array['id'] == 0 && empty($array['created_by']))
		{
			$array['created_by'] = Factory::getUser()->id;
		}

		if($array['id'] == 0 && empty($array['modified_by']))
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_time'] = $date->toSql();
		}

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

		// Support for multiple field: robots
		$this->multipleFieldSupport($array, 'robots');

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

		if(!Factory::getUser()->authorise('core.admin', _JOOM_OPTION.'.category.'.$array['id']))
		{
			$actions         = Access::getActionsFromFile(_JOOM_PATH_ADMIN.'/access.xml', "/access/section[@name='category']/");
			$default_actions = Access::getAssetRules(_JOOM_OPTION.'.category.'.$array['id'])->getData();
			$array_jaccess   = array();

			foreach($actions as $action)
			{
				if (key_exists($action->name, $default_actions))
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
    $this->setPathWithLocation();

		return parent::store($updateNulls);
	}

  /**
	 * Method to set path based on the location properties.
	 *
	 * @param   boolean  $old  To use the old location path.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
  public function setPathWithLocation($old = false)
  {
    // Check with new categories and category data changes!!
    if($old && $this->_old_location_path)
    {
      $this->path = $this->_old_location_path;
    }
    elseif($this->_new_location_path)
    {
      $this->path = \str_replace('{alias}', $this->alias, $this->_new_location_path);
    }
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
			$count = 0;
			$currentAlias =  $this->alias;

			while(!$this->isUnique('alias'))
      {
				$this->alias = $currentAlias . '-' . $count++;
			}
		}

    // Check if title is unique inside this parent category
		if(!$this->isUnique('title', $this->parent_id))
		{
			$count = 0;
			$currentTitle =  $this->title;

			while(!$this->isUnique('title', $this->parent_id))
      {
				$this->title = $currentTitle . ' (' . $count++ . ')';
			}
		}

    // Check if path is correct
    $manager    = JoomHelper::getService('FileManager');
    $this->path = $manager->getCatPath($this->id, false, $this->parent_id, $this->alias);

		// Support for subform field params
		if(is_array($this->params))
		{
			$this->params = json_encode($this->params, JSON_UNESCAPED_UNICODE);
		}

		return parent::check();
	}

  /**
   * Method to set the location of a node in the tree object.  This method does not
   * save the new location to the database, but will set it in the object so
   * that when the node is stored it will be stored in the new location.
   *
   * @param   integer  $referenceId  The primary key of the node to reference new location by.
   * @param   string   $position     Location type string.
   *
   * @return  void
   *
   * @note    Since 3.0.0 this method returns void and throws an \InvalidArgumentException when an invalid position is passed.
   * @see     Nested::$_validLocations
   * @since   1.7.0
   * @throws  \InvalidArgumentException
   */
  public function setLocation($referenceId, $position = 'after')
  {
    parent::setLocation($referenceId, $position);

    if($referenceId !== 0 && !empty($this->id))
    {
      $referenceObj = JoomHelper::getRecord('category', $referenceId);

      if(empty($referenceObj->path))
      {
        $this->_new_location_path = '{alias}';
      }
      else
      {
        $this->_new_location_path = $referenceObj->path.'/{alias}';
      }      
    }
  }

  /**
   * Delete a record by id
   *
   * @param  mixed   $pk  Primary key value to delete. Optional
   * @param  boolean  $children  True to delete child nodes, false to move them up a level.
   * @return bool
   */
  public function delete($pk = null, $children = true)
  {
    $result = parent::delete($pk, $children);

    return $result;
  }

  /**
   * Add the root node to an empty table.
   *
   * @return    mixed  The id of the new root node or false on error.
   */
  public function addRoot()
  {
    $db = Factory::getDbo();

    $checkQuery = $db->getQuery(true);
    $checkQuery->select('*');
    $checkQuery->from(_JOOM_TABLE_CATEGORIES);
    $checkQuery->where('level = 0');

    $db->setQuery($checkQuery);

    // Add root category
    if(empty($db->loadAssoc()))
    {
      $query = $db->getQuery(true)
      ->insert(_JOOM_TABLE_CATEGORIES)
      ->set('parent_id = 0')
      ->set('lft = 0')
      ->set('rgt = 1')
      ->set('level = 0')
      ->set('path = ' . $db->quote(''))
      ->set('title = ' . $db->quote('Root'))
      ->set('alias = ' . $db->quote('root'))
      ->set('description = ' . $db->quote(''))
      ->set('access = 1')
      ->set('published = 1')
      ->set('params = ' . $db->quote(''))
      ->set('language = ' . $db->quote('*'))
      ->set('metadesc = ' . $db->quote(''))
      ->set('metakey = ' . $db->quote(''));
      
      $db->setQuery($query);

      if(!$db->execute())
      {
        Factory::getApplication()->enqueueMessage(Text::_('Error create root category'), 'error');

        return false;
      }      
      $root_catid = $db->insertid();

      // Get parent id for asset
      $old_parentID = $this->parent_id;
      $this->parent_id = 0;
      $parentId = $this->_getAssetParentId();
      $this->parent_id = $old_parentID;
      
      // Get asset name
      $name = $this->typeAlias . '.1';

      // Create asset for root category
      $assetTable = new Asset(Factory::getContainer()->get(DatabaseInterface::class));
      $assetTable->loadByName($name);

      if($assetTable->getError())
      {
        Factory::getApplication()->enqueueMessage(Text::_('Error load asset for root category creation'), 'error');

        return false;
      }
      else
      {
        // Specify how a new or moved node asset is inserted into the tree.
        if(empty($assetTable->id) || $assetTable->parent_id != $parentId)
        {
          $assetTable->setLocation($parentId, 'last-child');
        }

        // Prepare the asset to be stored.
        $assetTable->parent_id = $parentId;
        $assetTable->name      = $name;
        $assetTable->title     = 'Root';
        $assetTable->rules     = '{}';

        if(!$assetTable->check() || !$assetTable->store(false))
        {
          Factory::getApplication()->enqueueMessage(Text::_('Error create asset for root category'), 'error');

          return false;
        }
      }

      // Connect root category with asset table
      $query = $db->getQuery(true);
      $query->update($db->quoteName(_JOOM_TABLE_CATEGORIES))->set($db->quoteName('asset_id') . ' = ' . $assetTable->id)->where($db->quoteName('id') . ' = ' . $root_catid);
      $db->setQuery($query);

      if(!$db->execute())
      {
        Factory::getApplication()->enqueueMessage(Text::_('Error connect root category with asset'), 'error');

        return false;
      }

      return $root_catid;
    }

    return true;
  }

  /**
   * Get root node id
   *
   * @return    int  The id of the root node.
   */
  public function getRootId()
  {
    $rootId = parent::getRootId();

    // If root is not set then create it.
    if($rootId === false)
    {
      $rootId = $this->addRoot();
    }

    return $rootId;
  }

  /**
   * Get a node tree based on current category (children, parents, complete)
   *
   * @param    string   $type     Which kind of nde tree (default: cpl)
   * @param    bool     $self     Include current node id (default: false)
   * @param    bool     $root     Include root node (default: false)
   * 
   * @return   array  List tree node node ids ordered by level ascending.
   * @throws  \UnexpectedValueException
   */
  public function getNodeTree($type = 'cpl', $self = false, $root = false)
  {
    // Check if object is loaded
    if(!$this->id)
    {
      throw new \UnexpectedValueException('Table not loaded. Load table first.');
    }

    // Convert type
    switch($type)
    {
      case 'parents':
        $type = 'parents';
        break;

      case 'childs':
      case 'children':
        $type = 'children';
        break;
      
      default:
        $type = 'cpl';
        break;
    }

    // Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

    // Select the required fields from the table.
		$query->select(array('id', 'level', 'title'));
    $query->from($db->quoteName(_JOOM_TABLE_CATEGORIES));

    if($type === 'children')
    {
      // Select children
      if($self)
      {
        $query->where($db->quoteName('lft') . ' >= ' . $this->lft . ' AND ' . $db->quoteName('rgt') . ' <= ' . $this->rgt);
      }
      else
      {
        $query->where($db->quoteName('lft') . ' > ' . $this->lft . ' AND ' . $db->quoteName('rgt') . ' < ' . $this->rgt);
      }
    }
    elseif($type === 'parents')
    {
      // Select parents
      if($self)
      {
        $query->where($db->quoteName('lft') . ' <= ' . $this->lft . ' AND ' . $db->quoteName('rgt') . ' >= ' . $this->rgt);
      }
      else
      {
        $query->where($db->quoteName('lft') . ' < ' . $this->lft . ' AND ' . $db->quoteName('rgt') . ' > ' . $this->rgt);
      }
    }
    else
    {
      // children and itself
      $cWhere = '(' . $db->quoteName('lft') . ' >= ' . $this->lft . ' AND ' . $db->quoteName('rgt') . ' <= ' . $this->rgt . ')';
      // parents
      $pWhere = '(' . $db->quoteName('lft') . ' < ' . $this->lft . ' AND ' . $db->quoteName('rgt') . ' > ' . $this->rgt . ')';

      $query->where('(' . implode(' OR ', array($cWhere, $pWhere)) . ')');
    }

    // Exclude root category
    if(!$root)
    {
      $query->where($db->quoteName('level') . ' > 0');
    }
    
    // Apply ordering
    $query->order($db->quoteName('level') . ' ASC');

    // Reset the query using our newly populated query object.
    $db->setQuery($query);

    if(!$tree = $db->loadAssocList())
    {
      if($type === 'children')
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_NO_CHILDREN_FOUND'));
      }
      elseif($type === 'parents')
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_NO_PARENT_FOUND'));
      }
      else
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_GETNODETREE'));
      }
    }

    return $tree;
  }

  /**
   * Get a list of (direct) siblings (left, right, both)
   *
   * @param    string   $type    Left or right siblings (default: both)
   * @param    bool     $direct  Only direct siblings (default: true)
   * @param    object   $parent  Parent category (only needed if direct=false)
   * 
   * @return   array  List of siblings.
   * @throws  \UnexpectedValueException
   */
  public function getSibling($type = 'both', $direct = true, $parent = null)
  {
    // Check if object is loaded
    if(!$this->id)
    {
      throw new \UnexpectedValueException('Table not loaded. Load table first.');
    }

    // Convert type
    switch($type)
    {
      case 'left':
      case 'lft':
      case 'l':
        $type = 'left';
        break;

      case 'right':
      case 'rgt':
      case 'r':
        $type = 'right';
        break;
      
      default:
        $type = 'both';
        break;
    }

    // Check if parent object is loaded
    if(!$direct && !$parent->id)
    {
      throw new \UnexpectedValueException('Parent table not loaded. Load parent table first.');
    }

    // Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

    // Select the required fields from the table.
		$query->select(array('id', 'title', 'lft', 'rgt'));
    $query->from($db->quoteName(_JOOM_TABLE_CATEGORIES));

    if($type === 'left')
    {
      // Select left siblings
      if($direct)
      {
        $query->where($db->quoteName('rgt') . ' = ' . strval($this->lft - 1));
      }
      else
      {
        $query->where($db->quoteName('lft') . ' > ' . strval($parent->lft) . ' AND ' . $db->quoteName('rgt') . ' < ' . strval($this->lft));
      }
    }
    elseif($type === 'right')
    {
      // Select right siblings
      if($direct)
      {
        $query->where($db->quoteName('lft') . ' = ' . strval($this->rgt + 1));
      }
      else
      {
        $query->where($db->quoteName('lft') . ' > ' . strval($this->rgt) . ' AND ' . $db->quoteName('rgt') . ' < ' . strval($parent->rgt));
      }
    }
    else
    {
      // Select all siblings
      if($direct)
      {
        $query->where($db->quoteName('rgt') . ' = ' . strval($this->lft - 1). ' OR ' . $db->quoteName('lft') . ' = ' . strval($this->rgt + 1));
      }
      else
      {
        $query->where($db->quoteName('id') . ' != ' . $this->id);
      }
    }

    //Apply level
    $query->where($db->quoteName('level') . ' = ' . $this->level);

    // Apply ordering
    if(!$direct)
    {
      $query->order($db->quoteName('lft') . ' ASC');
    }

    // Reset the query using our newly populated query object.
    $db->setQuery($query);

    $siblings = $db->loadAssocList();

    if(!$siblings)
    {
      $this->setError(Text::_('COM_JOOMGALLERY_ERROR_NO_SIBLING_FOUND'));
    }

    // Loop through the sibling and add the position (left or right)
    foreach($siblings as $key => $sibling)
    {
      if($sibling['rgt'] < $this->lft)
      {
        // left sibling
        $siblings[$key]['side'] = 'left';
      }
      elseif($sibling['lft'] > $this->rgt)
      {
        // right sibling
        $siblings[$key]['side'] = 'right';
      }
      else
      {
        throw new \UnexpectedValueException('Unexpected sibling received.');
      }

      unset($siblings[$key]['lft']);
      unset($siblings[$key]['rgt']);
    }

    return $siblings;
  }
}
