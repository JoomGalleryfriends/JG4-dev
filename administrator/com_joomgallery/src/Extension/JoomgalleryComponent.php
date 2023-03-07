<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Extension;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Tag\TagServiceTrait;
use Psr\Container\ContainerInterface;
use Joomgallery\Component\Joomgallery\Administrator\Extension\JoomConfig;
use Joomgallery\Component\Joomgallery\Administrator\Extension\MessageTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Config\ConfigServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\Config\ConfigServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\FilesystemServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\FilesystemServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Refresher\RefresherServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\Refresher\RefresherServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtoolsServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtoolsServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\FileManager\FileManagerServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\FileManager\FileManagerServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessengerServiceTraitInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessengerServiceTrait;

/**
 * Component class for Joomgallery
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class JoomgalleryComponent extends MVCComponent implements BootableExtensionInterface, RouterServiceInterface
{
  /**
   * Array of supported image types
   *
   * @var array
   */
  public $supported_types = null;

  use MessageTrait;
	use AssociationServiceTrait;
	use RouterServiceTrait;
	use HTMLRegistryAwareTrait;
	use CategoryServiceTrait, TagServiceTrait
  {
		CategoryServiceTrait::getTableNameForSection insteadof TagServiceTrait;
		CategoryServiceTrait::getStateColumnForSection insteadof TagServiceTrait;
	}

  /**
   * JoomGallery services
   *
   * How to:
   * --------
   * $component = $app->bootComponent(_JOOM_OPTION); // boot JoomGallery component
   * $component->create<SERVICENAME>('<VARIANT>');   // instantiate new class with specified variant of the service
   * $component->get<SERVICENAME>()-><METHOD>();     // execute method of service class
   *
   */
  use ConfigServiceTrait;
  use UploaderServiceTrait;
  use FilesystemServiceTrait;
  use RefresherServiceTrait;
  use IMGtoolsServiceTrait;
  use FileManagerServiceTrait;
  use MessengerServiceTrait;

  /**
	 * Booting the extension. This is the function to set up the environment of the extension like
	 * registering new class loaders, etc.
	 *
	 * If required, some initial set up can be done from services of the container, eg.
	 * registering HTML services.
	 *
	 * @param   ContainerInterface  $container  The container
	 *
	 * @return  void
   *
	 * @since   4.0.0
	 */
  public function boot(ContainerInterface $container)
 	{
    if(!\defined('_JOOM_OPTION'))
    {
      require_once JPATH_ADMINISTRATOR . '/components/com_joomgallery/includes/defines.php';
    }
  }
}
