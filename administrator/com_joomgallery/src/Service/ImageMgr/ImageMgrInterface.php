<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\ImageMgr;

\defined('JPATH_PLATFORM') or die;

/**
* Image manager Interface for the helper classes
*
* @since  4.0.0
*/
interface ImageMgrInterface
{
  /**
   * Creates image types
   *
   * @param   string    $source     The source file for which the thumbnail and the detail image shall be created
   * @param   string    $catid      The id of the corresponding category
   * @param   string    $filename   The file name for the created files
   * 
   * @return  bool      True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function createImages($source, $catid, $filename): bool;

  /**
   * Creates image types
   *
   * @param   string    $filename   The file name for the created files
   * @param   integer   $catid      The id of the corresponding category
   * 
   * @return  bool      True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function deleteImages($filename, $catid): bool;

  /**
   * Returns the path to an image without root path.
   *
   * @param   string  $type        The imagetype
   * @param   string  $catid       The id of the corresponding category
   * @param   string  $filename    The filename
   * 
   * @return  mixed   Path to the image on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function getImgPath($type, $catid, $filename): mixed;
}
