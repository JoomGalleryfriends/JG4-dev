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
use \Joomla\CMS\Feed\FeedFactory;
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

    // get available extensions data
    $this->galleryavailableextensionsdata = $this->getAvailableExtensions();

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
 * Method to get the statistic data
 *
 * @return   array   Array with statistic data
 *
 * @since 4.0.0
 */
protected function getStatisticData()
{
  $statisticdata = array();

  $db = Factory::getContainer()->get('DatabaseDriver');

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

  $db = Factory::getContainer()->get('DatabaseDriver');

  $query = $db->getQuery(true)
              ->select($db->quoteName('manifest_cache'))
              ->from($db->quoteName('#__extensions'))
              ->where($db->quoteName('element') . ' = ' . $db->quote('com_joomgallery'));
  $db->setQuery($query);

  $galleryinfodata = json_decode($db->loadResult(), true);

  return $galleryinfodata;
}

  /**
   * Returns all downloadable extensions developed by JoomGallery::ProjectTeam
   * with some additional information like the current version number or a
   * short description of the extension
   *
   * @return  array Two-dimensional array with extension information
   * @since   1.5.0
   */
  protected function getAvailableExtensions()
  {
    static $extensions;

    if(isset($extensions))
    {
      return $extensions;
    }

    // Check whether the german or the english RSS file should be loaded
    $subdomain = '';
    $language = Factory::getLanguage();
    if(strpos($language->getTag(), 'de-') === false)
    {
      $subdomain = 'en.';
    }

    $site   = 'https://www.'.$subdomain.'joomgalleryfriends.net';
    $site2  = 'https://'.$subdomain.'joomgalleryfriends.net';
    $rssurl = $site.'/components/com_newversion/rss/extensions4.rss';

    // Get RSS parsed object
    $rssDoc = false;
    try
    {
      $feed = new FeedFactory;
      $rssDoc = $feed->getFeed($rssurl);
    }
    catch (InvalidArgumentException $e)
    {
    }
    catch (RunTimeException $e)
    {
    }

    $extensions = array();
    if($rssDoc != false)
    {
      for($i = 0; isset($rssDoc[$i]); $i++)
      {
        $item = $rssDoc[$i];
        $name = $item->title;

        // The data type is delivered as the name of the first category
        $categories = $item->categories;
        $type = key($categories);
        switch($type)
        {
          case 'general':
            $description  = $item->content;
            $link         = $item->uri;
            if(!is_null($description) && $description != '')
            {
              $extensions[$name]['description']   = $description;
            }
            if(!is_null($link) && $link != $site && $link != $site2)
            {
              $extensions[$name]['downloadlink']  = $link;
            }
            break;
          case 'version':
            $version  = $item->content;
            $link     = $item->uri;
            if(!is_null($version) && $version != '')
            {
              $extensions[$name]['version']       = $version;
            }
            if(!is_null($link) && $link != $site && $link != $site2)
            {
              $extensions[$name]['releaselink']   = $link;
            }
            break;
          case 'autoupdate':
            $xml  = $item->content;
            $link = $item->uri;
            if(!is_null($xml) && $xml != '')
            {
              $extensions[$name]['xml']           = $xml;
            }
            if(!is_null($link) && $link != $site && $link != $site2)
            {
              $extensions[$name]['updatelink']    = $link;
            }
            break;
          default:
            break;
        }
      }

      // Sort the extensions in alphabetical order
      ksort($extensions);
    }
    return $extensions;
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

  $db = Factory::getContainer()->get('DatabaseDriver');

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

    // Images button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=images" class="btn btn-primary"><span class="icon-images" title="'.Text::_('COM_JOOMGALLERY_IMAGES').'"></span> '.Text::_('COM_JOOMGALLERY_IMAGES').'</a>';
    $toolbar->appendButton('Custom', $html);

    // Multiple add button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=image&amp;layout=upload" class="btn btn-primary"><span class="icon-upload" title="'.Text::_('Upload').'"></span> '.Text::_('Upload').'</a>';
    $toolbar->appendButton('Custom', $html);

    // Categories button
    $html = '<a href="index.php?option=com_joomgallery&amp;view=categories" class="button-folder-open btn btn-primary"><span class="icon-folder-open" title="'.Text::_('JCATEGORIES').'"></span> '.Text::_('JCATEGORIES').'</a>';
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