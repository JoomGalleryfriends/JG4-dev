<?php
/**
******************************************************************************************
**   @version    4.0.0-beta1                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Table\Table;
use \Joomla\Database\DatabaseDriver;
use \Joomgallery\Component\Joomgallery\Administrator\Table\Asset\GlobalAssetTableTrait;

/**
 * Imagetype table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImagetypeTable extends Table
{
  use JoomTableTrait, GlobalAssetTableTrait {
    GlobalAssetTableTrait::_getAssetName insteadof JoomTableTrait;
    GlobalAssetTableTrait::_getAssetParentId insteadof JoomTableTrait;
    GlobalAssetTableTrait::_getAssetTitle insteadof JoomTableTrait;
  }

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
   * Delete a record by id
   *
   * @param   mixed  $pk  Primary key value to delete. Optional
   *
   * @return bool
   */
  public function delete($pk = null)
  {
    $this->_trackAssets = false;
    
    return parent::delete($pk);
  }
}
