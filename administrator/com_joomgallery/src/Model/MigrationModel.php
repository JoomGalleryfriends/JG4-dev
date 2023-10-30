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
use \Joomla\Registry\Registry;
use \Joomla\CMS\Filesystem\Folder;
use \Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\Language\Multilanguage;
use \Joomgallery\Component\Joomgallery\Administrator\Table\MigrationTable;
use \Joomgallery\Component\Joomgallery\Administrator\Table\ImageTable;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

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
	 * Method to set the migration parameters in the migration script.
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
      // Check the session for validated migration parameters
      $params = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$info->name.'.params', null);
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
   * Method to get a migrateable record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  CMSObject|boolean  Object on success, false on failure.
   *
   * @since   4.0.0
   */
  public function getItem($pk = null)
  {
    $item = parent::getItem($pk);

    if(\property_exists($item, 'queue'))
    {
      $registry    = new Registry($item->queue);
      $item->queue = $registry->toArray();
    }

    if(\property_exists($item, 'successful'))
    {
      $registry         = new Registry($item->successful);
      $item->successful = $registry;
      //$item->successful = $registry->toArray();
    }

    if(\property_exists($item, 'failed'))
    {
      $registry     = new Registry($item->failed);
      $item->failed = $registry;
      //$item->failed = $registry->toArray();
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

    // Add source and destination table info if empty
    if(empty($item->src_table))
    {
      // Get table information
      list($src_table, $src_pk) = $this->component->getMigration()->getSourceTableInfo($type);
      $item->src_table = $src_table;
      $item->src_pk    = $src_pk;
      $item->dst_table = JoomHelper::$content_types[$type];
      $item->dst_pk    = 'id';
    }

    // Add queue if empty
    if(\is_null($item->queue) || empty($item->queue))
    {
      // Load queue
      $item->queue = $this->getQueue($type, $item);
    }

    // Add params
    if($this->component->getMigration()->get('params'))
    {
      $item->params = $this->component->getMigration()->get('params');
    }

    // Empty type storage
    $this->tmp_type = null;

    return $item;
  }

  /**
    * Method to get an array of migrateables based on current script.
    *
    * @return  MigrationTable[]  An array of data items
    *
    * @since   4.0.0
    */
  public function getItems(): array
  {
    // Get types from migration service
    $types = $this->component->getMigration()->get('types');

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

      array_push($items, $item);
    }

    return $items;
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
    // Retreive script
    $script = $this->getScript();

    // Create a new query object.
		list($db, $dbPrefix) = $this->component->getMigration()->getDB('source');
		$query               = $db->getQuery(true);

    if(!$script)
    {
      return $query;
    }

    if(\is_null($table))
    {
      $migrateables = $this->component->getMigration()->getMigrateables();
      $migrateable  = $migrateables[$type];
    }
    else
    {
      $migrateable = $table;
    }

    // Select the required fields from the table.
    $query->select($db->quoteName($migrateable->get('src_pk', 'id')))
          ->from($db->quoteName($migrateable->get('src_table')))
          ->order($db->quoteName($migrateable->get('src_pk', 'id')) . ' ASC');
    $db->setQuery($query);

    return $db->loadColumn();
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

		// Get the form.
    $name   = _JOOM_OPTION.'.migration.'.$this->component->getMigration()->get('name');
    $source = $this->component->getMigration()->get('name');
		$form   = $this->loadForm($name, $source,	array('control' => 'jform_'.$source, 'load_data' => true));

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
    $params = $this->app->getUserState($name.'.params', null);

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
   * @param   array  $params  The migration parameters entered in the migration form
	 *
	 * @return  array|boolean  An array containing the postcheck results on success.
	 *
	 * @since   4.0.0
	 */
  public function postcheck($params)
  {
    $info = $this->getScript();

    // Set the migration parameters
    $this->setParams($params);

    // Perform the prechecks
    return $this->component->getMigration()->postcheck();
  }

  /**
	 * Method to perform one migration of one record.
   * 
   * @param   string           $type   Name of the content type to migrate.
   * @param   integer          $pk     The primary key of the source record.
   * @param   MigrationTable   $mig    The MigrationTable object.
	 *
	 * @return  object   The object containing the migration results.
	 *
	 * @since   4.0.0
	 */
  public function migrate(string $type, int $pk, MigrationTable $mig): MigrationTable
  {
    $info = $this->getScript();

    // Set the migration parameters
    $this->setParams($mig->params);

    // Get record data from source
    $data = $this->component->getMigration()->getData($type, $pk);

    // Convert record data into structure needed for JoomGallery v4+
    $data = $this->component->getMigration()->convertData($data);

    // Create new record based on data array
    $sameIDs = \boolval($mig->params->get('source_ids', '0'));
    $record  = $this->insertRecord($type, $data, $sameIDs);

    // Recreate images
    if($type === 'image')
    {
      $img_source = $this->component->getMigration()->getImageSource($data);
      if(\array_key_first($img_source) === 0)
      {
        // Create imagetypes based on one given image
        $this->createImages($record, $img_source[0]);
      }
      else
      {
        // Reuse images from source as imagetypes
        $this->reuseImages($record, $img_source);
      }
    }

    return $this->component->getMigration()->migrate();
  }

  /**
	 * Method to insert a content type record from migration data.
	 *
   * @param   string  $type   Name of the content type to insert.
	 * @param   array   $data   The record data gathered from the migration source.
   * @param   bool    $newID  True to auto-increment the id in the database
	 *
	 * @return  object  Inserted record object on success, False on error.
	 *
	 * @since   4.0.0
	 */
  protected function insertRecord(string $type, array $data, bool $newID=true): bool
  {
    // Check content type
    JoomHelper::isAvailable($type);

    // Create table
    if(!$table = $this->getMVCFactory()->createTable($type, 'administrator'))
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_IMGTYPE_TABLE_NOT_EXISTING', $type));

      return false;
    }

    // Get table primary key name
    $key = $table->getKeyName();

    // Disable auto-incrementing record ID
    if($newID && \in_array($key, \array_keys($data)) && \method_exists($table, 'insertID'))
    {
      $table->insertID();
    }

    // Change language to 'All' if multilangugae is not enabled
    if(!Multilanguage::isEnabled())
		{
			$data['language'] = '*';
		}

    // Bind migrated data to table object
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

    // Store the data.
    if(!$table->store())
    {
      $this->setError($table->getError());

      return false;
    }

    return $table;
  }

  /**
   * Creation of imagetypes based on one source file.
   * Source file has to be given with a full system path.
   *
   * @param   ImageTable     $img        ImageTable object, already stored
   * @param   string         $source     Source file with which the imagetypes shall be created
   * 
   * @return  bool           True on success, false otherwise
   * 
   * @since   4.0.0
   */
  protected function createImages(ImageTable $img, string $source): bool
  {
    // Create file manager service
    $this->component->createFileManager();

    return $this->component->getFileManager()->createImages($source, $img->filename, $img->catid);
  }

  /**
   * Creation of imagetypes based on images already available on the server.
   * Source files has to be given for each imagetype with a full system path.
   *
   * @param   ImageTable     $img        ImageTable object, already stored
   * @param   array          $sources    List of source images for each imagetype
   *  
   * @return  bool           True on success, false otherwise
   * 
   * @since   4.0.0
   * @throws  \Exception
   */
  protected function reuseImages(ImageTable $img, array $sources): bool
  {
    // Create services
    $this->component->createFileManager();
    $this->component->createFilesystem($this->component->getConfig()->get('jg_filesystem','local-images'));

    // Fetch available imagetypes
    $imagetypes = $this->component->getFileManager()->get('imagetypes');

    // Check the sources
    if($imagetypes !=  \array_keys($sources))
    {
      throw new \Exception('Imagetype mapping from migration script does not match component configuration!', 1);
    }

    // Loop through all imagetypes
    $error = false;
    foreach($sources as $type => $path)
    {
      // Get image source path
      $img_src = $path;

      // Get category destination path
      $cat_dst = $this->component->getFileManager()->getCatPath($img->catid, $type);

      // Create image destination path
      $img_dst = $cat_dst . '/' . $img->filename;

      // Create folders if not existent
      $folder_dst = \dirname($img_dst);
      try
      {
        $this->component->getFilesystem()->createFolder(\basename($folder_dst), \dirname($folder_dst));
      }
      catch(\FileExistsException $e)
      {
        // Do nothing
      }
      catch(\Exception $e)
      {
        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_CATEGORY', \ucfirst($folder_dst)));
        $error = true;

        continue;
      }

      // Move images
      try
      {
        $this->component->getFilesystem()->move($img_src, $img_dst);
      }
      catch(\Exception $e)
      {
        // Operation failed
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVE_IMAGETYPE', \basename($img_src), $type));
        $error = true;

        continue;
      }
    }
    
    if($error)
    {
      return false;
    }
    else
    {
      return true;
    }
  }
}
