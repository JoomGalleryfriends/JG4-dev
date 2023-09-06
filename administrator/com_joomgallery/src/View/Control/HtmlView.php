<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                              **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
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
    $this->config = JoomHelper::getService('config');

    ToolBarHelper::title(Text::_('COM_JOOMGALLERY_CONTROL_PANEL') , 'home');

    $lang          = Factory::getLanguage();
    $this->canDo   = JoomHelper::getActions();
    $this->modules = ModuleHelper::getModules('joom_cpanel');
    $imglimit      = $this->config->get('jg_control_count_images');

    // Check for allowed number of images
    if($imglimit < 1)
    {
      $imglimit = 1;
    }
    elseif($imglimit > 100)
    {
      $imglimit = 100;
    }

    // get most viewed images
    $this->mostviewedimages = $this->getMostViewedImages($imglimit);

    // get newest images
    $this->newestimages = $this->getNewestImages($imglimit);

    // get best rated images
    $this->bestratedimages = $this->getBestRatedImages($imglimit);

    // get most downloaded images
    $this->mostdownloadedimages = $this->getMostDownloadedImages($imglimit);

    // get statistic data
    $this->statisticdata = $this->getStatisticData();

    // get gallery info data
    $this->galleryinfodata = $this->getGalleryInfoData();

    // get installed extensions data
    $this->galleryinstalledextensionsdata = $this->getInstalledExtensionsData();

    // get php system info
    $this->php_settings = [
        'memory_limit'        => ini_get('memory_limit'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size'       => ini_get('post_max_size'),
        'file_uploads'        => ini_get('file_uploads') == '1',
        'max_execution_time'  => ini_get('max_execution_time'),
        'max_input_vars'      => ini_get('max_input_vars'),
        // 'zlib'                => \extension_loaded('zlib'),
        'zip'                 => \function_exists('zip_open') && \function_exists('zip_read'),
        'gd'                  => \extension_loaded('gd'),
        'exif'                => \extension_loaded('exif'),
        'iconv'               => \function_exists('iconv')
      ];

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
 * Method to get the most viewed images
 *
 * @param    int     $imglimit  limit for the displayed images
 *
 * @return   array   Array with statistic data
 *
 * @since 4.0.0
 */
protected function getMostViewedImages($imglimit)
{
  $popularImages = array();

  $db = Factory::getDbo();

  // alt: $model->getImages('a.hits desc', true, 5, 'a.hits > 0');

  $query = $db->getQuery(true)
              ->select($db->quoteName(array('imgtitle', 'hits', 'id')))
              ->from($db->quoteName('#__joomgallery'))
              ->where($db->quoteName('hits') . ' > ' . $db->quote(0))
              ->order('hits DESC')
              ->setLimit($imglimit);
  $db->setQuery($query);
  $db->execute();

  $popularImages = $db->loadRowList();

  return $popularImages;
}

/**
 * Method to get the newest images
 *
 * @param    int     $imglimit  limit for the displayed images
 *
 * @return   array   Array with statistic data
 *
 * @since 4.0.0
 */
protected function getNewestImages($imglimit)
{
  $newestimages = array();

  $db = Factory::getDbo();

  $query = $db->getQuery(true)
              ->select($db->quoteName(array('imgtitle', 'created_time', 'id')))
              ->from($db->quoteName('#__joomgallery'))
              ->order('created_time DESC')
              ->setLimit($imglimit);
  $db->setQuery($query);
  $db->execute();

  $newestimages = $db->loadRowList();

  return $newestimages;
}

/**
 * Method to get the best rated images
 *
 * @param    int     $imglimit  limit for the displayed images
 *
 * @return   array   Array with statistic data
 *
 * @since 4.0.0
 */
protected function getBestRatedImages($imglimit)
{
  $bestratedimages = array();

  $db = Factory::getDbo();

  $query = $db->getQuery(true)
              ->select(array('imgtitle', 'imgvotesum/imgvotes' .' AS ' . 'rating', 'id'))
              ->from($db->quoteName('#__joomgallery'))
              ->where($db->quoteName('imgvotes') . ' > ' . $db->quote(0))
              ->order('imgvotesum/imgvotes DESC')
              ->setLimit($imglimit);
  $db->setQuery($query);
  $db->execute();

  $bestratedimages = $db->loadRowList();

  return $bestratedimages;
}

/**
 * Method to get the MOst downloaded images
 *
 * @param    int     $imglimit  limit for the displayed images
 *
 * @return   array   Array with statistic data
 *
 * @since 4.0.0
 */
protected function getMostDownloadedImages($imglimit)
{
  $mostdownloadedimages = array();

  $db = Factory::getDbo();

  $query = $db->getQuery(true)
              ->select(array('imgtitle', 'downloads', 'id'))
              ->from($db->quoteName('#__joomgallery'))
              ->where($db->quoteName('downloads') . ' > ' . $db->quote(0))
              ->order('downloads DESC')
              ->setLimit($imglimit);
  $db->setQuery($query);
  $db->execute();

  $mostdownloadedimages = $db->loadRowList();

  return $mostdownloadedimages;
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
    // $toolbar->appendButton('Custom', $html);

    if($this->canDo->get('core.admin'))
    {
      ToolBarHelper::preferences('com_joomgallery');
    }

    // Set sidebar action
    Sidebar::setAction('index.php?option=com_joomgallery&view=control');
  }
}