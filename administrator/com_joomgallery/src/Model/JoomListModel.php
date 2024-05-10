<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\Registry\Registry;
use \Joomla\CMS\MVC\Model\ListModel;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface;

/**
 * Base model class for JoomGallery list of items
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class JoomListModel extends ListModel
{
  /**
   * Joomla application class
   *
   * @access  protected
   * @var     Joomla\CMS\Application\AdministratorApplication
   */
  protected $app;

  /**
   * Joomla user object
   *
   * @access  protected
   * @var     Joomla\CMS\User\User
   */
  protected $user;

  /**
   * JoomGallery extension calss
   *
   * @access  protected
   * @var     Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   */
  protected $component;

  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'image';

  /**
   * Constructor
   * 
   * @param   array  $config  An optional associative array of configuration settings.
   *
   * @return  void
   * @since   4.0.0
   */
  function __construct($config = array())
  {
    parent::__construct($config);

    $this->app       = Factory::getApplication('administrator');
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
    $this->user      = Factory::getUser();
  }

  /**
	 * Method to get parameters from model state.
	 *
	 * @return  Registry[]   List of parameters
   * @since   4.0.0
	 */
	public function getParams(): array
	{
		$params = array('component' => $this->getState('parameters.component'),
										'menu'      => $this->getState('parameters.menu'),
									  'configs'   => $this->getState('parameters.configs')
									);

		return $params;
	}

	/**
	 * Method to get the access service class.
	 *
	 * @return  AccessInterface   Object on success, false on failure.
   * @since   4.0.0
	 */
	public function getAcl(): AccessInterface
	{
		$this->component->createAccess();

		return $this->component->getAccess();
	}

  /**
	 * Method to load component specific parameters into model state.
	 *
	 * @return  void
   * @since   4.0.0
	 */
  protected function loadComponentParams()
  {
    // Load the componen parameters.
		$params       = Factory::getApplication('com_joomgallery')->getParams();
		$params_array = $params->toArray();

		if(isset($params_array['item_id']))
		{
			$this->setState($this->type.'.id', $params_array['item_id']);
		}

		$this->setState('parameters.component', $params);

		// Load the configs from config service
		$this->component->createConfig('com_joomgallery');
		$configArray = $this->component->getConfig()->getProperties();
		$configs     = new Registry($configArray);

		$this->setState('parameters.configs', $configs);
  }
}
