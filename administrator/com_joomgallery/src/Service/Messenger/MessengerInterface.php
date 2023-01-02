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

// No direct access
\defined('_JEXEC') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessageInterface;

/**
* Interface for the messenger class
*
* @property  MessageInterface  $message
*
* @since  4.0.0
*/
interface MessengerInterface
{
  /**
   * Method to send a message
   *
   * @return  bool    True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function send(): bool;
}
