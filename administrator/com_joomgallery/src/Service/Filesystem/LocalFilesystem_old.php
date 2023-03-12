<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\User\UserHelper;
use \Joomla\CMS\Filesystem\File as JFile;
use \Joomla\CMS\Filesystem\Folder as JFolder;
use \Joomla\CMS\Filesystem\Path as JPath;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\FilesystemInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\Filesystem as BaseFilesystem;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Filesystem Class (Local filesystem)
 *
 * Provides methods to handle the local filesystem to store the image files
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class LocalFilesystem extends BaseFilesystem implements FilesystemInterface
{
  /**
   * Root folder of the local storage system
   *
   * @var string
   */
  protected $root = JPATH_ROOT;

  /**
   * Constructor enables the connection to the filesystem
   * in which the images should be stored
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct()
  {
    return true;
  }

  /**
   * Function to strip additional / or \ in a path name.
   *
   * @param   string  $path   The path to clean
   * @param   string  $ds     Directory separator (optional)
   *
   * @return  string  The cleaned path
   *
   * @since   4.0.0
   */
  public function cleanPath($path, $ds=\DIRECTORY_SEPARATOR): string
  {
    return JPath::clean($path, $ds);
  }

  /**
   * Moves a file from local folder to storage
   *
   * @param   string  $src   File name at local folder
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function uploadFile($src): bool
  {
    // nothing to do since storage is local filesystem
    return true;
  }

  /**
   * Moves a file from the storage to a local folder
   *
   * @param   string  $dest  File name at local folder
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function downloadFile($dest): bool
  {
    // nothing to do since storage is local filesystem
    return true;
  }

  /**
   * Moves a file at the storage filesystem
   *
   * @param   string  $src   Source file name
   * @param   string  $dest  Destination file name
   * @param   bool    $copy  True, if you want to copy the file (default: false)
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function moveFile($src, $dest, $copy = false): bool
  {
    // complete source path
    $src = $this->completePath($src);

    // complete destination path
    $dest = $this->completePath($dest);

    if($copy)
    {
      return JFile::copy($src, $dest);
    }
    else
    {
      return JFile::move($src, $dest);
    }
  }

  /**
   * Delete a file or array of files
   *
   * @param   mixed  $file   The file name or an array of file names
   *
   * @return  bool   true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function deleteFile($file): bool
  {
    // complete file path
    $file = $this->completePath($file);

    return JFile::delete($file);
  }

  /**
   * Rename a file.
   *
   * @param   string  $file      The file name.
   * @param   string  $newName   The new name of the file.
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function renameFile($file, $newName): bool
  {
    // complete file path
    $src = $this->completePath($file);

    // exchange trailing name in file path
    $dest = $this->renamePath($src, $newName);

    return JFile::move($src, $dest);
  }

  /**
   * Checks a file for existence, validity and size
   *
   * @param   string  $file  The file name
   *
   * @return  mixed   file size on success, false otherwise
   *
   * @since   4.0.0
   */
  public function checkFile($file)
  {
    // complete file path
    $file = $this->completePath($file);

    if(file_exists($file))
    {
      $info     = array();
      $img_info = getimagesize($file);

      if(\is_array($img_info))
      {
        // image file type
        $info['mime']     = $img_info['mime'];
        $info['width']    = $img_info[0];
        $info['height']   = $img_info[1];
        $info['bits']     = $img_info['bits'];
        $info['channels'] = $img_info['channels'];
      }
      else
      {
        // other file type
        $info['mime'] = mime_content_type($file);
      }

      $info['size'] = filesize($file);

      return $info;
    }
    else
    {
      return false;
    }
  }

  /**
   * Create a folder and all necessary parent folders (local and storage).
   *
   * @param   string  $path   A path to create from the base path.
   * @param   bool    $index  True to create an index.html file (default: false)
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function createFolder($path, $index=false): bool
  {
    // complete folder path
    $path = $this->completePath($path);

    // create folder
    $folder = JFolder::create($path);
    
    // create index.html if needed
    if($folder && $index)
    {
      if(!$this->createIndexHtml($path))
      {
        $this->deleteFolder($path);

        return false;
      }
    }

    return $folder;
  }

  /**
   * Moves a folder including all all files and subfolders (local and storage).
   *
   * @param   string  $src    The path to the source folder.
   * @param   string  $dest   The path to the destination folder.
   * @param   bool    $copy   True, if you want to copy the folder (default: false)
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function moveFolder($src, $dest, $copy = false): bool
  {
    // complete source path
    $src = $this->completePath($src);

    // complete destination path
    $dest = $this->completePath($dest);

    if($copy)
    {
      return JFolder::copy($src, $dest);
    }
    else
    {
      return JFolder::move($src, $dest);
    }
  }

  /**
   * Delete a folder including all all files and subfolders (local and storage).
   *
   * @param   string  $path   The path to the folder to delete.
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function deleteFolder($path): bool
  {
    // complete folder path
    $path = $this->completePath($path);

    return JFolder::delete($path);
  }

  /**
   * Rename a folder.
   *
   * @param   string  $path      The path to the folder to rename.
   * @param   string  $newName   The new name of the folder.
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function renameFolder($path, $newName): bool
  {
    // complete source path
    $src = $this->completePath($path);

    // exchange trailing name in path
    $dest = $this->renamePath($src, $newName);

    return JFolder::move($src, $dest);
  }

  /**
   * Checks a folder for existence  (local and storage).
   *
   * @param   string  $path      The path to the folder to check.
   * @param   bool    $files     True to return a list of files in the folder
   * @param   bool    $folders   True to return a list of subfolders of the folder
   * @param   int     $maxLevel  The maximum number of levels to recursively read (default: 3).
   *
   * @return  mixed   Array with files and folders on success, false otherwise
   *
   * @since   4.0.0
   */
  public function checkFolder($path, $files = false, $folders = false, $maxLevel = 3)
  {
    // complete folder path
    $path = $this->completePath($path);

    if(file_exists($path))
    {
      if ($files && !$folders)
      {
        // list only files
        return JFolder::files($path,'',$maxLevel);
      }
      elseif (!$files && $folders)
      {
        // list only folders
        return JFolder::listFolderTree($path,'',$maxLevel);
      }
      elseif ($files && $folders)
      {
        // list files and folders
        return $this->listFolderTree($path,'',$maxLevel);
      }
      else
      {
        return true;
      }
    }
    else
    {
      return false;
    }
  }

  /**
   * Sets the permission of a given file or folder recursively.
   *
   * @param   string  $path      The path to the file/folder
   * @param   string  $val       The octal representation of the value to change file/folder mode
   * @param   bool    $mode      True to use file mode. False to use folder mode. (default: true)
   *
   * @return  bool    True if successful [one fail means the whole operation failed].
   *
   * @since   4.0.0
   */
  public function chmod($path, $val, $mode=true): bool
  {
    // complete folder path
    $path = $this->completePath($path);

    if($mode)
    {
      return JPath::setPermissions(JPath::clean($path), $val, null);
    }
    else
    {
      return JPath::setPermissions(JPath::clean($path), null, $val);
    }
  }

  /**
   * Lists files and folders in format suitable for tree display.
   *
   * @param   string  $path      The path of the folder to read.
   * @param   bool    $filter    A filter for folder names.
   * @param   int     $maxLevel  The maximum number of levels to recursively read, defaults to three.
   * @param   int     $level     The current level, optional.
   * @param   int     $parent    Unique identifier of the parent folder, if any.
   *
   * @return  mixed   Array with files and folders in the given folder.
   *
   * @since   4.0.0
   */
  private function listFolderTree($path, $filter, $maxLevel, $level = 0, $parent = 0)
	{    
    $dirs = array();

		if($level < $maxLevel)
		{
			if($level == 0)
      {
        $id = $GLOBALS['_JFolder_folder_tree_index'] = 0;
      }
      else
      {
        $id = ++$GLOBALS['_JFolder_folder_tree_index'];
      }      

      // Put folder info
      $dirs['id']       = $id;
      $dirs['name']     = \basename($path);
      $dirs['fullname'] = JPath::clean($path);
      $dirs['relname']  = \str_replace(JPATH_ROOT, '', JPath::clean($path));
      $dirs['files']    = JFolder::files($path);

      // Get list of subfolders
      $folders          = JFolder::folders($path, $filter);

			// Get subfolder info
			foreach ($folders as $name)
			{
        $fullName = JPath::clean($path . '/' . $name);

        $dirs['folders'][$name] = $this->listFolderTree($fullName, $filter, $maxLevel, $level + 1, $id);
			}
		}

		return $dirs;
  }

  /**
   * Completes a path with the root if it does not already contain it.
   *
   * @param   string  $path      The path of the folder to read.
   *
   * @return  string  Completed path
   *
   * @since   4.0.0
   */
  private function completePath($path)
  {
    if(strpos($path, $this->root) === false)
    {
      $path = $this->root.\DIRECTORY_SEPARATOR.$path;
    }

    return JPath::clean($path);
  }

  /**
   * Exchanges the trailing name component of a path
   *
   * @param   string  $path      The path.
   * @param   string  $newName   The trailing name.
   *
   * @return  string  Renamed path
   *
   * @since   4.0.0
   */
  private function renamePath($path, $newName)
  {
    // get trailing name component of path
    $trName = \basename($path);

    // remove trailing name from path
    $clean_path = \substr($path, 0, strrpos($path, $trName));

    // complete renamed path
    return $this->completePath($clean_path.$newName);
  }
}