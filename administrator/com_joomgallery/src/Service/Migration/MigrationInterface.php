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

/**
 * Interface for the migration service class
 *
 * @package JoomGallery
 * @since   4.0.0
 */
interface MigrationInterface
{
  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct();

  /**
   * Step 2
   * Perform pre migration checks.
   *
   * @return  array|boolean  An array containing the precheck results on success.
   * 
   * @since   4.0.0
   */
  public function precheck(): array;

  /**
   * Step 4
   * Perform post migration checks.
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  public function postcheck();

  /**
   * Step 3
   * Perform one specific miration step and mark it as done at the end.
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  public function migrate($type, $source, $dest);

  /**
   * Returns a list of content types which can be migrated.
   *
   * @return  array  List of content types
   * 
   * @since   4.0.0
   */
  public function getMigrateables(): array;

  /**
   * Returns an object with compatibility info for this migration script.
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
   *
   * @return  \SimpleXMLElement   Extension XML object
   * 
   * @since   4.0.0
   */
  public function getSourceXML(): \SimpleXMLElement;

  /**
   * Returns a list of involved source directories.
   *
   * @return  array    List of paths
   * 
   * @since   4.0.0
   */
  public function getSourceDirs(): array;

  /**
   * Returns the Joomla root path of the source.
   *
   * @return  string    Source Joomla root path
   * 
   * @since   4.0.0
   */
  public function getSourceRootPath(): string;

  /**
   * Returns the most important info of the corresponding source table
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
   *
   * @return  array    List of table names (Joomla style, e.g #__joomgallery)
   * 
   * @since   4.0.0
   */
  public function getSourceTables(): array;

  /**
   * Returns an associative array containing the record data from source.
   *
   * @param   string   $type   Name of the content type
   * @param   int      $pk     The primary key of the content type
   * 
   * @return  array  Record data
   * 
   * @since   4.0.0
   */
  public function getData(string $type, int $pk): array;

  /**
   * Converts data from source into the structure needed for JoomGallery.
   *
   * @param   array   $data   Data received from getData() method.
   * 
   * @return  array   Converted data to save into JoomGallery
   * 
   * @since   4.0.0
   */
  public function convertData(array $data): array;

  /**
   * Fetches an array of images from source to be used for creating the imagetypes
   * for the current image.
   *
   * @param   array   $data   Record data received from getData()
   * 
   * @return  array   List of images from sources used to create the new imagetypes
   *                  1. If imagetypes get recreated: array('image/source/path')
   *                  2. If imagetypes get copied:    array('original' => 'image/source/path1', 'detail' => 'image/source/path2', ...)
   * 
   * @since   4.0.0
   */
  public function getImageSource(array $data): array;
}