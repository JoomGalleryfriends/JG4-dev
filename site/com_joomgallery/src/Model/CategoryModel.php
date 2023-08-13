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
use \Joomla\CMS\MVC\Model\ItemModel;
use \Joomla\Registry\Registry;
use Joomla\CMS\Language\Multilanguage;

/**
 * Joomgallery model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryModel extends ItemModel
{
	/**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   */
  var $component;

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
	 * @throws Exception
	 */
	protected function populateState()
	{
		$app  = Factory::getApplication('com_joomgallery');
		$user = Factory::getUser();

		// Check published state
		if((!$user->authorise('core.edit.state', 'com_joomgallery')) && (!$user->authorise('core.edit', 'com_joomgallery')))
		{
			$this->setState('filter.published', 1);
			$this->setState('filter.archived', 2);
		}

		// Load state from the request userState on edit or from the passed variable on default
		if(Factory::getApplication()->input->get('layout') == 'edit')
		{
			$id = Factory::getApplication()->getUserState('com_joomgallery.edit.category.id');
		}
		else
		{
			$id = Factory::getApplication()->input->get('id');
			Factory::getApplication()->setUserState('com_joomgallery.edit.category.id', $id);
		}

		$this->setState('category.id', $id);

		// Load the componen parameters.
		$params       = $app->getParams();
		$params_array = $params->toArray();

		if(isset($params_array['item_id']))
		{
			$this->setState('category.id', $params_array['item_id']);
		}

		$this->setState('parameters.component', $params);

		// Load the configs from config service
		$this->component->createConfig('com_joomgallery.category', $id, true);
		$configArray = $this->component->getConfig()->getProperties();
		$configs     = new Registry($configArray);

		$this->setState('parameters.configs', $configs);
	}

	/**
	 * Method to get the category item object.
	 *
	 * @param   integer  $id   The id of the object to get.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @throws \Exception
	 */
	public function getItem($id = null)
	{
		if($this->_item === null)
		{
			$this->_item = false;

			if(empty($id))
			{
				$id = $this->getState('category.id');
			}

			// Attempt to load the item
			$adminModel = $this->component->getMVCFactory()->createModel('category', 'administrator');
			$this->_item = $adminModel->getItem($id);

			if(empty($this->_item))
			{
				throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 404);
			}
		}

		// Add created by name
		if(isset($this->_item->created_by))
		{
			$this->_item->created_by_name = Factory::getUser($this->_item->created_by)->name;
		}

		// Add modified by name
		if(isset($this->_item->modified_by))
		{
			$this->_item->modified_by_name = Factory::getUser($this->_item->modified_by)->name;
		}

		// Delete unnessecary properties
		$toDelete = array('asset_id', 'password', 'params');
		foreach($toDelete as $property)
		{
			unset($this->_item->{$property});
		}

		// Get child items
		$this->_item->children = $adminModel->getChildren($this->_item->id);

		return $this->_item;
	}

  /**
	 * Method to get the children categories.
	 *
	 * @return  array|false    Array of children on success, false on failure.
	 *
	 * @throws Exception
	 */
  public function getChildren()
  {
    if($this->_item === null)
		{
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load categories list model
    $listModel = $this->component->getMVCFactory()->createModel('categories', 'administrator');
    $listModel->getState();
    
    // Select fields to load
    $fields = array('id', 'alias', 'title', 'description', 'thumbnail');
    $fields = $this->addColumnPrefix('a', $fields);
    $listModel->setState('list.select', $fields);

    // Get current user
    $user = Factory::getUser();

    // Apply filters
    $listModel->setState('filter.category', $this->_item->id);
    $listModel->setState('filter.level', 2);
    $listModel->setState('filter.showself', 0);
    $listModel->setState('filter.access', $user->getAuthorisedViewLevels());
    $listModel->setState('filter.published', 1);
    $listModel->setState('filter.showhidden', 0);
    $listModel->setState('filter.showempty', 0);

    if(Multilanguage::isEnabled())
    {
      $listModel->setState('filter.language', $this->_item->language);
    }

    // Apply ordering
    $listModel->setState('list.fullordering', 'a.lft ASC');

    // Get children
    $items = $listModel->getItems();

    if(!empty($listModel->getError()))
    {
      $this->setError($listModel->getError());
    }

    return $items;
  }

  /**
	 * Method to get the images in this category.
	 *
	 * @return  array|false    Array of images on success, false on failure.
	 *
	 * @throws Exception
	 */
  public function getImages()
  {
    if($this->_item === null)
		{
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load images list model
    $listModel = $this->component->getMVCFactory()->createModel('images', 'administrator');
    $listModel->getState();

    // Select fields to load
    $fields = array('id', 'alias', 'imgtitle', 'imgtext', 'imgauthor', 'imgdate', 'hits', 'imgvotes', 'imgvotesum');
    $fields = $this->addColumnPrefix('a', $fields);
    $listModel->setState('list.select', $fields);

    // Get current user
    $user = Factory::getUser();

    // Apply filters
    $listModel->setState('filter.category', $this->_item->id);
    $listModel->setState('filter.access', $user->getAuthorisedViewLevels());
    $listModel->setState('filter.published', 1);
    $listModel->setState('filter.showunapproved', 0);
    $listModel->setState('filter.showhidden', 0);

    if(Multilanguage::isEnabled())
    {
      $listModel->setState('filter.language', $this->_item->language);
    }

    // Apply ordering
    $listModel->setState('list.fullordering', 'a.id ASC');

    // Get images
    $items = $listModel->getItems();

    if(!empty($listModel->getError()))
    {
      $this->setError($listModel->getError());
    }

    return $items;
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
	 * Get an instance of Table class
	 *
	 * @param   string $type   Name of the Table class to get an instance of.
	 * @param   string $prefix Prefix for the table class name. Optional.
	 * @param   array  $config Array of configuration values for the Table object. Optional.
	 *
	 * @return  Table|bool Table if success, false on failure.
	 */
	public function getTable($type = 'Category', $prefix = 'Administrator', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

	/**
	 * Method to delete an item
	 *
	 * @param   int $id Element id
	 *
	 * @return  bool
	 */
	public function delete($id)
	{
		$table = $this->getTable();

		return $table->delete($id);
	}

  /**
	 * Method to add a prefix to a list of field names
	 *
	 * @param   string  $prefix   The prefix to apply
   * @param   array   $fields   List of fields
	 *
	 * @return  array   List of fields with applied prefix
	 */
  protected function addColumnPrefix(string $prefix, array $fields): array
  {
    foreach($fields as $key => $field)
    {
      $field = (string) $field;

      if(\strpos($field, $prefix.'.') === false)
      {
        $fields[$key] = $prefix . '.' . $field;
      }
    }

    return $fields;
  }
}
