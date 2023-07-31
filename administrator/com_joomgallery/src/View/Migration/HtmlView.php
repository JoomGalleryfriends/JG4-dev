<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Migration;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a single Tag.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	protected $scripts;

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
    $this->scripts = $this->get('Scripts');
    $this->script  = $this->app->input->get('script', '', 'cmd');
    $this->layout  = $this->app->input->get('layout', 'default', 'cmd');
    $this->error   = array();

    // Add page title
    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_MIGRATION'), 'migration');

    if($this->layout != 'default')
    {
      $this->app->input->set('hidemainmenu', true);
      ToolbarHelper::cancel('migration.cancel', 'JTOOLBAR_CLOSE');

      // Check if requested script exists
      if(!\in_array($this->script, \array_keys($this->scripts)))
      {
        // Requested script does not exists
        \array_push($this->error, 'COM_JOOMGALLERY_MIGRATION_SCRIPT_NOT_EXIST');
      }
    }

		// Check for errors.
		if(count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

		parent::display($tpl);
	}
}
