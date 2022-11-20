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
use \Joomla\CMS\Table\Table;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Config\ConfigInterface;
use Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;

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
   * Table object of the `#_joomgallery_configs` db table
   *
   * @var \Joomla\CMS\Table\Table
   */
  protected $table = null;

  /**
   * Constructor
   *
   * @param   int  $id  row id of the config record to be loaded
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($id = 1)
  {
    // get global configuration set
    $glob_params = $this->getParamsByID($id);

    if($glob_params == false || empty($glob_params))
    {
      Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_LOAD_CONFIG'), 'error');

      return;
    }

    // write config values to class properties
    $this->setParamsToClass($glob_params);

    // get user specific configuration set
    $user_params = $this->getParamsByGroups();

    // override class properties where needed
    if($user_params != false && !empty($user_params))
    {
      $this->setParamsToClass($user_params);
    }

    if(Factory::getApplication()->isClient('site'))
    {
      // get config values from current category

      // override class properties where needed

      // get config values from current image

      // override class properties where needed

      // get config values from current manuitem

      // override class properties where needed
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
    if($this->table == null)
    {
      $this->table = Table::getInstance('ConfigTable', '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Table\\');
    }

    $id     = intval($id);

    $this->table->load($id);
    $params = $this->table->getProperties();
    $this->table->reset();

    return $params;
  }

  /**
	 * Read out the row from `#_joomgallery_configs` table
   * with the biggest group_id number
   * and correspond to the user group of the current user
	 *
	 * @return  array  record values
	 *
	 * @since   4.0.0
	 */
  private function getParamsByGroups()
  {
    // get array of all user groups the current user is in
    $user    = Factory::getUser();
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
