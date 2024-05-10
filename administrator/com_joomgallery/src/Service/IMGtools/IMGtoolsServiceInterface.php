<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools;

\defined('JPATH_PLATFORM') or die;

/**
* The IMGtools service
*
* @since  4.0.0
*/
interface IMGtoolsServiceInterface
{
  /**
	 * Storage for the IMGtools class.
	 *
	 * @var IMGtoolsInterface
	 *
	 * @since  4.0.0
	 */
	private $IMGtools;

  /**
	 * Creates the IMGtools class
   *
   * @param   string  $processor  Name of the image processor to be used
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createIMGtools($processor, $keep_metadata = false, $keep_anim = false): void;

  /**
	 * Destroys the IMGtools class
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function delIMGtools(): void;

	/**
	 * Returns the IMGtools class.
	 *
	 * @return  IMGtoolsInterface
	 *
	 * @since  4.0.0
	 */
	public function getIMGtools(): IMGtoolsInterface;
}
