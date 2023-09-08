<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\FileManager;

\defined('_JEXEC') or die;

use \Joomla\CMS\Language\Text;

use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\FileManager\FileManagerInterface;

use \Joomla\Component\Media\Administrator\Exception\FileExistsException;
use \Joomla\Component\Media\Administrator\Exception\FileNotFoundException;
use \Joomla\Component\Media\Administrator\Exception\InvalidPathException;

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
  use ServiceTrait;

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
   * No image path
   *
   * @var string
   */
  protected $no_image = '/images/joomgallery/no-image.png';

  /**
   * Constructor
   * 
   * @param   array|bool     $selection    List of imagetypes to consider or false to consider all (default: False)
   *
   * @return  void
   *
   * @since   1.0.0
   */
  public function __construct($selection=False)
  {
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    // Instantiate config service
    $this->component->createConfig();

    // Instantiate filesystem service
    $this->component->createFilesystem($this->component->getConfig()->get('jg_filesystem','local-images'));

    // Get imagetypes
    $this->getImagetypes();

    // Apply imagetype selection
    if($selection !== False)
    {
      $this->selectImagetypes($selection);
    }
  }

  /**
   * Creation of image types based on source file.
   * Source file has to be given with a full system path.
   * 
   *
   * @param   string               $source        Source file with which the image types shall be created
   * @param   string               $filename      Name for the files to be created
   * @param   object|int|string    $cat           Object, ID or alias of the corresponding category (default: 2)
   * @param   bool                 $processing    True to create imagetypes by processing source (defualt: True)
   * 
   * @return  bool                 True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function createImages($source, $filename, $cat=2, $processing=True): bool
  {
    if(!$filename)
    {
      // Debug info
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CLEAN_FILENAME', \basename($source)));

      return false;
    }

    // Loop through all imagetypes
    $error = false;
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Create the IMGtools service
      $this->component->createIMGtools($this->component->getConfig()->get('jg_imgprocessor'));

      // Only proceed if imagetype is active
      if($imagetype->params->get('jg_imgtype', 1) != 1)
      {
        continue;
      }

      // Debug info
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_PROCESSING_IMAGETYPE', $imagetype->typename), true, true);

      // Process image
      if($processing)
      {
        // Keep metadata only for original images
        if($imagetype->typename == 'original')
        {
          $this->component->getIMGtools()->keep_metadata = true;
        }
        else
        {
          $this->component->getIMGtools()->keep_metadata = false;
        }

        // Do we need to keep animation?
        if($imagetype->params->get('jg_imgtypeanim', 0) == 1)
        {
          // Yes
          $this->component->getIMGtools()->keep_anim = true;
        }
        else
        {
          // No
          $this->component->getIMGtools()->keep_anim = false;
        }

        // Grap resource if needed
        $isStream = false;
        if(\strpos($this->component->getFilesystem()->getFilesystem(), 'local') === false)
        {
          // We are dealing with an external filesystem
          $source   = $this->component->getFilesystem()->getResource($source);
          $isStream = true;
        }
        
        // Read source image
        if(!$this->component->getIMGtools()->read($source, $isStream))
        {
          // Destroy the IMGtools service
          $this->component->delIMGtools();

          // Debug info
          $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_IMAGETYPE', $filename, $imagetype->typename));

          continue;
        }

        // Do we need to auto orient?
        if($imagetype->params->get('jg_imgtypeorinet', 0) == 1)
        {
          // Yes
          if(!$this->component->getIMGtools()->orient())
          {  
            // Destroy the IMGtools service
            $this->component->delIMGtools();

            // Debug info
            $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_IMAGETYPE', $filename, $imagetype->typename));
            $error = true;

            continue;
          }
        }

        // Need for resize?
        if($imagetype->params->get('jg_imgtyperesize', 0) > 0)
        {
          // Yes
          if(!$this->component->getIMGtools()->resize($imagetype->params->get('jg_imgtyperesize', 3),
                                              $imagetype->params->get('jg_imgtypewidth', 5000),
                                              $imagetype->params->get('jg_imgtypeheight', 5000),
                                              $imagetype->params->get('jg_cropposition', 2),
                                              $imagetype->params->get('jg_imgtypesharpen', 0))
            )
          {
            // Destroy the IMGtools service
            $this->component->delIMGtools();

            // Debug info
            $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_IMAGETYPE', $filename, $imagetype->typename));
            $error = true;

            continue;
          }
        }

        // Need for watermarking?
        if($imagetype->params->get('jg_imgtypewatermark', 0) == 1)
        {
          // Yes
          if(!$this->component->getIMGtools()->watermark(JPATH_ROOT.\DIRECTORY_SEPARATOR.$this->component->getConfig()->get('jg_wmfile'),
                                                  $imagetype->params->get('jg_imgtypewtmsettings.jg_watermarkpos', 9),
                                                  $imagetype->params->get('jg_imgtypewtmsettings.jg_watermarkzoom', 0),
                                                  $imagetype->params->get('jg_imgtypewtmsettings.jg_watermarksize', 15),
                                                  $imagetype->params->get('jg_imgtypewtmsettings.jg_watermarkopacity', 80))
            )
          {
            // Destroy the IMGtools service
            $this->component->delIMGtools();

            // Debug info
            $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_IMAGETYPE', $filename, $imagetype->typename));
            $error = true;

            continue;
          }
        }
      }
      else
      {
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_MANIPULATION_NOT_NEEDED'));
      }

      // Path to save image
      $file = $this->getImgPath(0, $imagetype->typename, $cat, $filename, false);

      // Create folders if not existent
      $folder = \dirname($file);
      try
      {
        $res = $this->component->getFilesystem()->createFolder(\basename($folder), \dirname($folder));
      }
      catch(\FileExistsException $e)
      {
        // Do nothing
      }
      catch(\Exception $e)
      {
        // Destroy the IMGtools service
        $this->component->delIMGtools();

        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_CATEGORY', \basename(\dirname($file))));
        $error = true;

        continue;
      }

      if(!$res)
      {
        // Destroy the IMGtools service
        $this->component->delIMGtools();

        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_CATEGORY', \basename(\dirname($file))));
        $error = true;

        continue;
      }

      // Get image stream
      if($processing)
      {
        $image_content = $this->component->getIMGtools()->stream($imagetype->params->get('jg_imgtypequality', 100), false);
      }
      else
      {
        $image_content = \file_get_contents($source);
      }

      if(!$image_content)
      {
        // Destroy the IMGtools service
        $this->component->delIMGtools();

        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_IMAGETYPE', $filename, $imagetype->typename));
        $error = true;

        continue;
      }

      // Create image file
      try
      {
        $this->component->getFilesystem()->createFile(\basename($file), \dirname($file), $image_content);
      }
      catch (FileExistsException $e)
      {
        // File already exists

        // Destroy the IMGtools service
        $this->component->delIMGtools();

        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_FILE_ALREADY_EXISTING', $filename));
        $error = true;

        continue;

      }
      catch (InvalidPathException $e)
      {
        // Not allowed filetype

        // Destroy the IMGtools service
        $this->component->delIMGtools();

        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_ERROR', $e->getMessage()));
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_IMAGETYPE', $filename, $imagetype->typename));
        $error = true;

        continue;
      }
      catch (\Exception $e)
      {
        // Any other error during file creation

        // Destroy the IMGtools service
        $this->component->delIMGtools();

        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_ERROR', $e->getMessage()));
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_IMAGETYPE', $filename, $imagetype->typename));
        $error = true;

        continue;
      }

      // Destroy the IMGtools service
      $this->component->delIMGtools();

      // Debug info
      if(!$error)
      {
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SUCCESS_CREATE_IMAGETYPE', $filename, $imagetype->typename));
      }
    }

    if($error)
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SOME_ERRORS_IMAGEFILE'));

      return false;
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
    // Loop through all imagetypes
    $error = false;
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get image file name
      $file = $this->getImgPath($img, $imagetype->typename);

      // Delete imagetype
      try
      {
        $this->component->getFilesystem()->delete($file);
      }
      catch (FileNotFoundException $e)
      {
        // Do nothing
      }
      catch (\Exception $e)
      {
        // Deletion failed
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_DELETE_IMAGETYPE', \basename($file), $imagetype->typename));
        $error = true;

        continue;
      }

      // Deletion successful
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SUCCESS_DELETE_IMAGETYPE', \basename($file), $imagetype->typename));
    }

    if($error)
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SOME_ERRORS_IMAGEFILE'));

      return false;
    }

    return true;
  }

  /**
   * Checks image types for existence, validity and size
   *
   * @param   object|int|string    $img    Image object, image ID or image alias
   * 
   * @return  array                List of filetype info
   * 
   * @since   4.0.0
   */
  public function checkImages($img): array
  {
    $images = array();

    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get image file name
      $file = $this->getImgPath($img, $imagetype->typename);

      // Get file info
      try
      {
        $images[$imagetype->typename] = $this->component->getFilesystem()->getFile($file);
      }
      catch (FileNotFoundException $e)
      {
        // File not found
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_FILE_NOT_EXISTING').', '.\basename($file).' ('.$imagetype->typename.')');

        return false;
      }
      catch (\Exception $e)
      {
        $this->component->addDebug($e->getMessage());

        return false;
      }
    }

    return $images;
  }

  /**
   * Move image files from one category to another
   *
   * @param   object|int|string    $img        Image object, image ID or image alias
   * @param   object|int|string    $dest       Category object, ID or alias of the destination category
   * @param   string|false         $filename   Filename of the moved image (default: false)
   * @param   bool                 $copy       True, if you want to copy the images (default: false)
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function moveImages($img, $dest, $filename=false, $copy=false): bool
  {
    // Switch method
    $method = 'MOVE';
    if($copy)
    {
      $method = 'COPY';
    }

    // Loop through all imagetypes
    $error = false;
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get image source path
      $img_src = $this->getImgPath($img, $imagetype->typename);

      // Get category destination path
      $cat_dst = $this->getCatPath($dest, $imagetype->typename);

      // Get image filename
      $img_filename = \basename($img_src);
      if($filename)
      {
        $img_filename = $filename;
      }

      // Create image destination path
      $img_dst = $cat_dst . '/' . $img_filename;

      // Create folders if not existent
      $folder_dst = \dirname($img_dst);
      try
      {
        $res = $this->component->getFilesystem()->createFolder(\basename($folder_dst), \dirname($folder_dst));
      }
      catch(\FileExistsException $e)
      {
        // Do nothing
      }
      catch(\Exception $e)
      {
        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_CATEGORY', \ucfirst($folder_dst)));
        $error = true;

        continue;
      }

      if(!$res)
      {
        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_CATEGORY', \basename($folder_dst)));
        $error = true;

        continue;
      }

      // Move/Copy imagetype
      try
      {
        if($copy)
        {
          $this->component->getFilesystem()->copy($img_src, $img_dst);
        }
        else
        {
          $this->component->getFilesystem()->move($img_src, $img_dst);
        }
      }
      catch(\FileNotFoundException $e)
      {
        // File not found
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_FILE_NOT_EXISTING').', '.\basename($img_src).' ('.$imagetype->typename.')');
        $error = true;

        continue;
      }
      catch(\Exception $e)
      {
        // Operation failed
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_'.$method.'_IMAGETYPE', \basename($img_src), $imagetype->typename));
        $error = true;

        continue;
      }

      // Move successful
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SUCCESS_'.$method.'_IMAGETYPE', \basename($img_src), $imagetype->typename));
    }

    if($error)
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SOME_ERRORS_IMAGEFILE'));

      return false;
    }

    return true;
  }

  /**
   * Copy image files from one category to another
   *
   * @param   object|int|string    $img        Image object, image ID or image alias
   * @param   object|int|string    $dest       Category object, ID or alias of the destination category
   * @param   string|false         $filename   Filename of the moved image (default: False)
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function copyImages($img, $dest, $filename=false): bool
  {
    return $this->moveImages($img, $dest, $filename, true);
  }

  /**
   * Rename files of image
   *
   * @param   object|int|string   $img        Image object, image ID or image alias
   * @param   string              $filename   New filename of the image
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function renameImages($img, $filename): bool
  {
    // Loop through all imagetypes
    $error = false;
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get full image filename
      $file_orig = $this->getImgPath($img, $imagetype->typename);

      // Create renamed image filename
      $file_new  = \substr($file_orig, 0, strrpos($file_orig, \basename($file_orig))).$filename;

      // Rename file
      try
      {
        $this->component->getFilesystem()->move($file_orig, $file_new);
      }
      catch(\FileNotFoundException $e)
      {
        // File not found
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_FILE_NOT_EXISTING').', '.\basename($file_orig).' ('.$imagetype->typename.')');
        $error = true;

        continue;
      }
      catch(\Exception $e)
      {
        // Renaming failed
        $error = true;

        continue;
      }
    }
    
    if($error)
    {
      // Renaming failed
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_RENAME_IMAGE', \ucfirst(\basename($file_orig))));

      return false;
    }

    // Renaming successful
    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SUCCESS_RENAME_IMAGE', \ucfirst(\basename($file_orig))));

    return true;
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
    // Loop through all imagetypes
    $error = false;
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Category path
      $path = $this->getCatPath(0, $imagetype->typename, $parent, $foldername, false);

      // Create folders if not existent
      try
      {
        $res = $this->component->getFilesystem()->createFolder(\basename($path), \dirname($path));
      }
      catch(\FileExistsException $e)
      {
        // Do nothing
      }
      catch(\Exception $e)
      {
        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_CATEGORY', \ucfirst($foldername)));
        $error = true;

        continue;
      }

      if(!$res)
      {
        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_CATEGORY', \ucfirst($foldername)));
        $error = true;

        continue;
      }
    }

    if($error)
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SOME_ERRORS_IMAGEFILE'));

      return false;
    }

    // Debug info
    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SUCCESS_CREATE_CATEGORY', \ucfirst($foldername)));

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
    // Check if we are allowed to delete the category
    if(!$del_images)
    {
      // Loop through all imagetypes
      foreach($this->imagetypes as $key => $imagetype)
      {
        // Category path
        $path  = $this->getCatPath($cat, $imagetype->typename);

        // Available files and subfolders
        try
        {
          $files = $this->component->getFilesystem()->getFiles($path);
        }
        catch (FileNotFoundException $e)
        {
          // Folder not found
          $this->component->addDebug(Text::_('COM_JOOMGALLERY_ERROR_FOLDER_NOT_EXISTING').' ('.\ucfirst(\basename($path)).')');

          return false;
        }

        if(\count($files) > 0)
        {
          // There are still images and subcategories available
          // Deletion not allowed
          $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_DELETE_CATEGORY_NOTEMPTY', \ucfirst(\basename($path))));

          return false;
        }
      }
    }

    // Loop through all imagetypes
    $error = false;
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Category path
      $path  = $this->getCatPath($cat, $imagetype->typename);

      // Available files and subfolders
      try
      {
        $files = $this->component->getFilesystem()->getFiles($path);
      }
      catch (FileNotFoundException $e)
      {
        // Folder not found
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_ERROR_FOLDER_NOT_EXISTING').' ('.\ucfirst(\basename($path)).')');

        return false;
      }

      // Delete folder if existent
      if($files)
      {
        try
        {
          $this->component->getFilesystem()->delete($path);
        }
        catch (FileNotFoundException $e)
        {
          // Do nothing
        }
        catch (\Exception $e)
        {
          // Deletion failed
          $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_DELETE_CATEGORY', \ucfirst(\basename($path))));
          $error = true;

          continue;
        }
      }
    }

    if($error)
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SOME_ERRORS_IMAGEFILE'));

      return false;
    }

    // Debug info
    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SUCCESS_DELETE_CATEGORY', \ucfirst(\basename($path))));

    return true;
  }

  /**
   * Checks a category for existence, correct images and file path
   *
   * @param   object|int|string   $cat    Object, ID or alias of the category to be checked
   * 
   * @return  array               List of folder info
   * 
   * @since   4.0.0
   */
  public function checkCategory($cat): array
  {
    $folders = array();

    // Loop through all imagetypes
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get category path
      $path = $this->getCatPath($cat, $imagetype->typename);

      // Get folder info
      try
      {
        $folders[$imagetype->typename] = $this->component->getFilesystem()->getFiles($path);
      }
      catch (FileNotFoundException $e)
      {
        // Folder not found
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_ERROR_FOLDER_NOT_EXISTING').' ('.\ucfirst(\basename($path)).')');

        return false;
      }
    }

    return $folders;
  }

  /**
   * Move category with all images from one parent category to another
   *
   * @param   object|int|string   $cat          Object, ID or alias of the category to be moved
   * @param   object|int|string   $dest         Category object, ID or alias of the destination category
   * @param   string|false        $foldername   Foldername of the moved category (default: false)
   * @param   bool                $copy         True, if you want to copy the category (default: false)
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function moveCategory($cat, $dest, $foldername=false, $copy=false): bool
  {
    // Switch method
    $method = 'MOVE';
    if($copy)
    {
      $method = 'COPY';
    }

    // Loop through all imagetypes
    $error = false;
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get category source path
      $src_path = $this->getCatPath($cat, $imagetype->typename);

      // Get path of target category
      $cat_path = $this->getCatPath($dest, $imagetype->typename);

      // Get category foldername
      $cat_foldername = \basename($src_path);
      if($foldername)
      {
        $cat_foldername = $foldername;
      }

      // Create category destination path
      $dst_path = $cat_path . '/' . $cat_foldername;

      // Move/Copy folder
      try
      {
        if($copy)
        {
          $this->component->getFilesystem()->copy($src_path, $dst_path);
        }
        else
        {
          $this->component->getFilesystem()->move($src_path, $dst_path);
        }        
      }
      catch(\FileNotFoundException $e)
      {
        // Folder not found
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_FOLDER_NOT_EXISTING').', '.$src_path);
        $error = true;

        continue;
      }
      catch(\Exception $e)
      {
        // Operation failed
        $error = true;

        continue;
      }
    }

    if($error)
    {
      // Moving failed
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_'.$method.'_CATEGORY', \ucfirst(\basename($src_path))));

      return false;
    }

    // Move successful
    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SUCCESS_'.$method.'_CATEGORY', \ucfirst(\basename($src_path))));

    return true;
  }

  /**
   * Rename folder of category
   *
   * @param   object|int|string   $cat          Object, ID or alias of the category to be renamed
   * @param   string              $foldername   New foldername of the category
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function renameCategory($cat, $foldername): bool
  {
    // Loop through all imagetypes
    $error = false;
    foreach($this->imagetypes as $key => $imagetype)
    {
      // Get category path
      $folder_orig = $this->getCatPath($cat, $imagetype->typename);

      // Create renamed category foldername
      $folder_new  = \substr($folder_orig, 0, strrpos($folder_orig, \basename($folder_orig))).$foldername;

      // Rename folder
      try
      {
        $this->component->getFilesystem()->move($folder_orig, $folder_new);
      }
      catch(\FileNotFoundException $e)
      {
        // Folder not found
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_FOLDER_NOT_EXISTING').', '.$folder_orig);
        $error = true;

        continue;
      }
      catch(\Exception $e)
      {
        // Renaming failed
        $error = true;

        continue;
      }
    }
    
    if($error)
    {
      // Renaming failed
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_RENAME_CATEGORY', \ucfirst(\basename($folder_orig))));

      return false;
    }

    // Renaming successful
    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SUCCESS_RENAME_CATEGORY', \ucfirst(\basename($folder_orig))));

    return true;
  }

  /**
   * Copy category with all images from one parent category to another
   *
   * @param   object|int|string   $cat          Object, ID or alias of the category to be copied
   * @param   object|int|string   $dest         Category object, ID or alias of the destination category
   * @param   string|false        $foldername   Foldername of the moved category (default: false)
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function copyCategory($cat, $dest, $foldername=false): bool
  {
    return $this->moveCategory($cat, $dest, $foldername, true);
  }

  /**
   * Returns the path to an image
   *
   * @param   object|int|string         $img       Image object, image ID or image alias (new images: ID=0)
   * @param   string                    $type      Imagetype
   * @param   object|int|string|bool    $catid     Category object, category ID, category alias or category path (default: false)
   * @param   string|bool               $filename  The filename (default: false)
   * @param   boolean                   $root      True to add the system root to the path
   * 
   * @return  mixed   Path to the image on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function getImgPath($img, $type, $catid=false, $filename=false, $root=false)
  {
    if($catid === false || $filename === false)
    {
      // We got a valid image object
      if(\is_object($img) && $img instanceof \Joomla\CMS\Object\CMSObject && isset($img->filename))
      {
        $catid    = ($catid === false) ? $img->catid : $catid;
        $filename = ($filename === false) ? $img->filename : $filename;
      }
      // We got an image ID or an alias
      elseif((\is_numeric($img) && $img > 0) || (\is_string($img) && !$this->is_path($img)))
      {
        // Get image object
        $img = JoomHelper::getRecord('image', $img);

        if($img === false || \is_null($img->id))
        {
          $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_GETIMGPATH', $img), 'error');

          return false;
        }

        $catid    = ($catid === false) ? $img->catid : $catid;
        $filename = ($filename === false) ? $img->filename : $filename;
      }
      // We got nothing to work with
      else
      {
        if($img === 0)
        {
          // ID: 0. Return no-image path
          $path = $this->no_image;

          // add root to path if needed
          if($root)
          {
            $path = JPATH_ROOT.\DIRECTORY_SEPARATOR.$path;
          }

          return $this->component->getFilesystem()->cleanPath($path);
        }

        $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_GETPATH_NOQUERY', 'Image'), 'error');

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
    if($root)
    {
      $path = JPATH_ROOT.\DIRECTORY_SEPARATOR.$path;
    }

    return $this->component->getFilesystem()->cleanPath($path);
  }

  /**
   * Returns the path to a category without root path.
   *
   * @param   object|int|string        $cat       Category object, category ID or category alias (new categories: ID=0)
   * @param   string|bool              $type      Imagetype if needed
   * @param   object|int|string|bool   $parent    Parent category object, parent category ID, parent category alias or parent category path (default: false)
   * @param   string|bool              $alias     The category alias (default: false)
   * @param   boolean                  $root      True to add the system root to the path
   * 
   * 
   * @return  mixed   Path to the category on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function getCatPath($cat, $type=false, $parent=false, $alias=false, $root=false)
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
    elseif((\is_numeric($cat) && $cat > 0) || (\is_string($cat) && \intval($cat) > 0))
    {
      if(\is_numeric($cat))
      {
        $cat = \intval($cat);
      }

      // Get the category object
      $cat = JoomHelper::getRecord('category', $cat);

      if($cat === false)
      {
        $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_GETCATPATH', $cat), 'error');

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
        if(empty($parent->path))
        {
          $path = $alias;
        }
        else
        {
          $path = $parent->path.\DIRECTORY_SEPARATOR.$alias;
        }
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
          $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_GETCATPATH', $parent), 'error');

          return false;
        }

        if(empty($parent->path))
        {
          $path = $alias;
        }
        else
        {
          $path = $parent->path.\DIRECTORY_SEPARATOR.$alias;
        }
      }
    }
    // We got nothing to work with
    else
    {
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_GETPATH_NOQUERY', 'Category'), 'error');

      return false;
    }

    // add imagetype to path if needed
    if($type && \key_exists($type, $this->imagetypes_dict))
    {
      $path = $this->imagetypes[$this->imagetypes_dict[$type]]->path.\DIRECTORY_SEPARATOR.$path;
    }
    
    // add root to path if needed
    if($root)
    {
      $path = JPATH_ROOT.\DIRECTORY_SEPARATOR.$path;
    }

    return $this->component->getFilesystem()->cleanPath($path);
  }

  /**
   * Generates image filenames
   * e.g. <Name/Title>_<Filecounter (opt.)>_<Date>_<Random Number>.<Extension>
   *
   * @param   string    $filename     Original upload name without extension
   * @param   string    $tag          File extension e.g. 'jpg'
   * @param   int       $filecounter  Optinally a filecounter
   *
   * @return  string    The generated filename
   *
   * @since   4.0.0
   */
  public function genFilename($filename, $tag, $filecounter = null): string
  {
    $filedate = date('Ymd');

    mt_srand();
    $randomnumber = mt_rand(1000000000, 2099999999);

    $maxlen = 255 - 2 - strlen($filedate) - strlen($randomnumber) - (strlen($tag) + 1);
    if(!is_null($filecounter))
    {
      $maxlen = $maxlen - (strlen($filecounter) + 1);
    }
    if(strlen($filename) > $maxlen)
    {
      $filename = substr($filename, 0, $maxlen);
    }

    // New filename
    if(is_null($filecounter))
    {
      $newfilename = $filename.'_'.$filedate.'_'.$randomnumber.'.'.$tag;
    }
    else
    {
      $newfilename = $filename.'_'.$filecounter.'_'.$filedate.'_'.$randomnumber.'.'.$tag;
    }

    return $newfilename;
  }

  /**
   * Regenerates image filenames
   * Input is a filename generated from genFilename()
   *
   * @param   string    $filename     Original filename created from genFilename()
   *
   * @return  string    The generated filename
   *
   * @since   4.0.0
   */
  public function regenFilename($filename): string
  {
    $filecounter  = null;
    $filename_arr = \explode('_', $filename);

    // Extract different parts of the filename
    if(\count($filename_arr) === 3)
    {
      list($name, $date, $end) = $filename_arr;
    }
    elseif(\count($filename_arr) === 4)
    {
      list($name, $filecounter, $date, $end) = $filename_arr;
    }
    else
    {
      throw new \Exception('Invalide filename received. Please make sure filename has the correct form.');
    }
    list($rnd, $tag) = \explode('.', $end);

    return $this->genFilename($name, $tag, $filecounter);
  }

  /**
   * Get all imagetypes and stores it to the class
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  protected function getImagetypes()
  {
    // get all imagetypes
    $this->imagetypes = JoomHelper::getRecords('imagetypes', $this->component);

    // sort imagetypes by id descending
    $this->imagetypes = \array_reverse($this->imagetypes);

    // create dictionary for imagetypes array
    foreach ($this->imagetypes as $key => $imagetype)
    {
      $this->imagetypes_dict[$imagetype->typename] = $key;
    }
  }

  /**
   * Delete all imagetypes which are not selected
   * 
   * @param   array||string    $selection    Name or list of names of imagetypes to consider
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  protected function selectImagetypes($selection)
  {
    if(empty($this->imagetypes))
    {
      return;
    }

    if(!\is_array($selection))
    {
      $selection = array($selection);
    }

    foreach($this->imagetypes as $key =>$imagetype)
    {
      if(!\in_array($imagetype->typename, $selection))
      {
        // unselected imagetype
        unset($this->imagetypes[$key]);
      }
    }

    $this->imagetypes = array_values($this->imagetypes);

    // update dictionary for imagetypes array
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
  protected function is_path($string)
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
}
