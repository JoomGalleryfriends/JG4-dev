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

/**
 * Mail Messenger Class
 *
 * Provides methods to send email messages in the gallery.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class PmMessenger implements MessengerInterface
{
  use ServiceTrait;

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct()
  {
    $this->jg = Factory::getApplication()->bootComponent('com_joomgallery');
  }

  /**
   * Send a message to com_messages.
   *
   * @param   User        $recipient       The user receiving the message
   * @param   string      $user            The user making the transition
   * @param   string      $title           The title of the item transitioned
   * @param   string      $transitionName  The name of the transition executed
   * @param   string      $toStage         The stage moving to
   * @param   Language    $language        The language to use for translating the message
   * @param   string      $extraText       The additional text to add to the end of the message
   *
   * @return  void
   *
   * @since   4.0.0
   */
  protected function send(User $recipient, string $user, string $title, string $transitionName, string $toStage, Language $language, string $extraText): void
  {
    if($recipient->authorise('core.manage', 'com_message'))
    {
      // Get the model for private messages
      $modelMessage = $this->app->bootComponent('com_messages')->getMVCFactory()->createModel('Message', 'Administrator');

      // Remove users with locked input box from the list of receivers
      if($this->isMessageBoxLocked($recipient->id))
      {
        return;
      }

      $subject     = sprintf($language->_('PLG_WORKFLOW_NOTIFICATION_ON_TRANSITION_SUBJECT'), $title);
      $messageText = sprintf(
          $language->_('PLG_WORKFLOW_NOTIFICATION_ON_TRANSITION_MSG'),
          $title,
          $transitionName,
          $user,
          $toStage
      );
      $messageText .= '<br>' . $extraText;

      $message = [
          'id'         => 0,
          'user_id_to' => $recipient->id,
          'subject'    => $subject,
          'message'    => $messageText,
      ];

      $modelMessage->save($message);
    }
  }

  /**
   * Check if the message box is locked
   *
   * @param   int     $userId  The user ID which must be checked
   *
   * @return   bool   Return status of message box is locked
   *
   * @since   4.0.0
   */
  protected function isMessageBoxLocked(int $userId): bool
  {
    if(empty($userId))
    {
      return false;
    }

    // Check for locked inboxes would be better to have _cdf settings in the user_object or a filter in users model
    $query = $this->db->getQuery(true);

    $query->select($this->db->quoteName('user_id'))
        ->from($this->db->quoteName('#__messages_cfg'))
        ->where($this->db->quoteName('user_id') . ' = ' . $userId)
        ->where($this->db->quoteName('cfg_name') . ' = ' . $this->db->quote('locked'))
        ->where($this->db->quoteName('cfg_value') . ' = 1');

    return (int) $this->db->setQuery($query)->loadResult() === $userId;
  }
}
