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

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\User\User;
use \Joomla\CMS\User\UserFactoryInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Access Class
 *
 * Provides methods to handle access, permission and visibility rules of the gallery
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class Access implements AccessInterface
{
  use ServiceTrait;

  /**
   * The option which component to check the ACL.
   *
   * @var string
   */
  protected $option = 'com_joomgallery';

  /**
   * Available content types of current component (this->option)
   *
   * @var array
   */
  protected $types = array('image', 'category', 'tag', 'config');

  /**
   * List of all the base acl rules mapped with actions.
   *
   * @var array
   */
  protected $aclMap = array('add'       => array('name' => 'add', 'rule' => 'core.create', 'assets' => array('.', '.image', '.category', '.config', '.tag'), 'own' => 'inown'),
                            'admin'     => array('name' => 'admin', 'rule' => 'core.admin', 'assets' => array('.'), 'own' => false),
                            'delete'    => array('name' => 'delete', 'rule' => 'core.delete', 'assets' => array('.', '.image', '.category', '.config', '.tag'), 'own' => 'own'),
                            'edit'      => array('name' => 'edit', 'rule' => 'core.edit', 'assets' => array('.', '.image', '.category', '.config', '.tag'), 'own' => 'own'),
                            'editstate' => array('name' => 'editstate', 'rule' => 'core.edit.state', 'assets' => array('.', '.image', '.category', '.config', '.tag'), 'own' => false),
                            'manage'    => array('name' => 'manage', 'rule' => 'core.manage', 'assets' => array('.'), 'own' => false)
                          );

  /**
   * The user for which to check access
   *
   * @var  \Joomla\CMS\User\User
   */
  protected $user;

  /**
   * Initialize class for specific option
   *
   * @return  void
   *
   * @since   4.0.0 
   */
  public function __construct(string $option='')
  {
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    // Set option
    if($option)
    {
      $this->option = $option;
    }

    // Set current user
    $this->user = $this->app->getIdentity();

  }

  /**
   * Check the ACL permission for an asset on which to perform an action.
   *
   * @param   string   $action    The name of the action to check for permission.
   * @param   string   $asset     The name of the asset on which to perform the action.
   * @param   integer  $pk        The primary key of the item. (optional)
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function checkACL(string $action, string $asset='', int $pk=0): bool
  {
    // Prepare asset
    $asset = $this->prepareAsset($asset, $pk);

    // Explode asset
    $asset_array  = \explode('.', $asset);
    $asset_lenght = \count($asset_array);

    // Check if asset is available for this action
    if(($asset_lenght == 1 && !\in_array('.', $this->aclMap[$action]['assets'])) ||
       !\in_array('.'.$asset_array[1], $this->aclMap[$action]['assets'])
      )
    {
      // Provided asset not available for this action. Access check failed.
      return false;
    }

    // Get the acl rule for this action
    $acl_rule       = $this->aclMap[$action]['rule'];
    $acl_rule_array = \explode('.', $acl_rule);


    // Apply the acl check
    //---------------------
    $allowed = false;

    // 1. Standard permission check
    $allowed = $this->user->authorise($acl_rule, $asset);

    // 2. Check permission if you perform an action on an item in your own category
    $categorized_types = array('image', 'category');
    if( !$allowed && $asset_lenght > 1 && \in_array($asset_array[1], $categorized_types)
        && $this->aclMap[$action]['own'] !== false && $pk > 0
      )
    {
      // Get the owner of the parent/category of the item
      $cat_owner = JoomHelper::getCreator($asset_array[1], $pk, true);
      $cat_id    = JoomHelper::getParent($asset_array[1], $pk);

      // Check against parent ownership
      if($cat_owner && $cat_id)
      {
        $parent_asset = _JOOM_OPTION.'.category.'.$cat_id;

        if($asset_array[1] == 'image' && $action == 'add')
        {
          // Check for the category in general
          $allowed = $this->user->authorise('joom.upload', $parent_asset);

          if(!$allowed)
          {
            // Check also against parent ownership
            $allowed = $this->user->authorise('joom.upload.inown', $parent_asset) && $cat_owner == $user->get('id');
          }
        }
        else
        {
          $acl_rule = 'joom.'.$acl_rule_array[1].$this->aclMap->{$action}->own;
          $allowed  = $this->user->authorise($acl_rule, $parent_asset) && $cat_owner == $user->get('id');
        }
      }
    }

    // 3. Check the permission if you perform an action on your own item
    if(!$allowed && $asset_lenght > 1 && $this->aclMap[$action]['own'] !== false && $pk > 0)
    {
      // Get the owner of the item
      $item_owner = JoomHelper::getCreator($asset_array[1], $pk);

      // Check against ownership
      if($item_owner)
      {
        $acl_rule = 'joom.'.$acl_rule_array[1].$this->aclMap->{$action}->own;
        $allowed  = $this->user->authorise($acl_rule, $asset) && $item_owner == $user->get('id');
      }
    }

    return $allowed;
  }

  /**
   * Change the component related properties of the class.
   * Needed if you want to use this sercive for another component.
   *
   * @param   string   $option    The new option.
   * @param   array    $types     The new list of available content types.
   * @param   array    $aclMap    The new mapping of acl actions with rules.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function changeOption(string $option, array $types, array $aclMap)
  {
    $this->option = $option;
    $this->types  = $types;
    $this->aclMap = $aclMap;
  }

  /**
   * Set the user for which to check the access.
   *
   * @param   int|User   $user    The user id or a user object.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function setUser($user)
  {
    if(!\is_object($user) && \is_numeric($user))
    {
      // user id given
      $this->user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user);
    }
    elseif(!\is_object($user) && \is_string($user))
    {
      // username given
      $this->user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername($user);
    }
    elseif($user instanceof User)
    {
      // user object given
      $this->user = $user;
    }
  }

  /**
   * Prepare the entered asset to make it conform with $user->authorize method.
   *
   * @param   string   $asset    The given asset.
   * @param   int      $pk       Primary key of the asset (optional).
   *
   * @return  string   The prepared asset.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  protected function prepareAsset(string $asset, int $pk=0): string
  {
    // Prepare asset
    if(!$asset)
    {
      $asset = $this->option;
    }

    // First entry has to be the option
    if(\strpos($asset, $this->option) !== 0)
    {
      $asset = $this->option . '.' . $asset;
    }

    // Last position has to be the primary key
    if($pk > 0 && \substr($asset, -\strlen($pk)) !== $pk)
    {
      $asset = $asset . '.' . \strval($pk);
    }

    //Explode asset
    $asset_array  = \explode('.', $asset);

    // Check asset
    if($asset_array[0] != 'com_joomgallery' || !\in_array($asset_array[1], $this->types))
    {
      throw new \Exception('Invalid asset provided for ACL access check', 1);
    }

    return $asset;
  }
}
