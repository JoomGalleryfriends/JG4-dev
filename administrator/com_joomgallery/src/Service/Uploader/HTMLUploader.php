<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Uploader;

\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Filesystem\File as JFile;
use \Joomla\CMS\Filesystem\Path as JPath;
use \Joomgallery\Component\Joomgallery\Administrator\Table\ImageTable;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\Uploader as BaseUploader;

/**
* Uploader helper class (Single Upload)
*
* @since  4.0.0
*/
class HTMLUploader extends BaseUploader implements UploaderInterface
{
	/**
	 * Method to retrieve an uploaded image. Step 1.
   * (check upload, check user upload limit, create filename, onJoomBeforeUpload)
	 *
   * @param   array    $data        Form data (as reference)
   * @param   bool     $filename    True, if the filename has to be created (defaut: True)
   *
	 * @return  bool     True on success, false otherwise
	 *
	 * @since  4.0.0
	 */
	public function retrieveImage(&$data, $filename=True): bool
  {
    $app  = Factory::getApplication();
    $user = Factory::getUser();

    if(\count($data['images']) > 1)
    {
      if($this->filecounter >= 1)
      {
        $this->jg->addDebug('<hr />');
      }
      $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_POSITION', $this->filecounter + 1));
    }

    $image = $data['images'][$this->filecounter];

    // Check for upload error codes
    if($image['error'] > 0)
    {
      if($image['error'] == 4)
      {
        $this->jg->addDebug(Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_FILE_NOT_UPLOADED'));

        return false;
      }
      $this->jg->addDebug($this->checkError($image['error']));
      $this->error = true;

      return false;
    }

    // Get number of uploaded images of the current user
    $counter = $this->getImageNumber($user->get('id'));
    $is_site = $app->isClient('site');

    // Check if user already exceeds its upload limit
    if($is_site && $counter > ($this->jg->getConfig()->get('jg_maxuserimage') - 1) && $user->get('id'))
    {
      $timespan = $this->jg->getConfig()->get('jg_maxuserimage_timespan');
      $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_MAY_ADD_MAX_OF', $this->jg->getConfig()->get('jg_maxuserimage'), $timespan > 0 ? Text::plural('COM_JOOMGALLERY_UPLOAD_NEW_IMAGE_MAXCOUNT_TIMESPAN', $timespan) : ''));

      return false;
    }

    $this->src_tmp  = $image['tmp_name'];
    $this->src_name = $image['name'];
    $this->src_size = $image['size'];

    // Get extension
    $tag = strtolower(JFile::getExt($this->src_name));

    // Get supported formats
    $this->jg->createIMGtools($this->jg->getConfig()->get('jg_imgprocessor'));
    $supported_tags = $this->jg->getIMGtools()->get('supported_types');
    $this->jg->delIMGtools();

    // Check for supported image format
    if(!\in_array(\strtoupper($tag), $supported_tags) || strlen($this->src_tmp) == 0 || $this->src_tmp == 'none')
    {
      $this->jg->addDebug(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_INVALID_IMAGE_TYPE'));
      $this->error  = true;

      return false;
    }

    $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_FILENAME', $this->src_name));

    // Image size must not exceed the setting in backend if we are in frontend
    if($is_site && $this->src_size > $this->jg->getConfig()->get('jg_maxfilesize'))
    {
      $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_MAX_ALLOWED_FILESIZE', $this->jg->getConfig()->get('jg_maxfilesize')));
      $this->error  = true;

      return false;
    }

    if($filename)
    {
      // Get filecounter
      $filecounter = null;
      if($this->multiple && $this->jg->getConfig()->get('jg_filenamenumber'))
      {
        $filecounter = $this->getSerial();
      }

      // Create filesystem service
      $this->jg->createFilesystem('localhost');

      // Create new filename
      if($this->jg->getConfig()->get('jg_useorigfilename'))
      {
        $oldfilename = $this->src_name;
        $newfilename = $this->jg->getFilesystem()->cleanFilename($this->src_name);
      }
      else
      {
        $oldfilename = $data['imgtitle'];
        $newfilename = $this->jg->getFilesystem()->cleanFilename($data['imgtitle']);
      }

      // Check the new filename
      if(!$this->jg->getFilesystem()->checkFilename($oldfilename, $newfilename))
      {
        if($is_site)
        {
          $this->jg->addDebug(Text::_('COM_JOOMGALLERY_COMMON_ERROR_INVALID_FILENAME'));
        }
        else
        {
          $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_ERROR_INVALID_FILENAME', $newfilename, $oldfilename));
        }
        $this->error = true;

        return false;
      }

      // Generate image filename
      $this->jg->createFileManager();
      $data['filename'] = $this->jg->getFileManager()->genFilename($newfilename, $tag, $filecounter);
    }    

    // Trigger onJoomBeforeUpload
    $plugins  = $app->triggerEvent('onJoomBeforeUpload', array($data['filename']));
    if(in_array(false, $plugins, true))
    {
      return false;
    }

    // Upload file to temp file
    $this->src_file = JPath::clean(\dirname($this->src_tmp).\DIRECTORY_SEPARATOR.$this->src_name);
    $return = JFile::upload($this->src_tmp, $this->src_file);
    if(!$return)
    {
      $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_PROBLEM_MOVING', $this->src_file .' '. Text::_('COM_JOOMGALLERY_COMMON_CHECK_PERMISSIONS')));
      $this->rollback();
      $this->error = true;

      return false;
    }

    // Set permissions of uploaded file
    $return = JPath::setPermissions($this->src_file, '0644', null);
    $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_UPLOAD_COMPLETE', filesize($this->src_file) / 1000));

    return true;
  }

  /**
   * Override form data with image metadata
   * according to configuration. Step 2.
   *
   * @param   array   $data       The form data (as a reference)
   * 
   * @return  bool    True on success, false otherwise
   * 
   * @since   1.5.7
   */
  public function overrideData(&$data): bool
  {
    // Get upload date
    if(empty($data['imgdate']) || \strpos($data['imgdate'], '1900-01-01') !== false)
    {
      $data['imgdate'] = $data['created_time'];
    }

    // Override form data with image metadata
    return parent::overrideData($data);
  }

  /**
	 * Method to create uploaded image files. Step 3.
   * (create imagetypes, upload imagetypes to storage, onJoomAfterUpload)
	 *
   * @param   ImageTable   $data_row     Image object
   *
	 * @return  bool         True on success, false otherwise
	 *
	 * @since  4.0.0
	 */
	public function createImage($data_row): bool
  {
    // Check if filename was set
    if(!isset($data_row->filename) || empty($data_row->filename))
    {
      throw new \Exception('Filename has to be set in image data. Run retrieveImage() method first.');
    }

    // Create file manager service
    $this->jg->createFileManager();    
    
    // Create image types
    if(!$this->jg->getFileManager()->createImages($this->src_file, $data_row->filename, $data_row->catid))
    {
      $this->rollback($data_row);
      $this->error = true;

      return false;
    }

    // Message about new image
    // if($is_site)
    // {
    //   // Create message service
    //   $this->jg->createMessenger();

    //   $message    = array(
    //                         'from'      => $user->get('id'),
    //                         'subject'   => Text::_('COM_JOOMGALLERY_UPLOAD_MESSAGE_NEW_IMAGE_UPLOADED'),
    //                         'body'      => Text::sprintf('COM_JOOMGALLERY_MESSAGE_NEW_IMAGE_SUBMITTED_BODY', $this->jg->getConfig()->get('jg_realname') ? $user->get('name') : $user->get('username'), $row->imgtitle),
    //                         'mode'      => 'upload'
    //                       );
    //   $this->jg->getMessenger()->send($message);
    // }

    $this->jg->addDebug(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_IMAGE_SUCCESSFULLY_ADDED'));
    $this->jg->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_NEW_FILENAME', $data_row->filename));

    Factory::getApplication()->triggerEvent('onJoomAfterUpload', array($data_row));

    // Reset user states
    $this->resetUserStates();

    return !$this->error;
  }

  /**
   * Analyses an error code and returns its text
   *
   * @param   int     $uploaderror  The errorcode
   *
   * @return  string  The error message
   *
   * @since   1.0.0
   */
  protected function checkError($uploaderror)
  {
    // Common PHP errors
    $uploadErrors = array(
      1 => Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_PHP_MAXFILESIZE'),
      2 => Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_HTML_MAXFILESIZE'),
      3 => Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_FILE_PARTLY_UPLOADED'),
      4 => Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_FILE_NOT_UPLOADED')
    );

    if(in_array($uploaderror, $uploadErrors))
    {
      return Text::sprintf('COM_JOOMGALLERY_UPLOAD_ERROR_CODE', $uploadErrors[$uploaderror]);
    }
    else
    {
      return Text::sprintf('COM_JOOMGALLERY_UPLOAD_ERROR_CODE', Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_UNKNOWN'));
    }
  }
}
