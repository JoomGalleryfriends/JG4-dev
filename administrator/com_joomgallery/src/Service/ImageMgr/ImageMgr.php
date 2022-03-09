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
* Base class for the Image manager helper classes
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
  public function createImages($source, $catid, $filename): bool
  {
    // Get all imagetypes
    $imagetypes = JoomHelper::getRecords('imagetypes', $this->jg);

    // Sort imagetypes by id descending ()
    $imagetypes = \array_reverse($imagetypes);

    // Loop through all imagetypes
    foreach($imagetypes as $key => $config)
    {
      // Create the IMGtools service
      $this->jg->createIMGtools($this->jg->getConfig()->get('jg_imgprocessor'));

      // Only proceed if imagetype is active
      if($config->params->jg_imgtype != 1)
      {
        continue;
      }

      // Debug info
      $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_PROCESSING_IMAGETYPE', $config->typename));

      // Read source image
      if(!$this->jg->getIMGtools()->read($source))
      {
        // Destroy the IMGtools service
        $this->jg->delIMGtools();

        return false;
      }

      // Keep metadata only for original images
      if($config->typename == 'original')
      {
        $this->jg->getIMGtools()->keep_metadata = true;
      }
      else
      {
        $this->jg->getIMGtools()->keep_metadata = false;
      }

      // Do we need to keep animation?
      if($config->params->jg_imgtypeanim == 1)
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
      if($config->params->jg_imgtypeorinet == 1)
      {
        // Yes
        if(!$this->jg->getIMGtools()->orient())
        {  
          // Destroy the IMGtools service
          $this->jg->delIMGtools();
  
          return false;
        }
      }

      // Need for resize?
      if($config->params->jg_imgtyperesize > 0)
      {
        // Yes
        if(!$this->jg->getIMGtools()->resize($config->params->jg_imgtyperesize,
                                             $config->params->jg_imgtypewidth,
                                             $config->params->jg_imgtypeheight,
                                             $config->params->jg_cropposition,
                                             $config->params->jg_imgtypesharpen)
          )
        {
          // Destroy the IMGtools service
          $this->jg->delIMGtools();

          return false;
        }
      }

      // Need for watermarking?
      if($config->params->jg_imgtypewatermark == 1 && property_exists($config->params->jg_imgtypewtmsettings, 'jg_imgtypewtmsettings0'))
      {
        // Yes
        $config->params->jg_imgtypewtmsettings = $config->params->jg_imgtypewtmsettings->jg_imgtypewtmsettings0;
        
        if(!$this->jg->getIMGtools()->watermark(JPATH_ROOT.\DIRECTORY_SEPARATOR.$this->jg->getConfig()->get('jg_wmfile'),
                                                $config->params->jg_imgtypewtmsettings->jg_watermarkpos,
                                                $config->params->jg_imgtypewtmsettings->jg_watermarkzoom,
                                                $config->params->jg_imgtypewtmsettings->jg_watermarksize,
                                                $config->params->jg_imgtypewtmsettings->jg_watermarkopacity)
          )
        {
          // Destroy the IMGtools service
          $this->jg->delIMGtools();

          return false;
        }
      }

      // Write image to file
      $file = $this->getImgPath($config->typename, $catid, $filename);

      if(!$this->jg->getIMGtools()->write($file, $config->params->jg_imgtypequality))
      {
        // Destroy the IMGtools service
        $this->jg->delIMGtools();

        return false;
      }

      // Destroy the IMGtools service
      $this->jg->delIMGtools();
    }

    return true;
  }

  /**
   * Deletes image types
   *
   * @param   string    $filename   The file name for the created files
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
  public function getImgPath($type, $catid, $filename): mixed
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
}
