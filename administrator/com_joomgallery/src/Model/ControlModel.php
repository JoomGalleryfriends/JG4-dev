<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Feed\FeedFactory;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Control model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ControlModel extends BaseDatabaseModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'control';

  /**
   * Method to get the statistic data
   *
   * @return   array   Array with statistic data
   *
   * @since 4.0.0
   */
  public function getStatisticData()
  {
    $statisticdata = array();

    $db = Factory::getContainer()->get(DatabaseInterface::class);

    $query = $db->getQuery(true)
      ->select($db->quoteName('id'))
      ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
      ->where($db->quoteName('published') . ' = ' . $db->quote(1));
    $db->setQuery($query);
    $db->execute();

    $statisticdata['publishedcategories'] = $db->getNumRows() - 1; // Count-1 because Root cat is not counted

    $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
                ->where($db->quoteName('published') . ' = ' . $db->quote(0));
    $db->setQuery($query);
    $db->execute();

    $statisticdata['unpublishedcategories'] = $db->getNumRows();

    $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName(_JOOM_TABLE_IMAGES))
                ->where($db->quoteName('published') . ' = ' . $db->quote(1));
    $db->setQuery($query);
    $db->execute();

    $statisticdata['publishedimages'] = $db->getNumRows();

    $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName(_JOOM_TABLE_IMAGES))
                ->where($db->quoteName('published') . ' = ' . $db->quote(0));
    $db->setQuery($query);
    $db->execute();

    $statisticdata['unpublishedimages'] = $db->getNumRows();

    $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName(_JOOM_TABLE_IMAGES))
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
  public function getGalleryInfoData()
  {
    $galleryinfodata = array();

    $db = Factory::getContainer()->get(DatabaseInterface::class);

    $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                ->where($db->quoteName('element') . ' = ' . $db->quote(_JOOM_OPTION));
    $db->setQuery($query);

    $galleryinfodata = \json_decode($db->loadResult(), true);

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
  public function getAvailableExtensions()
  {
    static $extensions;

    if(isset($extensions))
    {
      return $extensions;
    }

    // Check whether the german or the english RSS file should be loaded
    $subdomain = '';
    $language = Factory::getApplication()->getLanguage();
    if(\strpos($language->getTag(), 'de-') === false)
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
    catch(\InvalidArgumentException $e)
    {
    }
    catch(\RunTimeException $e)
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
        $type = \key($categories);
        switch($type)
        {
          case 'general':
            $description  = $item->content;
            $link         = $item->uri;
            if(!\is_null($description) && $description != '')
            {
              $extensions[$name]['description']   = $description;
            }
            if(!\is_null($link) && $link != $site && $link != $site2)
            {
              $extensions[$name]['downloadlink']  = $link;
            }
            break;
          case 'version':
            $version  = $item->content;
            $link     = $item->uri;
            if(!\is_null($version) && $version != '')
            {
              $extensions[$name]['version']       = $version;
            }
            if(!\is_null($link) && $link != $site && $link != $site2)
            {
              $extensions[$name]['releaselink']   = $link;
            }
            break;
          case 'autoupdate':
            $xml  = $item->content;
            $link = $item->uri;
            if(!\is_null($xml) && $xml != '')
            {
              $extensions[$name]['xml']           = $xml;
            }
            if(!\is_null($link) && $link != $site && $link != $site2)
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
  public function getInstalledExtensionsData()
  {
    $InstalledExtensionsData = array();

    $db = Factory::getContainer()->get(DatabaseInterface::class);

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
}
