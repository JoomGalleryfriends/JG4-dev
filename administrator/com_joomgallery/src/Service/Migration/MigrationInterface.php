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

use \Joomgallery\Component\Joomgallery\Administrator\Table\ImageTable;
use \Joomgallery\Component\Joomgallery\Administrator\Table\CategoryTable;

/**
 * Interface for the migration service class
 *
 * @package JoomGallery
 * @since   4.0.0
 */
interface MigrationInterface
{
  // /**
	//  * Name of the migration script to be used.
  //  * (Required in migration scripts.)
	//  *
	//  * @var   string
	//  *
	//  * @since  4.0.0
	//  */
	// protected $name = 'scriptName';

  // /**
  //  * True to offer the task migration.removesource for this script
  //  * (Required in migration scripts.)
  //  *
  //  * @var    boolean
  //  * 
  //  * @since  4.0.0
  //  */
  // protected $sourceDeletion = false;

  /**
   * Returns an object with compatibility info for this migration script.
   * (Required in migration scripts.)
   * 
   * @param   string       $type    Select if you get source or destination info
   *
   * @return  Targetinfo   Compatibility info object
   * 
   * @since   4.0.0
   */
  public function getTargetinfo(string $type = 'source'): Targetinfo;

  /**
   * Returns the XML object of the source extension
   * (Required in migration scripts. Source extension XML must at least provide name and version info.)
   *
   * @return  \SimpleXMLElement   Extension XML object or False on failure
   * 
   * @since   4.0.0
   */
  public function getSourceXML();

  /**
   * A list of content type definitions depending on migration source
   * (Required in migration scripts. The order of the content types must correspond to its migration order.)
   * 
   * @param   bool    $names_only  True to load type names only. No migration parameters required.
   * 
   * @return  array   The source types info
   *                  array(tablename, primarykey, isNested, isCategorized, prerequirements, pkstoskip, ismigration, recordname)
   *                  Needed: tablename, primarykey, isNested, isCategorized
   *                  Optional: prerequirements, pkstoskip, ismigration, recordname
   * 
   * @since   4.0.0
   */
  public function defineTypes($names_only = false): array;

  /**
   * Returns a list of involved source directories.
   * (Required in migration scripts.)
   *
   * @return  array    List of paths
   * 
   * @since   4.0.0
   */
  public function getSourceDirs(): array;

  /**
   * Fetches an array of images from source to be used for creating the imagetypes
   * for the current image.
   * (Required in migration scripts.)
   *
   * @param   array   $data   Source record data received from getData() - before convertData()
   * 
   * @return  array   List of images from sources used to create the new imagetypes
   *                  1. If imagetypes get recreated:    array('image/source/path')
   *                  2. If imagetypes get copied/moved: array('original' => 'image/source/path1', 'detail' => 'image/source/path2', ...)
   * 
   * @since   4.0.0
   */
  public function getImageSource(array $data): array;

  /**
   * Returns an associative array containing the record data from source.
   * (Required in migration scripts.)
   *
   * @param   string   $type   Name of the content type
   * @param   int      $pk     The primary key of the content type
   * 
   * @return  array    Record data
   * 
   * @since   4.0.0
   */
  public function getData(string $type, int $pk): array;

  /**
   * Converts data from source into the structure needed for JoomGallery.
   * (Optional in migration scripts, but highly recommended.)
   *
   * @param   string  $type   Name of the content type
   * @param   array   $data   Source data received from getData()
   * 
   * @return  array   Converted data to save into JoomGallery
   * 
   * @since   4.0.0
   */
  public function convertData(string $type, array $data): array;

  /**
   * Perform pre migration checks.
   * (Optional in migration scripts, can be overwritten if required.)
   *
   * @return  array|boolean  An array containing the precheck results on success.
   * 
   * @since   4.0.0
   */
  public function precheck(): array;

  /**
   * Perform post migration checks.
   * (Optional in migration scripts, can be overwritten if required.)
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  public function postcheck();

  /**
   * Get a database object
   * (Optional in migration scripts, can be overwritten if required.)
   * 
   * @param   string   $target   The target (source or destination)
   *
   * @return  array    list($db, $dbPrefix)
   *
   * @since   4.0.0
   * @throws  \Exception
  */
  public function getDB(string $target): array;

  /**
   * Returns a list of content types which can be migrated.
   * (Optional in migration scripts, can be overwritten if required.)
   *
   * @return  array  List of content types
   * 
   * @since   4.0.0
   */
  public function getMigrateables(): array;

  /**
   * Returns tablename and primarykey name of the source table
   * (Optional in migration scripts, can be overwritten if required.)
   *
   * @param   string   $type    The content type name
   * 
   * @return  array   The corresponding source table info
   *                  list(tablename, primarykey)
   * 
   * @since   4.0.0
   */
  public function getSourceTableInfo(string $type): array;
  
  /**
   * Returns a list of involved source tables.
   * (Optional in migration scripts, can be overwritten if required.)
   *
   * @return  array    List of table names (Joomla style, e.g #__joomgallery)
   *                   array('image' => '#__joomgallery', ...)
   * 
   * @since   4.0.0
   */
  public function getSourceTables(): array;

  /**
   * Returns a list of involved content types.
   * (Optional in migration scripts, can be overwritten if required.)
   *
   * @return  array    List of type names
   *                   array('image', 'category', ...)
   * 
   * @since   4.0.0
   */
  public function getTypes(): array;

  /**
   * True if the given record has to be migrated
   * False to skip the migration for this record
   * (Optional in migration scripts, can be overwritten if required.)
   *
   * @param   string   $type   Name of the content type
   * @param   int      $pk     The primary key of the content type
   * 
   * @return  bool     True to continue migration, false to skip it
   * 
   * @since   4.0.0
   */
  public function needsMigration(string $type, int $pk): bool;

  /**
   * Performs the neccessary steps to migrate an image in the filesystem
   * (Optional in migration scripts, can be overwritten if required.)
   *
   * @param   ImageTable   $img    ImageTable object, already stored
   * @param   array        $data   Source data received from getData()
   * 
   * @return  bool         True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function migrateFiles(ImageTable $img, array $data): bool;

  /**
   * Performs the neccessary steps to migrate a category in the filesystem
   * (Optional in migration scripts, can be overwritten if required.)
   *
   * @param   CategoryTable   $cat    CategoryTable object, already stored
   * @param   array           $data   Source data received from getData()
   * 
   * @return  bool            True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function migrateFolder(CategoryTable $cat, array $data): bool;

  /**
   * Perform script specific checks at the end of pre and postcheck.
   * (Optional in migration scripts, can be overwritten if required.)
   * 
   * @param  string   $type       Type of checks (pre or post)
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function scriptSpecificChecks(string $type, Checks &$checks, string $category);

  /**
   * Delete migration source data.
   * It's recommended to use delete source data by uninstalling source extension if possible.
   * (Optional in migration scripts, can be overwritten if required.)
   *
   * @return  boolean  True if successful, false if an error occurs.
   * 
   * @since   4.0.0
   */
  public function deleteSource();
}
