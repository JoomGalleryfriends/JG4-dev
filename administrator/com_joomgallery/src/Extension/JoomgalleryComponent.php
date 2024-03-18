<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Extension;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Psr\Container\ContainerInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Config\ConfigServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\Config\ConfigServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\FileManager\FileManagerServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\FileManager\FileManagerServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\FilesystemServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\FilesystemServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtoolsServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtoolsServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessengerServiceTraitInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessengerServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Refresher\RefresherServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\Refresher\RefresherServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\TusServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\TusServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderServiceInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderServiceTrait;

/**
 * Component class for Joomgallery
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class JoomgalleryComponent extends MVCComponent implements BootableExtensionInterface, RouterServiceInterface
{
  use MessageTrait;
	use AssociationServiceTrait;
	use RouterServiceTrait;
	use HTMLRegistryAwareTrait;

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
  use AccessServiceTrait;
  use ConfigServiceTrait;
  use FileManagerServiceTrait;
  use FilesystemServiceTrait;
  use IMGtoolsServiceTrait;
  use MessengerServiceTrait;
  use RefresherServiceTrait;
  use TusServiceTrait;
  use UploaderServiceTrait;

  /**
   * Storage for the component cache object
   *
   * @var MVCStorage
   */
  public $cache = false;

  /**
   * Storage for the current component version
   *
   * @var string
   */
  public $version = '';

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

    if(!$this->cache)
    {
      $this->cache = new JoomCache();
    }

    if(!$this->version)
    {
      $xml  = \simplexml_load_file(Path::clean(JPATH_ADMINISTRATOR . '/components/com_joomgallery/joomgallery.xml'));
      $this->version = (string) $xml->version;
    }
  }
}
