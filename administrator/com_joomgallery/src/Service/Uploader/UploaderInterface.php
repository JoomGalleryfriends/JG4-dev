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

\defined('JPATH_PLATFORM') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Table\ImageTable;

/**
* Uploader Interface for the helper classes
*
* @since  4.0.0
*/
interface UploaderInterface
{
  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct();

	/**
	 * Method to retrieve an uploaded image. Step 1.
   * (check upload, check user upload limit, create filename, onJoomBeforeUpload)
	 *
   * @param   array    $data      Form data (as reference)
   *
	 * @return  bool     True on success, false otherwise
	 *
	 * @since  4.0.0
	 */
	public function retrieveImage(&$data): bool;

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
  public function overrideData(&$data): bool;

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
	public function createImage($data_row): bool;

  /**
   * Rollback an erroneous upload
   * 
   * @param   ImageTable   $data_row     Image object
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  public function rollback($data_row);
}
