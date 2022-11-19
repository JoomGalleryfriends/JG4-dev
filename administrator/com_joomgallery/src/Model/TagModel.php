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

use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
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
	 * @var    string  The prefix to use with controller messages.
	 *
	 * @since  4.0.0
	 */
	protected $text_prefix = _JOOM_OPTION_UC;

	/**
	 * @var    string  Alias to manage history control
	 *
	 * @since  4.0.0
	 */
	public $typeAlias = _JOOM_OPTION.'.tag';

	/**
	 * @var    null  Item data
	 *
	 * @since  4.0.0
	 */
	protected $item = null;	

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 *
	 * @since   4.0.0
	 */
	public function getTable($type = 'Tag', $prefix = 'Administrator', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

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
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   Table  $table  Table Object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if(empty($table->id))
		{
			// Set ordering to the last item if not set
			if(@$table->ordering === '')
			{
				$db = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM '._JOOM_TABLE_TAGS);
        
				$max             = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

  /**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   Form    $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function preprocessForm(Form $form, $data, $group = 'joomgallery')
	{
		if (!Multilanguage::isEnabled())
		{
			$form->setFieldAttribute('language', 'type', 'hidden');
			$form->setFieldAttribute('language', 'default', '*');
		}

		parent::preprocessForm($form, $data, $group);
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
   * @param   int  $img_id  ID of the image to be mapped.
   *
   * @return  boolean  True on success, False on error.
   *
   * @since   4.0.0
   */
  public function removeMapping($tag_id, $img_id)
  {
    $tag_id = (int) $tag_id;
    $img_id = (int) $img_id;

    $db = Factory::getDbo();
    $query = $db->getQuery(true);

    $conditions = array(
      $db->quoteName('imgid') . ' = ' . $db->quote($img_id),
      $db->quoteName('tagid') . ' = ' . $db->quote($tag_id)
    );

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
