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
use Joomla\CMS\Event\AbstractEvent;
use \Joomla\CMS\Versioning\VersionableTableInterface;
use \Joomla\Database\DatabaseDriver;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\Registry\Registry;

/**
 * Image table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageTable extends Table implements VersionableTableInterface
{
  use JoomTableTrait;

	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = _JOOM_OPTION.'.image';

		parent::__construct(_JOOM_TABLE_IMAGES, 'id', $db);

		$this->setColumnAlias('published', 'published');
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
		return $this->imgtitle;
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

		if($this->catid)
		{
			// The image has a category as asset-parent
			$catId = (int) $this->catid;
			$assetTable->loadByName(_JOOM_OPTION.'.category.'.$catId);
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
   * @since   1.7.0
   * @throws  \InvalidArgumentException
   * @throws  \RuntimeException
   * @throws  \UnexpectedValueException
   */
  public function load($keys = null, $reset = true)
  {
    $success = parent::load($keys, $reset);

    if($success)
    {
      // Record successfully loaded
      // load Tags
      $com_obj    = Factory::getApplication()->bootComponent('com_joomgallery');
      $tags_model = $com_obj->getMVCFactory()->createModel('Tags', 'administrator');

      $this->tags = $tags_model->getMappedItems($this->id);
    }
    
    return $success;
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
		$date      = Factory::getDate();
		$task      = Factory::getApplication()->input->get('task', '', 'cmd');
		$component = Factory::getApplication()->bootComponent('com_joomgallery');

    // Support for id field
    if(!\key_exists('id', $array))
    {
      $array['id'] = 0;
    }

		// Support for alias field: alias
		if(empty($array['alias']))
		{
			if(empty($array['imgtitle']))
			{
				$array['alias'] = OutputFilter::stringURLSafe(date('Y-m-d H:i:s'));
			}
			else
			{
				if(Factory::getConfig()->get('unicodeslugs') == 1)
				{
					$array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['imgtitle']));
				}
				else
				{
					$array['alias'] = OutputFilter::stringURLSafe(trim($array['imgtitle']));
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

		// Support for multiple or not foreign key field: catid
			if(!empty($array['catid']))
			{
				if(is_array($array['catid']))
        {
					$array['catid'] = implode(',',$array['catid']);
				}
				else if(strrpos($array['catid'], ',') != false)
        {
					$array['catid'] = explode(',',$array['catid']);
				}
			}
			else
      {
				$array['catid'] = 0;
			}

		if($array['id'] == 0)
		{
			$array['created_time'] = $date->toSql();
		}

		if($array['id'] == 0 && (!\key_exists('created_by', $array) || empty($array['created_by'])))
		{
			$array['created_by'] = Factory::getUser()->id;
		}

		if($array['id'] == 0 && !$component->getConfig()->get('jg_approve'))
		{
			$array['approved'] = 1;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_time'] = $date->toSql();
		}

		if($array['id'] == 0 && (!\key_exists('modified_by', $array) ||empty($array['modified_by'])))
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

		// Support for multiple field: robots
		$this->multipleFieldSupport($array, 'robots');

		// Support for empty date field: imgdate
		if(!\key_exists('imgdate', $array) || $array['imgdate'] == '0000-00-00' || empty($array['imgdate']))
		{
			$array['imgdate'] = $date->toSql();
			$this->imgdate    = $date->toSql();
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

		if(!Factory::getUser()->authorise('core.admin', _JOOM_OPTION.'.image.' . $array['id']))
		{
			$actions         = Access::getActionsFromFile(_JOOM_PATH_ADMIN.'/access.xml', "/access/section[@name='image']/");
			$default_actions = Access::getAssetRules(_JOOM_OPTION.'.image.' . $array['id'])->getData();
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

    // Support for tags
    if(!isset($this->tags))
    {
      $this->tags = array();
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
    $success = parent::store($updateNulls);

    if($success)
    {
      // Record successfully stored
     	// Store Tags
	  	$com_obj    = Factory::getApplication()->bootComponent('com_joomgallery');
    	$tags_model = $com_obj->getMVCFactory()->createModel('Tags', 'administrator');

      // Create tags
      $this->tags = $tags_model->storeTagsList($this->tags);
      if($this->tags === false)
      {
        $this->setError('Tags Model reports '.$tags_model->getError());
        $success = false;
      }

      // Update tags mapping
      if(!$tags_model->updateMapping($this->tags, $this->id))
      {
        $this->setError('Tags Model reports '.$tags_model->getError());
        $success = false;
      }
    }

    return $success;
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
		if(!$this->isUnique('imgtitle', $this->catid))
		{
			$count = 2;
			$currentTitle =  $this->imgtitle;

			while(!$this->isUnique('imgtitle', $this->catid))
      {
				$this->imgtitle = $currentTitle . ' (' . $count++ . ')';
			}
		}

		// Support for subform field params
    if(empty($this->params))
    {
      $this->params = $this->loadDefaultField('params');
    }
		elseif(\is_array($this->params))
		{
			$this->params = json_encode($this->params, JSON_UNESCAPED_UNICODE);
		}

    // Support for field metadesc
    if(empty($this->metadesc))
    {
      $this->metadesc = $this->loadDefaultField('metadesc');
    }

    // Support for field metakey
    if(empty($this->metakey))
    {
      $this->metakey = $this->loadDefaultField('metakey');
    }

    // Support for field metakey
    if(empty($this->imgmetadata))
    {
      $this->imgmetadata = $this->loadDefaultField('imgmetadata');
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
    $success = parent::delete($pk);

    if($success)
    {
      // Record successfully deleted
      // Delete Tag mapping
      $com_obj   = Factory::getApplication()->bootComponent('com_joomgallery');
      $tag_model = $com_obj->getMVCFactory()->createModel('Tag', 'administrator');

      // remove tag from mapping
      foreach($this->tags as $tag)
      {
        if(!$tag_model->removeMapping($tag->id, $this->id))
        {
          $this->setError($tag_model->getError());
          $success = false;
        }
      }
    }

    return $success;
  }

  /**
	 * Method to set the state for a row or list of rows in the database table.
	 *
	 * The method respects checked out rows by other users and will attempt to checkin rows that it can after adjustments are made.
	 *
   * @param   string   $type    Name of the state to be changed
	 * @param   mixed    $pks     An optional array of primary key values to update. If not set the instance property value is used.
	 * @param   integer  $state   The new state.
	 * @param   integer  $userId  The user ID of the user performing the operation.
	 *
	 * @return  boolean  True on success; false if $pks is empty.
	 *
	 * @since   4.0.0
	 */
	public function changeState($type = 'publish', $pks = null, $state = 1, $userId = 0)
	{
		// Sanitize input
		$userId = (int) $userId;
		$state  = (int) $state;

		// Pre-processing by observers
		$event = AbstractEvent::create(
			'onTableBefore'.\ucfirst($type),
			[
				'subject'	=> $this,
				'pks'		  => $pks,
				'state'		=> $state,
				'userId'	=> $userId,
			]
		);
		$this->getDispatcher()->dispatch('onTableBefore'.\ucfirst($type), $event);

		if (!\is_null($pks))
		{
			if (!\is_array($pks))
			{
				$pks = array($pks);
			}

			foreach ($pks as $key => $pk)
			{
				if (!\is_array($pk))
				{
					$pks[$key] = array($this->_tbl_key => $pk);
				}
			}
		}

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks))
		{
			$pk = array();

			foreach ($this->_tbl_keys as $key)
			{
				if ($this->$key)
				{
					$pk[$key] = $this->$key;
				}
				// We don't have a full primary key - return false
				else
				{
					$this->setError(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));

					return false;
				}
			}

			$pks = array($pk);
		}

    switch($type)
    {
      case 'feature':
        $stateField  = 'featured';
        break;

      case 'approve':
        $stateField  = 'approved';
        break;
      
      case 'publish':
      default:
        $stateField  = 'published';
        break;
    }
		$checkedOutField = $this->getColumnAlias('checked_out');

		foreach ($pks as $pk)
		{
			// Update the publishing state for rows with the given primary keys.
			$query = $this->_db->getQuery(true)
				->update($this->_tbl)
				->set($this->_db->quoteName($stateField) . ' = ' . (int) $state);

			// If publishing, set published date/time if not previously set
			if ($state && $this->hasField('publish_up') && (int) $this->publish_up == 0)
			{
				$nowDate = $this->_db->quote(Factory::getDate()->toSql());
				$query->set($this->_db->quoteName($this->getColumnAlias('publish_up')) . ' = ' . $nowDate);
			}

			// Determine if there is checkin support for the table.
			if ($this->hasField('checked_out') || $this->hasField('checked_out_time'))
			{
				$query->where(
					'('
						. $this->_db->quoteName($checkedOutField) . ' = 0'
						. ' OR ' . $this->_db->quoteName($checkedOutField) . ' = ' . (int) $userId
						. ' OR ' . $this->_db->quoteName($checkedOutField) . ' IS NULL'
					. ')'
				);
				$checkin = true;
			}
			else
			{
				$checkin = false;
			}

			// Build the WHERE clause for the primary keys.
			$this->appendPrimaryKeys($query, $pk);

			$this->_db->setQuery($query);

			try
			{
				$this->_db->execute();
			}
			catch (\RuntimeException $e)
			{
				$this->setError($e->getMessage());

				return false;
			}

			// If checkin is supported and all rows were adjusted, check them in.
			if ($checkin && (\count($pks) == $this->_db->getAffectedRows()))
			{
				$this->checkIn($pk);
			}

			// If the Table instance value is in the list of primary keys that were set, set the instance.
			$ours = true;

			foreach ($this->_tbl_keys as $key)
			{
				if ($this->$key != $pk[$key])
				{
					$ours = false;
				}
			}

			if ($ours)
			{
				$this->$stateField = $state;
			}
		}

		$this->setError('');

		// Pre-processing by observers
		$event = AbstractEvent::create(
			'onTableAfter'.\ucfirst($type),
			[
				'subject'	=> $this,
				'pks'		=> $pks,
				'state'		=> $state,
				'userId'	=> $userId,
			]
		);
		$this->getDispatcher()->dispatch('onTableAfter'.\ucfirst($type), $event);

		return true;
	}

  /**
	 * Method to set the publishing state for a row or list of rows in the database table.
	 *
	 * The method respects checked out rows by other users and will attempt to checkin rows that it can after adjustments are made.
	 *
	 * @param   mixed    $pks     An optional array of primary key values to update. If not set the instance property value is used.
	 * @param   integer  $state   The publishing state. eg. [0 = unpublished, 1 = published]
	 * @param   integer  $userId  The user ID of the user performing the operation.
	 *
	 * @return  boolean  True on success; false if $pks is empty.
	 *
	 * @since   1.7.0
	 */
	public function publish($pks = null, $state = 1, $userId = 0)
	{
    return $this->changeState('publish', $pks, $state, $userId);
  } 
}
