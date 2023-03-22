<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Image;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Filesystem\File as JFile;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * Raw view class for a single Image.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class RawView extends JoomGalleryView
{
  /**
	 * Raw view display method, outputs one image
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function display($tpl = null)
	{
    // Get request variables
    $type = $this->app->input->get('type', 'thumbnail', 'word');
    $id   = $this->app->input->get('id', 0, 'int');

    // Get image path
    $img_path = JoomHelper::getImg($id, $type, false, false);

    // Create filesystem service
    $this->component->createFilesystem();

    // Have a Read
    // https://stackoverflow.com/questions/1851849/output-an-image-in-php

    // Get image ressource
    try
    {
      list($file_info, $ressource) = $this->component->getFilesystem()->getResource($img_path);
    }
    catch (InvalidPathException $e)
    {
      $this->app->enqueueMessage($e, 'error');
      $this->app->redirect(Route::_('index.php', false), 404);
    }

    // Set mime encoding
    $this->document->setMimeEncoding($file_info->mime_type);

    // Set header to specify the file name
    $disposition = 'inline';
    $this->app->setHeader('Content-disposition', $disposition.'; filename='.basename($img_path));

    echo \stream_get_contents($ressource);
  }
}
