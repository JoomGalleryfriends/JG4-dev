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
    $checks->addCategory('general', Text::_('COM_JOOMGALLERY_GENERAL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGFILE_CHECK_DESC'));
    $this->checkLogFile($checks, 'general');
    $this->checkSiteState($checks, 'general');

    // Check source extension (version, compatibility)
    $checks->addCategory('source', Text::_('COM_JOOMGALLERY_SOURCE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_CHECK_DESC'));
    $this->checkSourceExtension($checks, 'source');

    // Check existance and writeability of source directories
    $this->checkSourceDir($checks, 'source_directories');

    // Check existence and integrity of source databasetables
    $this->checkSourceTable($checks, 'source_tables');

    // Check destination extension (version, compatibility)
    $checks->addCategory('destination', Text::_('COM_JOOMGALLERY_DESTINATION'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_CHECK_DESC'));
    $this->checkDestExtension($checks, 'destination');

    // Check existance and writeability of destination directories
    $this->checkDestDir($checks, 'dest_directories');

    // Check existence and integrity of destination databasetables
    $this->checkDestTable($checks, 'dest_tables');

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
   * Precheck: Check the source extension to be the correct one for this migration script
   * 
   * @param  Checks   $checks     The checks object
   * @param  int      $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkSourceExtension(Checks &$checks, int $category)
  {
    $src_info = $this->getTargetinfo('source');

    if(\version_compare(PHP_VERSION, $src_info->php_min, '<'))
    {
      // PHP version not supported
      $checks->addCheck($category, 'src_extension', false, Text::sprintf('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_PHP_WRONG_VERSION', PHP_VERSION, $src_info->php_min));
    }
    elseif($this->component->xml->name !== $src_info->extension)
    {
      // Wrong destination extension
      $checks->addCheck($category, 'src_extension', false, Text::sprintf('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_NOT_SUPPORTED', $this->component->xml->name));
    }
    elseif(\version_compare($this->component->version, $src_info->min, '<') || \version_compare($this->component->version, $src_info->max, '>'))
    {
      // Version not correct
      $checks->addCheck($category, 'src_extension', false, Text::sprintf('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_WRONG_VERSION', $this->component->version, $src_info->min . ' - ' . $src_info->max));
    }
    else
    {
      // Check successful
      $checks->addCheck($category, 'src_extension', true, Text::sprintf('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_SUCCESS', $this->component->xml->name, $this->component->version));
    }
  }

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
  protected function checkDestExtension(Checks &$checks, int $category)
  {
    $dest_info = $this->getTargetinfo('destination');

    if(\version_compare(PHP_VERSION, $dest_info->php_min, '<'))
    {
      // PHP version not supported
      $checks->addCheck($category, 'dest_extension', false, Text::sprintf('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_PHP_WRONG_VERSION', PHP_VERSION, $dest_info->php_min));
    }
    elseif($this->component->xml->name !== $dest_info->extension)
    {
      // Wrong destination extension
      $checks->addCheck($category, 'dest_extension', false, Text::sprintf('COM_JOOMGALLERY_FIELDS_DEST_EXTENSION_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_NOT_SUPPORTED', $this->component->xml->name));
    }
    elseif(\version_compare($this->component->version, $dest_info->min, '<') || \version_compare($this->component->version, $dest_info->max, '>'))
    {
      // Version not correct
      $checks->addCheck($category, 'dest_extension', false, Text::sprintf('COM_JOOMGALLERY_FIELDS_DEST_EXTENSION_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_WRONG_VERSION', $this->component->version, $dest_info->min . ' - ' . $dest_info->max));
    }
    else
    {
      // Check successful
      $checks->addCheck($category, 'dest_extension', true, Text::sprintf('COM_JOOMGALLERY_FIELDS_DEST_EXTENSION_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_SUCCESS', $this->component->xml->name, $this->component->version));
    }
  }

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
