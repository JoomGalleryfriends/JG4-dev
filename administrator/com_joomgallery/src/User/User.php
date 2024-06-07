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
use \Joomla\CMS\Access\Access as AccessBase;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface;

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
    // Make sure we only check for core.admin once during the run.
    if($this->isRoot === null)
    {
      $this->isRoot = false;

      // Check for the configuration file failsafe.
      $rootUser = Factory::getApplication()->get('root_user');

      // The root_user variable can be a numeric user ID or a username.
      if(\is_numeric($rootUser) && $this->id > 0 && $this->id == $rootUser)
      {
        $this->isRoot = true;
      }
      elseif($this->username && $this->username == $rootUser)
      {
        $this->isRoot = true;
      }
      elseif ($this->id > 0)
      {
        // Get all groups against which the user is mapped.
        $identities = $this->getAuthorisedGroups();
        \array_unshift($identities, $this->id * -1);

        if(AccessBase::getAssetRules(1)->allow('core.admin', $identities))
        {
          $this->isRoot = true;

          return true;
        }
      }
    }

    if(\strpos($assetname, 'joomgallery') !== false)
    {
      // For com_joomgallery
      return $this->getAcl()->checkACL($action, $assetname);
    }
    else
    {
      // For core components
      return $this->isRoot ? true : (bool) AccessBase::check($this->id, $action, $assetname);
    }    
  }
}
