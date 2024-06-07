<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\View\Image;

// No direct access
defined('_JEXEC') or die;

use \Joomgallery\Component\Joomgallery\Administrator\View\Image\RawView as AdminRawView;

/**
 * Raw view class for a single Image.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class RawView extends AdminRawView
{
  /**
	 * Postprocessing the image after retrieving the image ressource
	 *
	 * @param   \stdClass  $file_info    Object with file information
   * @param   resource   $resource     Image resource
	 *
	 * @return void
	 */
  public function ppImage(&$file_info, &$resource)
  {
    // postprocessing image
    $tmp = 1;

    return;
  }
}