<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\ApiRouter;

/**
 * Web Services adapter for joomgallery.
 * @package JoomGallery
 * @since   4.0.0
 */
class PlgWebservicesJoomgallery extends CMSPlugin
{
	public function onBeforeApiRoute(&$router)
	{		
		$router->createCRUDRoutes('v1/joomgallery/images', 'images', ['component' => 'com_joomgallery']);
		$router->createCRUDRoutes('v1/joomgallery/categories', 'categories', ['component' => 'com_joomgallery']);
	}
}
