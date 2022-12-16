<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Config;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Object\CMSObject;
use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Config\ConfigInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Configuration Class
 *
 * Provides methods to handle configuration sets of the gallery
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class Config implements ConfigInterface
{
  use ServiceTrait;

  /**
   * Array with key values of subforms
   *
   * @var array
   */
  protected $subforms = array('jg_replaceinfo', 'jg_staticprocessing', 'jg_dynamicprocessing', 'jg_imgtypewtmsettings');

  /**
   * Item object of the `#_joomgallery_configs` db table
   *
   * @var \Joomla\CMS\Object\CMSObject
   */
  protected $item = null;

  /**
   * Content for which the settings has to be calculated
   *
   * @var string
   */
  protected $context = 'com_joomgallery';

  /**
   * IDs of the different content
   *
   * @var array
   */
  protected $ids = array('user' => null, 'category' => null, 'image' => null, 'menu' => null);

  /**
   * Loading the calculated settings for a specific content
   * to class properties
   *
   * @param   string   $context   Context of the content (default: com_joomgallery)
   * @param   int      $id        ID of the content if needed (default: null)
   *
   * @return  void
   *
   * @since   4.0.0 
   */
  public function __construct($context = 'com_joomgallery', $id = null)
  {
    // Load component
    $this->getComponent();

    // Check context
    $context_array = \explode('.', $context);
    $context_ok    = true;

    if($context_array[0] != 'com_joomgallery' || (\count($context_array) > 1 && !\array_key_exists($context_array[1], $this->ids)) || \count($context_array) > 2)
    {
      Factory::getApplication()->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_CONFIG_INVALID_CONTEXT', $context), 'error');

      $context_ok = false;
    }

    // Completing $this->ids based on given context
    if(\count($context_array) > 1)
    {
      switch($context_array[1])
      {
        case 'user':
          $this->ids['user'] = (int) $id;
          break;

        case 'category':
          $this->ids['user']     = Factory::getUser()->get('id');
          $this->ids['category'] = (int) $id;
          break;

        case 'image':
          $img = JoomHelper::getRecord('image', $id);

          $this->ids['user']     = Factory::getUser()->get('id');
          $this->ids['image']    = (int) $id;
          $this->ids['category'] = (int) $img->catid;
          break;

        case 'menu':
          $this->ids['user'] = Factory::getUser()->get('id');
          $this->ids['menu'] = (int) $id;
          // TBD
          // Depending on frontend views and router 
          break;
        
        default:
          $this->ids['user'] = Factory::getUser()->get('id');
          break;
      }
    }
    else
    {
      $this->ids['user'] = Factory::getUser()->get('id');
    }

    //---------Level 1---------

    // Get global configuration set
    $glob_params = $this->getParamsByID(1);

    if($glob_params == false || empty($glob_params))
    {
      Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_LOAD_CONFIG'), 'error');

      return;
    }

    // Write config values to class properties
    $this->setParamsToClass($glob_params);

    //---------Level 2---------

    // Get user specific configuration set
    $user_params = $this->getParamsByUser($this->ids['user']);

    // Override class properties where needed
    if($user_params != false && !empty($user_params))
    {
      $this->setParamsToClass($user_params);
    }

    if(!$context_ok)
    {
      // Wrong context provided. No further inheritantion
      return;
    }

    //---------Level 3---------
    if(isset($this->ids['category']))
    {
      // Get category specific configuration set
      $cat_model = $this->component->getMVCFactory()->createModel('Category');
      $parents   = $cat_model->getParents($this->ids['category']);
    }

    //---------Level 4---------

    // Get image specific configuration set

    //---------Level 5---------

    // Get menu specific configuration set

  }

  /**
	 * Writes params from database record to class properties
	 *
	 * @param   array  $params  Array of all configs
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	private function setParamsToClass($params)
	{
    foreach($params as $key => $value)
    {
      if(strncmp($key, 'jg_', strlen('jg_')) === 0)
      {
        // param key starts with 'jg_'

        if(in_array($key,$this->subforms))
        {
          // param is a subform
          $object = \json_decode($value, false);

          // convert to object if needed
          if(\is_array($object))
          {
            $object = (object) $object;
          }

          // set object to class property
          if(!isset($this->$key) || $object != $this->$key)
          {
            $this->set($key, $object);
          }
        }
        else
        {
          // set param to class property
          if(!isset($this->$key) || $value !== $this->$key)
          {
            $this->set($key, $value);
          }
        }
      }
    }
  }

  /**
	 * Read out a row by id from `#_joomgallery_configs` table
	 *
	 * @param   int    $id  id of param row to be loaded (default: 1)
	 *
	 * @return  array  record values
	 *
	 * @since   4.0.0
	 */
	private function getParamsByID($id = 1)
	{
    $com_obj = Factory::getApplication()->bootComponent('com_joomgallery');
    $model   = $com_obj->getMVCFactory()->createModel('Config');

    $id          = intval($id);
    $this->item = $model->getItem($id);

    return $this->item->getProperties();
  }

  /**
	 * Read out the row from `#_joomgallery_configs` table
   * with the biggest group_id number
   * and correspond to the user group of the given user
	 *
   * @param   int    $id  id of the user
   * 
	 * @return  array  record values
	 *
	 * @since   4.0.0
	 */
  private function getParamsByUser($id)
  {
    // get array of all user groups the current user is in
    $user    = Factory::getUser($id);
    $groups  = $user->get('groups');

    // get a db connection
    $db = Factory::getDbo();

    // create the query
    $query = $db->getQuery(true);
    $query->select($db->quoteName('id').', MAX('.$db->quoteName('group_id').')');
    $query->from($db->quoteName(_JOOM_TABLE_CONFIGS));
    $query->where($db->quoteName('group_id').' IN '.' (\''.implode('\', \'', $groups).'\')');

    // set the query
    $db->setQuery($query);

    // load the matching param-set as an associated array
    $match = $db->loadAssoc();

    // load matching configuration set
    $params = false;
    if(!empty($match['id']))
    {
      $params = $this->getParamsByID($match['id']);
    }

    return $params;
  }
}
