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
* Interface for the access class
*
* @since  4.0.0
*/
interface AccessInterface
{
  /**
	 * The context.
	 *
	 * @var string
	 *
	 * @since  4.0.0
	 */
	protected $context;

	/**
   * Loading the calculated settings for a specific content
   * to class properties
   *
   * @param   string   $context   Context of the content (default: com_joomgallery)
   * @param   int      $id        ID of the content if needed (default: null)
   *
   * @return  void
   *
   * @since   4.0.0 
   */
  public function __construct(string $context = _JOOM_OPTION, $id = null);

  /**
   * Check the ACL permission for an asset on which to perform an action.
   *
   * @param   string   $action    The name of the action to check for permission.
   * @param   string   $asset     The name of the asset on which to perform the action.
   * @param   integer  $pk        The primary key of the item.
   *
   * @return  void
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function checkACL(string $action, string $asset='', int $pk=0): bool;
}
