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
   * Initialize class for specific option
   *
   * @return  void
   *
   * @since   4.0.0 
   */
  public function __construct(string $option='')
	{
    $this->acl = new Access($option);

    return;
	}
}
