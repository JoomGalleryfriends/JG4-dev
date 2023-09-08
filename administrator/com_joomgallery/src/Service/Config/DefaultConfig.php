<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Config;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Config\ConfigInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Config\Config;

/**
 * Configuration Class
 *
 * Provides methods to handle configuration sets of the gallery
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class DefaultConfig extends Config implements ConfigInterface
{
  /**
   * Loading the calculated settings for a specific content
   * to class properties
   *
   * @param   string   $context   Context of the content (default: com_joomgallery)
   * @param   int      $id        ID of the content if needed (default: null)
   * @param   bool		 $inclOwn   True, if you want to include settings of current item (default: true)
   *
   * @return  void
   *
   * @since   4.0.0 
   */
  public function __construct($context = 'com_joomgallery', $id = null, $inclOwn = true)
  {
    parent::__construct($context, $id);

    // Check context
    $context_array = \explode('.', $context);

    //---------Level 1---------

    // Get global configuration set
    $glob_params = $this->getParamsByID(1);

    if($glob_params == false || empty($glob_params))
    {
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_LOAD_CONFIG'), 'error');

      return;
    }

    // Write config values to class properties
    $this->setParamsToClass($glob_params);

    //---------Level 2---------

    // Get user specific configuration set
    $user_params = $this->getParamsByUser($this->ids['user']);

    // Override class properties based on user specific configuration set
    if($user_params != false && !empty($user_params))
    {
      $this->setParamsToClass($user_params);
    }

    if(!$this->context)
    {
      // Wrong context provided. No further inheritantion
      return;
    }

    //---------Level 3---------
    if(isset($this->ids['category']) && $this->ids['category'] > 1)
    {
      // Load parent categories
      $cat_model = $this->component->getMVCFactory()->createModel('Category', 'administrator');
      $parents   = $cat_model->getParents($this->ids['category'], true);

      if($parents === false && empty($parents))
      {
        $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_LOAD_CONFIG'), 'error');
        return;
      }

      // Override class properties based on category params
      foreach ($parents as $key => $cat)
      {
        if($context_array[1] == 'category' && $cat['id'] == $id && !$inclOwn)
        {
          // Skip own category settings
          continue;
        }
        $category   = $cat_model->getItem($cat['id']);
        $cat_params = \json_decode($category->params);
        $this->setParamsToClass($cat_params);
      }
    }

    //---------Level 4---------
    if(isset($this->ids['image']))
    {
      // Load image
      $img_model  = $this->component->getMVCFactory()->createModel('Image', 'administrator');
      $image      = $img_model->getItem($this->ids['image']);

      if($image === false && empty($image))
      {
        $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_LOAD_CONFIG'), 'error');
        return;
      }

      if($context_array[1] == 'image' && $image->id == $id && !$inclOwn)
      {
        // Skip own image settings
      }
      else
      {
        // Override class properties based on image params
        $img_params = \json_decode($image->params);
        $this->setParamsToClass($img_params);
      }
    }

    //---------Level 5---------
    if(isset($this->ids['menu']))
    {
      // Load menu item
      $menu = $this->app->getMenu()->getItem($this->ids['menu']);

      if($menu === false && empty($menu))
      {
        $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_LOAD_CONFIG'), 'error');
        return;
      }
      
      // Override class properties based on menu item params
      $this->setParamsToClass($menu->getParams());
    }
  }
}
