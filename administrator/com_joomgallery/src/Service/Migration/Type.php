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
   * Name of the corresponding record
   *
   * @var  string
   *
   * @since  4.0.0
   */
  public $recordName = 'image';

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
   * Name of the owner field
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $owner = 'created_by';

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
   * List of types this type depends on.
   * They have to be migrated before this one.
   *
   * @var  array
   *
   * @since  4.0.0
   */
  protected $dependent_on = array();

  /**
   * List of types depending on this type.
   * This type has to be migrated before the dependent ones.
   *
   * @var  array
   *
   * @since  4.0.0
   */
  protected $dependent_of = array();

  /**
   * Table name used to load the queue
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $queue_tablename = '#__joomgallery';

  /**
   * Do we have to create/inert new database records for this type?
   * If no this type is just an adjustment of data within the destination table.
   *
   * @var  boolean
   *
   * @since  4.0.0
   */
  protected $insertRecord = true;

  /**
   * Constructor
   * 
   * @param  string  $name  Name of this content type
   * @param  array   $list  Source types info created by Migration::defineTypes()
   * @param  array   $lists List of source types info
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($name, $list, $lists=array())
  {
    $this->name       = $name;
    $this->recordName = $name;

    if(\count($list) < 4)
    {
      throw new Exception('Type object needs a list of at least 4 entries as the second argument.', 1);
    }

    $this->tablename       = $list[0];
    $this->queue_tablename = $list[0];
    $this->pk              = $list[1];
    $this->nested          = $list[2];
    $this->categorized     = $list[3];    

    if(\count($list) > 4)
    {
      $this->owner = $list[4];
    }

    if(\count($list) > 5)
    {
      $this->dependent_on = $list[5];
    }

    if(\count($list) > 6)
    {
      if(\is_array($list[6]))
      {
        $this->skip = \array_merge($this->skip, $list[6]);
      }
      else
      {
        \array_push($this->skip, $list[6]);
      }
    }

    if(\count($list) > 7)
    {
      $this->insertRecord = $list[7];
    }

    if(\count($list) > 8)
    {
      $this->queue_tablename = $list[8];
    }    

    if(\count($list) > 9)
    {
      $this->recordName = $list[9];
    }

    // search for types depending on this type
    if(!empty($lists))
    {
      foreach($lists as $type_name => $type_list)
      {
        if(\count($type_list) > 4 && !empty($type_list[4]) && in_array($name, $type_list[4]))
        {
          array_push($this->dependent_of, $type_name);
        }
      }
    }    
  }
}