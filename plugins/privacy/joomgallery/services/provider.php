<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\DI\Container;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomgallery\Plugin\Privacy\Joomgallery\Extension\JoomgalleryPrivacy;

return new class implements ServiceProviderInterface
{
  public function register(Container $container)
  {
    $container->set(
      PluginInterface::class,
      function (Container $container)
      {
        $plugin     = PluginHelper::getPlugin('privacy', 'joomgallery');
        $dispatcher = $container->get(DispatcherInterface::class);

        /** @var \Joomla\CMS\Plugin\CMSPlugin $plugin */
        $plugin = new JoomgalleryPrivacy($dispatcher, (array) $plugin);
        $plugin->setApplication(Factory::getApplication());

        return $plugin;
      }
    );
  }
};