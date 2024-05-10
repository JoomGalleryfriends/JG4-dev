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

\defined('JPATH_PLATFORM') or die;

/**
* The Config service
*
* @since  4.0.0
*/
interface ConfigServiceInterface
{
  /**
	 * Storage for the config helper class.
	 *
	 * @var ConfigInterface
	 *
	 * @since  4.0.0
	 */
	// private $config;

  /**
	 * Creates the config helper class based on the selected
   * inheritance method in global component settings
   * 
   * @param   string   $context   Context of the content (default: com_joomgallery)
   * @param   int      $id        ID of the content if needed (default: null)
	 * @param   bool		 $inclOwn   True, if you want to include settings of current item (default: true)
	 * @param   bool     $useCache  True, to load params from cache if available (default: true)
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createConfig($context = 'com_joomgallery', $id = null, $inclOwn = true, $useCache = true): void;

	/**
	 * Returns the config helper class.
	 *
	 * @return  ConfigInterface
	 *
	 * @since  4.0.0
	 */
	public function getConfig(): ConfigInterface;
}
