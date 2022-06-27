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

\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Filesystem\Path as JPath;
use Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;
use \Joomgallery\Component\Joomgallery\Administrator\Service\ImageMgr\ImageMgrInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
* Image manager Class
*
* Provides methods to handle image files and folders based ...
* - ... on the current available image types (#_joomgallery_img_types)
* - ... on the parameters from the configuration set of the current user (#_joomgallery_configs)
*
* @since  4.0.0
*/
class ImageMgr implements ImageMgrInterface
{
  /**
   * Constructor
   *
   * @return  void
   *
   * @since   1.0.0
   */
  public function __construct()
  {
    $this->jg = JoomHelper::getComponent();
  }

  /**
   * Creation of image types
   *
   * @param   string    $source     The source file for which the image types shall be created
   * @param   string    $catid      The id of the corresponding category
   * @param   string    $filename   The file name for the created files
   * 
   * @return  bool      True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function createImages($source, $catid, $filename): bool
  {
    // Get all imagetypes
    $imagetypes = JoomHelper::getRecords('imagetypes', $this->jg);

    // Sort imagetypes by id descending ()
    $imagetypes = \array_reverse($imagetypes);

    // Loop through all imagetypes
    foreach($imagetypes as $key => $imagetype)
    {
      // Create the IMGtools service
      $this->jg->createIMGtools($this->jg->getConfig()->get('jg_imgprocessor'));

      // Only proceed if imagetype is active
      if($imagetype->params->jg_imgtype != 1)
      {
        continue;
      }

      // Debug info
      $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_PROCESSING_IMAGETYPE', $imagetype->typename));

      // Read source image
      if(!$this->jg->getIMGtools()->read($source))
      {
        // Destroy the IMGtools service
        $this->jg->delIMGtools();

        continue;
      }

      // Keep metadata only for original images
      if($imagetype->typename == 'original')
      {
        $this->jg->getIMGtools()->keep_metadata = true;
      }
      else
      {
        $this->jg->getIMGtools()->keep_metadata = false;
      }

      // Do we need to keep animation?
      if($imagetype->params->jg_imgtypeanim == 1)
      {
        // Yes
        $this->jg->getIMGtools()->keep_anim = true;
      }
      else
      {
        // No
        $this->jg->getIMGtools()->keep_anim = false;
      }

      // Do we need to auto orient?
      if($imagetype->params->jg_imgtypeorinet == 1)
      {
        // Yes
        if(!$this->jg->getIMGtools()->orient())
        {  
          // Destroy the IMGtools service
          $this->jg->delIMGtools();
  
          continue;
        }
      }

      // Need for resize?
      if($imagetype->params->jg_imgtyperesize > 0)
      {
        // Yes
        if(!$this->jg->getIMGtools()->resize($imagetype->params->jg_imgtyperesize,
                                             $imagetype->params->jg_imgtypewidth,
                                             $imagetype->params->jg_imgtypeheight,
                                             $imagetype->params->jg_cropposition,
                                             $imagetype->params->jg_imgtypesharpen)
          )
        {
          // Destroy the IMGtools service
          $this->jg->delIMGtools();

          continue;
        }
      }

      // Need for watermarking?
      if($imagetype->params->jg_imgtypewatermark == 1)
      {
        // Yes        
        if(!$this->jg->getIMGtools()->watermark(JPATH_ROOT.\DIRECTORY_SEPARATOR.$this->jg->getConfig()->get('jg_wmfile'),
                                                $imagetype->params->jg_imgtypewtmsettings->jg_watermarkpos,
                                                $imagetype->params->jg_imgtypewtmsettings->jg_watermarkzoom,
                                                $imagetype->params->jg_imgtypewtmsettings->jg_watermarksize,
                                                $imagetype->params->jg_imgtypewtmsettings->jg_watermarkopacity)
          )
        {
          // Destroy the IMGtools service
          $this->jg->delIMGtools();

          continue;
        }
      }

      // Write image to file
      $file = $this->getImgPath($imagetype->typename, $catid, $filename);
      if(!$this->jg->getIMGtools()->write($file, $imagetype->params->jg_imgtypequality))
      {
        // Destroy the IMGtools service
        $this->jg->delIMGtools();

        continue;
      }

      // Destroy the IMGtools service
      $this->jg->delIMGtools();
    }

    return true;
  }

  /**
   * Deletion of image types
   *
   * @param   string    $filename   The file name of the files to be deleted
   * @param   integer   $catid      The id of the corresponding category
   * 
   * @return  bool      True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function deleteImages($filename, $catid): bool
  {
    // Get all imagetypes
    $imagetypes = JoomHelper::getRecords('imagetypes', $this->jg);

    // Loop through all imagetypes
    foreach($imagetypes as $key => $config)
    {
      // Get image file name
      $file = $this->getImgPath($config->typename, $catid, $filename);

      // Create filesystem service
      $this->jg->createFilesystem('localhost');

      // Delete imagetype
      if(!$this->jg->getFilesystem()->deleteFile($this->jg->getFilesystem()->get('local_root') . $file))
      {
        // Deletion failed
        $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_DELETE_IMAGETYPE', $filename, $config->typename));

        return false;
      }
    }

    // Deletion successful
    $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_DELETE_IMAGETYPE', $filename, $config->typename));

    return true;
  }

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
  public function getImgPath($type, $catid, $filename)
  {
    // get imagetype object
    $imagetype = JoomHelper::getRecord('imagetype', array('typename' => $type));

    if($imagetype === false)
    {
      Factory::getApplication()->enqueueMessage('Imagetype not found!', 'error');

      return false;
    }

    // get corresponding category
    $cat = JoomHelper::getRecord('category', $catid);

    if($cat === false)
    {
      Factory::getApplication()->enqueueMessage('Category not found. Please create the category before uploading into this category.', 'error');

      return false;
    }

    // Create the complete path
    $path = $imagetype->path.\DIRECTORY_SEPARATOR.$cat->path.\DIRECTORY_SEPARATOR.$filename;

    return JPath::clean($path);
  }

  /**
   * Returns the path to a category without root path.
   *
   * @param   string  $catid    The id of the category
   * 
   * @return  mixed   Path to the category on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function getCatPath($catid)
  {
    // get category object
    $cat = JoomHelper::getRecord('category', $catid);

    if($cat === false)
    {
      Factory::getApplication()->enqueueMessage('Category not found. Please create the category before uploading into this category.', 'error');

      return false;
    }

    return JPath::clean($cat->path);
  }
}
