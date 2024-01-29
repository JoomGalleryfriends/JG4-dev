<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @subpackage plg_privacyjoomgalleryimages                                           **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/
namespace Joomgallery\Plugin\System\Joomowner\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

/**
 * System plugin managing ownership of JoomGallery content
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
final class JoomgalleryOwner extends CMSPlugin
{

  /**
   * Event triggered before an item gets saved into the db.
   * Check if owner of JG record is valid and exists.
   *
   * @param   string   $context  The context
   * @param   object   $table    The item
   * @param   boolean  $isNew    Is new item
   * @param   array    $data     The validated data
   *
   * @return  boolean  True to continue the save process, false to stop it
   *
   * @since   4.0.0
   */
  public function onContentBeforeSave($context, $table, $isNew, $data)
  {
    if(\strpos($context, 'com_joomgallery') !== 0)
    {
      // Do nothing if we are not handling joomgallery content
      return true;
    }

    $extensionClass = $this->getExtensionClass($context);

    if($extensionClass instanceof JtChUserBeforeDelInterface)
    {
      $this->changeUserIdIfUserDoesNotExistAnymore($extensionClass, $item);
    }
  }

  /**
   * Event triggered before the user is deleted.
   * Handle JG records that are owned by the deleted user.
   *
   * @param   array  $user
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function onUserBeforeDelete($user)
  {
    $fallbackUser = $this->params->get('fallbackUser');

    if($user['id'] == $fallbackUser)
    {
      $this->app->enqueueMessage(Text::_('PLG_SYSTEM_JTCHUSERBEFOREDEL_ERROR_FALLBACK_USER_CONNECTED_MSG'), 'error');

      $url = Uri::getInstance()->toString(array('path', 'query', 'fragment'));
      $this->app->redirect($url, 500);
    }

    if(!$this->changeUser($user))
    {
      $this->app->enqueueMessage(Text::_('PLG_SYSTEM_JTCHUSERBEFOREDEL_ERROR_USER_NOT_DELETED_MSG'), 'error');

      $url = Uri::getInstance()->toString(array('path', 'query', 'fragment'));
      $this->app->redirect($url, 500);
    }
  }
}
