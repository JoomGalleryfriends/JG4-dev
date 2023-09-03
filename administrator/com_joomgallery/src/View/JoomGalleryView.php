<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

/**
 * Parent HTML View Class for JoomGallery
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class JoomGalleryView extends BaseHtmlView
{
  /**
   * Joomla\CMS\Application\AdministratorApplication
   *
   * @access  protected
   * @var     object
   */
  var $app;

  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   */
  var $component;

  /**
   * JUser object, holds the current user data
   *
   * @access  protected
   * @var     object
   */
  var $user;

  /**
   * JDocument object
   *
   * @access  protected
   * @var     object
   */
  var $document;

  /**
   * Constructor
   *
   * @access  protected
   * @return  void
   * @since   1.5.5
   */
  function __construct($config = array())
  {
    parent::__construct($config);

    $this->app       = Factory::getApplication('administrator');
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
    $this->user      = Factory::getUser();
    $this->document  = Factory::getDocument();

    if(\strpos($this->component->version, 'dev'))
    {
      // We are dealing with a development version (alpha or beta)
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_NOTE_DEVELOPMENT_VERSION'), 'warning');
    }
  }
}
