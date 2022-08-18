<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Form\Form;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Object\CMSObject;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Language\Multilanguage;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Model\JoomAdminModel;

/**
 * Image model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageModel extends JoomAdminModel
{
	/**
	 * @var    string  The prefix to use with controller messages.
	 *
	 * @since  4.0.0
	 */
	protected $text_prefix = _JOOM_OPTION_UC;

	/**
	 * @var    string  Alias to manage history control
	 *
	 * @since  4.0.0
	 */
	public $typeAlias = _JOOM_OPTION.'.image';

	/**
	 * @var    null  Item data
	 *
	 * @since  4.0.0
	 */
	protected $item = null;

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 *
	 * @since   4.0.0
	 */
	public function getTable($type = 'Image', $prefix = 'Administrator', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

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
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm($this->typeAlias, 'image',	array('control' => 'jform',	'load_data' => $loadData));

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
		$data = Factory::getApplication()->getUserState(_JOOM_OPTION.'.edit.image.data', array());

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
	 * @param   integer|array  $pk  The id of the primary key or array(fieldname => value)
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function getItem($pk = null) 
	{
    $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');
		$table = $this->getTable();

		if($pk > 0 || \is_array($pk))
		{
			// Attempt to load the row.
			$return = $table->load($pk);

			// Check for a table object error.
			if($return === false)
			{
				// If there was no underlying error, then the false means there simply was not a row in the db for this $pk.
				if(!$table->getError())
				{
					$this->setError(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'));
				}
				else
				{
					$this->setError($table->getError());
				}

				return false;
			}
    	}

    	// Convert to the CMSObject before adding other data.
		$properties = $table->getProperties(1);
		$item = ArrayHelper::toObject($properties, CMSObject::class);

		if(property_exists($item, 'params'))
		{
			$registry = new Registry($item->params);
			$item->params = $registry->toArray();
		}

		if(isset($item->params))
		{
		$item->params = json_encode($item->params);
		}

		return $item;
	}

	/**
	 * Method to duplicate an Image
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

				if(!$table->check())
				{
					throw new \Exception($table->getError());
				}

				if(!empty($table->catid))
				{
					if(is_array($table->catid))
					{
						$table->catid = implode(',', $table->catid);
					}
				}
				else
				{
					$table->catid = '';
				}

				// Create file manager service
				$manager = JoomHelper::getService('FileManager');

				// Regenerate filename
				$newFilename = $manager->regenFilename($table->filename);

				// Copy images
				$manager->copyImages($table, $table->catid, $newFilename);

        // Transfer new filename to table
        $table->filename = $newFilename;

				// Output warning messages
				if(\count($this->component->getWarning()) > 1)
				{
					$this->component->printWarning();
				}

				// Output debug data
				if(\count($this->component->getDebug()) > 1)
				{
					$this->component->printDebug();
				}

				// Trigger the before save event.
				$result = $app->triggerEvent($this->event_before_save, array($context, &$table, true, $table));

				if(in_array(false, $result, true) || !$table->store())
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
	 * Method to save image from form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   1.6
	 */
	public function save($data)
	{
		$table       = $this->getTable();
		$context     = $this->option . '.' . $this->name;
		$app         = Factory::getApplication();
		$imgUploaded = false;
		$catMoved    = false;
		$isNew       = true;
		$isCopy      = false;

		$key = $table->getKeyName();
		$pk  = (isset($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');

		// Are we going to copy the image record?
    	if($app->input->get('task') == 'save2copy')
		{
			$isCopy = true;
		}
		
		// Rertrieve request image file data
    	if(\array_key_exists('image', $app->input->files->get('jform')) && !empty($app->input->files->get('jform')['image'])
			&& $app->input->files->get('jform')['image']['error'] != 4 &&  $app->input->files->get('jform')['image']['size'] > 0)
		{
			$imgUploaded = true;
			$data['images'] = array();
			\array_push($data['images'], $app->input->files->get('jform')['image']);
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

		// Record editing and image creation
		try
		{
			// Load the row if saving an existing record.
			if($pk > 0)
			{
				$table->load($pk);
				$isNew    = false;

				// Check if the category was changed
				if($table->catid != $data['catid'])
				{
					$catMoved = true;
				}
			}

			// Save form data in session
			$app->setUserState(_JOOM_OPTION.'.image.upload', $data);

			// Retrieve image from request
			if($imgUploaded)
			{
				// Create uploader service
				$uploader = JoomHelper::getService('uploader', array('html', false));

				// Determine if we have to create new filename
				$createFilename = false;
				if($isNew || empty($data['filename']))
				{
					$createFilename = true;
				}

				// Retrieve image
				// (check upload, check user upload limit, create filename, onJoomBeforeSave)
				if(!$uploader->retrieveImage($data, $createFilename))
				{
					$this->setError($this->component->getDebug());

					return false;
				}

				// Override data with image metadata
				if(!$uploader->overrideData($data))
				{
					$this->setError($this->component->getDebug());

					return false;
				}
			}

      // Handle images if category was changed
			if(!$isNew && $catMoved)
			{
				// Create file manager service
				$manager = JoomHelper::getService('FileManager');

				if($imgUploaded)
				{
					// Delete Images
					$manager->deleteImages($table);
				}
				else
				{
					// Move Images
					$manager->moveImages($table, $data['catid']);
				}				
			}

			// Bind data to table object
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

			// Handle images if record gets copied
			if($isNew && $isCopy)
			{
				// Create file manager service
				$manager = JoomHelper::getService('FileManager');

				// Get source image id
				$source_id = $app->input->get('origin_id', false, 'INT');

				if(!$imgUploaded)
				{
					// Regenerate filename
					$table->filename = $manager->regenFilename($data['filename']);

					// Copy Images
					$manager->copyImages($source_id, $data['catid'], $table->filename);
				}
			}

			// Create images
			if($imgUploaded)
			{
				// Create images
				// (create imagetypes, upload imagetypes to storage, onJoomAfterUpload)
				if(!$uploader->createImage($table))
				{
					$uploader->rollback();
					$this->setError($this->component->getDebug());

					return false;
				}
			}

			// Handle images if category was changed
			if(!$isNew && $catMoved)
			{
				// Create file manager service
				$manager = JoomHelper::getService('FileManager');

				if($imgUploaded)
				{
					// Delete Images
					$manager->deleteImages($table);
				}
				else
				{
					// Move Images
					$manager->moveImages($table, $data['catid']);
				}				
			}

			// Handle images if record gets copied
			if($isNew && $isCopy)
			{
				// Create file manager service
				$manager = JoomHelper::getService('FileManager');

				// Get source image id
				$source_id = $app->input->get('origin_id', false, 'INT');

				if($imgUploaded)
				{
					// Delete Images
					$manager->deleteImages($source_id);
				}
				else
				{
					// Regenerate filename
					$table->filename = $manager->regenFilename($data['filename']);

					// Copy Images
					$manager->copyImages($source_id, $data['catid'], $table->filename);
				}
			}

			// Create images
			if($imgUploaded)
			{
				// Create images
				// (create imagetypes, upload imagetypes to storage, onJoomAfterUpload)
				if(!$uploader->createImage($table))
				{
					$uploader->rollback();
					$this->setError($this->component->getDebug());

					return false;
				}
			}

			// Trigger the before save event.
			$result = $app->triggerEvent($this->event_before_save, array($context, $table, $isNew, $data));

			if(\in_array(false, $result, true))
			{
        		if($imgUploaded)
				{
        			$uploader->rollback();
				}
				$this->setError($table->getError());

				return false;
			}

			// Store the data.
			if(!$table->store())
			{
				if($imgUploaded)
				{
        			$uploader->rollback();
				}
				$this->setError($table->getError());

				return false;
			}

			// Clean the cache.
			$this->cleanCache();

			// Trigger the after save event.
			$app->triggerEvent($this->event_after_save, array($context, $table, $isNew, $data));
		}
		catch (\Exception $e)
		{
			if($imgUploaded)
			{
				$uploader->rollback();
			}
			$this->setError($e->getMessage());

			return false;
		}
		
		// Output warning messages
		if(\count($this->component->getWarning()) > 1)
		{
			$this->component->printWarning();
		}

		// Output debug data
		if(\count($this->component->getDebug()) > 1)
		{
			$this->component->printDebug();
		}

		// Set state
		if(isset($table->$key))
		{
			$this->setState($this->getName() . '.id', $table->$key);
		}

		$this->setState($this->getName() . '.new', $isNew);

		// Create associations
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
				$app->enqueueMessage(Text::_(strtoupper($this->option) . '_ERROR_ALL_LANGUAGE_ASSOCIATED'),	'warning');
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
						implode(
							',',
							$query->bindArray(
								[$id, $this->associationsContext, $key],
								[ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING]
							)
						)
					);
				}

				$db->setQuery($query);
				$db->execute();
			}
		}

		// Redirect to associations
		if($app->input->get('task') == 'editAssociations')
		{
			return $this->redirectToAssociations($data);
		}

		return true;
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

					// Delete corresponding imagetypes
					$manager = JoomHelper::getService('FileManager');

					if(!$manager->deleteImages($table))
					{
						$this->setError($this->component->getDebug());

						return false;
					}

					// Delete corresponding comments

					// Delete corresponding votes

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
	 * Method to change the state of one or more records.
	 *
   * @param   string   $type   Name of the state to be changed
	 * @param   array    &$pks   A list of the primary keys to change.
	 * @param   integer  $value  The value of the state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function changeSate($type='publish', &$pks, $value = 1)
	{
		$user    = Factory::getUser();
		$table   = $this->getTable();
		$pks     = (array) $pks;
		$context = $this->option . '.' . $this->name . '.' . $type;

		// Include the plugins for the change of state event.
		PluginHelper::importPlugin($this->events_map['change_state']);

		// Access checks.
		foreach($pks as $i => $pk)
		{
			$table->reset();

			if($table->load($pk))
			{
				if(!$this->canEditState($table))
				{
					// Prune items that you can't change.
					unset($pks[$i]);

					Log::add(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), Log::WARNING, 'jerror');

					return false;
				}

				// If the table is checked out by another user, drop it and report to the user trying to change its state.
				if($table->hasField('checked_out') && $table->checked_out && ($table->checked_out != $user->id))
				{
					Log::add(Text::_('JLIB_APPLICATION_ERROR_CHECKIN_USER_MISMATCH'), Log::WARNING, 'jerror');

					// Prune items that you can't change.
					unset($pks[$i]);

					return false;
				}

        switch($type)
        {
          case 'feature':
            $stateColumnName  = 'featured';
            break;

          case 'approve':
            $stateColumnName  = 'approved';
            break;
          
          case 'publish':
          default:
            $stateColumnName  = 'published';
            break;
        }

				if(property_exists($table, $stateColumnName) && $table->get($stateColumnName, $value) == $value)
				{
					unset($pks[$i]);

					continue;
				}
			}
		}

		// Check if there are items to change
		if(!\count($pks))
		{
			return true;
		}

		// Trigger the before change state event.
		$result = Factory::getApplication()->triggerEvent($this->event_before_change_state, array($context, $pks, $value));

		if(\in_array(false, $result, true))
		{
			$this->setError($table->getError());

			return false;
		}

		// Attempt to change the state of the records.
		if (!$table->changeState($type, $pks, $value, $user->get('id')))
		{
			$this->setError($table->getError());

			return false;
		}

		// Trigger the change state event.
		$result = Factory::getApplication()->triggerEvent($this->event_change_state, array($context, $pks, $value));

		if (\in_array(false, $result, true))
		{
			$this->setError($table->getError());

			return false;
		}

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}

  /**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array    &$pks   A list of the primary keys to change.
	 * @param   integer  $value  The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function publish(&$pks, $value = 1)
	{
    return $this->changeSate('publish', $pks, $value);
  }

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   Table  $table  Table Object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if(empty($table->id))
		{
			// Set ordering to the last item if not set
			if(@$table->ordering === '')
			{
				$db = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM '._JOOM_TABLE_IMAGES);
        
				$max             = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

  /**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   Form    $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function preprocessForm(Form $form, $data, $group = 'joomgallery')
	{
		if (!Multilanguage::isEnabled())
		{
			$form->setFieldAttribute('language', 'type', 'hidden');
			$form->setFieldAttribute('language', 'default', '*');
		}

		parent::preprocessForm($form, $data, $group);
	}
}
