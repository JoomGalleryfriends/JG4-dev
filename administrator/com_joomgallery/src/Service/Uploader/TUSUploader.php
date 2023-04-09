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

use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\ServerInterface as TUSServerInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\Uploader as BaseUploader;

/**
* Uploader helper class (TUS Upload)
*
* @since  4.0.0
*/
class TUSUploader extends BaseUploader implements UploaderInterface
{
	/**
   * Constructor
   * 
   * @param   bool   $multiple     True, if it is a multiple upload  (default: false)
   *
   * @return  void
   *
   * @since   1.0.0
   */
  public function __construct($multiple=false)
  {
		parent::__construct($multiple);

		$this->component->createTusServer();
	}

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
		$user = Factory::getUser();

		// Check for upload errors
		$isfinal = $this->component->getTusServer()->getMetaDataValue('isfinal');
		$size    = $this->component->getTusServer()->getMetaDataValue('size');
		$offset  = $this->component->getTusServer()->getMetaDataValue('ofset');
	}


}
