<?php

/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\View\Categoryform;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

/**
 * View class for a list of Joomgallery.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The image object
	 *
	 * @var  \stdClass
	 */
	protected $item;

	/**
	 * The page parameters
	 *
	 * @var    array
	 *
	 * @since  4.0.0
	 */
	protected $params = array();

	/**
	 * The model state
	 *
	 * @var   \Joomla\CMS\Object\CMSObject
	 */
	protected $state;

  /**
	 * The form object
	 *
	 * @var   \Joomla\CMS\Form\Form
	 */
	protected $form;

	/**
	 * The Access service class
	 *
	 * @var   \Joomgallery\Component\Joomgallery\Administrator\Service\Access\Access
	 */
	protected $acl;

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
		$this->state  = $this->get('State');
		$this->params = $this->get('Params');
		$this->acl    = $this->get('Acl');
		$this->item   = $this->get('Item');
		$this->form		= $this->get('Form');

    // Get return page
    $this->return_page = $this->get('ReturnPage');

    // Check acces view level
		if(!in_array($this->item->access, $this->getCurrentUser()->getAuthorisedViewLevels()))
    {
      Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 'error');
    }

		// Check for errors.
		if(count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function _prepareDocument()
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu();
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
			$title = $app->get('sitename');
		}
		elseif($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif($app->get('sitename_pagetitles', 0) == 2)
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

    // Add Breadcrumbs
    $pathway = $app->getPathway();
    $breadcrumbList = Text::_('COM_JOOMGALLERY_CATEGORIES');

    if(!in_array($breadcrumbList, $pathway->getPathwayNames()))
    {
      $pathway->addItem($breadcrumbList, "index.php?option=com_joomgallery&view=categories");
    }

    $breadcrumbTitle = isset($this->item->id) ? Text::_("JGLOBAL_EDIT") : Text::_("JGLOBAL_FIELD_ADD");

    if(!in_array($breadcrumbTitle, $pathway->getPathwayNames()))
    {
      $pathway->addItem($breadcrumbTitle);
    }
	}
}
