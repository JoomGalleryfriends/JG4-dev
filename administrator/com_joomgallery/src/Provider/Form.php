<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Provider;

use \Joomgallery\Component\Joomgallery\Administrator\Form\FormFactory;
use \Joomla\CMS\Form\FormFactoryInterface;
use \Joomla\Database\DatabaseInterface;
use \Joomla\DI\Container;
use \Joomla\DI\ServiceProviderInterface;

// No direct access
\defined('_JEXEC') or die;

/**
 * Service provider for the form dependency
 *
 * @since  4.0.0
 */
class Form implements ServiceProviderInterface
{
  /**
   * Registers the service provider with a DI container.
   *
   * @param   Container  $container  The DI container.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function register(Container $container)
  {
    $container->set(FormFactoryInterface::class, function (Container $container) {
          $factory = new FormFactory();
          $factory->setDatabase($container->get(DatabaseInterface::class));
          return $factory;
        });
  }

  /*
  public function register(Container $container)
  {
    $container->alias('form.factory', FormFactoryInterface::class)
      ->alias(FormFactory::class, FormFactoryInterface::class)
      ->share(FormFactoryInterface::class, function (Container $container)
        {
          $factory = new FormFactory();
          $factory->setDatabase($container->get(DatabaseInterface::class));
          return $factory;
        },
        true
      );
  }
  */
}
