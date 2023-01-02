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

use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\Message\MessageInterface;

/**
 * PM Message Class
 * Contains the content of a Joomla internal message (com_messages)
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class PmMessage implements MessageInterface
{
  /**
   * List of recipients
   *
   * @var array
   */
  protected $recipients = array();

  /**
   * Sender
   *
   * @var int|string
   */
  protected $from = null;

  /**
   * Sender display name
   *
   * @var string
   */
  protected $fromname = '';

  /**
   * Subject line
   *
   * @var string
   */
  protected $subject = '';

  /**
   * Message body
   *
   * @var string
   */
  protected $body = '';

  /**
   * Method to add one ore more recipients
   *
   * @param   array   $recipients  An array of recipients or a single one as a string
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  public function addRecipients($recipients)
  {
    if(is_array($recipients))
    {
      $this->recipients = array_merge($this->recipients, $recipients);
    }
    else
    {
      \array_push($this->recipients, $recipients);
      $this->recipients[] = $recipients;
    }
  }
}
