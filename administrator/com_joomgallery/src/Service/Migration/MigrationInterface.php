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
   * Returns a associative array containing the record data written from source.
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
   * Translates the record data from source structure to destination structure
   * based on migration parameters
   *
   * @param   array   $data   Record data received from getData()
   * 
   * @return  array  Restructured record data to save into JG4
   * 
   * @since   4.0.0
   */
  public function applyDataMapping(array $data): array;

  /**
   * Fetches an array of source images for the current migrated image
   * based on migration parameters.
   * 
   * There are two possibilities how the new imagetypes are created:
   * 1. Imagetypes get recreated using one source image from the migration source
   * 2. Imagetypes get copied from existing images available from the migration source
   *
   * @param   array   $data   Record data received from getData()
   * 
   * @return  array   List of image sources to be used to create the new imagetypes
   *                  If imagetypes get recreated: array('image/source/path')
   *                  If imagetypes get copied:    array('original' => 'image/source/path1', 'detail' => 'image/source/path2', ...)
   * 
   * @since   4.0.0
   */
  public function getImageSource(array $data): array;
}
