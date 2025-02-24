<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Test;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Table\Asset;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\Factory;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for testing.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
    $this->app->enqueueMessage('This view is for testing purposes only!<br><small>Add "testing=1" to your query parameters (URL) to access this testing view.</small>', 'warning');

		if(!$this->app->input->get('testing', 1))
		{
			return;
		}

    // Rebuild assets table
    if($this->app->input->get('rebuilt', '') == 'asset')
    {
      $assetTable = new Asset(Factory::getContainer()->get(DatabaseInterface::class));
      if($assetTable->rebuild())
      {
        $this->app->enqueueMessage('Asset-Table rebuilt successfully.');
      }
      else
      {
        $this->app->enqueueMessage('Error when rebuilt Asset-Table.');
      }

      return;
    }
	}
}
