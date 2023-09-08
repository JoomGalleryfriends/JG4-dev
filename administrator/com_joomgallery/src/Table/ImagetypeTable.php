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

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Asset;
use \Joomla\CMS\Table\Table;
use \Joomla\Database\DatabaseDriver;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\Filter\OutputFilter;

/**
 * Imagetype table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImagetypeTable extends Table
{
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = _JOOM_OPTION.'.imagetype';

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
	 * Define a namespaced asset name for inclusion in the #__assets table
	 *
	 * @return string The asset name
	 *
	 * @see Table::_getAssetName
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;

		return $this->typeAlias . '.' . (int) $this->$k;
	}

	/**
	 * Method to return the title to use for the asset table.
	 *
	 * @return  string
	 *
	 * @since   1.6
	 */
	protected function _getAssetTitle()
	{
		return $this->typename;
	}

	/**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
	 *
	 * @param   Table   $table  Table name
	 * @param   integer  $id     Id
	 *
	 * @see Table::_getAssetParentId
	 *
	 * @return mixed The id on success, false on failure.
	 */
	protected function _getAssetParentId($table = null, $id = null)
	{
		// We will retrieve the parent-asset from the Asset-table
		$assetTable = new Asset(Factory::getContainer()->get(DatabaseInterface::class));

		// The item has the component as asset-parent
		$assetTable->loadByName(_JOOM_OPTION);

		// Return the found asset-parent-id
		if($assetTable->id)
		{
			$assetParentId = $assetTable->id;
		}
		else
		{
			// If no asset-parent can be found we take the global asset
			$assetParentId = $assetTable->getRootId();
		}

		return $assetParentId;
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
		// If there is an ordering column and this is a new row then get the next ordering value
		if(property_exists($this, 'ordering') && $this->id == 0)
		{
			$this->ordering = self::getNextOrder();
		}

    // Support for subform field params
		if(is_array($this->params))
		{
			$this->params = json_encode($this->params, JSON_UNESCAPED_UNICODE);
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
    // Support for alias field: alias
		if(empty($array['type_alias']))
		{
			if(empty($array['typename']))
			{
				$array['type_alias'] = OutputFilter::stringURLSafe(date('Y-m-d'));
			}
			else
			{
				if(Factory::getConfig()->get('unicodeslugs') == 1)
				{
					$array['type_alias'] = substr(OutputFilter::stringURLUnicodeSlug(trim($array['typename'])), 0, 4);
				}
				else
				{
					$array['type_alias'] = substr(OutputFilter::stringURLSafe(trim($array['typename'])), 0, 4);
				}
			}
		}

    // Support for params field
    if(isset($array['params']) && is_array($array['params']))
		{
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

    return parent::bind($array, $ignore);
  }
}
