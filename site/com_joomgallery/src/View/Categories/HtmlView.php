<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\View\Categories;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a list of Joomgallery.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	protected $items;

	protected $pagination;

	/**
	 * The page parameters
	 *
	 * @var    array
	 *
	 * @since  4.0.0
	 */
	protected $params = array();

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function display($tpl = null)
	{
		$this->state         = $this->get('State');
    $this->params        = $this->get('Params');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if(\count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(\implode("\n", $errors), 500);
		}

    // Check if is userspace is enabled
    // Check access permission (ACL)
    if($this->params['configs']->get('jg_userspace', 1, 'int') == 0 || !$this->getAcl()->checkACL('manage', 'com_joomgallery'))
    {
      if($this->params['configs']->get('jg_userspace', 1, 'int') == 0)
      {
        $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_CATEGORIES_VIEW_NO_ACCESS'), 'message');
      }

      // Redirect to category view
      $this->app->redirect(Route::_('index.php?option='._JOOM_OPTION.'&view=category&id=1'));
      
      return false;
    }

		// Preprocess the list of items to find ordering divisions.
		foreach($this->items as &$item)
		{
			$this->ordering[$item->parent_id][] = $item->id;
		}

		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	protected function _prepareDocument()
	{
		$menus = $this->app->getMenu();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if($menu)
		{
			$this->params['menu']->def('page_heading', $this->params['menu']->get('page_title', $menu->title));
		}
		else
		{
			$this->params['menu']->def('page_heading', Text::_('JoomGallery'));
		}

		$title = $this->params['menu']->get('page_title', '');

		if(empty($title))
		{
			$title = $this->app->get('sitename');
		}
		elseif($this->app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif($this->app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
		}

		$this->document->setTitle($title);

		if($this->params['menu']->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params['menu']->get('menu-meta_description'));
		}

		if($this->params['menu']->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params['menu']->get('menu-meta_keywords'));
		}

		if($this->params['menu']->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params['menu']->get('robots'));
		}

		if(!$this->isMenuCurrentView($menu))
		{
			// Add Breadcrumbs
			$pathway = $this->app->getPathway();
			$breadcrumbTitle = Text::_('COM_JOOMGALLERY_CATEGORIES');

			if(!\in_array($breadcrumbTitle, $pathway->getPathwayNames()))
			{
				$pathway->addItem($breadcrumbTitle, '');
			}
		}
	}
}
