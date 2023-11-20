<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                              **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Migration;

// No direct access
\defined('_JEXEC') or die;

use Exception;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Log\Log;
use \Joomla\CMS\Language\Text;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Filesystem\Path;
use \Joomla\Database\DatabaseInterface;
use \Joomla\Database\DatabaseFactory;
use \Joomla\Component\Media\Administrator\Exception\FileNotFoundException;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Table\MigrationTable;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\Checks;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\MigrationInterface;
use stdClass;

/**
 * Migration Base Class
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class Migration implements MigrationInterface
{
  use ServiceTrait;

  /**
	 * Storage for the migration form object.
	 *
	 * @var   Registry
	 *
	 * @since  4.0.0
	 */
	protected $params = null;

  /**
	 * Storage for the migration info object.
	 *
	 * @var   \stdClass
	 *
	 * @since  4.0.0
	 */
	protected $info = null;

  /**
	 * Name of the migration script.
	 *
	 * @var   string
	 *
	 * @since  4.0.0
	 */
	protected $name = '';

  /**
   * Is the migration performed from the command line
   *
   * @var    boolean
   * 
   * @since  4.0.0
   */
  protected $isCli = false;

  /**
   * List of content types which can be migrated with this script
   * Use the singular form of the content type (e.g image, not images)
   *
   * @var    Types[]
   * 
   * @since  4.0.0
   */
  protected $types = array();

  /**
   * List of migrateables processed/migrated with this script
   *
   * @var    MigrationTable[]
   * 
   * @since  4.0.0
   */
  protected $migrateables = array();

  /**
   * True, if the migration process of the current content type should be continued
   * False to stop the automatic migration process.
   *
   * @var    boolean
   * 
   * @since  4.0.0
   */
  protected $continue = true;

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct()
  {
    // Load application
    $this->getApp();
    
    // Load component
    $this->getComponent();

    // Try to load language file of the migration script
    $this->app->getLanguage()->load('com_joomgallery.migration.'.$this->name, _JOOM_PATH_ADMIN);

    // Set logger
    $this->component->setLogger('migration');

    // Fill info object
    $this->info               = new \stdClass;
    $this->info->name         = $this->name;
    $this->info->title        = Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($this->name).'_TITLE');
    $this->info->description  = Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($this->name).'_DESC');
  }

  /**
	 * Destructor
	 *
	 * @return  void
   *
	 * @since  4.0.0
	 */
	public function __destruct()
	{
    // Reset logger to default
    $this->component->setLogger();
	}

  /**
   * A list of content type definitions depending on migration source
   * 
   * @param   bool    $names_only  True to load type names only. No migration parameters required.
   * 
   * @return  array   The source types info
   *                  array(tablename, primarykey, isNested, isCategorized, prerequirements, pkstoskip)
   *                  Needed: tablename, primarykey, isNested, isCategorized
   *                  Optional: prerequirements, pkstoskip
   * 
   * @since   4.0.0
   */
  public function defineTypes($names_only = false): array
  {
    // Content type definition array
    // Order of the content types must correspond to the migration order
    // Pay attention to the prerequirements when ordering here !!!

    /* Example:
    $types = array( 'category' => array('#__joomgallery_catg', 'cid', true, false, array(), array(1)),
                    'image' =>    array('#__joomgallery', 'id', false, true, array('category'))
                  );
    */
    
    return array();
  }

  /**
   * Returns a list of content types which can be migrated.
   *
   * @return  array  List of content types
   * 
   * @since   4.0.0
   */
  public function getMigrateables(): array
  {
    if(empty($this->migrateables))
    {
      // Get MigrationModel
      $model = $this->component->getMVCFactory()->createModel('migration', 'administrator');

      // Load migrateables
      $this->migrateables = $model->getItems();
    }

    return $this->migrateables;
  }

  /**
   * Prepare the migration.
   *
   * @return  MigrationTable  The currently processed migrateable
   * 
   * @since   4.0.0
   */
  public function prepareMigration(string $type)
  {
    // Load migrateables to migration service
    $this->getMigrateables();

    // Set the migration parameters
    $migrateableKey = 0;
    foreach($this->migrateables as $key => $migrateable)
    {
      if($migrateable->type == $type)
      {
        $this->setParams($migrateable->params);
        $migrateableKey = $key;

        continue;
      }
    }

    return $this->migrateables[$migrateableKey];
  }

  /**
   * Step 2
   * Perform pre migration checks.
   *
   * @return  \stdClass[]  An array containing the precheck results.
   * 
   * @since   4.0.0
   */
  public function precheck(): array
  {
    // Instantiate a new checks class
    $checks = new Checks();

    // Check general requirements
    $checks->addCategory('general', Text::_('COM_JOOMGALLERY_GENERAL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_GENERAL_CHECK_DESC'));
    $this->checkLogFile($checks, 'general');
    $this->checkSiteState($checks, 'general');

    // Check source extension (version, compatibility)
    $checks->addCategory('source', Text::_('COM_JOOMGALLERY_SOURCE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_SOURCE_CHECK_DESC'));
    $this->checkSourceExtension($checks, 'source');

    // Check existance and writeability of source directories
    $this->checkSourceDir($checks, 'source');

    // Check existence and integrity of source database tables
    $this->checkSourceTable($checks, 'source');

    // Check destination extension (version, compatibility)
    $checks->addCategory('destination', Text::_('COM_JOOMGALLERY_DESTINATION'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_DESTINATION_CHECK_DESC'));
    $this->checkDestExtension($checks, 'destination');

    // Check existance and writeability of destination directories
    $this->checkDestDir($checks, 'destination');

    // Check existence and integrity of destination database tables
    $this->checkDestTable($checks, 'destination');

    // Check image mapping
    if($this->params->get('image_usage', 0) > 0)
    {
      $this->checkImageMapping($checks, 'destination');
    }

    // Perform some script specific checks
    $this->scriptSpecificChecks($checks, 'general');

    return $checks->getAll();
  }

  /**
   * Step 4
   * Perform post migration checks.
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  public function postcheck()
  {
    return;
  }

  /**
   * Step 3
   * Perform one specific miration step and mark it as done at the end.
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  public function migrate($type, $source, $dest)
  {
    return;
  }

  /**
   * Get a database object
   * 
   * @param   string   $target   The target (source or destination)
   *
   * @return  array    list($db, $dbPrefix)
   *
   * @since   4.0.0
   * @throws  \Exception
  */
  public function getDB(string $target): array
  {
    if(!in_array($target, array('source', 'destination')))
    {
      throw new \Exception('Taget has to be eighter "source" or "destination". Given: ' . $target, 1);
    }

    if($target === 'destination' || $this->params->get('same_db'))
    {
      $db        = Factory::getContainer()->get(DatabaseInterface::class);
      $dbPrefix  = $this->app->get('dbprefix');
    }
    else
    {
      $options   = array ('driver' => $this->params->get('dbtype'), 'host' => $this->params->get('dbhost'), 'user' => $this->params->get('dbuser'), 'password' => $this->params->get('dbpass'), 'database' => $this->params->get('dbname'), 'prefix' => $this->params->get('dbprefix'));
      $dbFactory = new DatabaseFactory();
      $db        = $dbFactory->getDriver($this->params->get('dbtype'), $options);
      $dbPrefix  = $this->params->get('dbprefix');
    }

    return array($db, $dbPrefix);
  }

  /**
   * Set params to object
   * 
   * @param   mixed   $params   Array or object of params
   *
   * @since   4.0.0
  */
  public function setParams($params)
  {
    $this->params = new Registry($params);
  }

  /**
   * Returns the Joomla root path of the source.
   *
   * @return  string    Source Joomla root path
   * 
   * @since   4.0.0
   */
  protected function getSourceRootPath(): string
  {
    if($this->params->get('same_joomla', 1))
    {
      $root = Path::clean(JPATH_ROOT . '/');
    }
    else
    {
      $root = Path::clean($this->params->get('joomla_path'));

      if(\substr($root, -1) != '/')
      {
        $root = Path::clean($root . '/');
      }
    }

    return $root;
  }

  /**
   * Loads all available content types to Migration object.
   * Gets available with the function defineTypes() from migration script.
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  protected function loadTypes()
  {
    if(empty($this->types))
    {
      if(\is_null($this->params))
      {
        throw new Exception('Migration parameters need to be set in order to load types.', 1);
      }

      $types = $this->defineTypes();

      foreach($types as $key => $list)
      {
        $type = new Type($key, $list);

        $this->types[$key] = $type;
      }
    }
  }

  /**
   * Returns a list of involved source tables.
   *
   * @return  array    List of table names (Joomla style, e.g #__joomgallery)
   *                   array('image' => '#__joomgallery', ...)
   * 
   * @since   4.0.0
   */
  public function getSourceTables(): array
  {
    $this->loadTypes();

    $tables = array();
    foreach($this->types as $key => $type)
    {
      $tables[$key] = $this->types[$key]->get('tablename');
    }

    return $tables;
  }

  /**
   * Returns tablename and primarykey name of the source table
   *
   * @param   string   $type    The content type name
   * 
   * @return  array   The corresponding source table info
   *                  list(tablename, primarykey)
   * 
   * @since   4.0.0
   */
  public function getSourceTableInfo(string $type): array
  {
    $this->loadTypes();

    return array($this->types[$type]->get('tablename'), $this->types[$type]->get('pk'));
  }

  /**
   * Returns a list of involved content types.
   *
   * @return  array    List of type names
   *                   array('image', 'category', ...)
   * 
   * @since   4.0.0
   */
  public function getTypes(): array
  {
    $types = $this->defineTypes(true);

    return $types;
  }

  /**
   * True if the given record has to be migrated
   * False to skip the migration for this record
   *
   * @param   string   $type   Name of the content type
   * @param   int      $pk     The primary key of the content type
   * 
   * @return  bool     True to continue migration, false to skip it
   * 
   * @since   4.0.0
   */
  public function needsMigration(string $type, int $pk): bool
  {
    $this->loadTypes();

    // Content types that require another type beeing migrated completely
    if(!empty($this->types[$type]))
    {
      foreach($this->types[$type]->get('needsMigrated') as $key => $req)
      {
        if(!$this->migrateables[$req] || !$this->migrateables[$req]->completed || $this->migrateables[$req]->failed->count() > 0)
        {
          $this->continue = false;
          $this->component->setError(Text::sprintf('FILES_JOOMGALLERY_MIGRATION_PREREQUIREMENT_ERROR', \implode(', ', $this->types[$type]->get('needsMigrated'))));

          return false;
        }
      }
    }

    // Specific record primary keys which can be skiped
    foreach($this->types[$type]->get('skip') as $skip)
    {   
      if($pk == $skip)
      {
        return false;
      }
    }

    return true;
  }

  /**
   * Precheck: Check logfile and add check to checks array.
   * 
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkLogFile(Checks &$checks, string $category)
  {
    $log_dir  = Path::clean($this->app->get('log_path'));

    if(\is_dir($log_dir))
    {
      $log_file = Path::clean($log_dir . '/' . 'com_joomgallery.log.php');

      if(\is_file($log_file))
      {
        if(\is_writable($log_dir))
        {
          $checks->addCheck($category, 'log_file', true, false, Text::_('COM_JOOMGALLERY_LOGFILE'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGFILE_SUCCESS', $log_file));
        }
        else
        {
          $checks->addCheck($category, 'log_file', false, false, Text::_('COM_JOOMGALLERY_LOGFILE'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGFILE_ERROR', $log_file));
        }
      }
      else
      {
        if(\is_writable($log_dir))
        {
          $checks->addCheck($category, 'log_dir', true, false, Text::_('COM_JOOMGALLERY_LOGDIRECTORY'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGDIR_SUCCESS', $log_dir));
        }
        else
        {
          $checks->addCheck($category, 'log_dir', false, false, Text::_('COM_JOOMGALLERY_LOGDIRECTORY'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGDIR_ERROR', $log_dir));
        }
      }
    }
    else
    {
      $checks->addCheck($category, 'log_dir', false, false, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_LOG_DIR_LABEL'), Text::_('Logging directory not existent.'));
    }
    
  }

  /**
   * Precheck: Check the source extension to be the correct one for this migration script
   * 
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkSourceExtension(Checks &$checks, string $category)
  {
    $src_info = $this->getTargetinfo('source');
    $src_xml  = $this->getSourceXML();

    if(\version_compare(PHP_VERSION, $src_info->get('php_min'), '<'))
    {
      // PHP version not supported
      $checks->addCheck($category, 'src_extension', false, false, Text::_('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_PHP_WRONG_VERSION', PHP_VERSION, $src_info->get('php_min')));
    }
    elseif(\strval($src_xml->name) !== $src_info->get('extension'))
    {
      // Wrong source extension
      $checks->addCheck($category, 'src_extension', false, false, Text::_('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_NOT_SUPPORTED', \strval($src_xml->name)));
    }
    elseif(\version_compare($src_xml->version, $src_info->get('min'), '<') || \version_compare($src_xml->version, $src_info->get('max'), '>'))
    {
      // Version not correct
      $checks->addCheck($category, 'src_extension', false, false, Text::_('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_WRONG_VERSION', $src_xml->version, $src_info->get('min') . ' - ' . $src_info->get('max')));
    } 
    else
    {
      // Check successful
      $checks->addCheck($category, 'src_extension', true, false, Text::_('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_SUCCESS', \strval($src_xml->name), $src_xml->version));
    }
  }

  /**
   * Precheck: Check the destination extension to be the correct one for this migration script
   * 
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkDestExtension(Checks &$checks, string $category)
  {
    $dest_info = $this->getTargetinfo('destination');
    $version   = \str_replace('-dev', '', $this->component->version);

    if(\version_compare(PHP_VERSION, $dest_info->get('php_min'), '<'))
    {
      // PHP version not supported
      $checks->addCheck($category, 'dest_extension', false, false, Text::_('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_PHP_WRONG_VERSION', PHP_VERSION, $dest_info->get('php_min')));
    }
    elseif(\strval($this->component->xml->name) !== $dest_info->get('extension'))
    {
      // Wrong destination extension
      $checks->addCheck($category, 'dest_extension', false, false, Text::_('COM_JOOMGALLERY_FIELDS_DEST_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_NOT_SUPPORTED', \strval($this->component->xml->name)));
    }
    elseif(\version_compare($version, $dest_info->get('min'), '<') || \version_compare($version, $dest_info->get('max'), '>'))
    {
      // Version not correct
      $checks->addCheck($category, 'dest_extension', false, false, Text::_('COM_JOOMGALLERY_FIELDS_DEST_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_WRONG_VERSION', $this->component->version, $dest_info->get('min') . ' - ' . $dest_info->get('max')));
    }
    else
    {
      // Check successful
      $checks->addCheck($category, 'dest_extension', true, false, Text::_('COM_JOOMGALLERY_FIELDS_DEST_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_SUCCESS', \strval($this->component->xml->name), $this->component->version));
    }
  }

  /**
   * Precheck: Check site state and add check to checks array.
   * 
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkSiteState(Checks &$checks, string $category)
  {
    if($this->app->get('offline'))
    {
      $checks->addCheck($category, 'offline', true, false, Text::_('COM_JOOMGALLERY_SITE_OFFLINE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_OFFLINE_SUCCESS'));
    }
    else
    {
      $checks->addCheck($category, 'offline', false, false, Text::_('COM_JOOMGALLERY_SITE_OFFLINE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_OFFLINE_ERROR'));
    }
  }

  /**
   * Precheck: Check directories of the source to be existent
   * 
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkSourceDir(Checks &$checks, string $category)
  {
    // Retrieve a list of source directories involved in migration
    $directories = $this->getSourceDirs();
    $root        = $this->getSourceRootPath();

    foreach($directories as $dir)
    {
      $check_name = 'src_dir_' . \basename($dir);

      if(!\is_dir($root . $dir))
      {
        // Path is not a directory
        $checks->addCheck($category, $check_name, false, false, Text::_('COM_JOOMGALLERY_DIRECTORY') . ': ' . $dir, Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_NOT_A_DIRECTORY'));
      }
      else
      {
        $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_DIRECTORY') . ': ' . $dir, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_DIRECTORY_SUCCESS'));
      }
    }
  }

  /**
   * Precheck: Check directories of the destination to be existent and writeable
   * 
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkDestDir(Checks &$checks, string $category)
  {
    // Instantiate filesystem service
    $this->component->createFilesystem($this->component->getConfig()->get('jg_filesystem','local-images'));

    // Get all imagetypes
    $imagetypes = JoomHelper::getRecords('imagetypes', $this->component);

    foreach($imagetypes as $imagetype)
    {
      $check_name = 'dest_dir_' . $imagetype->typename;
      $error      = false;

      try
      {
        $dir_info = $this->component->getFilesystem()->getFile($imagetype->path);
      }
      catch(FileNotFoundException $msg)
      { 
        // Path doesn't exist
        $checks->addCheck($category, $check_name, false, false, Text::_('COM_JOOMGALLERY_DIRECTORY') . ': ' . $imagetype->path, Text::_('COM_JOOMGALLERY_ERROR_PATH_NOT_EXISTING'));
        $error = true;
      }
      catch(\Exception $msg)
      {
        // Error in filesystem
        $checks->addCheck($category, $check_name, false, false, Text::_('COM_JOOMGALLERY_DIRECTORY') . ': ' . $imagetype->path, Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_ERROR', $msg));
        $error = true;
      }

      if(!$error)
      {
        if($dir_info->type !== 'dir')
        {
          // Path is not a directory
          $checks->addCheck($category, $check_name, false, false, Text::_('COM_JOOMGALLERY_DIRECTORY') . ': ' . $imagetype->path, Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_NOT_A_DIRECTORY'));
        }
        else
        {
          $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_DIRECTORY') . ': ' . $imagetype->path, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_DIRECTORY_SUCCESS'));
        }
      }      
    }
  }

  /**
   * Precheck: Check db and tables of the source
   * 
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkSourceTable(Checks &$checks, string $category)
  {
    list($db, $dbPrefix) = $this->getDB('source');

    // Check connection to database
    try
    {
      $tableList = $db->getTableList();
    }
    catch (\Exception $msg)
    {
      $checks->addCheck($category, 'src_table_connect', true, Text::_('JLIB_FORM_VALUE_SESSION_DATABASE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_TABLE_CONN_ERROR'));
    }

    // Check required tables
    $tables = $this->getSourceTables();
    foreach($tables as $tablename)
    {
      $check_name = 'src_table_' . $tablename;

      // Check if required tables exists
      if(!\in_array(\str_replace('#__', $dbPrefix, $tablename), $tableList))
      {
        $checks->addCheck($category, $check_name, false, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::_('COM_JOOMGALLERY_ERROR_TABLE_NOT_EXISTING'));
        continue;
      }

      $query = $db->getQuery(true)
              ->select('COUNT(*)')
              ->from($tablename);
      $db->setQuery($query);

      $count = $db->loadResult();

      // Check number of records in tables
      $check_name = 'dest_table_' . $tablename . '_count';
      if($count == 0)
      {
        $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES_EMPTY'));
      }
      else
      {
        $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES', $count));
      }
    }
  }

  /**
   * Precheck: Check db and tables of the destination
   * 
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkDestTable(Checks &$checks, string $category)
  {
    // Get table info
    list($db, $dbPrefix) = $this->getDB('destination');
    $tables              = JoomHelper::$content_types;
    $tableList           = $db->getTableList();

    // Check whether root category exists
    $rootCat = false;    
    $query   = $db->getQuery(true)
          ->select('COUNT(*)')
          ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
          ->where($db->quoteName('id') . ' = 1')
          ->where($db->quoteName('title') . ' = ' . $db->quote('Root'))
          ->where($db->quoteName('parent_id') . ' = 0');
    $db->setQuery($query);

    if($db->loadResult())
    {
      $checks->addCheck($category, 'dest_root_cat', true, false, Text::_('COM_JOOMGALLERY_ROOT_CATEGORY'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_CAT_SUCCESS'));
      $rootCat = true;
    }
    else
    {
      $checks->addCheck($category, 'dest_root_cat', false, false, Text::_('COM_JOOMGALLERY_ROOT_CATEGORY'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_CAT_ERROR'));
    }

    // Check whether root asset exists
    $query = $db->getQuery(true)
          ->select('id')
          ->from($db->quoteName('#__assets'))
          ->where($db->quoteName('name') . ' = ' . $db->quote(_JOOM_OPTION))
          ->where($db->quoteName('parent_id') . ' = 1');
    $db->setQuery($query);

    if($rootAssetID = $db->loadResult())
    {
      $checks->addCheck($category, 'dest_root_asset', true, false, Text::_('COM_JOOMGALLERY_ROOT_ASSET'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_ASSET_SUCCESS'));
    }
    else
    {
      $checks->addCheck($category, 'dest_root_asset', false, false, Text::_('COM_JOOMGALLERY_ROOT_ASSET'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_ASSET_ERROR'));
    }

    // Check whether root category asset exists
    $query = $db->getQuery(true)
          ->select('id')
          ->from($db->quoteName('#__assets'))
          ->where($db->quoteName('name') . ' = ' . $db->quote('com_joomgallery.category.1'))
          ->where($db->quoteName('parent_id') . ' = ' . $db->quote($rootAssetID));
    $db->setQuery($query);

    if($db->loadResult())
    {
      $checks->addCheck($category, 'dest_root_cat_asset', true, false, Text::_('COM_JOOMGALLERY_ROOT_CAT_ASSET'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_CAT_ASSET_SUCCESS'));
    }
    else
    {
      $checks->addCheck($category, 'dest_root_cat_asset', false, false, Text::_('COM_JOOMGALLERY_ROOT_CAT_ASSET'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_CAT_ASSET_ERROR'));
    }

    // Check required tables
    foreach($tables as $tablename)
    {
      $check_name = 'dest_table_' . $tablename;

      // Check if required tables exists
      if(!\in_array( \str_replace('#__', $dbPrefix, $tablename), $tableList))
      {
        $checks->addCheck($category, $check_name, false, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::_('COM_JOOMGALLERY_ERROR_TABLE_NOT_EXISTING'));
        continue;
      }

      // Check number of records in tables
      $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($tablename);
      $db->setQuery($query);

      $count = $db->loadResult();

      if($tablename == _JOOM_TABLE_CATEGORIES && $rootCat)
      {
        $count = $count - 1;
      }

      $check_name = 'dest_table_' . $tablename . '_count';
      if($count == 0)
      {
        $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES_EMPTY'));
      }
      elseif($this->params->get('source_ids', 0) > 0 && $count > 0)
      {
        $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES', $count));
        $this->checkDestTableIdAvailability($checks, $category, $tablename);
      }
      else
      {
        $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES', $count));
      }
    }    
  }

  /**
   * Precheck: Check destination tables for already existing ids
   * 
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   * @param  string   $tablename  The table to be checked
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkDestTableIdAvailability(Checks &$checks, string $category, string $tablename)
  {
    // Get content type to check
    $type = '';
    foreach(JoomHelper::$content_types as $type => $table)
    {
      if($table === $tablename)
      {
        break;
      }

      $type = '';
    }

    // Get migrateable to check
    $this->getMigrateables();
    $migrateable = null;
    foreach($this->migrateables as $key => $migrateable)
    {
      if($migrateable->get('type', false) === $type)
      {
        break;
      }

      $migrateable = null;
    }

    if(!$migrateable)
    {
      // Table does not correspont to a migrateable. Exit method.
      return;
    }

    // Get destination database
    list($db, $dbPrefix) = $this->getDB('destination');

    // Get a list of used ids from destination database
    $destQuery = $db->getQuery(true);
    $destQuery->select($db->quoteName('id'))
        ->from($db->quoteName($tablename));
    $destQuery_string = \trim($destQuery->__toString());

    if($this->params->get('same_db', 1))
    {
      // Get list of used ids from source databse
      $srcQuery = $db->getQuery(true);
      $srcQuery->select($db->quoteName($migrateable->get('pk'), 'id'))
          ->from($db->quoteName($migrateable->get('table')));
      $srcQuery_string = \trim($srcQuery->__toString());

      // Get a list of ids used in both source and destination
      $query = $db->getQuery(true);
      $query->select($db->quoteName('ids.id'))
          ->from('(' . $srcQuery_string . ') ids')
          ->where($db->quoteName('ids.id') . ' IN (' . $destQuery_string . ')');
      $db->setQuery($query);
    }
    else
    {
      // Get source database
      list($src_db, $src_dbPrefix) = $this->getDB('source');

      // Get list of used ids from the source database
      $query = $src_db->getQuery(true);
      $query->select($db->quoteName($migrateable->get('pk'), 'id'))
          ->from($db->quoteName($migrateable->get('table')));
      $src_db->setQuery($query);

      // Load list from source database
      $src_list = $src_db->loadColumn();

      if(\count($src_list) < 1)
      {
        // There are no records in the source tabele. Exit method.
        return;
      }

      // Create UNION query string
      foreach($src_list as $i => $id)
      {
        ${'query' . $i} = $db->getQuery(true);
        ${'query' . $i}->select($db->quote($id) . ' AS ' . $db->quoteName('id'));
        if($i > 0)
        {
          $query0->unionAll(${'query' . $i});
        }
      }
      $srcQuery_string = \trim($query0->__toString());

      // Get a list of ids used in both source and destination
      $query = $db->getQuery(true);
      $query->select($db->quoteName('ids.id'))
          ->from('(' . $srcQuery_string . ') ids')
          ->where($db->quoteName('ids.id') . ' IN (' . $destQuery_string . ')');
      $db->setQuery($query);
    }

    // Load list of Id's used in both tables (source and destination)
    $list = $db->loadColumn();

    // Exception for root category
    if($tablename == _JOOM_TABLE_CATEGORIES)
    {
      $list = \array_diff($list, array(1, '1'));
    }

    if(!empty($list))
    {
      $checks->addCheck($category, 'dest_table_' . $tablename . '_ids', false, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES_USE_IDS_HINT', \implode(',', $list)));
    }
  }

  /**
   * Precheck: Check the configured image mapping
   * 
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkImageMapping(Checks &$checks, string $category)
  {
    $mapping         = $this->params->get('image_mapping');
    $dest_imagetypes = JoomHelper::getRecords('imagetypes', $this->component);
    $src_imagetypes  = array();

    // Check if mapping contains enough elements
    if(\count((array)$mapping) != \count($dest_imagetypes))
    {
      $checks->addCheck($category, 'mapping_count', false, false, Text::_('COM_JOOMGALLERY_FIELDS_IMAGEMAPPING_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_MAPPING_ERROR'));
      return;
    }

    // Load source imagetypes from xml file
    $xml     = \simplexml_load_file(JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/src/Service/Migration/Scripts/'. $this->name . '.xml');
    $element = $xml->xpath('/form/fieldset/field[@name="image_mapping"]/form/field[@name="source"]');

    foreach($element[0]->option as $option)
    {
      \array_push($src_imagetypes, (string) $option['value']);
    }

    // Prepare destination imagetypes
    $tmp_dest_imagetypes = array();
    foreach($dest_imagetypes as $key => $type)
    {
      \array_push($tmp_dest_imagetypes, (string) $type->typename);
    }

    // Check if all imagetypes are correctly set in the mapping
    foreach($mapping as $key => $mapVal)
    {
      if(\in_array($mapVal->destination, $tmp_dest_imagetypes))
      {
        // Remove imagetype from tmp_dest_imagetypes array
        $tmp_dest_imagetypes = \array_diff($tmp_dest_imagetypes, array($mapVal->destination));
      }
      else
      {
        // Destination imagetype in mapping does not exist
        $checks->addCheck($category, 'mapping_dest_types_'.$mapVal->destination, false, false, Text::_('COM_JOOMGALLERY_FIELDS_IMAGEMAPPING_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_MAPPING_DEST_IMAGETYPE_NOT_EXIST', Text::_('COM_JOOMGALLERY_' . \strtoupper($mapVal->destination))));
        return;
      }

      if(!\in_array($mapVal->source, $src_imagetypes))
      {
        // Source imagetype in mapping does not exist
        $checks->addCheck($category, 'mapping_src_types_'.$mapVal->source, false, false, Text::_('COM_JOOMGALLERY_FIELDS_IMAGEMAPPING_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_MAPPING_IMAGETYPE_NOT_EXIST', Text::_('COM_JOOMGALLERY_' . \strtoupper($mapVal->source))));
        return;
      }
    }

    if(!empty($tmp_dest_imagetypes))
    {
      // Destination imagetype not used in the mapping
      $checks->addCheck($category, 'mapping_dest_types', false, false, Text::_('COM_JOOMGALLERY_FIELDS_IMAGEMAPPING_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_MAPPING_IMAGETYPE_NOT_USED', \implode(', ', $tmp_dest_imagetypes)));
    }
  }

  /**
   * Precheck: Perform script specific checks
   * 
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function scriptSpecificChecks(Checks &$checks, string $category)
  {
    return;
  }

  /**
   * Converts a data array based on a mapping.
   *
   * @param   array   $data     Data received from getData() method.
   * @param   array   $mapping  Mapping array telling how to convert data.
   * 
   * @return  array   Converted data array
   * 
   * @since   4.0.0
   */
  protected function applyConvertData(array $data, array $mapping): array
  {
    // Loop through the data provided
    foreach($data as $key => $value)
    {
      // Key not in the mapping array --> Nothing to do.
      if(!\key_exists($key, $mapping))
      {
        continue;
      }

      // Mapping from an old to a new key ('old key' => 'new key')
      if(\is_string($mapping[$key]) && !empty($mapping[$key]))
      {
        $data[$mapping[$key]] = $value;
        unset($data[$key]);

        continue;
      }

      // Remove content from data array element ('old key' => false)
      if($mapping[$key] === false)
      {
        $data[$key] = null;

        continue;
      }

      // Content gets merged into anothter data array element ('old key' => array(string, string, bool))
      // array('destination field name', 'new field name', 'create child')
      if(\is_array($mapping[$key]))
      {
        $destFieldName = $mapping[$key][0];

        // Prepare destField
        if(!\key_exists($destFieldName, $data) || empty($data[$destFieldName]))
        {
          // Field does not exist or is empty
          $data[$destFieldName] = new Registry();
        }
        elseif(!($data[$destFieldName] instanceof Registry))
        {
          // Field exists and is already of type Registry
          $data[$destFieldName] = new Registry($data[$destFieldName]);
        }

        // Create new field name
        $newKey = $key;
        if(\count($mapping[$key]) > 1 && !empty($mapping[$key][1]))
        {
          $newKey = $mapping[$key][1];        
        }

        // Prepare srcField
        if(\count($mapping[$key]) > 2 && !empty($mapping[$key][2]))
        {
          // Add as a child node
          $child = new Registry($value);
          $value = new Registry(array($newKey=> $child));
        }
        else
        {
          // Add directly
          $srcLenght = 1;
          $isJson    = false;

          // Detect we have a json string
          if(\is_string($value))
          {
            \json_decode($value);
            if(json_last_error() === JSON_ERROR_NONE)
            {
              $isJson = true;
            }
          }

          // Get source lenght
          elseif(\is_array($value))
          {
            $srcLenght = \count($value);
          }
          elseif(\is_object($value))
          {
            if($value instanceof \Countable)
            {
              $srcLenght = $value->count();
            }
            else
            {
              $srcLenght = \count(\get_object_vars($value));
            }
          }

          if($srcLenght > 1 || $isJson)
          {
            // We are trying to add a json or an object directly without adding a child
            // Here 'new field name' has no effect
            $value = new Registry($value);
          }
          else
          {
            if(\is_array($value))
            {
              $value = $value[0];
            }
            elseif(\is_object($value))
            {
              $keys = \array_keys(\get_object_vars($value));
              $value = $value[$keys[0]];
            }

            // Create registry with only one key value pair
            $value = new Registry(array($newKey=> $value));
          }
        }

        // Apply merge
        $data[$destFieldName]->merge($value);
        
        if($key != $destFieldName)
        {
          unset($data[$key]);
        }

        continue;
      }
    }

    return $data;
  }
}
