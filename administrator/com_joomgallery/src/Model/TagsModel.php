<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;
use \Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Model\JoomListModel;

/**
 * Methods supporting a list of Tags records.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class TagsModel extends JoomListModel
{
	/**
	* Constructor.
	*
	* @param   array  $config  An optional associative array of configuration settings.
	*
	* @see        JController
	* @since      1.6
	*/
	public function __construct($config = array())
	{
		if(empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'ordering', 'a.ordering',
				'title', 'a.title',
				'published', 'a.published',
				'access', 'a.access',
				'language', 'a.language',
				'description', 'a.description',
				'created_time', 'a.created_time',
				'created_by', 'a.created_by',
				'modified_time', 'a.modified_time',
				'modified_by', 'a.modified_by',
				'id', 'a.id',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function populateState($ordering = 'a.id', $direction = 'ASC')
	{
    $app = Factory::getApplication();

    $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

    $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

    $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		$formSubmited = $app->input->post->get('form_submited');

    // Gets the value of a user state variable and sets it in the session
		$this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');

    if ($formSubmited)
		{
			$access = $app->input->post->get('access');
			$this->setState('filter.access', $access);
		}

		// List state information.
		parent::populateState($ordering, $direction);

    // Force a language
		if (!empty($forcedLanguage))
		{
			$this->setState('filter.language', $forcedLanguage);
			$this->setState('filter.forcedLanguage', $forcedLanguage);
		}

		// $context = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		// $this->setState('filter.search', $context);

		// // Split context into component and optional section
		// $parts = FieldsHelper::extract($context);

		// if($parts)
		// {
		// 	$this->setState('filter.component', $parts[0]);
		// 	$this->setState('filter.section', $parts[1]);
		// }
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string A store id.
	 *
	 * @since   4.0.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . serialize($this->getState('filter.access'));
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . serialize($this->getState('filter.created_by'));
		$id .= ':' . $this->getState('filter.language');

		return parent::getStoreId($id);
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
		// Create a new query object. 
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
    $query->select($this->getState('list.select', 'DISTINCT a.*'));
    $query->from($db->quoteName('#__joomgallery_tags', 'a'));

		// Join over the users for the checked out user
    $query->select($db->quoteName('uc.name', 'uEditor'));
    $query->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

		// Join over the access level field 'access'
    $query->select($db->quoteName('access.title', 'access'));
    $query->join('LEFT', $db->quoteName('#__viewlevels', 'access'), $db->quoteName('access.id') . ' = ' . $db->quoteName('a.access'));

		// Join over the user field 'created_by'
    $query->select(array($db->quoteName('ua.name', 'created_by'), $db->quoteName('ua.id', 'created_by_id')));
    $query->join('LEFT', $db->quoteName('#__users', 'ua'), $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by'));

		// Join over the user field 'modified_by'
    $query->select(array($db->quoteName('um.name', 'modified_by'), $db->quoteName('um.id', 'modified_by_id')));
    $query->join('LEFT', $db->quoteName('#__users', 'um'), $db->quoteName('um.id') . ' = ' . $db->quoteName('a.modified_by'));

    // Join over the language fields 'language_title' and 'language_image'
		$query->select(array($db->quoteName('l.title', 'language_title'), $db->quoteName('l.image', 'language_image')));
		$query->join('LEFT', $db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));

    // Count Items
		$subQueryCountTaggedItems = $db->getQuery(true);
		$subQueryCountTaggedItems
			->select('COUNT(' . $db->quoteName('tags_ref.imgid') . ')')
			->from($db->quoteName('#__joomgallery_tags_ref', 'tags_ref'))
			->where($db->quoteName('tags_ref.tagid') . ' = ' . $db->quoteName('a.id'));
		$query->select('(' . (string) $subQueryCountTaggedItems . ') AS ' . $db->quoteName('countTaggedItems'));

    // Filter by access level.
		$filter_access = $this->state->get("filter.access");
    
    if(is_numeric($filter_access))
		{
			$filter_access = (int) $filter_access;
			$query->where($db->quoteName('a.access') . ' = :access')
				    ->bind(':access', $filter_access, ParameterType::INTEGER);
		}
		elseif (is_array($filter_access))
		{
			$filter_access = ArrayHelper::toInteger($filter_access);
			$query->whereIn($db->quoteName('a.access'), $filter_access);
		}

		// Filter by search
		$search = $this->getState('filter.search');

		if(!empty($search))
		{
			if(stripos($search, 'id:') === 0)
			{
				$search = (int) substr($search, 3);
				$query->where($db->quoteName('a.id') . ' = :search')
					->bind(':search', $search, ParameterType::INTEGER);
			}
			else
			{
        $search = '%' . str_replace(' ', '%', trim($search)) . '%';
				$query->where(
					'(' . $db->quoteName('a.title') . ' LIKE :search1 OR ' . $db->quoteName('a.alias') . ' LIKE :search2'
						. ' OR ' . $db->quoteName('a.description') . ' LIKE :search3)'
				)
					->bind([':search1', ':search2', ':search3'], $search);
			}
		}
 
    // Filter by published state
		$published = (string) $this->getState('filter.published');

		if($published !== '*')
		{
			if(is_numeric($published))
			{
				$state = (int) $published;
				$query->where($db->quoteName('a.published') . ' = :state')
					->bind(':state', $state, ParameterType::INTEGER);
			}
		}

    // Filter on the language.
		if($language = $this->getState('filter.language'))
		{
			$query->where($db->quoteName('a.language') . ' = :language')
				->bind(':language', $language);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.id'); 
		$orderDirn = $this->state->get('list.direction', 'ASC');

		// if($orderCol && $orderDirn)
		// {
    //   $query->order($db->escape($orderCol . ' ' . $orderDirn));
		// }
    // else
    // {
      $query->order($db->escape($this->state->get('list.fullordering', 'a.id ASC')));
    // }

		return $query;
	}

	/**
	 * Get an array of data items
	 *
	 * @return mixed Array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}
}
