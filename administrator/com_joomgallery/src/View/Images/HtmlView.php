<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Images;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use \Joomla\CMS\Uri\Uri;

/**
 * View class for a list of Images.
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

		ToolbarHelper::title(Text::_('COM_JOOMGALLERY_IMAGE_MANAGER'), "image");

		$toolbar = Toolbar::getInstance('toolbar');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/src/View/Images';

		if(file_exists($formPath))
		{
			if($canDo->get('core.create'))
			{
				$toolbar->addNew('image.add');
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
				$childBar->publish('images.publish')->listCheck(true);
				$childBar->unpublish('images.unpublish')->listCheck(true);
				$childBar->archive('images.archive')->listCheck(true);
			}
			elseif(isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				$toolbar->delete('images.delete')
				->text('JTOOLBAR_EMPTY_TRASH')
				->message('JGLOBAL_CONFIRM_DELETE')
				->listCheck(true);
			}

			$childBar->standardButton('duplicate')
				->text('JTOOLBAR_DUPLICATE')
				->icon('fas fa-copy')
				->task('images.duplicate')
				->listCheck(true);

			if(isset($this->items[0]->checked_out))
			{
				$childBar->checkin('images.checkin')->listCheck(true);
			}

			if(isset($this->items[0]->state))
			{
				$childBar->trash('images.trash')->listCheck(true);
			}
		}

		// Show trash and delete for components that uses the state field
		if(isset($this->items[0]->state))
		{
			if($this->state->get('filter.state') == ContentComponent::CONDITION_TRASHED && $canDo->get('core.delete'))
			{
				$toolbar->delete('images.delete')
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
		Sidebar::setAction('index.php?option=com_joomgallery&view=images');
	}

	/**
	 * Method to order fields
	 *
	 * @return void
	 */
	protected function getSortFields()
	{
		return array(
			'a.`ordering`' => Text::_('JGRID_HEADING_ORDERING'),
			'a.`hits`' => Text::_('COM_JOOMGALLERY_COMMON_HITS'),
			'a.`downloads`' => Text::_('COM_JOOMGALLERY_COMMON_DOWNLOADS'),
			'a.`approved`' => Text::_('COM_JOOMGALLERY_COMMON_APPROVED'),
			'a.`imgtitle`' => Text::_('JGLOBAL_TITLE'),
			'a.`catid`' => Text::_('JCATEGORY'),
			'a.`published`' => Text::_('JSTATUS'),
			'a.`imgauthor`' => Text::_('JAUTHOR'),
			'a.`language`' => Text::_('JGRID_HEADING_LANGUAGE'),
			'a.`access`' => Text::_('JGRID_HEADING_ACCESS'),
			'a.`created_by`' => Text::_('JGLOBAL_FIELD_CREATED_BY_LABEL'),
			'a.`id`' => Text::_('JGRID_HEADING_ID'),
			'a.`imgdate`' => Text::_('JDATE'),
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
