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
use \Joomla\CMS\Log\Log;
use \Joomla\CMS\Mail\Exception\MailDisabledException;
use \Joomla\CMS\Mail\MailTemplate;
use \PHPMailer\PHPMailer\Exception as phpMailerException;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessengerInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessageInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\Messenger;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MailTemplateMessage;

/**
 * Mail Template Messenger Class
 *
 * Provides methods to send template based email messages in the gallery.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class MailTemplateMessenger extends Messenger implements MessengerInterface
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
    $this->message = New MailTemplateMessage();
  }

  /**
   * Send a template based email.
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
   * @throws  \PHPMailer\PHPMailer\Exception
   */
  protected function send(User $recipient, string $user, string $title, string $transitionName, string $toStage, Language $language, string $extraText): void
  {
    $data                   = [];
    $data['siteurl']        = Uri::base();
    $data['title']          = $title;
    $data['user']           = $user;
    $data['transitionName'] = $transitionName;
    $data['toStage']        = $toStage;
    $data['extraText']      = $extraText;

    $mailer = new MailTemplate('plg_workflow_notification.mail', $this->app->getLanguage()->getTag());
    $mailer->addTemplateData($data);
    $mailer->addRecipient($recipient->email);
    
    try
    {
      $mailer->send();
    }
    catch(MailDisabledException | phpMailerException $exception)
    {
      try
      {
        Log::add(Text::_($exception->getMessage()), Log::WARNING, 'jerror');

        $this->setError(Text::_('COM_MESSAGES_ERROR_MAIL_FAILED'));

        return false;
      }
      catch(\RuntimeException $exception)
      {
        Factory::getApplication()->enqueueMessage(Text::_($exception->errorMessage()), 'warning');

        $this->setError(Text::_('COM_MESSAGES_ERROR_MAIL_FAILED'));

        return false;
      }
    }
  }
}
