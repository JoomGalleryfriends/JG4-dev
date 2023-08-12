<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\Input\Input;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomgallery\Component\Joomgallery\Administrator\Controller\DisplayController as AdminDisplayController;

/**
 * Joomgallery frontend display controller.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class DisplayController extends AdminDisplayController
{
	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $default_view = 'category';
}
