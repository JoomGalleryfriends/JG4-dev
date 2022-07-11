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
use \Joomla\CMS\Filter\InputFilter;
use \Joomla\CMS\Object\CMSObject;
use Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
* Base class for the Uploader helper classes
*
* @since  4.0.0
*/
abstract class Uploader implements UploaderInterface
{
  /**
   * Set to true if a error occured
   *
   * @var bool
   */
  public $error = false;

  /**
   * Holds the key of the user state variable to be used
   *
   * @var string
   */
  protected $userStateKey = 'com_joomgallery.image.upload';

  /**
   * Counter for the number of files alredy uploaded
   *
   * @var int
   */
  public $filecounter = 0;

  /**
   * The ID of the category in which
   * the images shall be uploaded
   *
   * @var int
   */
  public $catid = 0;

  /**
   * The title of the image if the original
   * file name shouldn't be used
   *
   * @var string
   */
  public $imgtitle = '';

  /**
   * Holds the JoomgalleryComponent object
   *
   * @var JoomgalleryComponent
   */
  protected $jg;

  /**
   * Name of the used filesystem
   *
   * @var string
   */
  protected $filesystem_type = 'localhost';

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
    $this->jg->createConfig();

    $app  = Factory::getApplication();

    $this->error       = $app->getUserStateFromRequest($this->userStateKey.'.error', 'error', false, 'bool');
    $this->catid       = $app->getUserStateFromRequest($this->userStateKey.'.catid', 'catid', 0, 'int');
    $this->imgtitle    = $app->getUserStateFromRequest($this->userStateKey.'.imgtitle', 'imgtitle', '', 'string');
    $this->filecounter = $app->getUserStateFromRequest($this->userStateKey.'.filecounter', 'filecounter', 0, 'post', 'int');

    $this->jg->addDebug($app->getUserStateFromRequest($this->userStateKey.'.debugoutput', 'debugoutput', '', 'string'));
    $this->jg->addWarning($app->getUserStateFromRequest($this->userStateKey.'.warningoutput', 'warningoutput', '', 'string'));
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
    // Get image extension
    $tag = strtolower(JFile::getExt($this->src_file));

    if(!($tag == 'jpg' || $tag == 'jpeg' || $tag == 'jpe' || $tag == 'jfif'))
    {
      // Check for the right file-format, else throw warning
      $this->jg->addWarning(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_WARNING_WRONGFILEFORMAT'));

      return true;
    }

    // Create the IMGtools service
    $this->jg->createIMGtools($this->jg->getConfig()->get('jg_imgprocessor'));

    // Get image metadata (source)
    $metadata = $this->jg->getIMGtools()->readMetadata($this->src_file);

    // Add image metadata to data
    $data['imgmetadata'] = \json_encode($metadata);

    // Check if there is something to override
    if(!\property_exists($this->jg->getConfig()->get('jg_replaceinfo'), 'jg_replaceinfo0'))
    {
      // Destroy the IMGtools service
      $this->jg->delIMGtools();
      
      return true;
    }

    // Load dependencies
    $filter = InputFilter::getInstance();
    require_once JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/iptcarray.php';
    require_once JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/exifarray.php';

    // Loop through all replacements defined in config
    foreach ($this->jg->getConfig()->get('jg_replaceinfo') as $replaceinfo)
    {
      $source_array = \explode('-', $replaceinfo->source);

      // Get matadata value from image
      switch ($source_array[0])
      {
        case 'IFD0':
          // 'break' intentionally omitted
        case 'EXIF':
          // Get exif source attribute
          if(isset($exif_config_array[$source_array[0]]) && isset($exif_config_array[$source_array[0]][$source_array[1]]))
          {
            $source = $exif_config_array[$source_array[0]][$source_array[1]];
          }
          else
          {
            // Unknown source
            continue 2;
          }

          $source_attribute = $source['Attribute'];
          $source_name      = $source['Name'];

          // Get matadata value
          if(isset($metadata['exif'][$source_array[0]]) && isset($metadata['exif'][$source_array[0]][$source_attribute])
              && !empty($metadata['exif'][$source_array[0]][$source_attribute]))
          {
            $source_value = $metadata['exif'][$source_array[0]][$source_attribute];
          }
          else
          {
            // Matadata value not available in image
            $this->jg->addWarning(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_WARNING_REPLACE', $source_name));
            continue 2;
          }          
          break;

        case 'COMMENT':
          // Get metadata value
          if(isset($metadata['comment']) && !empty($metadata['comment']))
          {
            $source_value = $metadata['comment'];
          }
          else
          {
            // Matadata value not available in image
            $this->jg->addWarning(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_WARNING_REPLACE', Text::_('COM_JOOMGALLERY_META_COMMENT')));
            continue 2;
          }
          break;

        case 'IPTC':
          // Get iptc source attribute
          if(isset($iptc_config_array[$source_array[0]]) && isset($iptc_config_array[$source_array[0]][$source_array[1]]))
          {
            $source = $iptc_config_array[$source_array[0]][$source_array[1]];
          }
          else
          {
            // Unknown source
            continue 2;
          }

          $source_attribute = $source['IMM'];
          $source_name      = $source['Name'];

          // Adjust iptc source attribute
          $source_attribute = \str_replace(':', '#', $source_attribute);

          // Get matadata value 
          if(isset($metadata['iptc'][$source_attribute]) && !empty($metadata['iptc'][$source_attribute]))
          {
            $source_value = $metadata['iptc'][$source_attribute];
          }
          else
          {
            // Matadata value not available in image
            $this->jg->addWarning(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_WARNING_REPLACE', $source_name));
            continue 2;
          }
          break;
        
        default:
          // Unknown metadata source
          continue 2;
          break;
      }

      // Replace target with metadata value
      if($replaceinfo->target == 'tags')
      {
        //TODO: Add tags based on metadata
      }
      else
      {
        $data[$replaceinfo->target] = $filter->clean($source_value, 'string');
        $this->jg->addWarning(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_UPLOAD_REPLACE_' . \strtoupper($replaceinfo->target)));
      }
    }

    // Destroy the IMGtools service
    $this->jg->delIMGtools();

    return true;
  }

  /**
   * Rollback an erroneous upload
   * 
   * @param   CMSObject   $data_row     Image object containing at least catid and filename (default: false)
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  public function rollback($data_row=false)
  {
    if($data_row)
    {
      // Create file manager service
      $this->jg->createFileManager();

      // Delete just created images
      $this->jg->getFileManager()->deleteImages($data_row);
    }

    // Delete temp image
    if(isset($this->src_file) && !empty($this->src_file) && \file_exists($this->src_file))
    {
      JFile::delete($this->src_file);
    }

    $this->resetUserStates();
  }

  /**
   * Returns the number of images of the current user
   *
   * @param   $userid  Id of the current user
   *
   * @return  int      The number of images of the current user
   *
   * @since   1.5.5
   */
  protected function getImageNumber($userid)
  {
    $db = Factory::getDbo();

    $query = $db->getQuery(true)
          ->select('COUNT(id)')
          ->from(_JOOM_TABLE_IMAGES)
          ->where('created_by = '.$userid);

    $timespan = $this->jg->getConfig()->get('jg_maxuserimage_timespan');
    if($timespan > 0)
    {
      $query->where('imgdate > (UTC_TIMESTAMP() - INTERVAL '. $timespan .' DAY)');
    }

    $db->setQuery($query);

    return $db->loadResult();
  }

  /**
   * Calculates the serial number for images file names and titles
   *
   * @return  int       New serial number
   *
   * @since   1.0.0
   */
  protected function getSerial()
  {
    $app  = Factory::getApplication();

    // Check if the initial value is already calculated
    if(isset($this->filecounter))
    {
      $this->filecounter++;

      // Store the next value in the session
      $app->setUserState($this->userStateKey.'.filecounter', $this->filecounter + 1);

      return $this->filecounter;
    }

    // If there is no starting value set, disable numbering
    if(!$this->filecounter)
    {
      return null;
    }

    // No negative starting value
    if($this->filecounter < 0)
    {
      $picserial = 1;
    }
    else
    {
      $picserial = $this->filecounter;
    }

    return $picserial;
  }

  /**
   * Generates filenames
   * e.g. <Name/gen. Title>_<opt. Filecounter>_<Date>_<Random Number>.<Extension>
   *
   * @param   string    $filename     Original upload name e.g. 'malta.jpg'
   * @param   string    $tag          File extension e.g. 'jpg'
   * @param   int       $filecounter  Optinally a filecounter
   *
   * @return  string    The generated filename
   *
   * @since   1.0.0
   */
  protected function genFilename($filename, $tag, $filecounter = null)
  {
    $filedate = date('Ymd');

    // Remove filetag = $tag incl '.'
    // Only if exists in filename
    if(stristr($filename, $tag))
    {
      $filename = substr($filename, 0, strlen($filename)-strlen($tag)-1);
    }

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
   * Resets user states
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  protected function resetUserStates()
  {
    $app  = Factory::getApplication();

    // Reset file counter, delete original and create special gif selection and debug information
    $app->setUserState($this->userStateKey.'.filecounter', 0);
    $app->setUserState($this->userStateKey.'.error', false);
    $app->setUserState($this->userStateKey.'.debugoutput', null);
    $app->setUserState($this->userStateKey.'.warningoutput', null);
  }

  /**
   * Creation of a temporary image object for the rollback
   * 
   * @param   array   $data      The form data
   * 
   * @return  CMSObject
   * 
   * @since   4.0.0
   */
  protected function tempImgObj($data)
  {
    if(!\key_exists('catid', $data) || !empty($data['catid']) || !\key_exists('filename', $data) || !empty($data['filename']))
    {
      throw new \Exception('Form data must have at least catid and filename');
    }

    $img = new CMSObject;

    $img->set('catid', $data['catid']);
    $img->set('filename', $data['filename']);

    return $img;
  }
}
