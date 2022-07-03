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
* Interface for the image manager classes
*
* Image manager classes provides methods to handle image files and folders
* based on the current available image types (#_joomgallery_img_types)
*
* @since  4.0.0
*/
interface ImageMgrInterface
{
  /**
   * Creation of image types
   *
   * @param   string               $source     Source file for which the image types shall be created
   * @param   string               $filename   Name for the files to be created
   * @param   object|int|string    $cat        Object, ID or alias of the corresponding category (default: 2)
   * 
   * @return  bool                 True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function createImages($source, $filename, $cat=2): bool;

  /**
   * Deletion of image types
   *
   * @param   object|int|string    $img    Image object, image ID or image alias
   * 
   * @return  bool                 True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function deleteImages($img): bool;

  /**
   * Checks image types for existence, validity and size
   *
   * @param   object|int|string    $img    Image object, image ID or image alias
   * 
   * @return  mixed                List of filetype info on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function checkImages($img);

  // /**
  //  * Move image files from one category to another
  //  *
  //  * @param   string  $filename     The file name of the files to be deleted
  //  * @param   string  $src_catid    Id of the source category
  //  * @param   string  $dest_catid   Id of the destination category
  //  * @param   bool    $copy         True, if you want to copy the images (default: false)
  //  *
  //  * @return  bool    true on success, false otherwise
  //  *
  //  * @since   4.0.0
  //  */
  // public function moveImages($filename, $src_catid, $dest_catid, $copy): mixed;

  /**
   * Creation of a category
   *
   * @param   string              $foldername   Name of the folder to be created
   * @param   object|int|string   $parent       Object, ID or alias of the parent category (default: 1)
   * 
   * @return  bool                True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function createCategory($foldername, $parent=1): bool;

  /**
   * Deletion of a category
   *
   * @param   object|int|string   $cat          Object, ID or alias of the category to be deleted
   * @param   bool                $del_images   True, if you want to delete even if there are still images in it (default: false)
   * 
   * @return  bool                True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function deleteCategory($cat, $del_images=false): bool;

  /**
   * Checks a category for existence, correct images and file path
   *
   * @param   object|int|string   $cat    Object, ID or alias of the category to be checked
   * 
   * @return  mixed               List of folder info on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function checkCategory($cat);

  // /**
  //  * Move category with all images from one parent category to another
  //  *
  //  * @param   string  $catid        Id of the category to be moved
  //  * @param   string  $src_catid    Id of the source parent category
  //  * @param   string  $dest_catid   Id of the destination parent category
  //  * @param   bool    $copy         True, if you want to copy the category (default: false)
  //  *
  //  * @return  bool    true on success, false otherwise
  //  *
  //  * @since   4.0.0
  //  */
  // public function moveCategory($catid, $src_catid, $dest_catid, $copy): mixed;

  /**
   * Returns the path to an image
   *
   * @param   string                    $type      Imagetype
   * @param   object|int|string         $img       Image object, image ID or image alias (new images: ID=0)
   * @param   object|int|string|bool    $catid     Category object, category ID, category alias or category path (default: false)
   * @param   string|bool               $filename  The filename (default: false)
   * @param   integer                   $root      The root to use / 0:no root, 1:local root, 2:storage root (default: 0)
   * 
   * @return  mixed   Path to the image on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function getImgPath($type, $img, $catid=false, $filename=false, $root=0);

  /**
   * Returns the path to a category without root path.
   *
   * @param   object|int|string        $cat       Category object, category ID or category alias (new categories: ID=0)
   * @param   string|bool              $type      Imagetype if needed in the path
   * @param   object|int|string|bool   $parent    Parent category object, parent category ID, parent category alias or parent category path (default: false)
   * @param   string|bool              $alias     The category alias (default: false)
   * @param   int                      $root      The root to use / 0:no root, 1:local root, 2:storage root (default: 0)
   * 
   * 
   * @return  mixed   Path to the category on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function getCatPath($cat, $type=false, $parent=false, $alias=false, $root=0);
}
