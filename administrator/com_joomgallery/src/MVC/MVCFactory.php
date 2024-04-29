<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\MVC;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\MVC\Factory\MVCFactory as MVCFactoryBase;
use \Joomgallery\Component\Joomgallery\Administrator\User\User;

/**
 * Factory to create MVC objects based on a namespace.
 *
 * @since  3.10.0
 */
class MVCFactory extends MVCFactoryBase
{
  /**
   * The extensions identity object.
   *
   * @var    User
   * @since  4.0.0
   */
  protected $identity = null;

  /**
   * Get the extension identity.
   *
   * @return  User
   *
   * @since   4.0.0
   */
  public function getIdentity()
  {
    if(\is_null($this->identity))
    {
      $this->loadIdentity();
    }

    return $this->identity;
  }

  /**
   * Allows the extension to load a custom or default identity.
   *
   * @param   User  $identity  An optional identity object. If omitted, a null user object is created.
   *
   * @return  $this
   *
   * @since   4.0.0
   */
  public function loadIdentity(User $identity = null)
  {
    $this->identity = $identity ?: $this->getUserFactory()->loadUserById(0);

    return $this;
  }
}
