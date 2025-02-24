<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Migration;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Toolbar\ToolbarHelper;
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
    $this->script  = $this->get('Script');
    $this->scripts = $this->get('Scripts');    
    $this->layout  = $this->app->input->get('layout', 'default', 'cmd');
    $this->error   = array();

    // Add page title
    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_MIGRATION'), 'migration');

    if($this->layout != 'default')
    {
      $this->app->input->set('hidemainmenu', true);
      ToolbarHelper::cancel('migration.cancel', 'COM_JOOMGALLERY_MIGRATION_INERRUPT_MIGRATION');
      ToolbarHelper::help('', false, Text::_('COM_JOOMGALLERY_WEBSITE_HELP_URL').'/migration/'. \strtolower($this->script->name) . '?tmpl=component');

      // Check if requested script exists
      if(!\in_array($this->script->name, \array_keys($this->scripts)))
      {
        // Requested script does not exists
        \array_push($this->error, 'COM_JOOMGALLERY_MIGRATION_SCRIPT_NOT_EXIST');
      }
      else
      {
        // Try to load the migration params
        $this->params = $this->get('Params');

        // Check if migration params exist
        if(\is_null($this->params) && $this->layout != 'step1')
        {
          // Requested script does not exists
          \array_push($this->error, 'COM_JOOMGALLERY_SERVICE_MIGRATION_STEP_NOT_AVAILABLE');
        }
      }

      switch($this->layout) 
      {
        case 'step1':
          // Load migration form
          $this->form = $this->get('Form');
          break;

        case 'step2':
          // Load precheck results
          $this->precheck = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$this->script->name.'.step2.results', array());
          $this->success  = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$this->script->name.'.step2.success', false);
          break;

        case 'step3':
          // Data for the migration view
          $this->precheck     = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$this->script->name.'.step2.success', false);
          $this->migrateables = $this->get('Migrateables');
          $this->migration    = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$this->script->name.'.step3.results', array());
          $this->dependencies = $this->get('Dependencies');
          $this->completed    = $this->get('Completed');
          break;

        case 'step4':
          // Load postcheck results
          $this->postcheck      = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$this->script->name.'.step4.results', array());
          $this->success        = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$this->script->name.'.step4.success', false);
          $this->sourceDeletion = $this->get('sourceDeletion');

          $this->openMigrations = $this->get('IdList');
          if(!empty($this->openMigrations) && \key_exists($this->script->name, $this->openMigrations))
          {
            $this->openMigrations = $this->openMigrations[$this->script->name];
          }
          else
          {
            $this->openMigrations = array();
          }
          break;
        
        default:
          break;
      }
    }
    else
    {
      // default view
      foreach($this->scripts as $script)
      {
        $this->app->getLanguage()->load('com_joomgallery.migration.'.$script['name'], _JOOM_PATH_ADMIN);
      }

      // ID list of open migrations
      $this->openMigrations = $this->get('IdList');
    }

		// Check for errors.
		if(\count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

		parent::display($tpl);
	}
}
