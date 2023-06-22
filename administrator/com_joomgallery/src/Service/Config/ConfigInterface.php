<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Config;

\defined('JPATH_PLATFORM') or die;

/**
* Interface for the configuration classes
*
* @since  4.0.0
*/
interface ConfigInterface
{
	/**
   * Loading the calculated settings for a specific content
   * to class properties
   *
   * @param   string   $context   Context of the content (default: com_joomgallery)
   * @param   int      $id        ID of the contenttype if needed (default: null)
   * @param   bool		 $inclOwn   True, if you want to include settings of current item (default: true)
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($context = 'com_joomgallery', $id = null, $inclOwn = true);
}
