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
use \Joomla\Registry\Registry;
use \Joomgallery\Component\Joomgallery\Administrator\Model\CategoriesModel as AdminCategoriesModel;

/**
 * Methods supporting a list of category records.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoriesModel extends AdminCategoriesModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see    JController
	 * @since  4.0.0
	 */
	public function __construct($config = array())
	{
		if(empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'lft', 'a.lft',
				'rgt', 'a.rgt',
				'level', 'a.level',
				'path', 'a.path',
				'in_hidden', 'a.in_hidden',
				'title', 'a.title',
				'alias', 'a.alias',
				'parent_id', 'a.parent_id',
				'parent_title', 'a.parent_title',
				'published', 'a.published',
				'access', 'a.access',
				'language', 'a.language',
				'description', 'a.description',
				'hidden', 'a.hidden',
				'created_time', 'a.created_time',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'modified_time', 'a.modified_time',
				'id', 'a.id',
				'img_count', 'a.img_count',
				'child_count', 'a.child_count'
			);
		}

		parent::__construct($config);

		// JoomGallery extension class
		$this->component = Factory::getApplication()->bootComponent(_JOOM_OPTION);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   4.0.0
	 */
	protected function populateState($ordering = 'a.lft', $direction = 'ASC')
	{
		// List state information.
		parent::populateState($ordering, $direction);

		// Load the componen parameters.
		$params       = Factory::getApplication('com_joomgallery')->getParams();
		$params_array = $params->toArray();

		if(isset($params_array['item_id']))
		{
			$this->setState('category.id', $params_array['item_id']);
		}

		$this->setState('parameters.component', $params);

		// Load the configs from config service
		$this->component->createConfig('com_joomgallery');
		$configArray = $this->component->getConfig()->getProperties();
		$configs     = new Registry($configArray);

		$this->setState('parameters.configs', $configs);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   4.0.0
	 */
	protected function getListQuery()
	{
    $query = parent::getListQuery();

    return $query;
	}

	/**
	 * Method to get an array of data items
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

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
	 * Overrides the default function to check Date fields format, identified by
	 * "_dateformat" suffix, and erases the field if it's not correct.
	 *
	 * @return void
	 */
	protected function loadFormData()
	{
		return parent::loadFormData();
	}
}
