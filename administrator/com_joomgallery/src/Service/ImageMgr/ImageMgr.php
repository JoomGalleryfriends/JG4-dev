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
use \Joomla\CMS\Filesystem\File as JFile;
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

    // instantiate config service
    $this->jg->createConfig();

    // get imagetypes
    $this->getImagetypes();
  }

  /**
   * Creation of image types
   *
   * @param   string    $source     The source file for which the image types shall be created
   * @param   string    $filename   The name for the files to be created
   * @param   string    $catid      The id of the corresponding category (default: 2)
   * 
   * @return  bool      True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function createImages($source, $filename, $catid=2): bool
  {
    // Create filesystem service
    $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

    // Fix filename
    $filename = $this->jg->getFilesystem()->cleanFilename($filename, 1, JFile::getExt($source));

    if(!$filename)
    {
      // Debug info
      $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_CLEAN_FILENAME', \basename($source)));

      return false;
    }

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
      $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_PROCESSING_IMAGETYPE', $imagetype->typename), true, true);

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

      // Debug info
      $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_SUCCESS_CREATE_IMAGETYPE', $filename, $imagetype->typename));
    }    

    return true;
  }

  /**
   * Deletion of image types
   *
   * @param   integer   $id    The id of the image to be deleted
   * 
   * @return  bool      True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function deleteImages($id): bool
  {
    // Create filesystem service
    $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get image file name
      $file = $this->getImgPath($imagetype->typename, $id);      

      // Delete imagetype
      if(!$this->jg->getFilesystem()->deleteFile($file))
      {
        // Deletion failed
        $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_DELETE_IMAGETYPE', \basename($file), $imagetype->typename));

        return false;
      }
    }

    // Deletion successful
    $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_DELETE_IMAGETYPE', \basename($file), $imagetype->typename));

    return true;
  }

  /**
   * Checks image types for existence, validity and size
   *
   * @param   integer   $id    Id of the image to be checked
   * 
   * @return  mixed     list of filetype info on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function checkImages($id)
  {
    $images = array();

    // Create filesystem service
    $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get image file name
      $file = $this->getImgPath($imagetype->typename, $id);

      // Get file info
      $images[$imagetype->typename] = $this->jg->getFilesystem()->checkFile($file);
    }

    return $images;
  }

  /**
   * Creation of a category
   *
   * @param   string    $foldername  The name of the folder to be created
   * @param   integer   $parent_id   Id of the parent category (default: 1)
   * 
   * @return  bool      True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function createCategory($foldername, $parent_id=1): bool
  {
    // Create filesystem service
    $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Category path
      $path = $this->getCatPath(0, $imagetype->typename, 0, $parent_id, $foldername);

      // Create folder if not existent
      if(!$this->jg->getFilesystem()->createFolder($path))
      {
        // Debug info
        $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_CREATE_CATEGORY', $foldername));

        return false;
      }
    }

    return true;
  }

  /**
   * Deletion of a category
   *
   * @param   integer   $catid        Id of the category to be deleted
   * @param   bool      $del_images   True, if you want to delete even if there are still images in it (default: false)
   * 
   * @return  bool      True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function deleteCategory($catid, $del_images=false): bool
  {
    // Create filesystem service
    $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

    // Check if we are allowed to delete the category
    if(!$del_images)
    {
      // Loop through all imagetypes
      foreach($this->imagetypes as $key => $imagetype)
      {
        // Category path
        $path  = $this->getCatPath($catid, $imagetype->typename);

        // Available files and subfolders
        $files = $this->jg->getFilesystem()->checkFolder($path, true, true, 1);

        if(!empty($files['folders']) || !empty($files['files']))
        {
          // There are still images and subcategories available
          // Deletion not allowed
          $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_NOTEMPTY', \basename($path)));

          return false;
        }
      }
    }

    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Category path
      $path  = $this->getCatPath($catid, $imagetype->typename);

      // Delete folder if existent
      if($this->jg->getFilesystem()->checkFolder($path))
      {
        if(!$this->jg->getFilesystem()->deleteFolder($path))
        {
          // Debug info
          $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY', \basename($path)));

          return false;
        }
      }
    } 

    return true;
  }

  /**
   * Checks a category for existence, correct images and file path
   *
   * @param   integer   $catid     Id of the category to be checked
   * 
   * @return  mixed     list of folder info on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function checkCategory($catid)
  {
    $folders = array();

    // Create filesystem service
    $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get category path
      $path = $this->getCatPath($catid, $imagetype->typename);

      // Get folder info
      $folders[$imagetype->typename] = $this->jg->getFilesystem()->checkFolder($path, true, true, 100);
    }

    return $folders;
  }

  /**
   * Returns the path to an image
   *
   * @param   string        $type        The imagetype
   * @param   integer       $id          The id of the image (new image=0)
   * @param   integer       $root        The root to use (0:no root, 1:local root, 2:storage root) (default: 0)
   * @param   integer|bool  $catid       The id of the corresponding category (default: false)
   * @param   string|bool   $filename    The filename (default: false)
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
   * @param   string|bool   $type        The imagetype (default: false)
   * @param   integer       $root        The root to use (0:no root, 1:local root, 2:storage root) (default: 0)
   * @param   integer|bool  $parent_id   The id of the parent category (default: false)
   * @param   string|bool   $catname     The category alias (default: false)
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
