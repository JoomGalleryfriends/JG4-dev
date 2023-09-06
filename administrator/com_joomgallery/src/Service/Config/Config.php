<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Config;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Config\ConfigInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;

/**
 * Configuration Class
 *
 * Provides methods to handle configuration sets of the gallery
 *
 * @package JoomGallery
 * @since   1.5.5
 */
abstract class Config implements ConfigInterface
{
  use ServiceTrait;

  /**
   * Array with key values of subforms
   *
   * @var array
   */
  protected $subforms = array('jg_replaceinfo', 'jg_staticprocessing', 'jg_dynamicprocessing', 'jg_imgtypewtmsettings');

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
   * @param   bool		 $inclOwn   True, if you want to include settings of current item (default: true)
   *
   * @return  void
   *
   * @since   4.0.0 
   */
  public function __construct($context = 'com_joomgallery', $id = null, $inclOwn = true)
  {
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    // Check context
    $context_array = \explode('.', $context);

    if( $context_array[0] != 'com_joomgallery' || 
        (\count($context_array) > 1 && !\array_key_exists($context_array[1], $this->ids)) || 
        (\count($context_array) > 2 && $context_array[2] != 'id')
      )
    {
      $this->app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_CONFIG_INVALID_CONTEXT', $context), 'error');

      $this->context = false;
    }
    else
    {
      $this->context = $context;
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
          $img = $this->component->getMVCFactory()->createModel('image', 'administrator')->getItem($id);

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
	protected function setParamsToClass($params)
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
            if($value == '-1')
            {
              continue;
            }
            elseif(\is_integer($value) || \is_bool($value) || $value === '1' || $value === '0')
            {
              $value = \intval($value);
            }
            elseif(\is_numeric($value))
            {
              $value = \floatval($value);
            }

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
	protected function getParamsByID($id = 1)
	{
    $com_obj = $this->app->bootComponent('com_joomgallery');
    $model   = $com_obj->getMVCFactory()->createModel('Config', 'administrator');

    $id   = intval($id);
    $item = $model->getItem($id);

    return $item->getProperties();
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
  protected function getParamsByUser($id)
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
