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
   * @param   mixed   $recipients   List of users or email adresses receiving the message
   * 
   * @return  bool    True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function send($recipients): bool;

  /**
   * Method to select the template to be used for the message
   *
   * @param   string   $id   The id of the template to be used
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  public function selectTemplate(string $id);

  /**
   * Method to select the language of the message
   *
   * @param   string   $tag   The id of the template to be used
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  public function selectLanguage(string $tag);

  /**
   * Method to add one ore more variables to be used in the template
   *
   * @param   mixed   $data   An array of key value pairs with variables to be used in the template
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  public function addTemplateData($data);
}
