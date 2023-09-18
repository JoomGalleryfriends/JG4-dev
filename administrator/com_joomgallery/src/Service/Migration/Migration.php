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

use \Joomla\CMS\Factory;
use \Joomla\CMS\Log\Log;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Filesystem\Path;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Migration\Checks\Checks;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\MigrationInterface;

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
	 * @var   \stdClass
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
   *
   * @var    array
   * 
   * @since  4.0.0
   */
  protected $contentTypes = array();

  /**
   * State if logger is created
   *
   * @var bool
   * 
   * @since  4.0.0
  */
  protected $log = false;

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

    // Create logger
    $this->addLogger();

    // Fill info object
    $this->info               = new \stdClass;
    $this->info->name         = $this->name;
    $this->info->title        = Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($this->name).'_TITLE');
    $this->info->description  = Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($this->name).'_DESC');
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
    return $this->contentTypes;
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

    // Check log file
    $checks->addCategory('logfile', Text::_('COM_JOOMGALLERY_LOGFILE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGFILE_CHECK_DESC'));
    $this->checkLogFile($checks, 'logfile');

    // Check source extension (version, compatibility)
    $checks->addCategory('source', Text::_('COM_JOOMGALLERY_GENERAL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_CHECK_DESC'));
    $this->checkSourceExtension($checks, 'source');

    // Check destination extension (version, compatibility)
    $checks->addCategory('destination', Text::_('COM_JOOMGALLERY_GENERAL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_CHECK_DESC'));
    $this->checkDestExtension($checks, 'destination');

    // Check the state of the site and the environment (php, joomla, db, ...)
    $checks->addCategory('state', Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_STATE_CHECK_LABEL'));
    $this->checkEnvironment($checks, 'state');
    $this->checkSiteState($checks, 'state');

    // Check existance and writeability of source directories
    $checks->addCategory('source_directories', Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_SOURCE_DIRS_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_DIRECTORY_CHECK_DESC'));

    // Check existence and integrity of source databasetables
    $checks->addCategory('source_tables', Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_SOURCE_TABLES_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_TABLES_CHECK_DESC'));

    // Check existance and writeability of destination directories
    $checks->addCategory('dest_directories', Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_DEST_DIRS_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_DIRECTORY_CHECK_DESC'));

    // Check existence and integrity of destination databasetables
    $checks->addCategory('dest_tables', Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_DEST_TABLES_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_TABLES_CHECK_DESC'));





    /* $checks = array((object) array( 'name' => 'directories',
                                    'title' => 'Existence of directories',
                                    'colTitle' => 'Folder',
                                    'desc' => 'Are the nessecary directories available and writeable?',
                                    'checks' => array( (object) array(
                                                          'name' => 'originals',
                                                          'result' => true,
                                                          'title' => 'Original images',
                                                          'description' => '/joomla3/images/joomgallery/originals/',
                                                          'help' => 'Folder (/joomla3/images/joomgallery/originals/) exist and is writeable.'
                                                        ),
                                                        (object) array(
                                                          'name' => 'thumbs',
                                                          'result' => true,
                                                          'title' => 'Thumbnail images',
                                                          'description' => '/joomla3/images/joomgallery/thumbnails/',
                                                          'help' => 'Folder (/joomla3/images/joomgallery/thumbnails/) is not writeable. make sure the permissions are set correctly for this folder.'
                                                        ),
                                                      )
                                    )
                                ); */

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
   * Add a JoomGallery migration logger to the JLog class
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function addLogger()
  {
    if(!$this->log)
    {
      Log::addLogger(['text_file' =>  'com_joomgallery.migration.log.php'], Log::ALL, ['com_joomgallery.migration']);
    }
    
    $this->log = true;
  }

  /**
   * Log a message
   * 
   * @param   string   $txt       The message for a new log entry.
   * @param   integer  $priority  Message priority.
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function addLog($txt, $priority)
  {
    Log::add($txt, $priority, 'com_joomgallery.migration');
  }

  /**
   * Precheck: Check logfile and add check to checks array.
   * 
   * @param  Checks   $checks     The checks object
   * @param  int      $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkLogFile(Checks &$checks, int $category)
  {
    $log_dir  = Path::clean($this->app->get('log_path'));

    if(\is_dir($log_dir))
    {
      $log_file = Path::clean($log_dir . '/' . 'com_joomgallery.log.php');

      if(\is_file($log_file))
      {
        if(\is_writable($log_dir))
        {
          $checks->addCheck($category, 'log_file', true, Text::_('COM_JOOMGALLERY_LOGFILE'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGFILE_SUCCESS', $log_file));
        }
        else
        {
          $checks->addCheck($category, 'log_file', false, Text::_('COM_JOOMGALLERY_LOGFILE'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGFILE_ERROR', $log_file));
        }
      }
      else
      {
        if(\is_writable($log_dir))
        {
          $checks->addCheck($category, 'log_dir', true, Text::_('COM_JOOMGALLERY_LOGDIRECTORY'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGDIR_SUCCESS', $log_dir));
        }
        else
        {
          $checks->addCheck($category, 'log_dir', false, Text::_('COM_JOOMGALLERY_LOGDIRECTORY'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGDIR_ERROR', $log_dir));
        }
      }
    }
    else
    {
      $checks->addCheck($category, 'log_dir', false, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_LOG_DIR_LABEL'), Text::_('Logging directory not existent.'));
    }
    
  }

  /**
   * Precheck: Check the environment (joomla, php, db, ...) to be compatible with this migration script
   * 
   * @param  Checks   $checks     The checks object
   * @param  int      $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  abstract protected function checkEnvironment(Checks &$checks, int $category);

  /**
   * Precheck: Check the source extension to be the correct one for this migration script
   * 
   * @param  Checks   $checks     The checks object
   * @param  int      $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  abstract protected function checkSourceExtension(Checks &$checks, int $category);

  /**
   * Precheck: Check the destination extension to be the correct one for this migration script
   * 
   * @param  Checks   $checks     The checks object
   * @param  int      $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  abstract protected function checkDestExtension(Checks &$checks, int $category);

  /**
   * Precheck: Check site state and add check to checks array.
   * 
   * @param  Checks   $checks     The checks object
   * @param  int      $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkSiteState(Checks &$checks, int $category)
  {
    if($this->app->get('offline'))
    {
      $checks->addCheck($category, 'offline', true, Text::_('COM_JOOMGALLERY_SITE_OFFLINE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_OFFLINE_SUCCESS'));
    }
    else
    {
      $checks->addCheck($category, 'offline', false, Text::_('COM_JOOMGALLERY_SITE_OFFLINE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_OFFLINE_ERROR'));
    }
  }
}
