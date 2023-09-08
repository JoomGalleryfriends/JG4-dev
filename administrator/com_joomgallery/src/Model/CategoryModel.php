<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Language\Multilanguage;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Model\JoomAdminModel;

/**
 * Category model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryModel extends JoomAdminModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'category';

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \JForm|boolean  A \JForm object on success, false on failure
	 *
	 * @since   4.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm($this->typeAlias, 'category', array('control' => 'jform', 'load_data' => $loadData ));

    // Apply filter to exclude child categories
    $children = $form->getFieldAttribute('parent_id', 'children', 'true');
    $children = filter_var($children, FILTER_VALIDATE_BOOLEAN);
    if(!$children)
    {
      $form->setFieldAttribute('parent_id', 'exclude', $this->item->id);
    }

		// Apply filter for current category on thumbnail field
    $form->setFieldAttribute('thumbnail', 'categories', $this->item->id);

		if(empty($form))
		{
			return false;
		}

		return $form;
	}

  /**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   4.0.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState(_JOOM_OPTION.'.edit.category.data', array());

		if(empty($data))
		{
			if($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;			

			// Support for multiple or not foreign key field: robots
			$array = array();

			foreach((array) $data->robots as $value)
			{
				if(!is_array($value))
				{
					$array[] = $value;
				}
			}
			if(!empty($array))
      {
			  $data->robots = $array;
			}
		}

		return $data;
	}

  /**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  Object|boolean Object on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function getItem($pk = null)
	{		
    if($this->item === null)
		{
			$this->item = false;

      if(empty($pk))
			{
				$pk = $this->getState('category.id');
			}

      if($this->item = parent::getItem($pk))
      {
        if(isset($this->item->params))
        {
          $this->item->params = json_encode($this->item->params);
        }
        
        // Do any procesing on fields here if needed
      }
    }

    return $this->item;
	}

  /**
	 * Method to delete one or more images.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   1.6
	 */
	public function delete(&$pks)
	{
		$pks   = ArrayHelper::toInteger((array) $pks);
		$table = $this->getTable();

    // Check if the deletion is forced
    $force_delete = Factory::getApplication()->input->get('del_force', false, 'BOOL');

		// Include the plugins for the delete events.
		PluginHelper::importPlugin($this->events_map['delete']);

		// Iterate the items to delete each one.
		foreach($pks as $i => $pk)
		{
			if($table->load($pk))
			{
				if($this->canDelete($table)) 
				{
					$context = $this->option . '.' . $this->name;

					// Trigger the before delete event.
					$result = Factory::getApplication()->triggerEvent($this->event_before_delete, array($context, $table));

					if(\in_array(false, $result, true))
					{
						$this->setError($table->getError());

						return false;
					}

					// Create file manager service
					$manager = JoomHelper::getService('FileManager');

          // Delete corresponding folders
					if(!$manager->deleteCategory($table, $force_delete))
					{
						$this->setError($this->component->getDebug(true));

						return false;
					}

					// Multilanguage: if associated, delete the item in the _associations table
					if($this->associationsContext && Associations::isEnabled())
					{
						$db = $this->getDbo();
						$query = $db->getQuery(true)
							->select(
								[
									'COUNT(*) AS ' . $db->quoteName('count'),
									$db->quoteName('as1.key'),
								]
							)
							->from($db->quoteName('#__associations', 'as1'))
							->join('LEFT', $db->quoteName('#__associations', 'as2'), $db->quoteName('as1.key') . ' = ' . $db->quoteName('as2.key'))
							->where(
								[
									$db->quoteName('as1.context') . ' = :context',
									$db->quoteName('as1.id') . ' = :pk',
								]
							)
							->bind(':context', $this->associationsContext)
							->bind(':pk', $pk, ParameterType::INTEGER)
							->group($db->quoteName('as1.key'));

						$db->setQuery($query);
						$row = $db->loadAssoc();

						if(!empty($row['count']))
						{
							$query = $db->getQuery(true)
								->delete($db->quoteName('#__associations'))
								->where(
									[
										$db->quoteName('context') . ' = :context',
										$db->quoteName('key') . ' = :key',
									]
								)
								->bind(':context', $this->associationsContext)
								->bind(':key', $row['key']);

							if($row['count'] > 2)
							{
								$query->where($db->quoteName('id') . ' = :pk')
									->bind(':pk', $pk, ParameterType::INTEGER);
							}

							$db->setQuery($query);
							$db->execute();
						}
					}

					if(!$table->delete($pk))
					{
						$this->setError($table->getError());

						return false;
					}

					// Trigger the after event.
					Factory::getApplication()->triggerEvent($this->event_after_delete, array($context, $table));
				}
				else
				{
					// Prune items that you can't change.
					unset($pks[$i]);
					$error = $this->getError();

					if($error)
					{
						Log::add($error, Log::WARNING, 'jerror');

						return false;
					}
					else
					{
						Log::add(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), Log::WARNING, 'jerror');

						return false;
					}
				}
			}
			else
			{
				$this->setError($table->getError());

				return false;
			}
		}

		// Output messages
		if(\count($this->component->getWarning()) > 1)
		{
			$this->component->printWarning();
		}

		// Output debug data
		if(\count($this->component->getDebug()) > 1)
		{
			$this->component->printDebug();
		}

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}
	
  /**
   * Method to save the form data.
   *
   * @param   array  $data  The form data.
   *
   * @return  boolean  True on success, False on error.
   *
   * @since   1.6
   */
  public function save($data)
  { 
    $table        = $this->getTable();
    $context      = $this->option . '.' . $this->name;
    $app          = Factory::getApplication();
    $isNew        = true;
    $catMoved     = false;
		$isCopy       = false;
    $aliasChanged = false;

    $key = $table->getKeyName();
    $pk  = (isset($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
    
    // Are we going to copy the image record?
    if($app->input->get('task') == 'save2copy')
		{
			$isCopy = true;
		}

    // Create tags
    if(\array_key_exists('tags', $data) && \is_array($data['tags']) && \count($data['tags']) > 0)
    {
      $table->newTags = $data['tags'];
    }    

    // Change language to 'All' if multilangugae is not enabled
    if (!Multilanguage::isEnabled())
    {
      $data['language'] = '*';
    }

    // Include the plugins for the save events.
    PluginHelper::importPlugin($this->events_map['save']);

    // Allow an exception to be thrown.
    try
    {
        // Load the row if saving an existing record.
        if($pk > 0)
        {
          $table->load($pk);
          $isNew = false;

          // Check if the parent category was changed
          if($table->parent_id != $data['parent_id'])
          {
            $catMoved = true;
          }

          // Check if the alias was changed
          if($table->alias != $data['alias'])
          {
            $aliasChanged = true;
          }
        }

        if($table->parent_id != $data['parent_id'] || $data['id'] == 0)
        {
          $table->setLocation($data['parent_id'], 'last-child');
        }

        // Create file manager service
				$manager = JoomHelper::getService('FileManager');

        // Bind the data.
        if(!$table->bind($data))
        {
          $this->setError($table->getError());

          return false;
        }

        // Prepare the row for saving
        $this->prepareTable($table);

        // Check the data.
        if(!$table->check())
        {
          $this->setError($table->getError());

          return false;
        }

        // Trigger the before save event.
        $result = $app->triggerEvent($this->event_before_save, array($context, $table, $isNew, $data));

        // Store the data.
        if(!$table->store())
        {
          $this->setError($table->getError());

          return false;
        }

        // Handle folders if parent category was changed
        if(!$isNew && $catMoved)
			  {
          // Get path back from old location temporarely
          $table->setPathWithLocation(true);

          // Move folder (including files and subfolders)
					$manager->moveCategory($table, $table->parent_id);

          // Reset path
          $table->setPathWithLocation(false);
        }
        // Handle folders if alias was changed
        elseif (!$isNew && $aliasChanged)
        {
          // Rename folder
					$manager->renameCategory($table, $table->alias);
        }
        else
        {
          // Create folders
          $manager->createCategory($table->alias, $table->parent_id);
        }

        // Handle folders if record gets copied
        if($isNew && $isCopy)
        {
          // Get source image id
          $source_id = $app->input->get('origin_id', false, 'INT');

          // Copy folder (including files and subfolders)
          $manager->copyCategory($source_id, $table->path);
        }

        if(\in_array(false, $result, true))
        {
          $this->setError($table->getError());

          return false;
        }

        // Clean the cache.
        $this->cleanCache();

        // Trigger the after save event.
        $app->triggerEvent($this->event_after_save, array($context, $table, $isNew, $data));
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());

      return false;
    }

    if(isset($table->$key))
    {
      $this->setState($this->getName() . '.id', $table->$key);
    }

    $this->setState($this->getName() . '.new', $isNew);

    if($this->associationsContext && Associations::isEnabled() && !empty($data['associations']))
    {
      $associations = $data['associations'];

      // Unset any invalid associations
      $associations = ArrayHelper::toInteger($associations);

      // Unset any invalid associations
      foreach($associations as $tag => $id)
      {
        if(!$id)
        {
          unset($associations[$tag]);
        }
      }

      // Show a warning if the item isn't assigned to a language but we have associations.
      if($associations && $table->language === '*')
      {
        $app->enqueueMessage(Text::_(strtoupper($this->option) . '_ERROR_ALL_LANGUAGE_ASSOCIATED'), 'warning');
      }

      // Get associationskey for edited item
      $db    = $this->getDbo();
      $id    = (int) $table->$key;
      $query = $db->getQuery(true)
          ->select($db->quoteName('key'))
          ->from($db->quoteName('#__associations'))
          ->where($db->quoteName('context') . ' = :context')
          ->where($db->quoteName('id') . ' = :id')
          ->bind(':context', $this->associationsContext)
          ->bind(':id', $id, ParameterType::INTEGER);
      $db->setQuery($query);
      $oldKey = $db->loadResult();

      if($associations || $oldKey !== null)
      {
        // Deleting old associations for the associated items
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__associations'))
            ->where($db->quoteName('context') . ' = :context')
            ->bind(':context', $this->associationsContext);

        $where = [];

        if($associations)
        {
          $where[] = $db->quoteName('id') . ' IN (' . implode(',', $query->bindArray(array_values($associations))) . ')';
        }

        if($oldKey !== null)
        {
          $where[] = $db->quoteName('key') . ' = :oldKey';
          $query->bind(':oldKey', $oldKey);
        }

        $query->extendWhere('AND', $where, 'OR');
        $db->setQuery($query);
        $db->execute();
      }

      // Adding self to the association
      if($table->language !== '*')
      {
        $associations[$table->language] = (int) $table->$key;
      }

      if(\count($associations) > 1)
      {
        // Adding new association for these items
        $key   = md5(json_encode($associations));
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__associations'))
            ->columns(
                [
                  $db->quoteName('id'),
                  $db->quoteName('context'),
                  $db->quoteName('key'),
                ]
            );

        foreach($associations as $id)
        {
          $query->values(
            implode(',', $query->bindArray([$id, $this->associationsContext, $key], [ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING]))
          );
        }

        $db->setQuery($query);
        $db->execute();
      }
    }

    if($app->input->get('task') == 'editAssociations')
    {
      return $this->redirectToAssociations($data);
    }

    return true;
  }

	/**
	* Method rebuild the entire nested set tree.
	* @return  boolean  False on failure or error, true otherwise.
	* @since   1.6
	*/
	public function rebuild()
	{
		$table = $this->getTable();

		if(!$table->rebuild())
		{
			$this->setError($table->getError());

			return false;
		}
    
		$this->cleanCache();

		return true;
	}

  /**
	 * Method to save the reordered nested set tree.
	 * First we save the new order values in the lft values of the changed ids.
	 * Then we invoke the table rebuild to implement the new ordering.
	 *
	 * @param   array    $idArray   An array of primary key ids.
	 * @param   integer  $lftArray  The lft value
	 *
	 * @return  boolean  False on failure or error, True otherwise
	 *
	 * @since   1.6
	 */
	public function saveorder($idArray = null, $lftArray = null)
	{
		// Get an instance of the table object.
		$table = $this->getTable();

		if(!$table->saveorder($idArray, $lftArray))
		{
			$this->setError($table->getError());

			return false;
		}

		// Clear the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to duplicate an Category
	 *
	 * @param   array  &$pks  An array of primary key IDs.
	 *
	 * @return  boolean  True if successful.
	 *
	 * @throws  Exception
	 */
	public function duplicate(&$pks)
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		// Access checks.
		if(!$user->authorise('core.create', _JOOM_OPTION))
		{
			throw new \Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
		}

		$context = $this->option . '.' . $this->name;

		// Include the plugins for the save events.
		PluginHelper::importPlugin($this->events_map['save']);

		$table = $this->getTable();

		foreach($pks as $pk)
		{
      if($table->load($pk, true))
      {
        // Reset the id to create a new record.
        $table->id = 0;

        // Original category path
        $origin_path = $table->path;

        // Specify where to insert the new node.
        $table->setLocation($table->parent_id, 'last-child');

        // Clean entered data
        if(!$table->check())
        {
          throw new \Exception($table->getError());
        }

        /// Create file manager service
				$manager = JoomHelper::getService('FileManager');

        // Copy folder
				$manager->copyCategory($origin_path, $table->parent_id, $table->alias);

        // Trigger the before save event.
        $result = $app->triggerEvent($this->event_before_save, array($context, &$table, true, $table));

        if(in_array(false, $result, true) || !$table->store(true, true))
        {
          throw new \Exception($table->getError());
        }

        // Rebuild entire nested set tree
        if(!$table->rebuild())
        {
          throw new \Exception($table->getError());
        }

        // Trigger the after save event.
        $app->triggerEvent($this->event_after_save, array($context, &$table, true));
      }
      else
      {
        throw new \Exception($table->getError());
      }			
		}

		// Clean cache
		$this->cleanCache();

		return true;
	}

  /**
   * Get children categories.
   * 
   * @param   integer  $pk     The id of the primary key.
   * @param   bool     $self   Include current node id (default: false)
   *
   * @return  mixed    An array of categories or false if an error occurs.
   *
   * @since   4.0.0
   */
  public function getChildren($pk = null, $self = false)
  {
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk = intval($this->item->id);
    }

    $table = $this->getTable();
    if($table->load($pk) === false)
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk));

      return false;
    }

    // add root category
    $root = false;
    if($pk == 1 && $self)
    {
      $root = true;
    }

    $children = $table->getNodeTree('children', $self, $root);
    if(!$children)
    {
      $this->setError($table->getError());

      return false;
    }
    
    return $children;
  }

  /**
   * Get parent categories.
   * 
   * @param   integer  $pk     The id of the primary key.
   * @param   bool     $self   Include current node id (default: false)
   * @param   bool     $root   Include root node (default: false)
   *
   * @return  mixed    An array of categories or false if an error occurs.
   *
   * @since   4.0.0
   */
  public function getParents($pk = null, $self = false, $root = false)
  {
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk = intval($this->item->id);
    }

    $table = $this->getTable();
    if($table->load($pk) === false)
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk));

      return false;
    }

    $parents = $table->getNodeTree('parents', $self, $root);
    if(!$parents)
    {
      $this->setError($table->getError());

      return false;
    }
    
    return $parents;
  }

  /**
   * Get category tree
   * 
   * @param   integer  $pk     The id of the primary key.
   * @param   bool     $self   Include current node id (default: false)
   * @param   bool     $root   Include root node (default: false)
   *
   * @return  mixed    An array of categories or false if an error occurs.
   *
   * @since   4.0.0
   */
  public function getTree($pk = null, $root = false)
  {
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk = intval($this->item->id);
    }

    $table = $this->getTable();
    if($table->load($pk) === false)
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk));

      return false;
    }

    $tree = $table->getNodeTree('cpl', true, $root);
    if(!$tree)
    {
      $this->setError($table->getError());

      return false;
    }
    
    return $tree;
  }

  /**
   * Get direct left or right sibling (adjacent) of the category.
   * 
   * @param   integer  $pk    The id of the primary key.
   * @param   string   $side  Left or right side ribling. 
   *
   * @return  mixed    List of sibling or false if an error occurs.
   *
   * @since   4.0.0
   */
  public function getSibling($pk, $side)
  {
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk = intval($this->item->id);
    }

    $table = $this->getTable();
    if($table->load($pk) === false)
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk));

      return false;
    }
    
    $sibling = $table->getSibling($side, true);

    if(!$sibling)
    {
      $this->setError($table->getError());

      return false;
    }
    
    return $sibling;
  }

  /**
   * Get all left and/or right siblings (adjacent) of the category.
   * 
   * @param   integer  $pk    The id of the primary key.
   * @param   string   $side  Left, right or both sides siblings.
   *
   * @return  mixed    List of siblings or false if an error occurs.
   *
   * @since   4.0.0
   */
  public function getSiblings($pk, $side)
  {
    $parent_id = null;
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk        = intval($this->item->id);
      $parent_id = intval($this->item->parent_id);
    }

    // Load catgory table
    $table = $this->getTable();
    if($table->load($pk) === false)
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk));

      return false;
    }

    if(\is_null($parent_id))
    {
      $parent_id = intval($table->parent_id);
    }

    // Load parent table
    $ptable = $this->getTable();
    if($ptable->load($parent_id) === false)
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $parent_id));

      return false;
    }
    
    $sibling = $table->getSibling($side, false, $ptable);

    if(!$sibling)
    {
      $this->setError($table->getError());

      return false;
    }
    
    return $sibling;
  }
}
