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

/**
 * Mail Template Messenger Class
 *
 * Provides methods to send template based email messages in the gallery.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class MailTemplateMessenger implements MessengerInterface
{
  use ServiceTrait;

  /**
   * Language the message is written
   * 
   * @var Language
   */
  public $language = null;

  /**
   * Template id to use
   * 
   * @var Language
   */
  public $template = 'com_jomgallery.newimage';

  /**
   * List with variables available in the template
   * 
   * @var array
   */
  public $data = array();

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct()
  {
    $this->jg       = Factory::getApplication()->bootComponent('com_joomgallery');
    $this->language = Factory::getApplication()->getLanguage();

    $this->addTemplateData(array('sitename' => $app->get('sitename'), 'siteurl' => Uri::root()));
  }

  /**
   * Send a template based email.
   *
   * @param   mixed    $recipient    List of users or email adresses receiving the message
   *
   * @return  bool        true on success, false otherwise
   *
   * @since   4.0.0
   * @throws  \PHPMailer\PHPMailer\Exception
   */
  public function send($recipients): void
  {
    if(empty(MailTemplate::getTemplate($this->template, $this->language->getTag())))
    {
      $this->jg->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_MAIL_INVALID_TEMPLATE', $this->template));
    }

    $mailer = new MailTemplate($this->template, $this->language->getTag());
    $mailer->addTemplateData($this->data);
    $mailer->addRecipients($recipients);
    
    try
    {
      $mailer->send();
    }
    catch(MailDisabledException | phpMailerException $exception)
    {
      try
      {
        Log::add(Text::_($exception->getMessage()), Log::WARNING, 'jerror');

        $this->jg->setError(Text::_('COM_JOOMGALLERY_ERROR_MAIL_FAILED'));

        return false;
      }
      catch(\RuntimeException $exception)
      {
        $this->addWarning(Text::_('COM_JOOMGALLERY_ERROR_MAIL_FAILED'));

        return false;
      }
    }
  }

  /**
   * Method to add one ore more recipients
   *
   * @param   array   $recipients  An array of email adresses or a single one as a string
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  protected function addRecipients($recipients)
  {
    if(is_array($recipients))
    {
      foreach ($recipients as $recipient)
      {
        $this->mailer->addRecipient($recipient);
      }
    }
    else
    {
      $this->mailer->addRecipient($recipient);
    }
  }

  /**
   * Method to add one ore more variables to be used in the template
   *
   * @param   array   $data   An array of key value pairs with variables to be used in the template
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  public function addTemplateData($data)
  {
    if(is_array($data))
    {
      $this->data = array_merge($this->data, $data);
    }
    else
    {
      array_push($this->data, $data);
    }
  }
}
