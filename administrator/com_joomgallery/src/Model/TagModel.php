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

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Language\Multilanguage;
use \Joomgallery\Component\Joomgallery\Administrator\Model\JoomAdminModel;

/**
 * Tag model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class TagModel extends JoomAdminModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'tag';

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \JForm|boolean  A \JForm object on success, false on failure
	 *
	 * @since   4.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm($this->typeAlias, 'tag', array('control' => 'jform', 'load_data' => $loadData));

		if(empty($form))
		{
			return false;
		}

		return $form;
	}	

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   4.0.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = $this->app->getUserState(_JOOM_OPTION.'.edit.tag.data', array());

		if(empty($data))
		{
			if($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;			
		}

		return $data;
	}

  /**
	 * Method to get the item ID based on alias or title.
	 *
	 * @param   string  $string  The alias or title of the item
	 *
	 * @return  mixed    ID on success, false on failure.
	 *
	 * @since   4.0.0
	 */
  protected function getItemID($string)
  {
    $db = Factory::getDbo();
    $query = $db->getQuery(true);

    $query->select($db->quoteName('id'));
    $query->from($db->quoteName(_JOOM_TABLE_TAGS));
    $query->where($db->quoteName('alias') . ' = ' . $db->quote($string));

    $db->setQuery($query);

    try
    {
      $tag_id = $db->loadResult();
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
      return false;
    }

    if($tag_id)
    {
      return $tag_id;
    }

    $query = $db->getQuery(true);

    $query->select($db->quoteName('id'));
    $query->from($db->quoteName(_JOOM_TABLE_TAGS));
    $query->where($db->quoteName('title') . ' = ' . $db->quote($string));

    $db->setQuery($query);

    try
    {
      $tag_id = $db->loadResult();
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
      return false;
    }

    return $tag_id;
  }

	/**
	 * Method to get a single record.
	 *
	 * @param   int|string  $pk  The id alias or title of the item
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function getItem($pk = null)
	{
    if(!\is_null($pk) && !\is_numeric($pk))
    {
      // get item based on alias or title
      if(!$pk = $this->getItemID($pk))
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_INVALID_ALIAS'));
        return false;
      }
    }

    if($item = parent::getItem($pk))
    {
      if(isset($item->params))
      {
        $item->params = json_encode($item->params);
      }
      
      // Do any procesing on fields here if needed
    }

    return $item;		
	}

	/**
	 * Method to duplicate an Tag
	 *
	 * @param   array  &$pks  An array of primary key IDs.
	 *
	 * @return  boolean  True if successful.
	 *
	 * @throws  Exception
	 */
	public function duplicate(&$pks)
	{
		// Access checks.
		if(!$this->user->authorise('core.create', _JOOM_OPTION))
		{
			throw new \Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
		}

		$context = $this->option . '.' . $this->name;

		// Include the plugins for the save events.
		PluginHelper::importPlugin($this->events_map['save']);

		$table = $this->getTable();

		foreach($pks as $pk)
		{			
      if($table->load($pk, true))
      {
        // Reset the id to create a new record.
        $table->id = 0;

        if(!$table->check())
        {
          throw new \Exception($table->getError());
        }        

        // Trigger the before save event.
        $result = $this->app->triggerEvent($this->event_before_save, array($context, &$table, true, $table));

        if(in_array(false, $result, true) || !$table->store())
        {
          throw new \Exception($table->getError());
        }

        // Trigger the after save event.
        $this->app->triggerEvent($this->event_after_save, array($context, &$table, true));
      }
      else
      {
        throw new \Exception($table->getError());
      }			
		}

		// Clean cache
		$this->cleanCache();

		return true;
	}

  /**
   * Method to save the form data.
   *
   * @param   array  $data  The form data.
   *
   * @return  boolean  True on success, False on error.
   *
   * @since   4.0.0
   */
  public function save($data)
  {
    // Change language to 'All' if multilangugae is not enabled
    if (!Multilanguage::isEnabled())
    {
      $data['language'] = '*';
    }

    return parent::save($data);
  }

  /**
	 * Method to delete one or more tags.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   1.6
	 */
	public function delete(&$pks)
	{
    $success = parent::delete($pks);

    if($success)
    {
      // Record successfully deleted
      // Delete Tag mapping
      foreach($pks as $pk)
      {
        $success = $this->removeMapping($pk);
      }
    }

    return $success;
  }  

  /**
   * Method to add a mapping between tag and image.
   *
   * @param   int  $tag_id  ID of the tag to be mapped.
   * @param   int  $img_id  ID of the image to be mapped.
   *
   * @return  boolean  True on success, False on error.
   *
   * @since   4.0.0
   */
  public function addMapping($tag_id, $img_id)
  {
    $db = Factory::getDbo();

    $mapping = new \stdClass();
    $mapping->imgid = (int) $img_id;
    $mapping->tagid = (int) $tag_id;

    try
    {
      $db->insertObject(_JOOM_TABLE_TAGS_REF, $mapping);
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
      return false;
    }

    return true;
  }

  /**
   * Method to add a mapping between tag and image.
   *
   * @param   int  $tag_id  ID of the tag to be mapped.
   * @param   int  $img_id  ID of the image to be mapped. (optional)
   *
   * @return  boolean  True on success, False on error.
   *
   * @since   4.0.0
   */
  public function removeMapping($tag_id, $img_id=0)
  {
    $tag_id = (int) $tag_id;
    $img_id = (int) $img_id;

    $db = Factory::getDbo();
    $query = $db->getQuery(true);

    $conditions = array($db->quoteName('tagid') . ' = ' . $db->quote($tag_id));

    if($img_id > 0)
    {
      // Delete mapping only for a specific image
      \array_push($conditions, $db->quoteName('imgid') . ' = ' . $db->quote($img_id));
    }

    $query->delete($db->quoteName(_JOOM_TABLE_TAGS_REF));
    $query->where($conditions);

    $db->setQuery($query);

    try
    {
      $db->execute();
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
      return false;
    }

    return true;
  }
}
