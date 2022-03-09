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

\defined('JPATH_PLATFORM') or die;

/**
* The Image manager service
*
* @since  4.0.0
*/
interface ImageMgrServiceInterface
{
  /**
	 * Storage for the Image manager class.
	 *
	 * @var ImageMgrInterface
	 *
	 * @since  4.0.0
	 */
	private $imageManager;

  /**
	 * Creates the Image manager helper class
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createImageManager(): void;

	/**
	 * Returns the Image manager helper class.
	 *
	 * @return  ImageMgrInterface
	 *
	 * @since  4.0.0
	 */
	public function getImageManager(): ImageMgrInterface;
}
