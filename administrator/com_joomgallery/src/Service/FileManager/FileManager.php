<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\FileManager;

\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Filesystem\Path as JPath;
use \Joomla\CMS\Filesystem\File as JFile;
use Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;
use \Joomgallery\Component\Joomgallery\Administrator\Service\FileManager\FileManagerInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
* File manager Class
*
* Provides methods to handle image files and folders based ...
* - ... on the current available image types (#_joomgallery_img_types)
* - ... on the parameters from the configuration set of the current user (Config-Service)
* - ... on the chosen filesystem (Filesystem-Service)
* - ... on the chosen image processor (IMGtools-Service)
*
* @since  4.0.0
*/
class FileManager implements FileManagerInterface
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
   * @param   string               $source     Source file for which the image types shall be created
   * @param   string               $filename   Name for the files to be created
   * @param   object|int|string    $cat        Object, ID or alias of the corresponding category (default: 2)
   * 
   * @return  bool                 True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function createImages($source, $filename, $cat=2): bool
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
      $file = $this->getImgPath($imagetype->typename, 0, $cat, $filename, 0);

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
   * @param   object|int|string    $img    Image object, image ID or image alias
   * 
   * @return  bool                 True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function deleteImages($img): bool
  {
    // Create filesystem service
    $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get image file name
      $file = $this->getImgPath($imagetype->typename, $img);

      // Delete imagetype
      if(!$this->jg->getFilesystem()->deleteFile($file))
      {
        // Deletion failed
        $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_DELETE_IMAGETYPE', \basename($file), $imagetype->typename));

        return false;
      }

      // Deletion successful
      $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_SUCCESS_DELETE_IMAGETYPE', \basename($file), $imagetype->typename));
    }

    return true;
  }

  /**
   * Checks image types for existence, validity and size
   *
   * @param   object|int|string    $img    Image object, image ID or image alias
   * 
   * @return  mixed                List of filetype info on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function checkImages($img)
  {
    $images = array();

    // Create filesystem service
    $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get image file name
      $file = $this->getImgPath($imagetype->typename, $img);

      // Get file info
      $images[$imagetype->typename] = $this->jg->getFilesystem()->checkFile($file);
    }

    return $images;
  }

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
  public function createCategory($foldername, $parent=1): bool
  {
    // Create filesystem service
    $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Category path
      $path = $this->getCatPath(0, $imagetype->typename, $parent, $foldername, 0);

      // Create folder if not existent
      if(!$this->jg->getFilesystem()->createFolder($path))
      {
        // Debug info
        $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_CREATE_CATEGORY', $foldername));

        return false;
      }
    }

    // Debug info
    $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_SUCCESS_CREATE_CATEGORY', $foldername));

    return true;
  }

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
  public function deleteCategory($cat, $del_images=false): bool
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
        $path  = $this->getCatPath($cat, $imagetype->typename);

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
      $path  = $this->getCatPath($cat, $imagetype->typename);

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

    // Debug info
    $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_SUCCESS_DELETE_CATEGORY', \basename($path)));

    return true;
  }

  /**
   * Checks a category for existence, correct images and file path
   *
   * @param   object|int|string   $cat    Object, ID or alias of the category to be checked
   * 
   * @return  mixed               List of folder info on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function checkCategory($cat)
  {
    $folders = array();

    // Create filesystem service
    $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get category path
      $path = $this->getCatPath($cat, $imagetype->typename);

      // Get folder info
      $folders[$imagetype->typename] = $this->jg->getFilesystem()->checkFolder($path, true, true, 100);
    }

    return $folders;
  }

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
  public function getImgPath($type, $img, $catid=false, $filename=false, $root=0)
  {
    if($catid === false || $filename === false)
    {
      // We got a valid image object
      if(\is_object($img) && $img instanceof \Joomla\CMS\Object\CMSObject && isset($img->filename))
      {
        $catid    = ($catid === false) ? $img->catid : $catid;
        $filename = ($filename === false) ? $img->filename : $filename;
      }
      // We got a image ID or an alias
      elseif((\is_numeric($img) && $img > 0) || (\is_string($img) && !$this->is_path($img)))
      {
        // Get image object
        $img = JoomHelper::getRecord('image', $img);

        if($img === false)
        {
          Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_GETIMGPATH', $img), 'error');

          return false;
        }

        $catid    = ($catid === false) ? $img->catid : $catid;
        $filename = ($filename === false) ? $img->filename : $filename;
      }
      // We got nothing to work with
      else
      {
        Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_GETPATH_NOQUERY', 'Image'), 'error');

        return false;
      }
    }

    // Get corresponding category path
    $catpath = $this->getCatPath($catid);
    if($catpath === false)
    {
      return false;
    }

    // Create the path of the image
    $path = $this->imagetypes[$this->imagetypes_dict[$type]]->path.\DIRECTORY_SEPARATOR.$catpath.\DIRECTORY_SEPARATOR.$filename;

    // add root to path if needed
    if($root > 0)
    {
      $path = $this->addRoot($root).\DIRECTORY_SEPARATOR.$path;
    }

    return JPath::clean($path);
  } 

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
  public function getCatPath($cat, $type=false, $parent=false, $alias=false, $root=0)
  { 
    // We got a valid category object
    if(\is_object($cat) && $cat instanceof \Joomla\CMS\Object\CMSObject && isset($cat->path))
    {      
      $path = $cat->path;
    }
    // We got a category path
    elseif(\is_string($cat) && $this->is_path($cat))
    {
      $path = $cat;
    }
    // We got a category ID or an alias
    elseif((\is_numeric($cat) && $cat > 0) || \is_string($cat))
    {      
      if(\is_numeric($cat))
      {
        $cat = \intval($cat);
      }

      // Get the category object
      $cat = JoomHelper::getRecord('category', $cat);

      if($cat === false)
      {
        Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_GETCATPATH', $cat), 'error');

        return false;
      }

      $path = $cat->path;
    }
    // We got a parent category plus alias
    elseif($parent && $alias)
    {
      // We got a valid parent category object
      if(\is_object($parent) && $parent instanceof \Joomla\CMS\Object\CMSObject && isset($parent->path))
      {
        $path = $parent->path.\DIRECTORY_SEPARATOR.$alias;
      }
      // We got a parent category path
      elseif(\is_string($parent) && $this->is_path($parent))
      {
        $path = $parent.\DIRECTORY_SEPARATOR.$alias;
      }
      // We got a parent category ID or an alias
      elseif(\is_numeric($parent) || \is_string($parent))
      {
        if(\is_numeric($parent))
        {
          $parent = \intval($parent);
        }
        
        // Get the parent category object
        $parent = JoomHelper::getRecord('category', $parent);

        if($parent === false)
        {
          Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_GETCATPATH', $parent), 'error');

          return false;
        }

        $path = $parent->path.\DIRECTORY_SEPARATOR.$alias;
      }
    }
    // We got nothing to work with
    else
    {
      Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_GETPATH_NOQUERY', 'Category'), 'error');

      return false;
    }

    // add imagetype to path if needed
    if($type && \key_exists($type, $this->imagetypes_dict))
    {
      $path = $this->imagetypes[$this->imagetypes_dict[$type]]->path.\DIRECTORY_SEPARATOR.$path;
    }
    
    // add root to path if needed
    if($root > 0)
    {
      $path = $this->addRoot($root).\DIRECTORY_SEPARATOR.$path;
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

  /**
   * Check if given string could be a path
   * 
   * @param   string    $string    String to check
   * 
   * @return  bool
   * 
   * @since   4.0.0
   */
  private function is_path($string)
  {
    $string = \strval($string);

    // A valid path needs at least 5 chars (_ _ / _ _)
    if(\strlen($string) < 5)
    {
      return false;
    }

    // A valid path needs at least one directory separator
    if(strpos($string, '/') === false && strpos($string, \DIRECTORY_SEPARATOR) === false)
    {
      return false;
    }

    return true;
  }

  /**
   * Create root path based on $whichRoot
   * 
   * @param   integer   $whichRoot    0:no root, 1:local root, 2:storage root
   * 
   * @return  string    Root path
   * 
   * @since   4.0.0
   */
  private function addRoot($whichRoot)
  {
    // Create filesystem service
    $this->jg->createFilesystem($this->jg->getConfig()->get('jg_filesystem','localhost'));

    // Create root path
    switch($whichRoot)
    {
      case 1:
        return $this->jg->getFilesystem()->get('local_root');
        break;

      case 2:
        return $this->jg->getFilesystem()->get('root');
      
      default:
        return '';
    }
  }
}
