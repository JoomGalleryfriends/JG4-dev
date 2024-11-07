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

use \Joomla\Uri\Uri;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Menu\MenuItem;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface;

/**
 * Parent HTML View Class for JoomGallery
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class JoomGalleryView extends BaseHtmlView
{
  /**
   * The active document object
   *
   * @access  public
   * @var     Document
   *
   */
  public $document;

  /**
   * The model state
   *
   * @var  object
   */
  protected $state;

  /**
   * Joomla\CMS\Application\AdministratorApplication
   *
   * @access  protected
   * @var     object
   */
  protected $app;

  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   */
  protected $component;

  /**
   * JoomGallery access service
   *
   * @access  protected
   * @var     Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface
   */
  protected $acl = null;

  /**
   * JUser object, holds the current user data
   *
   * @access  protected
   * @var     object
   */
  protected $user;

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
    $this->user      = $this->component->getMVCFactory()->getIdentity();
    $this->document  = $this->app->getDocument();

    if(\strpos($this->component->version, 'dev'))
    {
      // We are dealing with a development version (alpha or beta)
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_NOTE_DEVELOPMENT_VERSION'), 'warning');
    }
  }

  /**
	 * Method to get the access service class.
	 *
	 * @return  AccessInterface   Object on success, false on failure.
   * @since   4.0.0
	 */
	public function getAcl(): AccessInterface
	{
    // Create access service
    if(\is_null($this->acl))
    {
      $this->component->createAccess();
      $this->acl = $this->component->getAccess();
    }

		return $this->acl;
	}

  /**
	 * Check if state is set
	 *
	 * @param   mixed  $state  State
	 *
	 * @return bool
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}

  
  /**
	 * Checks if the active menuitem corresponds to the view
	 *
	 * @param    MenuItem  $menu  The active menu item
	 *
	 * @return   bool      True if the active manuitem corresponds to the view
	 */
  protected function isMenuCurrentView($menu = null)
  {
    if(\is_null($menu))
    {
      $menu = $this->app->getMenu()->getActive();
    }

    $menu_link = new Uri($menu->link);

    if( $menu_link->getVar('option') == _JOOM_OPTION &&
        $menu_link->getVar('view') == $this->getName()
      )
    {
      if($menu_link->getVar('id', 0) && \property_exists($this->item, 'id'))
      {
        return $menu_link->getVar('id', 0) == $this->item->id;
      }

      return true;
    }

    return false;
  }
}
