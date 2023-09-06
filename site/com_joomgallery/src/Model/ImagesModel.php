<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\Registry\Registry;
use \Joomgallery\Component\Joomgallery\Administrator\Model\ImagesModel as AdminImagesModel;

/**
 * Model to get a list of image records.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ImagesModel extends AdminImagesModel
{
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
		if(empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'ordering', 'a.ordering',
				'hits', 'a.hits',
				'downloads', 'a.downloads',
				'imgvotes', 'a.imgvotes',
				'imgvotesum', 'a.imgvotesum',
				'approved', 'a.approved',
				'imgtitle', 'a.imgtitle',
				'alias', 'a.alias',
				'catid', 'a.catid',
				'published', 'a.published',
				'imgauthor', 'a.imgauthor',
				'language', 'a.language',
				'imgtext', 'a.imgtext',
				'access', 'a.access',
				'hidden', 'a.hidden',
				'featured', 'a.featured',
				'created_time', 'a.created_time',
				'created_by', 'a.created_by',
				'modified_time', 'a.modified_time',
				'modified_by', 'a.modified_by',
				'id', 'a.id',
				'imgdate', 'a.imgdate'
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
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   4.0.0
	 */
	protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
	{
    // List state information.
		parent::populateState($ordering, $direction);

    // Set filters based on how the view is used.
    // e.g. user list of images: $this->setState('filter.created_by', Factory::getUser());

    $this->loadComponentParams();
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
}
