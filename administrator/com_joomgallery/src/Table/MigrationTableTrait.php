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

\defined('_JEXEC') or die;

/**
* Add functionality for tables of migrateable records
*
* @since  4.0.0
*/
trait MigrationTableTrait
{
  /**
   * True to insert the provided value of the primary key
   * Needed if you want to create a new record with a given ID
   *
   * @var bool
  */
  protected $_insertID = false;

  /**
   * Validate that the primary key has been set.
   *
   * @return  boolean  True if the primary key(s) have been set.
   *
   * @since   3.1.4
   */
  public function hasPrimaryKey()
  {
    if($this->_insertID)
    {
      return false;
    }
    else
    {
      return parent::hasPrimaryKey();
    }
  }

  /**
   * Method to set force using the provided ID when storing a new record.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function insertID()
  {
    $this->_insertID = true;
  }
}
