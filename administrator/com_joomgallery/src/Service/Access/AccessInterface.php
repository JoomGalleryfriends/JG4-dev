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
* Interface for the access class
*
* @since  4.0.0
*/
interface AccessInterface
{
  /**
   * Initialize class for specific option
   *
   * @return  void
   *
   * @since   4.0.0 
   */
  public function __construct(string $option='');

  /**
   * Check the ACL permission for an asset on which to perform an action.
   *
   * @param   string   $action     The name of the action to check for permission.
   * @param   string   $asset      The name of the asset on which to perform the action.
   * @param   integer  $pk         The primary key of the item.
   * @param   bool     $parent_pk  True to show that the given primary key is its parent key.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function checkACL(string $action, string $asset='', int $pk=0, bool $parent_pk=false): bool;

  /**
   * Change the component option on which to check the action.
   *
   * @param   string   $option    The new option.
   * @param   array    $types     The new list of available content types.
   * @param   array    $aclMap    The new mapping of acl actions with rules.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function changeOption(string $option, array $types, array $aclMap);

  /**
   * Set the user for which to check the access.
   *
   * @param   int|User   $user    The user id or a user object.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function setUser($user);
}