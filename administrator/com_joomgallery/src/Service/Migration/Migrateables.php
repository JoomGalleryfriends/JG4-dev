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
 * Migrateable Class
 * Providing information about a record type beeing migrated
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class Migrateables
{
  use ServiceTrait;

  /**
   * Content type
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $type = '';

  /**
   * Table name (source table)
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $table = '';

  /**
   * Primary key (source table)
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $pk = '';

  /**
   * Migration progress (0-100)
   *
   * @var  int
   *
   * @since  4.0.0
   */
  protected $progress = 0;

  /**
   * List of source record ID's to be migrated
   *
   * @var  array
   *
   * @since  4.0.0
   */
  public $queue = array();

  /**
   * List of source record ID's successfully migrated
   *
   * @var  array
   *
   * @since  4.0.0
   */
  protected $success = array();

  /**
   * List of source record ID's with an error during migration
   *
   * @var  array
   *
   * @since  4.0.0
   */
  protected $error = array();

  /**
   * Constructor
   * 
   * @param   string  $type   The content type
   * @param   string  $pk     Primary key name
   * @param   string  $table  Table name
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct(string $type, string $pk, string $table)
  {
    $this->type  = $type;
    $this->pk    = $pk;
    $this->table = $table;
  }

  /**
   * Load the initial queue of ids from source db
   * 
   * @param   object  $db  Database object
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function loadQueue($db)
  {
    $query = $db->getQuery(true)
                ->select($this->pk)
                ->from($this->table);
    $db->setQuery($query);

    $this->queue = $db->loadColumn();
  }
}