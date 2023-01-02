<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\Message;

// No direct access
\defined('_JEXEC') or die;

/**
* Interface for a message class
*
* @since  4.0.0
*/
interface MessageInterface
{
  /**
   * Method to add one ore more recipients
   *
   * @param   array    $recipients   An array of recipients or a single one as a string
   * @param   string   $type         Type of recipient ('to', 'cc', 'bcc')
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  public function addRecipients($recipients, $type='to');

  /**
   * Set the message sender
   *
   * @param   mixed  $from  email address and Name of sender
   *                        <code>array([0] => email Address, [1] => Name)</code>
   *                        or as a string
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  public function setSender($from);

  /**
   * Set the message body
   *
   * @param   string  $content  Body of the message
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function setBody($content);
}
