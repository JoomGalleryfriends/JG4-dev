<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Refresher;

\defined('JPATH_PLATFORM') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Refresher\Refresher;

/**
* Trait to implement RefresherServiceInterface
*
* @since  4.0.0
*/
trait RefresherServiceTrait
{
  /**
	 * Storage for the refresher class.
	 *
	 * @var RefresherInterface
	 *
	 * @since  4.0.0
	 */
	private $refresher = null;

  /**
	 * Returns the refresher helper class.
	 *
	 * @return  RefresherInterface
	 *
	 * @since  4.0.0
	 */
	public function getRefresher(): RefresherInterface
	{
		return $this->refresher;
	}

  /**
	 * Creates the refresher helper class
   * 
   * @param   array  $params   An array with optional parameters
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createRefresher($params = array()): void
	{
    $this->refresher = new Refresher($params);

    return;
	}
}
