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

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\User\UserFactoryInterface;
use \Joomla\CMS\Access\Access as AccessBase;
use \Joomgallery\Component\Joomgallery\Administrator\User\User;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\Base\AccessOwn;

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
   * List of parent content types
   *
   * @var array
   */
  protected $parents = array('image' => 'category', 'category' => 'category');

  /**
   * List of content types with appended media (categorized, containing upload rules)
   *
   * @var array
   */
  protected $media_types = array('image');

  /**
   * Component specific prefix for rules.
   *
   * @var string
   */
  protected $prefix = 'joom';

  /**
   * List of all the base acl rules mapped with actions.
   *
   * @var array
   */
  protected $aclMap = array();

  /**
   * The user for which to check access
   *
   * @var  \Joomgallery\Component\Joomgallery\Administrator\User\User
   */
  protected $user;

  /**
   * Storage containing all applied acl checks.
   *
   * @var array
   */
  public $allowed = array('default' => null, 'own' => null, 'upload' => null, 'upload-own' => null);

  /**
   * Storage containing all acl checks with a mark to check for that
   *
   * @var array
   */
  public $tocheck = array('default' => true, 'own' => false, 'upload' => false, 'upload-own' => false);

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
    $this->user = $this->component->getMVCFactory()->getIdentity();

    // Set acl map for components with advanced rules
    $mapPath = JPATH_ADMINISTRATOR.'/components/'.$this->option.'/includes/rules.php';
    if(\file_exists($mapPath))
    {
      require $mapPath;
      $this->aclMap = $rules_map_array;
    }
  }

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
  public function checkACL(string $action, string $asset='', int $pk=0, bool $parent_pk=false): bool
  {
    // Prepare action
    if(!empty($this->aclMap))
    {
      $action = $this->prepareAction($action);
    }

    // Prepare asset
    $asset = $this->prepareAsset($asset, $pk, $parent_pk);

    // Explode asset
    $asset_array  = \explode('.', $asset);
    $asset_lenght = \count($asset_array);

    // Get pk from asset
    if($asset_lenght >= 3 && $pk == 0)
    {
      $pk = \intval($asset_array[2]);
    }

    // Get imagetype from asset
    if($asset_lenght > 1)
    {
      $asset_type = $asset_array[1];
    }
    else
    {
      $asset_type = false;
    }

    if(!empty($this->aclMap))
    {
      // Check if asset is available for this action
      if( ($asset_lenght == 1 && !\in_array('.', $this->aclMap[$action]['assets'])) ||
          (!\in_array('.'.$asset_type, $this->aclMap[$action]['assets']))
        )
      {
        // Action not available for this asset.
        throw new \Exception("Action not available for this asset. Access can not be checked. Please provide reasonable inputs.", 1);
      }

      // Get the acl rule for this action
      $acl_rule = $this->aclMap[$action]['rule'];
    }
    else
    {
      $acl_rule = $action;
    }
    
    $acl_rule_array = \explode('.', $acl_rule);

    // Check that parent_pk flag is set to yes if adding into a nested asset
    if($action == 'add' && \in_array($asset_type, \array_keys($this->parents)) && !$parent_pk)
    {
      // Flag parent_pk has to be set to yes
      throw new \Exception("Error in your input command: parent_pk (4th argumant) has to be set to check permission for the action 'add' on an item within a nested group of assets. Please set parent_pk to 'true' and make sure that the specified primary key corresponds to the category you want to add to.", 1);
    }

    // Apply the acl check
    //---------------------
    
    // Reset allowed array
    $this->allowed = array('default' => null, 'own' => null, 'upload' => null, 'upload-own' => null);

    // Adjust asset for further checks when only parent given
    if($action == 'add' && $parent_pk)
    {
      if(\in_array($asset_type, $this->media_types) && $action == 'add')
      {
        // Special acl rule for media upload
        $acl_rule = $this->prefix.'.upload';
      }

      // Get asset for parent checks
      $parent_type  = $asset_type ? $this->parents[$asset_type] : 'category';
      $asset        = $asset_array[0].'.'.$parent_type.'.'.$pk;
      $asset_lenght = \count(\explode('.', $asset));
    }

    // 1. Default permission checks based on asset table
    // (Global Configuration -> Recursive assets)
    // (Recursive assets for image: global -> component -> grand-parent -> parent -> type)
    $appuser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->user->get('id'));
    $this->allowed['default'] = $appuser->authorise($acl_rule, $asset);

    if($appuser->get('isRoot') === true)
    {
      // If it is the super user
      return true;
    }

    if($asset_lenght >= 3)
    {
      // 2. Permission checks based on asset table and owner
      if(!empty($this->aclMap) && $this->aclMap[$action]['own'] !== false && $pk > 0)
      {
        // Current user is the owner
        if($acl_rule_array[1] === 'edit')
        {
          $acl_rule = 'core.'.$acl_rule_array[1].'.'.$this->aclMap[$action]['own'];
        }
        else
        {
          $acl_rule = $this->prefix.'.'.$acl_rule_array[1].'.'.$this->aclMap[$action]['own'];
        }
        
        $this->tocheck['own'] = true;
        $this->allowed['own'] = AccessOwn::checkOwn($this->user->get('id'), $acl_rule, $asset);
      }

      // 3. Permission check if adding assets with media items
      if(\in_array($asset_type, $this->media_types) && $action == 'add')
      {
        // Get parent/category info
        $parent_id     = $parent_pk ? $pk : JoomHelper::getParent($asset_array[1], $pk);
        $parent_type   = $asset_type ? $this->parents[$asset_type] : 'category';
        $parent_asset  = $this->option.'.'.$parent_type.'.'.$parent_id;
        $parent_action = $this->prefix.'.upload';

        // Check for the category in general
        $this->tocheck['upload']     = true;
        $this->allowed['upload']     = AccessBase::check($this->user->get('id'), $parent_action, $parent_asset);

        // Check also against parent ownership
        $this->tocheck['upload-own'] = true;
        $this->allowed['upload-own'] = AccessOwn::checkOwn($this->user->get('id'), $parent_action.'.'.$this->aclMap[$action]['own'], $parent_asset);
      }
    }

    // Apply the results
    //--------

    // Basic: Apply the core result
    $allowedRes = $this->allowed['default'];

    // Advanced: Apply owner result
    if($this->tocheck['own'] === true)
    {
      $allowedRes = $this->allowed['default'] || $this->allowed['own'];
    }

    // Advanced: Apply media items result
    if($this->tocheck['upload'] === true)
    {
      if($this->allowed['upload'] !== null)
      {
        // Override the result from core
        $allowedRes = $this->allowed['upload'];
      }

      if($this->tocheck['upload-own'] === true)
      {
        // Action requires an owner check
        $allowedRes = $allowedRes || $this->allowed['upload-own'];
      }
    }

    return $allowedRes;
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
    if($user instanceof User)
    {
      // user object given
      $this->user = $user;
    }
    elseif(!\is_object($user))
    {
      if(\is_numeric($user))
      {
        // user id given
        $appuser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user);
      }
      elseif(\is_string($user))
      {
        // username given
        $appuser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername($user);
      }
      
      if(isset($appuser->id))
      {
        $this->user = new User($appuser->id);
      }
    }
  }

  /**
   * Prepare the entered asset to make it conform with $user->authorize method.
   *
   * @param   string   $asset      The given asset.
   * @param   int      $pk         Primary key of the asset (optional).
   * @param   bool     $parent_pk  True if given pk is key of parent asset.
   *
   * @return  string   The prepared asset.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  protected function prepareAsset(string $asset, int $pk=0, bool $parent_pk=false): string
  {
    // Do we have a global asset?
    $global = false;
    if(!$asset || $asset === \str_replace('com_', '', $this->option))
    {
      $asset  = $this->option;
      $global = true;
    }

    // Option in asset partially given?
    if(\strpos($asset, \str_replace('com_', '', $this->option)) === 0)
    {
      $asset = 'com_' . $asset;
    }

    // First entry has to be the option
    if(\strpos($asset, $this->option) !== 0)
    {
      $asset = $this->option . '.' . $asset;
    }

    // Last position has to be the primary key
    if(!$global && !$parent_pk && $pk > 0 && \substr($asset, -\strlen($pk)) !== $pk)
    {
      $asset = $asset . '.' . \strval($pk);
    }

    // Explode asset
    $asset_array  = \explode('.', $asset);

    // Check asset
    if($asset_array[0] != $this->option || (\count($asset_array) > 1 && !\in_array($asset_array[1], $this->types)))
    {
      throw new \Exception('Invalid asset provided for ACL access check', 1);
    }

    return $asset;
  }

  /**
   * Prepare the entered action to catch similar words.
   *
   * @param   string   $action     The given aaction.
   *
   * @return  string   The prepared action.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  protected function prepareAction(string $action): string
  {
    // Clean action if it is dot separated (core.delete)
    $act_array = \explode('.', $action, 2);
    if(\count($act_array) >= 2)
    {
      $action = $act_array[1];
    }

    // Take away own and inown in action statement
    $action = \str_replace(array('.own', '.inown'), '', $action);

    // Synonyms for add
    $addSyn    = array('add', 'create', 'new', 'upload');
    // Synonyms for delete
    $delSyn    = array('delete', 'remove', 'drop', 'clear', 'erase');
    // Synonyms for edit
    $editSyn   = array('edit', 'change', 'modify', 'alter');
    // Synonyms for editstate
    $stateSyn  = array('editstate', 'edit.state', 'feature', 'unfeature', 'publish', 'unpublish', 'approve', 'unapprove');
    // Synonyms for admin
    $adminSyn  = array('admin', 'acl');
    // Synonyms for manage
    $manageSyn = array('manage', 'options');

    // Compose array
    $composition = array($addSyn, $delSyn, $editSyn, $editSyn, $stateSyn, $adminSyn, $manageSyn);

    // Get the correct action from composition array
    if(!$res = $this->arrayRecursiveSearch($action, $composition))
    {
      throw new \Exception('Invalid action provided for ACL access check', 1);
    }

    return $res;
  }

  /**
   * Search for a value in a nested array and return first value of
   * current array level.
   *
   * @param   string   $needle    The serached value.
   * @param   array    $array     The array.
   *
   * @return  string   First value in the array where needle was found.
   *
   * @since   4.0.0
   */
  protected function arrayRecursiveSearch(string $needle, array $array): string
  {
    foreach($array as $key => $value)
    {
      if($needle === $value)
      {
        // value found in this level
        return $array[0];
      }
      elseif(is_array($value))
      {
        // perfom recursive search
        $callback = $this->arrayRecursiveSearch($needle, $value);
        
        if($callback)
        {
          return $callback;
        }
      }
    }

    return false;
  }
}