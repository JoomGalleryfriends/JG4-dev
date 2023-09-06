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

/**
 * Model to get an image record.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageModel extends JoomItemModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'image';

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
		$user = Factory::getUser();

		// Check published state
		if((!$user->authorise('core.edit.state', 'com_joomgallery')) && (!$user->authorise('core.edit', 'com_joomgallery')))
		{
			$this->setState('filter.published', 1);
			$this->setState('filter.archived', 2);
		}

		// Load state from the request userState on edit or from the passed variable on default
		$id = $this->app->input->getInt('id', null);
		if($id)
		{
			$this->app->setUserState('com_joomgallery.edit.image.id', $id);
		}
		else
		{
			$id = (int) $this->app->getUserState('com_joomgallery.edit.image.id', null);
		}

		if(is_null($id))
		{
			throw new Exception('No ID provided to the model!', 500);
		}

		$this->setState('image.id', $id);

		$this->loadComponentParams($id);
	}

	/**
	 * Method to get an object.
	 *
	 * @param   integer $id The id of the object to get.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @throws Exception
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

		if(isset($this->item->catid) && $this->item->catid != '')
		{
			$this->item->cattitle = $this->getCategoryName($this->item->catid);
		}

		if(isset($this->item->created_by))
		{
			$this->item->created_by_name = Factory::getUser($this->item->created_by)->name;
		}

		if(isset($this->item->modified_by))
		{
			$this->item->modified_by_name = Factory::getUser($this->item->modified_by)->name;
		}

    // Delete unnessecary properties
		$toDelete = array('asset_id', 'params');
		foreach($toDelete as $property)
		{
			unset($this->item->{$property});
		}

		return $this->item;
	}

  /**
	 * Method to load the title of a category
	 *
	 * @param   int  $catid  Category id
	 *
	 * @return  string|bool  The category title on success, false otherwise
	 */
  protected function getCategoryName(int $catid)
  {
    // Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

    // Select the required field from the table.
		$query->select($db->quoteName('title'));
    $query->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
          ->where($db->quoteName('id') . " = " . $db->quote($catid));

    // Reset the query using our newly populated query object.
    $db->setQuery($query);
    
    return $db->loadResult();
  }
}
