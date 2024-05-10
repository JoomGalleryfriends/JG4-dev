<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Faulties;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a list of Faulties.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	protected $items;

	protected $pagination;

	protected $state;

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
    $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_NOT_YET_AVAILABLE'), 'warning');

    return;

    $this->items = $this->get('Items');
		$this->state = $this->get('State');
		$this->pagination = $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if(count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

		$this->addToolbar();

		$this->sidebar = Sidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function addToolbar()
	{
		$state = $this->get('State');
		$canDo = JoomHelper::getActions();

		ToolbarHelper::title(Text::_('COM_JOOMGALLERY_MAINTENANCE'), "wrench");

		$toolbar = Toolbar::getInstance('toolbar');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/src/View/Faulties';

		// Show button back to control panel
		$html = '<a href="index.php?option=com_joomgallery&amp;view=control" class="btn btn-primary"><span class="icon-arrow-left-4" title="'.Text::_('COM_JOOMGALLERY_CONTROL_PANEL').'"></span> '.Text::_('COM_JOOMGALLERY_CONTROL_PANEL').'</a>';
		$toolbar->appendButton('Custom', $html);

		if(file_exists($formPath))
		{
			if($canDo->get('core.create'))
			{
				//$toolbar->addNew('faulty.add');
			}
		}

		if($canDo->get('core.edit.state')  || count($this->transitions))
		{
			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('fas fa-ellipsis-h')
				->buttonClass('btn btn-action')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();

			if(isset($this->items[0]->state))
			{
				$childBar->publish('faulties.publish')->listCheck(true);
				$childBar->unpublish('faulties.unpublish')->listCheck(true);
				$childBar->archive('faulties.archive')->listCheck(true);
			}
			elseif(isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				$toolbar->delete('faulties.delete')
				->text('JTOOLBAR_EMPTY_TRASH')
				->message('JGLOBAL_CONFIRM_DELETE')
				->listCheck(true);
			}

			$childBar->standardButton('duplicate')
				->text('JTOOLBAR_DUPLICATE')
				->icon('fas fa-copy')
				->task('faulties.duplicate')
				->listCheck(true);

			if(isset($this->items[0]->checked_out))
			{
				$childBar->checkin('faulties.checkin')->listCheck(true);
			}

			if(isset($this->items[0]->state))
			{
				$childBar->trash('faulties.trash')->listCheck(true);
			}
		}

		// Show trash and delete for components that uses the state field
		if(isset($this->items[0]->state))
		{
			if($this->state->get('filter.state') == ContentComponent::CONDITION_TRASHED && $canDo->get('core.delete'))
			{
				$toolbar->delete('faulties.delete')
					->text('JTOOLBAR_EMPTY_TRASH')
					->message('JGLOBAL_CONFIRM_DELETE')
					->listCheck(true);
			}
		}

		if($canDo->get('core.admin'))
		{
			$toolbar->preferences('com_joomgallery');
		}

		// Set sidebar action
		Sidebar::setAction('index.php?option=com_joomgallery&view=faulties');
	}

	/**
	 * Method to order fields
	 *
	 * @return void
	 */
	protected function getSortFields()
	{
		return array(
			'a.`id`' => Text::_('JGRID_HEADING_ID'),
		);
	}

	/**
	 * Check if state is set
	 *
	 * @param   mixed  $state  State
	 *
	 * @return bool
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}
}
