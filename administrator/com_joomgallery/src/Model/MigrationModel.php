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
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Language\Text;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Filesystem\Path;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Filesystem\Folder;
use \Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\Language\Multilanguage;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Table\MigrationTable;

/**
 * Migration model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class MigrationModel extends AdminModel
{
  /**
	 * @var    string  Alias to manage history control
	 *
	 * @since  4.0.0
	 */
	public $typeAlias = _JOOM_OPTION.'.migration';

	/**
	 * @var    string  The prefix to use with controller messages
	 *
	 * @since  4.0.0
	 */
	protected $text_prefix = _JOOM_OPTION_UC;

  /**
	 * Storage for the migration form object.
	 *
	 * @var   Registry
	 *
	 * @since  4.0.0
	 */
	protected $params = null;

  /**
	 * Name of the migration script.
	 *
	 * @var   string
	 *
	 * @since  4.0.0
	 */
	protected $scriptName = '';

  /**
	 * Temporary storage of type name.
	 *
	 * @var   string
	 *
	 * @since  4.0.0
	 */
  protected $tmp_type = null;

  /**
   * Constructor
   *
   * @param   array                 $config       An array of configuration options (name, state, dbo, table_path, ignore_request).
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function __construct($config = array())
  {
    parent::__construct($config);

    $this->app       = Factory::getApplication('administrator');
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
    $this->user      = Factory::getUser();

    // Create config service
    $this->component->createConfig();
  }

  /**
	 * Method to get the migration parameters from the userstate or from the database.
	 *
	 * @return  array  $params  The migration parameters entered in the migration form
	 *
	 * @since   4.0.0
	 */
  public function getParams()
  {
    // Try to load params from user state
    $params = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$this->scriptName.'.params', array());

    // Load params from db if there are migrateables in database
    $db    = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select('a.params');
    $query->from($db->quoteName(_JOOM_TABLE_MIGRATION, 'a'));
    $query->where($db->quoteName('script') . ' = ' . $db->quote($this->scriptName));

    $db->setQuery($query);

    try
    {
      $params_db = $db->loadResult();
    }
    catch (\RuntimeException $e)
    {
      $this->component->setError($e->getMessage());
    }

    if($params_db)
    {
      // Override params from user state with the one from db
      $params = \json_decode($params_db, true);
    }
    
    return $params;
  }

  /**
	 * Method to set the migration parameters in the model and the migration script.
   * 
   * @param   array  $params  The migration parameters entered in the migration form
	 *
	 * @return  void
	 *
	 * @since   4.0.0
   * @throws  \Exception      Missing migration params
	 */
  public function setParams($params = null)
  {
    $info = $this->getScript();

    if(\is_null($params))
    {
      $params = $this->getParams();
    }

    if(\is_null($params))
    {
      throw new \Exception('No migration params found. Please provide some migration params.', 1);
    }

    // Set the migration parameters
    $this->params = new Registry($params);
    $this->component->getMigration()->set('params', $this->params);
  }

  /**
	 * Method to get info array of current migration script.
	 *
	 * @return  object|boolean   Migration info object.
	 *
	 * @since   4.0.0
   * @throws  \Exception
	 */
  public function getScriptName()
  {
    return $this->getScript();
  }

  /**
	 * Method to get info array of current migration script.
	 *
	 * @return  object|boolean   Migration info object.
	 *
	 * @since   4.0.0
   * @throws  \Exception
	 */
  public function getScript()
  {
    // Retreive script variable
    $name = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');

    if(!$name || \strlen($name) < 2 || \strlen($name) > 30)
    {
      $tmp        = new \stdClass;
      $tmp->name  = '';
      $this->scriptName = '';
      
      return $tmp;
    }

    $this->scriptName = $name;
    
    if(!$this->component->getMigration())
    {
      $this->component->createMigration($name);
    }

    return $this->component->getMigration()->get('info');
  }

  /**
	 * Method to get all available migration scripts.
	 *
	 * @return  array|boolean   List of paths of all available scripts.
	 *
	 * @since   4.0.0
	 */
  public function getScripts()
  {
    $files = Folder::files(JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/src/Service/Migration/Scripts', '.php$', false, true);

    $scripts = array();
    foreach($files as $path)
    {
      $img = Uri::base().'components/'._JOOM_OPTION.'/src/Service/Migration/Scripts/'.basename($path, '.php').'.jpg';

      $scripts[basename($path, '.php')] = array('name' => basename($path, '.php'), 'path' => $path, 'img' => $img);
    }

    return $scripts;
  }

  /**
	 * Method to fetch a list of content types which can be migrated using the selected script.
	 *
	 * @return  array|boolean  List of content types on success, false otherwise
	 *
	 * @since   4.0.0
	 */
	public function getMigrateables()
	{
    // Retreive script
    $script = $this->getScript();

    if(!$script)
    {
      return false;
    }

    $this->setParams();

    return $this->component->getMigration()->getMigrateables();
  }

  /**
   * Method to get a migrateable record by id.
   *
   * @param   integer  $pk         The id of the primary key.
   * @param   bool     $withQueue  True to load the queue if empty.
   *
   * @return  object|boolean  Object on success, false on failure.
   *
   * @since   4.0.0
   */
  public function getItem($pk = null, $withQueue = true)
  {
    $item = parent::getItem($pk);

    if(!$item)
    {
      $item = parent::getItem(null);
    }

    // Support for queue field
    if(isset($item->queue))
    {
      $registry    = new Registry($item->queue);
      $item->queue = $registry->toArray();
      $item->queue = ArrayHelper::toInteger($item->queue);
    }

    // Support for successful field
    if(isset($item->successful))
    {
      $item->successful = new Registry($item->successful);
    }

    // Support for failed field
    if(isset($item->failed))
    {
      $item->failed = new Registry($item->failed);
    }

    // Support for params field
    if(isset($item->params))
    {
      $item->params = new Registry($item->params);
    }

    // Add script if empty
    if(empty($item->script))
    {
      $item->script = $this->scriptName;
    }

    // We can not go further without knowledge about the type
    if(\is_null($this->tmp_type))
    {
      return $item;
    }
    else
    {
      $type = $this->tmp_type;
    }

    // Add type if empty
    if(empty($item->type))
    {      
      $item->type = $type;
    }

    // Add destination table info if empty
    if(empty($item->dst_table))
    {
      if(\key_exists($type, JoomHelper::$content_types))
      {
        $item->dst_table = JoomHelper::$content_types[$type];
      }
      // else
      // {
      //   // We have a migrateable record whos name does not correspond to the record name
      //   $type_obj = $this->component->getMigration()->getType($type);

      //   $item->dst_table = JoomHelper::$content_types[$type_obj->get('recordName')];
      // }
      $item->dst_pk    = 'id';
    }

    // We can not go further without a properly loaded migration service
    if(\is_null($this->component->getMigration()) || \is_null($this->component->getMigration()->get('params')))
    {
      return $item;
    }

    // Add source table info if empty
    if(empty($item->src_table))
    {
      // Get table information
      list($src_table, $src_pk) = $this->component->getMigration()->getSourceTableInfo($type);
      $item->src_table = $src_table;
      $item->src_pk    = $src_pk;
    }

    // Add queue if empty
    if($withQueue && !$item->completed && (\is_null($item->queue) || empty($item->queue)))
    {
      // Load queue
      $item->queue = $this->getQueue($type, $item);
    }

    // Add params
    $item->params = $this->component->getMigration()->get('params');

    // Empty type storage
    $this->tmp_type = null;

    return $item;
  }

  /**
    * Method to get a list of migration records based on current script.
    * Select based on types from migration script.
    *
    * @return  Migrationtable[]  An array of migration tables
    *
    * @since   4.0.0
    */
  public function getItems(): array
  {
    // Get types from migration service
    $types = $this->component->getMigration()->getTypeNames();

    // Get available types from db
    try
    {
      $db    = $this->getDbo();
      $query = $this->getListQuery();

      if(\is_string($query))
      {
        $query = $db->getQuery(true)->setQuery($query);
      }
      
      $db->setQuery($query);

      $tables = $db->loadObjectList('type');
    }
    catch (\RuntimeException $e)
    {
      $this->component->setError($e->getMessage());

      return array();
    }

    $table  = $this->getTable();
    $tmp_pk = null;
    if($this->app->input->exists($table->getKeyName()))
    {
      // Remove id from the input data
      $tmp_pk = $this->app->input->get($table->getKeyName(), 'int');
      $this->app->input->set($table->getKeyName(), null);
    }

    $items = array();
    foreach($types as $key => $type)
    {
      // Fill type storage
      $this->tmp_type = $type;

      if(!empty($tables) && \key_exists($type, $tables))
      {
        // Load item based on id.
        $item = $this->getItem($tables[$type]->id);
      }
      else
      {
        // Load empty item.
        $item = $this->getItem(0);
      }

      // Empty type storage
      $this->tmp_type = null;

      // Check for a table object error.
      if($item === false)
      {
        $this->component->setError($e->getMessage());

        return array();
      }

      //array_push($items, $item);
      $items[$type] = $item;
    }

    // Reset id to input data
    if(!\is_null($tmp_pk))
    {
      $this->app->input->set($table->getKeyName(), $tmp_pk);
    }    

    return $items;
  }

  /**
    * Method to get a list of available migration IDs based on current script.
    * Select from #__joomgallery_migration only.
    *
    * @return  array  List of IDs
    *
    * @since   4.0.0
    */
  public function getIdList(): array
  {
    // Create a new query object.
    try
    {
      $db    = $this->getDbo();
      $query = $db->getQuery(true);

      // Select the required fields from the table.
      $query->select(array('a.id', 'a.script', 'a.type', 'a.checked_out'));
      $query->from($db->quoteName(_JOOM_TABLE_MIGRATION, 'a'));

      $db->setQuery($query);

      $list = $db->loadObjectList();
    }
    catch (\RuntimeException $e)
    {
      $this->component->setError($e->getMessage());

      return array();
    }

    $ids = array();
    foreach($list as $key => $value)
    {
      if(!array_key_exists($value->script, $ids))
      {
        $ids[$value->script] = array($value);
      }
      else
      {
        array_push($ids[$value->script], $value);
      }
    }

    return $ids;
  }

  /**
    * Method to get the sourceDeletion flag from migration script
    *
    * @return  bool  True to offer the task migration.removesource
    *
    * @since   4.0.0
    */
  public function getSourceDeletion(): bool
  {
    // Retreive script
    $script = $this->getScript();

    if(!$script)
    {
      return false;
    }

    return $this->component->getMigration()->get('sourceDeletion', false);
  }

  /**
   * Load the current queue of ids from table
   * 
   * @param   string     $type   Content type
   * @param   object     $table  Object containing migration item properties
   *
   * @return  array
   *
   * @since   4.0.0
   */
  public function getQueue($type, $table=null): array
  {
    return $this->component->getMigration()->getQueue($type, $table);
  }

  /**
	 * Build an SQL query to load the list data.
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   4.0.0
	 */
	protected function getListQuery()
	{
    // Retreive script
    $script = $this->getScript();

    // Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

    if(!$script)
    {
      return $query;
    }

    // Select the required fields from the table.
		$query->select(array('a.id', 'a.type'));
    $query->from($db->quoteName(_JOOM_TABLE_MIGRATION, 'a'));

    // Filter for the current script
    $query->where($db->quoteName('a.script') . ' = ' . $db->quote($script->name));

    return $query;
  }

  /**
	 * Method to get the migration form.
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
    // Retreive script
    $script = $this->getScript();

    if(!$script)
    {
      return false;
    }

    // Add migration form paths
    Form::addFormPath(JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/src/Service/Migration/Scripts');
    Form::addFormPath(JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/forms');

    // Get the form file path
    $file = Path::find(Form::addFormPath(), strtolower($script->name) . '.xml');
    if(!is_file($file))
    {
      $file = Path::find(Form::addFormPath(), $script->name . '.xml');
    }
    
    if(!is_file($file))
    {
      $this->component->setError('Migration form XML could not be found. XML filename: ' . $script->name . '.xml');
      return false;
    }

		// Get the form.
    $name = _JOOM_OPTION.'.migration.'.$this->component->getMigration()->get('name');
		$form = $this->loadForm($name, $file,	array('control' => 'jform_'.$script->name, 'load_data' => true));

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
    if(!$this->component->getMigration())
    {
      $this->getScript();
    }

		// Check the session for previously entered form data.
    $name = _JOOM_OPTION.'.migration.'.$this->component->getMigration()->get('name');
		$data = $this->app->getUserState($name.'.step2.data', array());

    // Check the session for validated migration parameters
    $params = $this->getParams();

		return (empty($params)) ? $data : $params;
	}

  /**
	 * Method to perform the pre migration checks.
   * 
   * @param   array  $params  The migration parameters entered in the migration form
	 *
	 * @return  array  An array containing the precheck results.
	 *
	 * @since   4.0.0
	 */
  public function precheck($params)
  {
    $info = $this->getScript();

    // Set the migration parameters
    $this->setParams($params);

    // Perform the prechecks
    return $this->component->getMigration()->precheck();
  }

  /**
	 * Method to perform the post migration checks.
	 *
	 * @return  array|boolean  An array containing the postcheck results on success.
	 *
	 * @since   4.0.0
	 */
  public function postcheck()
  {   
    // Prepare the migration object
    $migs = $this->getMigrateables();
    $keys = \array_keys($migs);
    $this->setParams($migs[$keys[0]]->params);

    // Perform the postchecks
    return $this->component->getMigration()->postcheck();
  }

  /**
	 * Method to perform the migration of one record.
   * 
   * @param   string           $type   Name of the content type to migrate.
   * @param   integer          $pk     The primary key of the source record.
	 *
	 * @return  object   The object containing the migration results.
	 *
	 * @since   4.0.0
	 */
  public function migrate(string $type, int $pk): object
  {
    // Initialise variables
    $new_pk    = $pk;
    $success   = true;
    $error_msg = '';

    // Prepare migration service and return migrateable object
    $this->component->createMigration($this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd'));
    $mig = $this->component->getMigration()->prepareMigration($type);

    // Perform the migration of the element if needed
    if($this->component->getMigration()->needsMigration($type, $pk))
    {
      // Get record data from source
      if($data = $this->component->getMigration()->getData($type, $pk))
      {
        // Copy source record data
        $src_data = (array) clone (object) $data;

        // Convert record data into structure needed for JoomGallery v4+
        $data = $this->component->getMigration()->convertData($type, $data);

        if(!$data)
        {
          $success = false;
          $error_msg = Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_FAILED_CONVERT_DATA');
        }
        else
        {
          // Create new record at destination based on converted data
          $autoIDs = !\boolval($mig->params->get('source_ids', 0));
          $record  = $this->insertRecord($type, (array) $data, $autoIDs);

          if(!$record)
          {
            $success = false;
            $error_msg = Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_FAILED_INSERT_RECORD');
          }
          else
          {
            // Set primary key value of new created record
            $new_pk = $record->id;

            // Migration in the filesystem
            switch($type)
            {
              case 'image':
                $res = $this->component->getMigration()->migrateFiles($record, $src_data);
                $error_msg_end = 'CREATE_IMGTYPE';
                break;

              case 'category':
                $res = $this->component->getMigration()->migrateFolder($record, $src_data);
                $error_msg_end = 'CREATE_FOLDER';

                if(!$res)
                {
                  // Stop automatic migration if something went wrong in the filesystem
                  $this->component->getMigration()->set('continue', false);
                }
                break;
              
              default:
                $res = true;
                break;
            }

            if(!$res)
            {
              $record  = $this->deleteRecord($type, $new_pk);
              $success = false;
              $error_msg = Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_FAILED_'.$error_msg_end);
            }
          }
        }
      }
      else
      {
        $success = false;
        $error_msg = Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_FAILED_FETCH_DATA');
      }
    }

    // Load migration data table
    $table = $this->getTable();
    if(!$table->load($mig->id))
    {
      $this->component->setError($table->getError());

      return $mig;
    }

    // Remove migrated primary key from queue
    if(($key = \array_search($pk, $table->queue)) !== false)
    {
      unset($table->queue[$key]);
    }

    if($success)
    {
      // Add migrated primary key to successful object
      $table->successful->set($pk, $new_pk);
    }
    else
    {
      // Add migrated primary key to failed object
      $table->failed->set($pk, $error_msg);
    }

    // Add errors
    if($error_msg !== '')
    {
      $this->component->setError($error_msg);
    }

    // Calculate progress and completed state
    $table->clcProgress();

    // Prepare the row for saving
		$this->prepareTable($table);

    // Check the data.
    if(!$table->check())
    {
      $this->component->setError($table->getError());

      return false;
    }

    $ret_table = clone $table;

    // Save table
    if(!$table->store())
    {
      $this->component->setError($table->getError());

      return $mig;
    }

    return $ret_table;
  }

  /**
	 * Method to manually apply a state for one record of one migrateable.
   * 
   * @param   string    $type     Name of the content type.
   * @param   integer   $state    The new state to be applied. (0: failed, 1:success, 2:pending)
   * @param   integer   $src_pk   The primary key of the source record.
   * @param   integer   $dest_pk  The primary key of the migrated record at destination.
   * @param   string    $error    The error message in case of failed state.
	 *
	 * @return  object   The object containing the migration results.
	 *
	 * @since   4.0.0
	 */
  public function applyState(string $type, int $state, int $src_pk, int $dest_pk = 0, string $error = ''): object
  {
    // Prepare migration service and return migrateable object
    $this->component->createMigration($this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd'));
    $mig = $this->component->getMigration()->prepareMigration($type);

    // Load migration data table
    $table = $this->getTable();
    if(!$table->load($mig->id))
    {
      $this->component->setError($table->getError());

      return $mig;
    }

    $removed = false;
    switch($state)
    {
      // apply successful state
      case 1:
        // Remove primary key from queue
        if(($key = \array_search($src_pk, $table->queue)) !== false)
        {
          unset($table->queue[$key]);
          $removed = true;
        }

        //Remove primary key from failed
        if($table->failed->exists($src_pk))
        {
          $table->failed->remove($src_pk);
          $removed = true;
        }

        // Add migrated primary key to successful object
        if($removed)
        {
          $table->successful->set($src_pk, $dest_pk);
        }        
        break;

      // apply pending state
      case 2:
        //Remove primary key from successful
        if($table->successful->exists($src_pk))
        {
          $table->successful->remove($src_pk);
          $removed = true;
        }

        //Remove primary key from failed
        if($table->failed->exists($src_pk))
        {
          $table->failed->remove($src_pk);
          $removed = true;
        }

        // Add primary key to queue
        if($removed)
        {
          \array_push($table->queue, $src_pk);
        }

        // Reordering queue
        $table->queue = $this->getQueue($type, $table);

        break;

      // apply failed state
      default:
        // Remove primary key from queue
        if(($key = \array_search($src_pk, $table->queue)) !== false)
        {
          unset($table->queue[$key]);
          $removed = true;
        }

        //Remove primary key from successful
        if($table->successful->exists($src_pk))
        {
          $table->successful->remove($src_pk);
          $removed = true;
        }
        
        // Add migrated primary key to failed object
        if($removed)
        {
          $table->failed->set($src_pk, $error);
        }
        break;
    }

    // Add errors
    if($error !== '')
    {
      $this->component->setError($error);
    }

    if(!$removed)
    {
      $this->component->setWarning(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_APPLYSTATE_NOT_AVAILABLE', $src_pk));
    }

    // Calculate progress and completed state
    $table->clcProgress();

    // Prepare the row for saving
		$this->prepareTable($table);

    // Check the data.
    if(!$table->check())
    {
      $this->component->setError($table->getError());

      return false;
    }

    $ret_table = clone $table;

    // Save table
    if(!$table->store())
    {
      $this->component->setError($table->getError());

      return $mig;
    }

    return $ret_table;
  }

  /**
   * Method to delete migration source data.
   *
   * @return  boolean  True if successful, false if an error occurs.
   *
   * @since   4.0.0
   */
  public function deleteSource()
  {
    // Prepare the migration object
    $migs = $this->getMigrateables();
    $keys = \array_keys($migs);
    $this->setParams($migs[$keys[0]]->params);

    // Delete sources
    return $this->component->getMigration()->deleteSource();
  }

  /**
	 * Method to insert a content type record from migration data.
	 *
   * @param   string  $type    Name of the content type to insert.
	 * @param   array   $data    The record data gathered from the migration source.
   * @param   bool    $autoID  True to auto-increment the id in the database
	 *
	 * @return  object  Inserted record object on success, False on error.
	 *
	 * @since   4.0.0
	 */
  protected function insertRecord(string $type, array $data, bool $autoID = true)
  {
    $recordType = $this->component->getMigration()->get('types')[$type]->get('recordName');

    // Check content type
    JoomHelper::isAvailable($recordType);

    // Create table
    if(!$table = $this->getMVCFactory()->createTable($recordType, 'administrator'))
    {
      $this->component->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_IMGTYPE_TABLE_NOT_EXISTING', $type));

      return false;
    }

    // We assume that the record gets newly created during migration step
    $isNew = true;

    // Get table primary key name
    $key = $table->getKeyName();

    // Special case: Only modification no creation of record
    if(!$this->component->getMigration()->get('types')[$type]->get('insertRecord') && $data[$key] > 0)
    {
      if($table->load($data[$key]))
      {
        // Table successfully loaded
        $isNew = false;
      }
    }

    // Special case: Use source IDs. Insert dummy record with JDatabase before binding data on it.
    if($isNew && !$autoID && \in_array($key, \array_keys($data)))
    {
      if(!$this->insertDummyRecord($type, $data[$key]))
      {
        // Insert dummy failed. Stop migration.
        return false;
      }

      if(!$table->load($data[$key]))
      {
        $this->component->setError($table->getError());

        return false;
      }
    }

    // Change language to 'All' if multilanguage is not enabled
    if($isNew && !Multilanguage::isEnabled())
		{
			$data['language'] = '*';
		}

    // Reset task
    $tmp_task = $this->app->input->get('task', '', 'cmd');
    $this->app->input->set('task', 'save');

    if($isNew && $this->component->getMigration()->get('types')[$type]->get('nested'))
    {
      // Assumption: parent primary key name for all nested types at destination is 'parent_id'
      $table->setLocation($data['parent_id'], 'last-child');
    }

    // Bind migrated data to table object
    if(!$table->bind($data))
    {
      $this->component->setError($table->getError());

      return false;
    }

    // Prepare the row for saving
		$this->prepareTable($table);

    // Check the data.
    if(!$table->check())
    {
      $this->component->setError($table->getError());

      return false;
    }

    // Store the data.
    if(!$table->store()) 
    {
      $this->component->setError($table->getError());

      return false;
    }

    // Restore task
    $this->app->input->set('task', $tmp_task);

    return $table;
  }

  /**
	 * Method to delete a content type record in destination table.
	 *
   * @param   string  $type   Name of the content type to insert.
	 * @param   array   $data   The record data gathered from the migration source.
   * @param   bool    $newID  True to auto-increment the id in the database
	 *
	 * @return  bool    True if record was successfully deleted, false otherwise.
	 *
	 * @since   4.0.0
	 */
  protected function deleteRecord(string $type, int $pk): bool
  {
    // Check content type
    JoomHelper::isAvailable($type);

    // Create table
    if(!$table = $this->getMVCFactory()->createTable($type, 'administrator'))
    {
      $this->component->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_IMGTYPE_TABLE_NOT_EXISTING', $type));

      return false;
    }

    // Load the table.
    $table->load($pk);

    if($type === 'image')
    {
      // Delete corresponding imagetypes
      $manager = JoomHelper::getService('FileManager');

      if(!$manager->deleteImages($table))
      {
        $this->component->setError($this->component->getDebug(true));

        return false;
      }
    }

    if(!$table->delete($pk))
    {
      $this->component->setError($table->getError());

      return false;
    }

    return true;
  }

  /**
	 * Method to insert an empty dummy record with a given primary key
	 *
   * @param   string    $type    Name of the content type to insert.
   * @param   int       $key     Primary key to use.
	 *
	 * @return  bool|int  Primary key of the created dummy record or false on failure
	 *
	 * @since   4.0.0
	 */
  protected function insertDummyRecord(string $type, int $key)
  {
    list($db, $dbPrefix) = $this->component->getMigration()->getDB('destination');
    $date                = Factory::getDate();

    // Create and populate a dummy object.
    $record = new \stdClass();
    $record->id = $key;

    $needed = array('category');
    if(\in_array($type, $needed))
    {
      $record->lft = 2147483644;
      $record->rgt = 2147483645;
    }

    $needed = array('image', 'category', 'comment', 'gallery', 'tag');
    if(\in_array($type, $needed))
    {
      $record->description = '';
    }
    
    $needed = array('image');
    if(\in_array($type, $needed))
    {
      $record->date = $date->toSql();
      $record->imgmetadata = '';
      $record->filename = '';
    }
    
    $needed = array('image', 'category', 'imagetype', 'user');
    if(\in_array($type, $needed))
    {
      $record->params = '';
    }

    $needed = array('image', 'category', 'gallery');
    if(\in_array($type, $needed))
    {
      $record->metadesc = '';
      $record->metakey = '';
    }
    
    $needed = array('image', 'category', 'field', 'tag', 'gallery', 'user', 'vote', 'comment');
    if(\in_array($type, $needed))
    {
      $record->created_time = $date->toSql();
    }

    $needed = array('image', 'category', 'tag', 'gallery', 'comment');
    if(\in_array($type, $needed))
    {
      $record->modified_time = $date->toSql();
    }

    // Insert the object into the user profile table.
    if(!$db->insertObject(JoomHelper::$content_types[$type], $record))
    {
      $this->component->setError(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_DUMMY_RECORD', $type, $key));

      return false;
    }
    else
    {
      return $key;
    }
  }
}
