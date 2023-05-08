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

use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

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
   * The asset.
   *
   * @var string
   */
  protected $asset = 'com_joomgallery';

  /**
   * List of all the base acl rules mapped with actions.
   *
   * @var \stdClass
   */
  protected $aclMap = null;

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
  public function __construct(string $context = _JOOM_OPTION, $id = null)
  {
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    // Check context
    $context_array = \explode('.', $context);

    if( $context_array[0] != _JOOM_OPTION || 
        (\count($context_array) > 1 && !\array_key_exists($context_array[1], $this->ids)) || 
        (\count($context_array) > 2 && $context_array[2] != 'id')
      )
    {
      $this->app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_CONFIG_INVALID_CONTEXT', $context), 'error');

      $this->context = false;
    }
    else
    {
      $this->context = $context;
    }

    // Create the acl map
    $base_rules = array('add'       => array('name' => 'add', 'rule' => 'core.create', 'assets' => array('.', '.image', '.category', '.config', '.tag'), 'own' => 'inown'),
                        'admin'     => array('name' => 'admin', 'rule' => 'core.admin', 'assets' => array('.'), 'own' => false),
                        'delete'    => array('name' => 'delete', 'rule' => 'core.delete', 'assets' => array('.', '.image', '.category', '.config', '.tag'), 'own' => 'own'),
                        'edit'      => array('name' => 'edit', 'rule' => 'core.edit', 'assets' => array('.', '.image', '.category', '.config', '.tag'), 'own' => 'own'),
                        'editstate' => array('name' => 'editstate', 'rule' => 'core.edit.state', 'assets' => array('.', '.image', '.category', '.config', '.tag'), 'own' => false),
                        'manage'    => array('name' => 'manage', 'rule' => 'core.manage', 'assets' => array('.'), 'own' => false)
                      );
    $this->aclMap = (object) $base_rules;
  }

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
  public function checkACL(string $action, string $asset='', int $pk=0): bool
  {
    $user = $this->app->getIdentity();

    // Prepare asset
    if(!$asset)
    {
      $asset = $this->asset;
    }
    $asset_array  = \explode('.', $asset);
    $asset_lenght = \count($asset_array);


    // Check asset
    if( $asset_array[0] != 'com_joomgallery' || 
        ($asset_lenght > 1 && !\array_key_exists($asset_array[1], $this->aclMap)) || 
        ($asset_lenght > 2 && !\array_key_exists($asset_array[2], array('state', 'own', 'inown')))
      )
    {
      throw new \Exception('Invalid context provided for ACL access check', 1);
    }

    // Check if asset is available for this action
    if(!($asset_lenght == 1 && \in_array('.', $this->aclMap->{$action}->assets)) ||
       !\in_array('.'.$asset_array[1], $this->aclMap->{$action}->assets)
      )
    {
      // Provided asset not available for this action. Access check failed.
      return false;
    }

    // Get the acl rule for this action
    $acl_rule       = $this->aclMap->{$action}->rule;
    $acl_rule_array = \explode('.', $acl_rule);


    // Apply the acl check
    //---------------------
    $allowed = false;

    // 1. Standard permission check
    $allowed = $user->authorise($acl_rule, $asset);

    // 2. Check permission if you perform an action on an item in your own category
    $categorized_types = array('image', 'category');
    if( !$allowed && $asset_lenght > 1 && \in_array($asset_array[1], $categorized_types)
        && $this->aclMap->{$action}->own !== false && $pk > 0
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
          $allowed = $user->authorise('joom.upload', $parent_asset);

          if(!$allowed)
          {
            // Check also against parent ownership
            $allowed = $user->authorise('joom.upload.inown', $parent_asset) && $cat_owner == $user->get('id');
          }
        }
        else
        {
          $acl_rule = 'joom.'.$acl_rule_array[1].$this->aclMap->{$action}->own;
          $allowed  = $user->authorise($acl_rule, $parent_asset) && $cat_owner == $user->get('id');
        }
      }
    }

    // 3. Check the permission if you perform an action on your own item
    if(!$allowed && $asset_lenght > 1 && $this->aclMap->{$action}->own !== false && $pk > 0)
    {
      // Get the owner of the item
      $item_owner = JoomHelper::getCreator($asset_array[1], $pk);

      // Check against ownership
      if($item_owner)
      {
        $acl_rule = 'joom.'.$acl_rule_array[1].$this->aclMap->{$action}->own;
        $allowed  = $user->authorise($acl_rule, $asset) && $item_owner == $user->get('id');
      }
    }

    return $allowed;
  }
}
