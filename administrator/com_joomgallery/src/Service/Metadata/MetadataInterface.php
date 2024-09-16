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

\defined('_JEXEC') or die;

/**
* Interface for the metadata class
*
* @since  4.0.0
*/
interface MetadataInterface
{
  /**
   * First method
   *
   * @return  string
   *
   * @since   4.0.0
   */
  public function hello(): string;
}