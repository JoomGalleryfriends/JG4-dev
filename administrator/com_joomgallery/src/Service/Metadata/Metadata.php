<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2024  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Metadata;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;

/**
 * Metadata Base Class
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class Metadata implements MetadataInterface
{
  use ServiceTrait;

  /**
   * First method
   *
   * @return  string
   *
   * @since   4.0.0
   */
  public function hello(): string
  {
    return 'Hello from Metadata-Service...';
  }
}