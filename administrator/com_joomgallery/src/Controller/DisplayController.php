<?php

/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Input\Input;
use Joomla\CMS\Factory;

/**
 * Joomgallery master display controller.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class DisplayController extends BaseController
{
  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   */
  var $component;

  /**
   * The context for storing internal data, e.g. record.
   *
   * @var    string
   * @since  1.6
   */
  protected $context;

	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $default_view = 'images';

  /**
   * Constructor.
   *
   * @param   array                $config   An optional associative array of configuration settings.
   *                                         Recognized key values include 'name', 'default_task', 'model_path', and
   *                                         'view_path' (this list is not meant to be comprehensive).
   * @param   MVCFactoryInterface  $factory  The factory.
   * @param   CMSApplication       $app      The Application for the dispatcher
   * @param   Input                $input    Input
   *
   * @since   3.0
   */
  public function __construct($config = array(), MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
  {
    parent::__construct($config, $factory, $app, $input);

    // Guess the context based on the view input variable
    if (empty($this->context))
    {
      // Get view variable
      $view  = Factory::getApplication()->input->get('view', $this->default_view);

      // Conduct the context
      $this->context = _JOOM_OPTION.'.'.$view.'.display';
    }

    $this->component = $this->app->bootComponent(_JOOM_OPTION);
  }

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link InputFilter::clean()}.
	 *
	 * @return  BaseController|boolean  This object to support chaining.
	 *
	 * @since   4.0.0
	 */
	public function display($cachable = false, $urlparams = array())
	{
    // Before execution of the task
    if(!empty($task))
    {
      $this->component->msgUserStateKey = 'com_joomgallery.'.$task.'.messages';
    }
    
    if(!$this->component->isRawTask($this->context))
    {
      // Get messages from session
      $this->component->msgFromSession();
    }

		$res = parent::display();

    // After execution of the task
    if(!$this->component->isRawTask($this->context))
    {
      // Print messages from session
      if(!$this->component->msgWithhold && $res->component->error)
      {
        $this->component->printError();
      }
      elseif(!$this->component->msgWithhold)
      {
        $this->component->printWarning();
        $this->component->printDebug();
      }
    }

    return $res;
	}
}
