<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Messenger;

\defined('_JEXEC') or die;

/**
* The Messenger service
*
* @since  4.0.0
*/
interface MessengerServiceInterface
{
  /**
	 * Storage for the messenger service class.
	 *
	 * @var MessengerInterface
	 *
	 * @since  4.0.0
	 */
	private $messenger;

  /**
	 * Creates the messenger service class
   * 
   * @param   string  $msgMethod   Name of the messager to be used
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createMessenger($msgMethod): void;

	/**
	 * Returns the messenger service class.
	 *
	 * @return  MessengerInterface
	 *
	 * @since  4.0.0
	 */
	public function getMessenger(): MessengerInterface;
}
