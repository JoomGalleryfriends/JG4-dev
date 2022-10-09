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
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Model\JoomAdminModel;
use stdClass;

/**
 * Config model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigModel extends JoomAdminModel
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
	public $typeAlias = _JOOM_OPTION.'.config';

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
	public function getTable($type = 'Config', $prefix = 'Administrator', $config = array())
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
		$form = $this->loadForm($this->typeAlias, 'config', array('control' => 'jform', 'load_data' => $loadData));

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
		$data = $this->app->getUserState(_JOOM_OPTION.'.edit.config.data', array());

		if(empty($data))
		{
			if($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;

      // Load imagetypes from database
      $new_staticprocessing = array();
      $imagetypes           = JoomHelper::getRecords('imagetypes');

      // Replace jg_staticprocessing based on imagetypes
      foreach($imagetypes as $key => $imagetype)
      {
        // initialize stdClass object
        if(!isset($new_staticprocessing['jg_staticprocessing'.$key]))
        {
          $new_staticprocessing['jg_staticprocessing'.$key] = new stdClass();
        }

        // create staticprocessing array
        $new_staticprocessing['jg_staticprocessing'.$key]->jg_imgtypename = $imagetype->typename;
        $new_staticprocessing['jg_staticprocessing'.$key]->jg_imgtypepath = $imagetype->path;

        foreach($imagetype->params as $k => $param)
        {
          $new_staticprocessing['jg_staticprocessing'.$key]->{$k} = $param;
        }
      }

      // Set jg_staticprocessing data
      $data->jg_staticprocessing = \json_encode((object) $new_staticprocessing);
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function getItem($pk = null)
	{		
    if($item = parent::getItem($pk))
    {
      if(isset($item->params))
      {
        $item->params = json_encode($item->params);
      }
    }

    return $item;		
	}

	/**
	 * Method to duplicate an Config
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
	 * @since   1.6
	 */
	public function save($data)
	{
    $mod_items = $this->component->getMVCFactory()->createModel('imagetypes');
    $model     = $this->component->getMVCFactory()->createModel('imagetype');

    // get all existing imagetypes in the database
    $imagetypes_list = $mod_items->getItems();
    $detail_path     = $imagetypes_list[1]->path;

    foreach($data['jg_staticprocessing'] as $staticprocessing)
    {
      // load data
      $imagetype_db = $model->getItem(array('typename' => $staticprocessing['jg_imgtypename']));

      // check if forbidden imagetypes gets disables
      $forbidden = array('detail', 'thumbnail');
      if(\in_array($staticprocessing['jg_imgtypename'], $forbidden) && $staticprocessing['jg_imgtype'] != '1')
      {
        // not allowed to unset this imagetype
        $this->app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_NOT_ALLOWED_DEACTIVATE_IMAGETYPE', $staticprocessing['jg_imgtypename']));

        $staticprocessing['jg_imgtype'] = 1;
      }
      
      // update data
      $imagetype_db->typename = $staticprocessing['jg_imgtypename'];
      $imagetype_db->path     = $staticprocessing['jg_imgtypepath'];
      $imagetype_db->params   = $this->encodeParams($staticprocessing);

      if(empty($imagetype_db->typename))
      {
        // we are currently handling a deleted imagetype
        // skip he rest of the current loop iteration
        continue;
      }

      if(\is_null($imagetype_db->id))
      {
        // prepare data to create new imagetype row
        $imagetype_db->id       = 0;
        $imagetype_db->ordering = '';
        
        if(empty($imagetype_db->path))
        {
          // create a default path for new imagetype row
          $path_parts = \explode('/',$detail_path);
          \array_pop($path_parts);

          $imagetype_db->path = \implode('/',$path_parts).'/'.$imagetype_db->typename;
        }
      }

      // save data
      $model->save((array) $imagetype_db);

      // unset current imagetype from imagetypes_db list
      foreach($imagetypes_list as $key => $imagetype)
      {
        if ($imagetype->typename == $staticprocessing['jg_imgtypename'])
        {
          unset($imagetypes_list[$key]);
        }
      }
    }

    // delete unused imagetypes from db
    $forbidden = array('original', 'detail', 'thumbnail');
    foreach($imagetypes_list as $imagetype_list)
    {
      if(\in_array($imagetype_list->typename, $forbidden))
      {
        // not allowed to delete this imagetype
        $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_NOT_ALLOWED_DELETE_IMAGETYPE'));
      }
      else
      {
        $model->delete($imagetype_list->id);
      }
    }

    $data['jg_staticprocessing'] = '';

    return parent::save($data);
  }

  /**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array    &$pks   A list of the primary keys to change.
	 * @param   integer  $value  The value of the published state.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function publish(&$pks, $value = 1)
	{
    // remove record with id=1 from the list of primary keys to change
    if(($key = \array_search(1, $pks)) !== false)
    {
      unset($pks[$key]);

      // It is not allowed to unpublish the global configuration set
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_NOT_ALLOWED_UNPUBLISH_CONFIG_SET'));
    }

    parent::publish($pks, $value);
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
			if (@$table->ordering === '')
			{
				$db = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM '._JOOM_TABLE_CONFIGS);
        
				$max             = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

  /**
	 * Initialize new stdObject with default config params of jg_staticprocessing.
	 *
   * @param   string     $type    Imagetype (default:original)
   * 
	 * @return  stdClass   Default config params of jg_staticprocessing
	 *
	 * @since   4.0.0
	 */
  protected function newStaticprocessing($type='original')
  {
    $obj = array();

    $obj['jg_imgtype']            = '1';
    $obj['jg_imgtypename']        = '';
    $obj['jg_imgtypepath']        = '';
    $obj['jg_imgtyperesize']      = '0';
    $obj['jg_imgtypewidth']       = '';
    $obj['jg_imgtypeheight']      = '';
    $obj['jg_cropposition']       = '2';
    $obj['jg_imgtypeorinet']      = '1';
    $obj['jg_imgtypeanim']        = '0';
    $obj['jg_imgtypesharpen']     = '0';
    $obj['jg_imgtypequality']     = 100;
    $obj['jg_imgtypewatermark']   = '0';
    $obj['jg_imgtypewtmsettings'] = [];

    return (object) $obj;
  }

  /**
	 * Initialize new stdObject with default config params of jg_staticprocessing.
	 *
   * @param   string     $type    Imagetype (default:original)
   * 
	 * @return  string   Params json string
	 *
	 * @since   4.0.0
	 */
  protected function newImagetypeParams($type='original')
  {
    switch($type)
    {
      case 'detail':
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"3","jg_imgtypewidth":"1000","jg_imgtypeheight":"1000","jg_cropposition":"2","jg_imgtypeorinet":"1","jg_imgtypeanim":"0","jg_imgtypesharpen":"0","jg_imgtypequality":"80","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"[]"}';
        break;

      case 'thumbnail':
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"4","jg_imgtypewidth":"250","jg_imgtypeheight":"250","jg_cropposition":"2","jg_imgtypeorinet":"1","jg_imgtypeanim":"0","jg_imgtypesharpen":"1","jg_imgtypequality":"60","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"[]"}';
        break;
      
      default:
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"0","jg_imgtypewidth":"","jg_imgtypeheight":"","jg_cropposition":"2","jg_imgtypeorinet":"0","jg_imgtypeanim":"1","jg_imgtypesharpen":"0","jg_imgtypequality":"100","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"[]"}';
        break;
    }
  }

  /**
	 * Decode params string.
	 *
   * @param   string      $params     Json string with params
   * 
	 * @return  CMSObject   Params object
	 *
	 * @since   4.0.0
	 */
  protected function decodeParams($params)
  {
    $params = (string) $params;

    if(isset($params))
    {
      $params = json_decode($params);
    }

    return $params;
  }

  /**
	 * Encode params string.
	 *
   * @param   array    $data     Form data
   * 
	 * @return  string   Params json string
	 *
	 * @since   4.0.0
	 */
  protected function encodeParams($data)
  {
    if(\array_key_exists('jg_imgtypename', $data))
    {
      unset($data['jg_imgtypename']);
    }

    if(\array_key_exists('jg_imgtypepath', $data))
    {
      unset($data['jg_imgtypepath']);
    }

    return json_encode($data);
  }
}
