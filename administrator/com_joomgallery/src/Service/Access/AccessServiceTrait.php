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
* Trait to implement AccessServiceInterface
*
* @since  4.0.0
*/
trait AccessServiceTrait
{
  /**
	 * Storage for the access helper class.
	 *
	 * @var AccessInterface
	 *
	 * @since  4.0.0
	 */
	private $acl = null;

  /**
	 * Returns the config helper class.
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
	 * Creates the access helper class
   * 
   * @param   string   $context   Context of the content (default: com_joomgallery)
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createAccess($context = 'com_joomgallery'): void
	{
    $this->acl = new Access($context);

    return;
	}
}
