<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                              **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Access\Rules;
use \Joomla\Registry\Registry;
use \Joomla\Database\DatabaseDriver;

/**
 * User table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class UserTable extends Table
{
  use JoomTableTrait, MultipleAssetsTableTrait {
    MultipleAssetsTableTrait::_getAssetName insteadof JoomTableTrait;
    MultipleAssetsTableTrait::_getAssetParentId insteadof JoomTableTrait;
    MultipleAssetsTableTrait::_getAssetTitle insteadof JoomTableTrait;
  }
  use MigrationTableTrait;

  /**
   * The default itemtype of assets
   *
   * @var    string  The name of the itemtype
   * @since  4.0.0
   */
  public $def_itemtype = 'useritems';

	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = _JOOM_OPTION.'.user';

		parent::__construct(_JOOM_TABLE_USERS, 'id', $db);
	}

  /**
	 * Overloaded bind function to pre-process the params.
	 *
	 * @param   array  $array   Named array
	 * @param   mixed  $ignore  Optional array or list of parameters to ignore
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     Table:bind
	 * @since   4.0.0
	 * @throws  \InvalidArgumentException
	 */
	public function bind($array, $ignore = '')
	{
		$date = Factory::getDate();

		if($array['id'] == 0)
		{
			$array['created_time'] = $date->toSql();
		}

    if(!\key_exists('cmsuser', $array) || empty($array['cmsuser']))
		{
			$array['cmsuser'] = Factory::getApplication()->getIdentity()->id;
		}

    if(isset($array['params']) && \is_array($array['params']))
		{
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

    if(isset($array['files']) && \is_array($array['files']))
		{
			$registry = new Registry;
			$registry->loadArray($array['files']);
			$array['files'] = (string) $registry;
		}

    // Support for collections
    if(!isset($this->collections))
    {
      $this->collections = array();
    }

    // Support for favourites
    if(!isset($this->favourites))
    {
      $this->favourites = array();
    }

    // Support for special rules
    foreach($array as $key => $value)
    {
      if(\strpos($key, 'rules') !== false)
      {
        // We found a rules entry in the data
        if($key === 'rules')
        {
          $itemtype = $this->def_itemtype;
        }
        else
        {
          $itemtype = \str_replace('rules-', '', $key);
        }

        // Bind the rules for ACL where supported.
        $rules = new Rules($value);
        $this->setRules($rules, $itemtype);
      }
    }

    return parent::bind($array, $ignore);
	}

  /**
	 * Overloaded check function
	 *
	 * @return bool
	 */
	public function check()
	{
    // Support for subform field params
    if(empty($this->params))
    {
      $this->params = $this->loadDefaultField('params');
    }
    if(isset($this->params))
    {
      $this->params = new Registry($this->params);
    }

    // Support for subform field files
    if(empty($this->files))
    {
      $this->files = $this->loadDefaultField('files');
    }
    if(isset($this->files))
    {
      $this->files = new Registry($this->files);
    }

    // Support for field description
    if(empty($this->description))
    {
      $this->description = $this->loadDefaultField('description');
    }

    return parent::check();
  }

  /**
	 * Method to store a row in the database from the Table instance properties.
	 *
	 * If a primary key value is set the row with that primary key value will be updated with the instance property values.
	 * If no primary key value is set a new row will be inserted into the database with the properties from the Table instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   4.0.0
	 */
	public function store($updateNulls = true)
	{
    // Support for params field
    if(isset($this->params) && !\is_string($this->params))
    {
      $registry = new Registry($this->params);
      $this->params = (string) $registry;
    }

    // Support for files field
    if(isset($this->files) && !\is_string($this->files))
    {
      $registry = new Registry($this->files);
      $this->files = (string) $registry;
    }

    return parent::store($updateNulls);
  }
}
