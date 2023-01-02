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

use \Joomla\CMS\Factory;
use \Joomla\CMS\User\User;
use \Joomla\CMS\Language\Language;
use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessengerInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessageInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\Messenger;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MailMessage;

/**
 * Mail Messenger Class
 *
 * Provides methods to send email messages in the gallery.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class MailMessenger extends Messenger implements MessengerInterface
{
  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct()
  {
    $this->message = New MailMessage();
  }
}
