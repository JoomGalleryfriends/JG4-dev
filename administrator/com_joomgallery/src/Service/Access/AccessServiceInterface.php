<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Access;

\defined('_JEXEC') or die;

/**
* The Access service
*
* @since  4.0.0
*/
interface AccessServiceInterface
{
  /**
	 * Storage for the access helper class.
	 *
	 * @var AccessInterface
	 *
	 * @since  4.0.0
	 */
	private $acl;

  /**
	 * Creates the access helper class
   * 
   * @param   string   $option   Component option
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createAccess($option = '');

	/**
	 * Returns the config helper class.
	 *
	 * @return  AccessInterface
	 *
	 * @since  4.0.0
	 */
	public function getAccess(): AccessInterface;
}
