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

\defined('_JEXEC') or die;

/**
* The Migration service
*
* @since  4.0.0
*/
interface MigrationServiceInterface
{
  /**
	 * Storage for the migration service class.
	 *
	 * @var MigrationInterface
	 *
	 * @since  4.0.0
	 */
	private $migration;

  /**
	 * Creates the migration service class
   * 
   * @param   string  $script   Name of the migration script to be used
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createMigration($script): void;

	/**
	 * Returns the migration service class.
	 *
	 * @return  MigrationInterface
	 *
	 * @since  4.0.0
	 */
	public function getMigration(): MigrationInterface;
}
