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
  use JoomTableTrait;
  use GlobalAssetTableTrait;

	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db             A database connector object
	 * @param   bool       $with_component  True to attach component object to class
	 */
	public function __construct(DatabaseDriver $db, bool $with_component = true)
	{
		if($with_component)
		{
		  $this->component = Factory::getApplication()->bootComponent('com_joomgallery');
		}
		else
		{
		  $this->addMessageTrait();
		}
    
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
