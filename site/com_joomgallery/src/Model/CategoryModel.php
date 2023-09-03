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
use Joomla\CMS\Language\Multilanguage;

/**
 * Model to get a category record.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryModel extends JoomItemModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'category';

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

		$this->setState('category.id', $id);

    $this->loadComponentParams($id);
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
		if($this->item === null)
		{
			$this->item = false;

			if(empty($id))
			{
				$id = $this->getState('category.id');
			}

			// Attempt to load the item
			$adminModel = $this->component->getMVCFactory()->createModel('category', 'administrator');
			$this->item = $adminModel->getItem($id);

			if(empty($this->item))
			{
				throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 404);
			}
		}

		// Add created by name
		if(isset($this->item->created_by))
		{
			$this->item->created_by_name = Factory::getUser($this->item->created_by)->name;
		}

		// Add modified by name
		if(isset($this->item->modified_by))
		{
			$this->item->modified_by_name = Factory::getUser($this->item->modified_by)->name;
		}

		// Delete unnessecary properties
		$toDelete = array('asset_id', 'password', 'params');
		foreach($toDelete as $property)
		{
			unset($this->item->{$property});
		}

		return $this->item;
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
    if($this->item === null)
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
    $listModel->setState('filter.category', $this->item->id);
    $listModel->setState('filter.level', 2);
    $listModel->setState('filter.showself', 0);
    $listModel->setState('filter.access', $user->getAuthorisedViewLevels());
    $listModel->setState('filter.published', 1);
    $listModel->setState('filter.showhidden', 0);
    $listModel->setState('filter.showempty', 0);

    if(Multilanguage::isEnabled())
    {
      $listModel->setState('filter.language', $this->item->language);
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
    if($this->item === null)
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
    $listModel->setState('filter.category', $this->item->id);
    $listModel->setState('filter.access', $user->getAuthorisedViewLevels());
    $listModel->setState('filter.published', 1);
    $listModel->setState('filter.showunapproved', 0);
    $listModel->setState('filter.showhidden', 0);

    if(Multilanguage::isEnabled())
    {
      $listModel->setState('filter.language', $this->item->language);
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
