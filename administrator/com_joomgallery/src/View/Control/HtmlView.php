<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Control;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * HTML View class for the control panel view
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
  protected $state;

  protected $item;

  protected $form;

  /**
   * HTML view display method
   *
   * @param   string  $tpl  The name of the template file to parse
   * @return  void
   * @since   4.0.0
   */
  public function display($tpl = null)
  {
    $this->params = ComponentHelper::getParams('com_joomgallery');

    ToolBarHelper::title(Text::_('COM_JOOMGALLERY_CONTROL_PANEL') , 'home');

    $lang          = Factory::getLanguage();
    $this->canDo   = JoomHelper::getActions();
    $this->modules = ModuleHelper::getModules('joom_cpanel');

    // get statistic data
    $this->statisticdata = $this->getStatisticData();

    // get gallery info data
    $this->galleryinfodata = $this->getGalleryInfoData();

    // get installed extensions data
    $this->galleryinstalledextensionsdata = $this->getInstalledExtensionsData();

    $this->addToolbar();

/*
    if($this->_config->get('jg_checkupdate'))
    {
      $this->available_extensions = JoomExtensions::getAvailableExtensions();
      $this->params->set('url_fopen_allowed', @ini_get('allow_url_fopen'));
      $this->params->set('curl_loaded', extension_loaded('curl'));

      // If there weren't any available extensions found
      // loading the RSS feed wasn't successful
      if(count($this->available_extensions))
      {
        $this->installed_extensions = JoomExtensions::getInstalledExtensions();
        $this->params->set('show_available_extensions', 1);

        $this->dated_extensions = JoomExtensions::checkUpdate();
        if(count($this->dated_extensions))
        {
          $this->params->set('dated_extensions', 1);
        }
        else
        {
          $this->params->set('dated_extensions', 0);
          $this->params->set('show_update_info_text', 1);
        }
      }
    }
    else
    {
      $this->params->set('dated_extensions', 0);
    }
*/

    parent::display($tpl);
  }

/**
 * Method to get the statistic data
 *
 * @return   array   Array with statistic data
 *
 * @since 4.0.0
 */
protected function getStatisticData()
{
  $statisticdata = array();

  $db = Factory::getDbo();

  $query = $db->getQuery(true)
    ->select($db->quoteName('id'))
    ->from($db->quoteName('#__joomgallery_categories'))
    ->where($db->quoteName('published') . ' = ' . $db->quote(1));
  $db->setQuery($query);
  $db->execute();

  $statisticdata['publishedcategories'] = $db->getNumRows() - 1; // Count-1 because Root cat is not counted

  $query = $db->getQuery(true)
              ->select($db->quoteName('id'))
              ->from($db->quoteName('#__joomgallery_categories'))
              ->where($db->quoteName('published') . ' = ' . $db->quote(0));
  $db->setQuery($query);
  $db->execute();

  $statisticdata['unpublishedcategories'] = $db->getNumRows();

  $query = $db->getQuery(true)
              ->select($db->quoteName('id'))
              ->from($db->quoteName('#__joomgallery'))
              ->where($db->quoteName('published') . ' = ' . $db->quote(1));
  $db->setQuery($query);
  $db->execute();

  $statisticdata['publishedimages'] = $db->getNumRows();

  $query = $db->getQuery(true)
              ->select($db->quoteName('id'))
              ->from($db->quoteName('#__joomgallery'))
              ->where($db->quoteName('published') . ' = ' . $db->quote(0));
  $db->setQuery($query);
  $db->execute();

  $statisticdata['unpublishedimages'] = $db->getNumRows();

  $query = $db->getQuery(true)
              ->select($db->quoteName('id'))
              ->from($db->quoteName('#__joomgallery'))
              ->where($db->quoteName('approved') . ' = ' . $db->quote(0));
  $db->setQuery($query);
  $db->execute();

  $statisticdata['unapprovedimages'] = $db->getNumRows();

  return $statisticdata;
}

/**
 * Method to get the Gallery info data
 *
 * @return   array   Array with info data
 *
 * @since 4.0.0
 */
protected function getGalleryInfoData()
{
  $galleryinfodata = array();

  $db = Factory::getDbo();

  $query = $db->getQuery(true)
              ->select($db->quoteName('manifest_cache'))
              ->from($db->quoteName('#__extensions'))
              ->where($db->quoteName('element') . ' = ' . $db->quote('com_joomgallery'));
  $db->setQuery($query);

  $galleryinfodata = json_decode($db->loadResult(), true);

  return $galleryinfodata;
}

/**
 * Method to get the installed JoomgGallery extensions
 *
 * @return   array   Array with extensions data
 *
 * @since 4.0.0
 */
protected function getInstalledExtensionsData()
{
  $InstalledExtensionsData = array();

  $db = Factory::getDbo();

  $query = $db->getQuery(true)
              ->select($db->quoteName(array('extension_id', 'enabled', 'manifest_cache')))
              ->from($db->quoteName('#__extensions'))
              ->where($db->quoteName('name')     . ' like ' . $db->quote('%joomgallery%'))
              ->where($db->quoteName('name')     . ' != '   . $db->quote('com_joomgallery'))
              ->orWhere($db->quoteName('folder') . ' like ' . $db->quote('%joomgallery%'));

  $db->setQuery($query);

  $InstalledExtensionsData = $db->loadRowList();

  return $InstalledExtensionsData;
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
    $state   = $this->get('State');
    $canDo   = JoomHelper::getActions('category');
    $toolbar = Toolbar::getInstance('toolbar');

    // Categories button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=categories" class="button-folder-open btn btn-primary"><span class="icon-folder-open" title="'.Text::_('JCATEGORIES').'"></span> '.Text::_('JCATEGORIES').'</a>';
    $toolbar->appendButton('Custom', $html);

    // Images button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=images" class="btn btn-primary"><span class="icon-images" title="'.Text::_('COM_JOOMGALLERY_IMAGES').'"></span> '.Text::_('COM_JOOMGALLERY_IMAGES').'</a>';
    $toolbar->appendButton('Custom', $html);

    // Multiple add button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=image&amp;layout=upload" class="btn btn-primary"><span class="icon-upload" title="'.Text::_('Upload').'"></span> '.Text::_('Upload').'</a>';
    $toolbar->appendButton('Custom', $html);

    // Tags button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=tags" class="btn btn-primary"><span class="icon-tags" title="'.Text::_('COM_JOOMGALLERY_TAGS').'"></span> '.Text::_('COM_JOOMGALLERY_TAGS').'</a>';
    $toolbar->appendButton('Custom', $html);

    // Configs button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=configs" class="btn btn-primary"><span class="icon-sliders-h" title="'.Text::_('COM_JOOMGALLERY_CONFIG_SETS').'"></span> '.Text::_('COM_JOOMGALLERY_CONFIG_SETS').'</a>';
    $toolbar->appendButton('Custom', $html);

    // Maintenance button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=faulties" class="btn btn-primary"><span class="icon-wrench" title="'.Text::_('COM_JOOMGALLERY_MAINTENANCE').'"></span> '.Text::_('COM_JOOMGALLERY_MAINTENANCE').'</a>';
    $toolbar->appendButton('Custom', $html);

    if($this->canDo->get('core.admin'))
    {
      ToolBarHelper::preferences('com_joomgallery');
    }

    // Set sidebar action
    Sidebar::setAction('index.php?option=com_joomgallery&view=control');
  }
}