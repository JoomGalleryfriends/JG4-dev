<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Messenger;

\defined('_JEXEC') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\Messenger;

/**
* Trait to implement MessengerServiceInterface
*
* @since  4.0.0
*/
trait MessengerServiceTrait
{
  /**
	 * Storage for the messenger service class.
	 *
	 * @var MessengerInterface
	 *
	 * @since  4.0.0
	 */
	private $messenger = null;

  /**
	 * Creates the messenger service class
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createMessenger()
  {
    $this->messenger = new Messenger;
    return;
  }

  /**
	 * Returns the messenger service class.
	 *
	 * @return  MessengerInterface
	 *
	 * @since  4.0.0
	 */
	public function getMessenger()
  {
    return $this->messenger;
  }
}
