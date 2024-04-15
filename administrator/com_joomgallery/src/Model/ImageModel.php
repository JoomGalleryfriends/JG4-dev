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
use \Joomla\CMS\Log\Log;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Language\Text;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Language\Multilanguage;
use \Joomla\CMS\Form\FormFactoryInterface;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;
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
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'image';

  /**
   * The event to trigger after recreation of imagetypes.
   *
   * @var    string
   * 
   * @since  4.0.0
   */
  protected $event_after_recreate = null;

  /**
   * The event to trigger before recreation of imagetypes.
   *
   * @var    string
   * 
   * @since  4.0.0
   */
  protected $event_before_recreate = null;

  /**
   * Constructor.
   *
   * @param   array                 $config       An array of configuration options (name, state, dbo, table_path, ignore_request).
   * @param   MVCFactoryInterface   $factory      The factory.
   * @param   FormFactoryInterface  $formFactory  The form factory.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
  {
    parent::__construct($config, $factory, $formFactory);

    // Set event after recreate
    if(isset($config['event_after_recreate']))
    {
      $this->event_after_recreate = $config['event_after_recreate'];
    }
    elseif(empty($this->event_after_recreate))
    {
      $this->event_after_recreate = 'onJoomAfterRecreate';
    }

    // Set event before recreate
    if(isset($config['event_before_recreate']))
    {
      $this->event_before_recreate = $config['event_before_recreate'];
    }
    elseif(empty($this->event_before_recreate))
    {
      $this->event_before_recreate = 'onJoomBeforeRecreate';
    }

    // Update events map
    $this->events_map = array_merge(
      [
        'recreate' => 'joomgallery',
      ],
      $this->events_map
    );
  }

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A \JForm object on success, false on failure
	 *
	 * @since   4.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
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
    if(!\is_null($this->item) && !empty($this->item->id))
    {
      return $this->item;
    }

    $pk = (!empty($pk)) ? $pk : (int) $this->getState('image.id');
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

    $this->item = $table->getFieldsValues();

		return $this->item;
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
		$table        = $this->getTable();
		$context      = $this->option . '.' . $this->name;
		$app          = Factory::getApplication();
		$imgUploaded  = false;
		$catMoved     = false;
		$isNew        = true;
		$isCopy       = false;
    $isAjax       = false;
    $aliasChanged = false;

		$key = $table->getKeyName();
		$pk  = (isset($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');

		// Are we going to copy the image record?
    if(\strpos($app->input->get('task'), 'save2copy') !== false)
		{
			$isCopy = true;
		}

    // Are we going to save image in an ajax request?
    if(\strpos($app->input->get('task'), 'ajaxsave') !== false)
		{
			$isAjax = true;
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
				$isNew = false;

				// Check if the category was changed
				if($table->catid != $data['catid'])
				{
					$catMoved = true;
				}

        // Check if the alias was changed
				if($table->alias != $data['alias'])
				{
					$aliasChanged = true;
          $old_alias    = $table->alias;
				}
			}

			// Save form data in session
			$app->setUserState(_JOOM_OPTION.'.image.upload', $data);

      // Detect uploader service
      $upload_service  = 'html';
      if(isset($data['uploader']) && !empty($data['uploader']))
      {
        $upload_service = $data['uploader'];
      }

      // Detect multiple upload service
      $upload_multiple  = false;
      if(isset($data['multiple']) && !empty($data['multiple']))
      {
        $upload_multiple = \boolval($data['multiple']);
      }

      // Create uploader service
			$uploader = JoomHelper::getService('uploader', array($upload_service, $upload_multiple, $isAjax));

      // Detect uploaded file
      $imgUploaded = $uploader->isImgUploaded($data);

			// Retrieve image from request
			if($imgUploaded)
			{
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
					$this->setError($this->component->getDebug(true));
          $uploader->rollback();

					return false;
				}

				// Override data with image metadata
				if(!$uploader->overrideData($data))
				{
					$this->setError($this->component->getDebug(true));
          $uploader->rollback();

					return false;
				}
			}

      // Create file manager service
			$manager = JoomHelper::getService('FileManager');

      // Get source image id
			$source_id = $app->input->get('origin_id', false, 'INT');

      // Handle images if category was changed
			if(!$isNew && ($catMoved || $aliasChanged))
			{
				// Douplicate old data
        $old_table = clone $table;
			}

			// Bind data to table object
			if(!$table->bind($data))
			{
				$this->setError($table->getError());
        $uploader->rollback();

				return false;
			}

			// Prepare the row for saving
			$this->prepareTable($table);

			// Check the data.
			if(!$table->check())
			{
				$this->setError($table->getError());
        $uploader->rollback();

				return false;
			}

      // Handle images if record gets copied
			if($isNew && $isCopy && !$imgUploaded)
			{
        // Regenerate filename
        $table->filename = $manager->regenFilename($data['filename']);
			}

      // Handle images if alias has changed
			if(!$isNew && $aliasChanged && !$imgUploaded)
			{
        if(!$this->component->getConfig()->get('jg_useorigfilename'))
        {
          // Replace alias in filename if filename is title dependent
          $table->filename = \str_replace($old_alias, $table->alias, $table->filename);
        }
      }

      // Trigger the before save event.
			$result = $app->triggerEvent($this->event_before_save, array($context, $table, $isNew, $data));

      // Stop storing data if one of the plugins returns false
			if(\in_array(false, $result, true))
			{
        if($imgUploaded)
				{
        	$uploader->rollback($table);
				}
				$this->setError($table->getError());

				return false;
			}

			// Store the data.
			if(!$table->store())
			{
				$this->setError($table->getError());
        $uploader->rollback();

				return false;
			}

      // Handle images if category was changed
			if(!$isNew && $catMoved)
			{
				if($imgUploaded)
				{
					// Delete old images
					$manager->deleteImages($old_table);
				}
				else
				{
					// Move old images to new location
					$manager->moveImages($old_table, $table->catid);
				}
			}

			// Handle images if record gets copied
			if($isNew && $isCopy && !$imgUploaded)
			{
        // Copy Images
        $manager->copyImages($source_id, $table->catid, $table->filename);
			}

      // Handle images if alias has changed
			if(!$isNew && $aliasChanged && !$imgUploaded)
			{
        if($catMoved)
        {
          // modify old_table object to fit with new image location
          $old_table->catid = $table->catid;
        }

        // Rename files
        $manager->renameImages($old_table, $table->filename);        
      }

			// Create images
			if($imgUploaded)
			{
				// Create images
				// (create imagetypes, upload imagetypes to storage, onJoomAfterUpload)
				if(!$uploader->createImage($table))
				{
					$this->setError($this->component->getDebug(true));
          $uploader->rollback($table);

          if($isNew)
          {
            // Delete the already stored new record if image creation failed
            if(!$table->delete($table->$key))
            {
              $this->component->setError($table->getError());
            }
          }

					return false;
				}
			}

      // Handle ajax uploads
      if($isAjax)
      {
        $this->component->cache->set('imgObj', $table->getFieldsValues(array('form', 'imgmetadata', 'params', 'created_by', 'modified_by', 'checked_out')));
      }

      // All done. Clean created temp files
      $uploader->deleteTmp();

			// Clean the cache.
			$this->cleanCache();

			// Trigger the after save event.
			$app->triggerEvent($this->event_after_save, array($context, $table, $isNew, $data));
		}
		catch (\Exception $e)
		{
			if($imgUploaded)
			{
				$uploader->rollback($table);
			}
			$this->setError($e->getMessage());

			return false;
		}
		
		// Output warning messages
		if(\count($this->component->getWarning()) > 0)
		{
			$this->component->printWarning();
		}

		// Output debug data
		if(\count($this->component->getDebug()) > 0)
		{
			$this->component->printDebug();
		}

		// Set state
		if(isset($table->$key))
		{
			$this->setState($this->getName() . '.id', $table->$key);
		}

		$this->setState($this->getName() . '.new', $isNew);

		// Create/update associations
		if($this->associationsContext && Associations::isEnabled() && !empty($data['associations']))
		{
      $this->createAssociations($table, $data['associations']);			
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
						$this->setError($this->component->getDebug(true));

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
	 * @param   array    &$pks   A list of the primary keys to change.
   * @param   string   $type   Name of the state to be changed
	 * @param   integer  $value  The value of the state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function changeSate(&$pks, $type='publish', $value = 1)
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
    return $this->changeSate($pks, 'publish', $value);
  }

  /**
	 * Method to replace an image type.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   4.0.0
	 */
	public function replace($data)
	{
    $table = $this->getTable();
		$app   = Factory::getApplication();
		$key   = $table->getKeyName();
		$pk    = (isset($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
		
		// Rertrieve request image file data
    if(\array_key_exists('image', $app->input->files->get('jform')) && !empty($app->input->files->get('jform')['image'])
    && $app->input->files->get('jform')['image']['error'] != 4 &&  $app->input->files->get('jform')['image']['size'] > 0)
		{
			$data['images'] = array();
			\array_push($data['images'], $app->input->files->get('jform')['image']);
		}

    try
    {
      // Load image table
      $table->load($pk);

      // Create uploader service
      $uploader = JoomHelper::getService('uploader', array('single', false));

      // Set replacement settings
      $uploader->type         = $data['replacetype'];
      $uploader->processImage = \boolval($data['replaceprocess']);

      // Set filename in data since it will not be new created during retrieveImage()
      $data['filename']       = $table->filename;

      // Retrieve image
      // (check upload, check user upload limit, create filename, onJoomBeforeSave)
      if(!$uploader->retrieveImage($data, false))
      {
        $this->setError($this->component->getDebug(true));

        return false;
      }

      // Create images
      // (create imagetypes, upload imagetypes to storage, onJoomAfterUpload)
      if(!$uploader->createImage($table))
      {
        $uploader->rollback();
        $this->setError($this->component->getDebug(true));

        return false;
      }

      // Clean the cache.
			$this->cleanCache();
    }
    catch (\Exception $e)
		{
			$uploader->rollback();
			$this->setError($e->getMessage());

			return false;
		}

    // Output debug data
		if(\count($this->component->getDebug()) > 1)
		{
			$this->component->printDebug();
		}

    return true;
  }

  public function crop()
  {
    throw new Exception('Task "image.crop" not yet implemented.', 1);
    
    $table = $this->getTable();
    $app   = Factory::getApplication();
    $key   = $table->getKeyName();
    $pk    = (isset($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');

    // Rertrieve request image file data
    if(\array_key_exists('image', $app->input->files->get('jform')) && !empty($app->input->files->get('jform')['image'])
        && $app->input->files->get('jform')['image']['error'] != 4 &&  $app->input->files->get('jform')['image']['size'] > 0)
    {
        $data['images'] = array();
        \array_push($data['images'], $app->input->files->get('jform')['image']);
    }

    try
    {
        // Load image table
        $table->load($pk);

        // Create uploader service
        $uploader = JoomHelper::getService('uploader', array('single', false));

        // Set replacement settings
        $uploader->type         = $data['replacetype'];
        $uploader->processImage = \boolval($data['replaceprocess']);

        // Set filename in data since it will not be new created during retrieveImage()
        $data['filename']       = $table->filename;

        // Retrieve image
        // (check upload, check user upload limit, create filename, onJoomBeforeSave)
        if(!$uploader->retrieveImage($data, false))
        {
            $this->setError($this->component->getDebug(true));

            return false;
        }

        // Create images
        // (create imagetypes, upload imagetypes to storage, onJoomAfterUpload)
        if(!$uploader->createImage($table))
        {
            $uploader->rollback();
            $this->setError($this->component->getDebug(true));

            return false;
        }

        // Clean the cache.
        $this->cleanCache();
    }
    catch (\Exception $e)
    {
        $uploader->rollback();
        $this->setError($e->getMessage());

        return false;
    }

    // Output debug data
    if(\count($this->component->getDebug()) > 1)
    {
        $this->component->printDebug();
    }

    return true;
  }

  /**
   * Method to recreate the imagetypes for one Image.
   *
   * @param   int     $pk    The record primary key.
   * @param   string  $type  The imagetype to use as source for the recreation
   *
   * @return  boolean  True if successful, false if an error occurs.
   *
   * @since   4.0
   */
  public function recreate(int $pk, $type='original'): bool
  {
		$table = $this->getTable();

		// Include the plugins for the recreate events.
		PluginHelper::importPlugin($this->events_map['recreate']);

    if($table->load($pk))
    {
      if($this->canRecreate($table)) 
      {
        $context = $this->option . '.' . $this->name . '.recreate';

        // Create file manager service
        $this->component->createFileManager();

        // Get imagetypes
        $imagetypes      = $this->component->getFileManager()->get('imagetypes');
        $imagetypes_dict = $this->component->getFileManager()->get('imagetypes_dict');

        // Select image source
        if(($type == 'original' || $type == 'orig') && $imagetypes[$imagetypes_dict['original']]->params->get('jg_imgtype', 1, 'int') > 0)
        {
          // Take original as source if available
          $type = 'original';
        }
        else
        {
          $type = 'detail';
        }

        // Get source file path
        $source = $this->component->getFileManager()->getImgPath($table, $type);

        // Trigger the before recreate event.
        $result = Factory::getApplication()->triggerEvent($this->event_before_recreate, array($table, $imagetypes, $source));

        if(\in_array(false, $result, true))
        {
          $this->setError($table->getError());

          return false;
        }

        // Perform the recreation
        if(!$this->component->getFileManager()->createImages($source, $table->filename, $table->catid))
        {
          $this->setError($table->getError());

          return false;
        }

        // Trigger the after event.
        Factory::getApplication()->triggerEvent($this->event_after_recreate, array($context, $table));
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

		// Clear the component's cache
		$this->cleanCache();

		return true;
  }

  /**
   * Method to test whether a record can be recreated.
   *
   * @param   object  $record  A record object.
   *
   * @return  boolean  True if allowed to recreate the record. Defaults to the permission for the component.
   *
   * @since   4.0
   */
  protected function canRecreate($record)
  {
    return Factory::getUser()->authorise('core.edit', $this->typeAlias);
  }
}
