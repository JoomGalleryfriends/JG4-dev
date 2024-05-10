<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Access;

\defined('_JEXEC') or die;

/**
* Trait to implement AccessServiceInterface
*
* @since  4.0.0
*/
trait AccessServiceTrait
{
  /**
	 * Storage for the access service class.
	 *
	 * @var AccessInterface
	 *
	 * @since  4.0.0
	 */
	private $acl = null;

  /**
	 * Returns the access service class.
	 *
	 * @return  AccessInterface
	 *
	 * @since  4.0.0
	 */
	public function getAccess(): AccessInterface
	{
		return $this->acl;
	}

  /**
	 * Creates the access service class
   * 
   * @param   string   $option   Component option
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createAccess($option = '')
	{
    $this->acl = new Access($option);

    return;
	}
}