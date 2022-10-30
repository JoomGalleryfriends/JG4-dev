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
use \Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Model\JoomListModel;

/**
 * Methods supporting a list of Configs records.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigsModel extends JoomListModel
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
				'id', 'a.id',
				'published', 'a.published',
				'ordering', 'a.ordering',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'jg_pathftpupload', 'a.jg_pathftpupload',
				'jg_wmfile', 'a.jg_wmfile',
				'jg_use_real_paths', 'a.jg_use_real_paths',
				'jg_checkupdate', 'a.jg_checkupdate',
				'jg_listbox_max_items', 'a.jg_listbox_max_items',
				'title', 'a.title',
				'jg_replaceinfo', 'a.jg_replaceinfo',
				'jg_replaceshowwarning', 'a.jg_replaceshowwarning',
				'jg_useorigfilename', 'a.jg_useorigfilename',
				'jg_uploadorder', 'a.jg_uploadorder',
				'jg_filenamenumber', 'a.jg_filenamenumber',
				'jg_delete_original', 'a.jg_delete_original',
				'jg_imgprocessor', 'a.jg_imgprocessor',
				'jg_fastgd2creation', 'a.jg_fastgd2creation',
				'jg_impath', 'a.jg_impath',
				'jg_staticprocessing', 'a.jg_staticprocessing',
				'jg_dynamicprocessing', 'a.jg_dynamicprocessing',
				'jg_msg_upload_type', 'a.jg_msg_upload_type',
				'jg_msg_upload_recipients', 'a.jg_msg_upload_recipients',
				'jg_msg_download_type', 'a.jg_msg_download_type',
				'jg_msg_download_recipients', 'a.jg_msg_download_recipients',
				'jg_msg_zipdownload', 'a.jg_msg_zipdownload',
				'jg_msg_comment_type', 'a.jg_msg_comment_type',
				'jg_msg_comment_recipients', 'a.jg_msg_comment_recipients',
				'jg_msg_comment_toowner', 'a.jg_msg_comment_toowner',
				'jg_msg_report_type', 'a.jg_msg_report_type',
				'jg_msg_report_recipients', 'a.jg_msg_report_recipients',
				'jg_msg_report_toowner', 'a.jg_msg_report_toowner',
				'jg_msg_rejectimg_type', 'a.jg_msg_rejectimg_type',
				'jg_msg_global_from', 'a.jg_msg_global_from',
				'group_id', 'a.group_id',
				'jg_userspace', 'a.jg_userspace',
				'jg_approve', 'a.jg_approve',
				'jg_maxusercat', 'a.jg_maxusercat',
				'jg_maxuserimage', 'a.jg_maxuserimage',
				'jg_maxuserimage_timespan', 'a.jg_maxuserimage_timespan',
				'jg_maxfilesize', 'a.jg_maxfilesize',
				'jg_newpiccopyright', 'a.jg_newpiccopyright',
				'jg_uploaddefaultcat', 'a.jg_uploaddefaultcat',
				'jg_useruploadsingle', 'a.jg_useruploadsingle',
				'jg_maxuploadfields', 'a.jg_maxuploadfields',
				'jg_useruploadajax', 'a.jg_useruploadajax',
				'jg_useruploadbatch', 'a.jg_useruploadbatch',
				'jg_special_upload', 'a.jg_special_upload',
				'jg_newpicnote', 'a.jg_newpicnote',
				'jg_redirect_after_upload', 'a.jg_redirect_after_upload',
				'jg_download', 'a.jg_download',
				'jg_download_hint', 'a.jg_download_hint',
				'jg_downloadfile', 'a.jg_downloadfile',
				'jg_downloadwithwatermark', 'a.jg_downloadwithwatermark',
				'jg_showrating', 'a.jg_showrating',
				'jg_maxvoting', 'a.jg_maxvoting',
				'jg_ratingcalctype', 'a.jg_ratingcalctype',
				'jg_votingonlyonce', 'a.jg_votingonlyonce',
				'jg_report_images', 'a.jg_report_images',
				'jg_report_hint', 'a.jg_report_hint',
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

    // Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}

    $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		// List state information.
		parent::populateState($ordering, $direction);
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
		$id .= ':' . $this->getState('filter.published');

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
    $query->from($db->quoteName('#__joomgallery_configs', 'a'));

		// Join over the users for the checked out user
		$query->select($db->quoteName('uc.name', 'uEditor'));
		$query->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

		// Join over the user field 'created_by'
		$query->select(array($db->quoteName('ua.name', 'created_by'), $db->quoteName('ua.id', 'created_by_id')));
    $query->join('LEFT', $db->quoteName('#__users', 'ua'), $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by'));
		
    // Join over the user field 'modified_by'
		$query->select(array($db->quoteName('um.name', 'modified_by'), $db->quoteName('um.id', 'modified_by_id')));
    $query->join('LEFT', $db->quoteName('#__users', 'um'), $db->quoteName('um.id') . ' = ' . $db->quoteName('a.modified_by'));

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
					'(' . $db->quoteName('a.title') . ' LIKE :search1)'
				)
					->bind([':search1'], $search);
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

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'id');
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

		foreach($items as $oneItem)
		{
			if(isset($oneItem->group_id))
			{
				$values    = explode(',', $oneItem->group_id);
				$textValue = array();

				foreach($values as $value)
				{
					if(!empty($value))
					{
						$db = Factory::getDbo();
						$query = "SELECT id, title FROM #__usergroups HAVING id LIKE '" . $value . "'";
						$db->setQuery($query);
						$results = $db->loadObject();

						if($results)
						{
							$textValue[] = $results->title;
						}
					}
				}

				$oneItem->group_id = !empty($textValue) ? implode(', ', $textValue) : $oneItem->group_id;
			}
		}

		return $items;
	}
}
