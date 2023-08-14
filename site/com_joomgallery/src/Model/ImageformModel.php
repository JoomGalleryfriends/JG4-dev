<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\MVC\Model\FormModel;
use \Joomla\Registry\Registry;

/**
 * Joomgallery model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageformModel extends FormModel
{
  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   */
  protected $component;

  /**
   * Item object
   *
   * @access  protected
   * @var     object
   */
	protected $item = null;

  /**
	 * Constructor
	 *
	 * @param   array                $config   An array of configuration options (name, state, dbo, table_path, ignore_request).
	 * @param   MVCFactoryInterface  $factory  The factory.
	 *
	 * @since   3.0
	 * @throws  \Exception
	 */
	public function __construct($config = [], $factory = null)
	{
		parent::__construct($config, $factory);

		// JoomGallery extension class
		$this->component = Factory::getApplication()->bootComponent(_JOOM_OPTION);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 *
	 * @throws  Exception
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('com_joomgallery');

		// Load state from the request userState on edit or from the passed variable on default
		if(Factory::getApplication()->input->get('layout') == 'edit')
		{
			$id = Factory::getApplication()->getUserState('com_joomgallery.edit.image.id');
		}
		else
		{
			$id = Factory::getApplication()->input->get('id');
			Factory::getApplication()->setUserState('com_joomgallery.edit.image.id', $id);
		}

		$this->setState('image.id', $id);

		// Load the parameters.
		$params       = $app->getParams();
		$params_array = $params->toArray();

		if(isset($params_array['item_id']))
		{
			$this->setState('image.id', $params_array['item_id']);
		}

		$this->setState('parameters.component', $params);

    // Load the configs from config service
		$this->component->createConfig('com_joomgallery.image', $id, true);
		$configArray = $this->component->getConfig()->getProperties();
		$configs     = new Registry($configArray);

		$this->setState('parameters.configs', $configs);
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer $id The id of the object to get.
	 *
	 * @return  Object|boolean Object on success, false on failure.
	 *
	 * @throws  Exception
	 */
	public function getItem($id = null)
	{
		if($this->item === null)
		{
			$this->item = false;

			if(empty($id))
			{
				$id = $this->getState('image.id');
			}

			// Attempt to load the item
			$adminModel = $this->component->getMVCFactory()->createModel('image', 'administrator');
			$this->item = $adminModel->getItem($id);

			if(empty($this->item))
			{
				throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 404);
			}
		}

		return $this->item;
	}

  /**
	 * Method to get parameters from model state.
	 *
	 * @return  array   List of parameters
	 */
	public function getParams()
	{
		$params = array('component' => $this->getState('parameters.component'),
										'menu'      => $this->getState('parameters.menu'),
									  'configs'   => $this->getState('parameters.configs')
									);

		return $params;
	}

  /**
	 * Method to get the params object.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @throws Exception
	 */
	public function getAcl()
	{
		$this->component->createAccess();

		return $this->component->getAccess();
	}

	/**
	 * Method to get the table
	 *
	 * @param   string $type   Name of the Table class
	 * @param   string $prefix Optional prefix for the table class name
	 * @param   array  $config Optional configuration array for Table object
	 *
	 * @return  Table|boolean Table if found, boolean false on failure
	 */
	public function getTable($type = 'Image', $prefix = 'Administrator', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

	/**
	 * Method to check in an item.
	 *
	 * @param   integer $id The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function checkin($id = null)
	{
		// Get the id.
		$id = (!empty($id)) ? $id : (int) $this->getState('image.id');
		
		if($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Attempt to check the row in.
			if(method_exists($table, 'checkin'))
			{
				if(!$table->checkin($id))
				{
					return false;
				}
			}
		}

		return true;		
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   integer $id The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function checkout($id = null)
	{
		// Get the user id.
		$id = (!empty($id)) ? $id : (int) $this->getState('image.id');
		
		if($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Get the current user object.
			$user = Factory::getUser();

			// Attempt to check the row out.
			if(method_exists($table, 'checkout'))
			{
				if(!$table->checkout($user->get('id'), $id))
				{
					return false;
				}
			}
		}

		return true;		
	}

	/**
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array   $data     An optional array of data for the form to interogate.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form    A Form object on success, false on failure
	 *
	 * @since   4.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_joomgallery.image', 'imageform', array('control'   => 'jform', 'load_data' => $loadData));

		if(empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The default data is an empty array.
	 * @since   4.0.0
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_joomgallery.edit.image.data', array());

		if(empty($data))
		{
			$data = $this->getItem();
		}

		if($data)
		{			
      // Support for multiple or not foreign key field: robots
      $array = array();

      foreach((array) $data->robots as $value)
      {
        if(!is_array($value))
        {
          $array[] = $value;
        }
      }

      if(!empty($array))
      {
        $data->robots = $array;
      }

			return $data;
		}

		return array();
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data
	 *
	 * @return  bool
	 *
	 * @throws  Exception
	 * @since   4.0.0
	 */
	public function save($data)
	{
		$id    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('image.id');
		$state = (!empty($data['state'])) ? 1 : 0;
		$user  = Factory::getUser();
		
		if($id)
		{
			// Check the user can edit this item
			$authorised = $user->authorise('core.edit', 'com_joomgallery.image.' . $id) || $authorised = $user->authorise('core.edit.own', 'com_joomgallery.image.' . $id);
		}
		else
		{
			// Check the user can create new items in this section
			$authorised = $user->authorise('core.create', 'com_joomgallery');
		}

		if($authorised !== true)
		{
			throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$table = $this->getTable();		

		if($table->save($data) === true)
		{
			return $table->id;
		}
		else
		{
			return false;
		}		
	}

	/**
	 * Method to delete data
	 *
	 * @param   int $pk Item primary key
	 *
	 * @return  int  The id of the deleted item
	 *
	 * @throws  Exception
	 *
	 * @since   4.0.0
	 */
	public function delete($id)
	{
		$user = Factory::getUser();
		
		if(empty($id))
		{
			$id = (int) $this->getState('image.id');
		}

		if($id == 0 || $this->getItem($id) == null)
		{
			throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_DOESNT_EXIST'), 404);
		}

		if($user->authorise('core.delete', 'com_joomgallery.image.' . $id) !== true)
		{
			throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$table = $this->getTable();

		if($table->delete($id) !== true)
		{
			throw new \Exception(Text::_('JERROR_FAILED'), 501);
		}

		return $id;		
	}
}
