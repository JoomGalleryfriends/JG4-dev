<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                              **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Migration;

// No direct access
\defined('_JEXEC') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;

/**
 * Type Class
 * Providing information about a content type beeing migrated
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class Type
{
  use ServiceTrait;

  /**
   * Name of this content type
   *
   * @var  string
   *
   * @since  4.0.0
   */
  public $name = 'image';

  /**
   * Source table name of this content type
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $tablename = '#__joomgallery';

  /**
   * Name of the primary key
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $pk = 'id';

  /**
   * True if this content type is nested
   *
   * @var  boolean
   *
   * @since  4.0.0
   */
  protected $nested = false;

  /**
   * True if this content type has categories
   *
   * @var  boolean
   *
   * @since  4.0.0
   */
  protected $categorized = true;

  /**
   * List of primary keys that dont need migration and can be skipped.
   *
   * @var  array
   *
   * @since  4.0.0
   */
  protected $skip = array(0);

  /**
   * List of content types that has to be migrated before this one.
   *
   * @var  array
   *
   * @since  4.0.0
   */
  protected $prerequirement = array();

  /**
   * Is this migration type really a migration?
   * True to perform a migration, false to perform a modification at destination
   *
   * @var  boolean
   *
   * @since  4.0.0
   */
  protected $isMigration = true;

  /**
   * Constructor
   * 
   * @param  string  $name  Name of this content type
   * @param  array   $list  Source types info created by Migration::defineTypes()
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($name, $list)
  {
    $this->name = $name;

    if(\count($list) < 4)
    {
      throw new Exception('Type object needs a list of at least 4 entries as the second argument.', 1);
    }

    $this->tablename   = $list[0];
    $this->pk          = $list[1];
    $this->nested      = $list[2];
    $this->categorized = $list[3];

    if(\count($list) > 4)
    {
      $this->prerequirement = $list[4];
    }

    if(\count($list) > 5)
    {
      if(\is_array($list[5]))
      {
        $this->skip = \array_merge($this->skip, $list[5]);
      }
      else
      {
        \array_push($this->skip, $list[5]);
      }
    }

    if(\count($list) > 6)
    {
      $this->isMigration = $list[6];
    }
  }
}