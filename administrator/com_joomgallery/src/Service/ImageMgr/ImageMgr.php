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
* - ... on the parameters from the configuration set of the current user (Config-Service)
* - ... on the chosen filesystem (Filesystem-Service)
* - ... on the chosen image processor (IMGtools-Service)
*
* @since  4.0.0
*/
class ImageMgr implements ImageMgrInterface
{
  /**
   * Imagetypes from #__joomgallery_img_types
   *
   * @var array
   */
  protected $imagetypes = array();

  /**
   * Imagetypes dictionary
   *
   * @var array
   */
  protected $imagetypes_dict = array();

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   1.0.0
   */
  public function __construct()
  {
    // get component object
    $this->jg = JoomHelper::getComponent();

    // get imagetypes
    $this->getImagetypes();
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
    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
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

        // Debug info
        $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_CREATE_IMAGETYPE', $filename, $imagetype->typename));

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

          // Debug info
          $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_CREATE_IMAGETYPE', $filename, $imagetype->typename));
  
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

          // Debug info
          $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_CREATE_IMAGETYPE', $filename, $imagetype->typename));

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

          // Debug info
          $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_CREATE_IMAGETYPE', $filename, $imagetype->typename));

          continue;
        }
      }

      // Path to save image
      $file = $this->getImgPath($imagetype->typename, 0, 0, $catid, $filename);

      // Create filesystem service
      $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

      // Create folders if not existent
      if(!$this->jg->getFilesystem()->createFolder(\dirname($file)))
      {
        // Destroy the IMGtools service
        $this->jg->delIMGtools();

        // Debug info
        $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_CREATE_CATEGORY', \basename(\dirname($file))));

        continue;
      }

      // Write image to file
      if(!$this->jg->getIMGtools()->write($file, $imagetype->params->jg_imgtypequality))
      {
        // Destroy the IMGtools service
        $this->jg->delIMGtools();

        // Debug info
        $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_CREATE_IMAGETYPE', $filename, $imagetype->typename));

        continue;
      }

      // Upload image file to storage
      $this->jg->getFilesystem()->uploadFile($file);

      // Destroy the IMGtools service
      $this->jg->delIMGtools();
    }

    // Debug info
    $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_SUCCESS_CREATE_IMAGETYPE', $filename, $imagetype->typename));

    return true;
  }

  // /**
  //  * Deletion of image types
  //  *
  //  * @param   string    $filename   The file name of the files to be deleted
  //  * @param   integer   $catid      The id of the corresponding category
  //  * 
  //  * @return  bool      True on success, false otherwise
  //  * 
  //  * @since   4.0.0
  //  */
  // public function deleteImages($filename, $catid): bool
  // {
  //   // Loop through all imagetypes
  //   foreach($this->imagetypes as $key => $config)
  //   {
  //     // Get image file name
  //     $file = $this->getImgPath($config->typename, $catid, $filename);

  //     // Create filesystem service
  //     $this->jg->createFilesystem('localhost');

  //     // Delete imagetype
  //     if(!$this->jg->getFilesystem()->deleteFile($this->jg->getFilesystem()->get('local_root') . $file))
  //     {
  //       // Deletion failed
  //       $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_DELETE_IMAGETYPE', $filename, $config->typename));

  //       return false;
  //     }
  //   }

  //   // Deletion successful
  //   $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_DELETE_IMAGETYPE', $filename, $config->typename));

  //   return true;
  // }

  /**
   * Creation of a category
   *
   * @param   string    $catname     The name of the folder to be created
   * @param   integer   $parent_id   Id of the parent category
   * 
   * @return  bool      True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function createCategory($catname, $parent_id): bool
  {
    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Category path
      $path = $this->getCatPath(0, $imagetype->typename, 0, $parent_id, $catname);

      // Create filesystem service
      $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

      // Create folder if not existent
      if(!$this->jg->getFilesystem()->createFolder($path))
      {
        // Debug info
        $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_CREATE_CATEGORY', $catname));

        return false;
      }
    }

    return true;
  }

  /**
   * Returns the path to an image
   *
   * @param   string        $type        The imagetype
   * @param   integer       $id          The id of the image (new image=0)
   * @param   integer       $root        The root to use (0:no root, 1:local root, 2:storage root)
   * @param   integer|bool  $catid       The id of the corresponding category
   * @param   string|bool   $filename    The filename
   * 
   * @return  mixed   Path to the image on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function getImgPath($type, $id, $root=0, $catid=false, $filename=false)
  {
    if($catid == false || $filename == false && $id > 0)
    {
      // get image object
      $img = JoomHelper::getRecord('image', $id);

      if($img === false)
      {
        Factory::getApplication()->enqueueMessage(Text::_('Image not found. You have to create the image first before accessing it.'), 'error');

        return false;
      }

      $catid    = $img->catid;
      $filename = $img->filename;
    }

    // get corresponding category
    $cat = JoomHelper::getRecord('category', $catid);

    if($cat === false)
    {
      Factory::getApplication()->enqueueMessage(Text::_('Category not found. Please create the category before uploading into this category.'), 'error');

      return false;
    }

    // create the path to image
    $path = $this->imagetypes[$this->imagetypes_dict[$type]]->path.\DIRECTORY_SEPARATOR.$cat->path.\DIRECTORY_SEPARATOR.$filename;

    // add root to path if needed
    if($root > 0)
    {
      // create filesystem service
      $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

      switch($root)
      {
        case 1:
          $path = $this->jg->getFilesystem()->get('local_root').\DIRECTORY_SEPARATOR.$path;
          break;

        case 2:
          $path = $this->jg->getFilesystem()->get('root').\DIRECTORY_SEPARATOR.$path;
          break;
        
        default:
          break;
      }
    }

    return JPath::clean($path);
  }

  /**
   * Returns the path to a category without root path.
   *
   * @param   string        $catid       The id of the category (new category=0)
   * @param   string|bool   $type        The imagetype
   * @param   integer       $root        The root to use (0:no root, 1:local root, 2:storage root)
   * @param   integer|bool  $parent_id   The id of the parent category
   * @param   string|bool   $catname     The category alias
   * 
   * @return  mixed   Path to the category on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function getCatPath($catid, $type=false, $root=0, $parent_id=false, $catname=false)
  {
    if($catid > 0)
    {
      // get category object
      $cat = JoomHelper::getRecord('category', $catid);

      if($cat === false)
      {
        Factory::getApplication()->enqueueMessage(Text::_('Category not found. Please create the category before uploading into this category.'), 'error');

        return false;
      }

      $path = $cat->path;
    }
    else
    {
      // get parent category object
      $parent_cat = JoomHelper::getRecord('category', $parent_id);

      if($parent_cat === false)
      {
        Factory::getApplication()->enqueueMessage(Text::_('Category not found. Please create the category before uploading into this category.'), 'error');

        return false;
      }

      $path = $parent_cat->path.\DIRECTORY_SEPARATOR.$catname;
    }

    // add imagetype to path if needed
    if($type && \key_exists($type, $this->imagetypes_dict))
    {
      $path = $this->imagetypes[$this->imagetypes_dict[$type]]->path.\DIRECTORY_SEPARATOR.$path;
    }
    
    // add root to path if needed
    if($root > 0)
    {
      // create filesystem service
      $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

      switch($root)
      {
        case 1:
          $path = $this->jg->getFilesystem()->get('local_root').\DIRECTORY_SEPARATOR.$path;
          break;

        case 2:
          $path = $this->jg->getFilesystem()->get('root').\DIRECTORY_SEPARATOR.$path;
          break;
        
        default:
          break;
      }
    }

    return JPath::clean($path);
  }

  /**
   * Get all imagetypes and stores it to the class
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  private function getImagetypes()
  {
    // get all imagetypes
    $this->imagetypes = JoomHelper::getRecords('imagetypes', $this->jg);

    // sort imagetypes by id descending
    $this->imagetypes = \array_reverse($this->imagetypes);

    // create dictionary for imagetypes array
    foreach ($this->imagetypes as $key => $imagetype)
    {
      $this->imagetypes_dict[$imagetype->typename] = $key;
    }
  }
}
