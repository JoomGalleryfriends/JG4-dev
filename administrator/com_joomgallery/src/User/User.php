<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\User;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\User\User as BaseUser;

/**
 * User class.  Handles all component interaction with a user
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class User extends BaseUser
{
  /**
   * JoomGallery access service
   *
   * @access  protected
   * @var     Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface
   */
  protected $acl = null;

  /**
	 * Method to get the access service class.
	 *
	 * @return  AccessInterface   Object on success, false on failure.
   * @since   4.0.0
	 */
	public function getAcl(): AccessInterface
	{
    // Create access service
    if(\is_null($this->acl))
    {
      $component = Factory::getApplication()->bootComponent(_JOOM_OPTION);
      $component->createAccess();
      $this->acl = $component->getAccess();
    }

		return $this->acl;
	}

	/**
   * Method to check User object authorisation against an access control
   * object and optionally an access extension object
   *
   * @param   string  $action     The name of the action to check for permission.
   * @param   string  $assetname  The name of the asset on which to perform the action.
   *
   * @return  boolean  True if authorised
   *
   * @since   4.0.0
   */
  public function authorise($action, $assetname = null)
  {
    return $this->getAcl()->checkACL($action, $assetname);
  }
}
