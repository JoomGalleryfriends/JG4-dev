<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Migration;

// No direct access
\defined('_JEXEC') or die;

/**
 * Interface for the migration service class
 *
 * @package JoomGallery
 * @since   4.0.0
 */
interface MigrationInterface
{
  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct();

  /**
   * Step 1
   * Renders the form for configuring a migration using an XML file
   * which has the same name than the migration script
   *
   * @return  string  HTML of the rendered form
   * 
   * @since   4.0.0
   */
  public function renderForm(): string;

  /**
   * Step 2
   * Perform pre migration checks.
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  public function checkPre();

  /**
   * Step 4
   * Perform post migration checks.
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  public function checkPost();

  /**
   * Step 3
   * Perform one specific miration step and mark it as done at the end.
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  public function migrate($type, $source, $dest);
}
