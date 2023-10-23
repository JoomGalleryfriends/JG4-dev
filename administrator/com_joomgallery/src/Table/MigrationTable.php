<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Table\Table;
use \Joomla\Database\DatabaseDriver;

/**
 * Migration table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class MigrationTable extends Table
{
	/**
   * Migration progress (0-100)
   *
   * @var  int
   *
   * @since  4.0.0
   */
  protected $progress = 0;

	/**
   * True if migration of this migrateable is completed
   *
   * @var  bool
   *
   * @since  4.0.0
   */
  protected $completed = false;


	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = _JOOM_OPTION.'.migration';

		parent::__construct(_JOOM_TABLE_IMG_TYPES, 'id', $db);
	}

	/**
	 * Get the type alias for the history table
	 *
	 * @return  string  The alias as described above
	 *
	 * @since   4.0.0
	 */
	public function getTypeAlias()
	{
		return $this->typeAlias;
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
		return parent::store($updateNulls);
	}

	/**
	 * Overloaded check function
	 *
	 * @return bool
	 */
	public function check()
	{
    // Support for subform field queue
		if(is_array($this->queue))
		{
			$this->queue = json_encode($this->queue, JSON_UNESCAPED_UNICODE);
		}

		// Support for subform field successful
		if(is_array($this->successful))
		{
			$this->successful = json_encode($this->successful, JSON_UNESCAPED_UNICODE);
		}

		// Support for subform field failed
		if(is_array($this->failed))
		{
			$this->failed = json_encode($this->failed, JSON_UNESCAPED_UNICODE);
		}

		return parent::check();
	}

  /**
   * Delete a record by id
   *
   * @param   mixed  $pk  Primary key value to delete. Optional
   *
   * @return bool
   */
  public function delete($pk = null)
  {
      $this->load($pk);
      $result = parent::delete($pk);

      return $result;
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
    // Support for queue field
    if(isset($array['queue']) && is_array($array['queue']))
		{
			$registry = new Registry;
			$registry->loadArray($array['queue']);
			$array['queue'] = (string) $registry;
		}

		// Support for successful field
    if(isset($array['successful']) && is_array($array['successful']))
		{
			$registry = new Registry;
			$registry->loadArray($array['successful']);
			$array['successful'] = (string) $registry;
		}

		// Support for failed field
    if(isset($array['failed']) && is_array($array['failed']))
		{
			$registry = new Registry;
			$registry->loadArray($array['failed']);
			$array['failed'] = (string) $registry;
		}

    return parent::bind($array, $ignore);
  }
}
