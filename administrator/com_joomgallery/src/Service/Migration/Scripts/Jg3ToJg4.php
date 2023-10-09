<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                              **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Migration\Scripts;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Filesystem\Path;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\Migration;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\Targetinfo;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\MigrationInterface;

/**
 * Migration script class
 * JoomGallery 3.x to JoomGallery 4.x
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class Jg3ToJg4 extends Migration implements MigrationInterface
{
  /**
	 * Name of the migration script to be used.
	 *
	 * @var   string
	 *
	 * @since  4.0.0
	 */
	protected $name = 'Jg3ToJg4';

  /**
   * List of content types which can be migrated with this script
   * Use the singular form of the content type (e.g image, not images)
   *
   * @var    array
   * 
   * @since  4.0.0
   */
  protected $contentTypes = array('image', 'category');

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Returns an object with compatibility info for this migration script.
   * 
   * @param   string       $type    Select if you get source or destination info
   *
   * @return  Targetinfo   Compatibility info object
   * 
   * @since   4.0.0
   */
  public function getTargetinfo(string $type = 'source'): Targetinfo
  {
    $info = new Targetinfo();

    $info->set('target', $type);
    $info->set('type','component');

    if($type === 'source')
    {
      $info->set('extension','JoomGallery');
      $info->set('min', '3.6.0');
      $info->set('max', '3.6.99');
      $info->set('php_min', '5.6.0');
    }
    elseif($type === 'destination')
    {
      $info->set('extension','com_joomgallery');
      $info->set('min', '4.0.0');
      $info->set('max', '5.99.99');
      $info->set('php_min', '7.4.0');
    }
    else
    {
      throw new \Exception('Type must be eighter "source" or "destination", but "'.$type.'" given.', 1);
    }

    return $info;
  }

  /**
   * Returns the XML object of the source extension
   *
   * @return  \SimpleXMLElement   Extension XML object
   * 
   * @since   4.0.0
   */
  public function getSourceXML(): \SimpleXMLElement
  {
    return \simplexml_load_file(Path::clean(JPATH_ADMINISTRATOR . '/components/com_joomgallery/joomgallery_old.xml'));
  }

  /**
   * Returns a list of involved source directories.
   *
   * @return  array    List of paths
   * 
   * @since   4.0.0
   */
  public function getSourceDirs(): array
  {
    $dirs = array( $this->params->get('orig_path'),
                   $this->params->get('detail_path'),
                   $this->params->get('thumb_path')
                  );

    return $dirs;
  }

  /**
   * Returns the Joomla root path of the source.
   *
   * @return  string    Source Joomla root path
   * 
   * @since   4.0.0
   */
  public function getSourceRootPath(): string
  {
    if($this->params->get('same_joomla', 1))
    {
      $root = Path::clean(JPATH_ROOT . '/');
    }
    else
    {
      $root = Path::clean($this->params->get('joomla_path'));

      if(\substr($root, -1) != '/')
      {
        $root = Path::clean($root . '/');
      }
    }

    return $root;
  }

  /**
   * Returns a list of involved source tables.
   *
   * @return  array    List of table names (Joomla style, e.g #__joomgallery)
   * 
   * @since   4.0.0
   */
  public function getSourceTables(): array
  {
    $tables = array( '#__joomgallery',
                     '#__joomgallery_image_details',
                     '#__joomgallery_catg',
                     '#__joomgallery_category_details',
                     '#__joomgallery_comments',
                     '#__joomgallery_config',
                     '#__joomgallery_countstop',
                     '#__joomgallery_maintenance',
                     '#__joomgallery_nameshields',
                     '#__joomgallery_orphans',
                     '#__joomgallery_users',
                     '#__joomgallery_votes'
                    );

    if($this->params->get('same_db'))
    {
      foreach($tables as $key => $table)
      {
        $tables[$key] = $table . '_old';
      }
    }

    return $tables;
  }

  /**
   * Returns an associative array containing the record data from source.
   *
   * @param   string   $type   Name of the content type
   * @param   int      $pk     The primary key of the content type
   * 
   * @return  array  Record data
   * 
   * @since   4.0.0
   */
  public function getData(string $type, int $pk): array
  {
    switch ($type)
    {
      case 'category':
        return $this->getCategoryData($pk);
        break;
      
      default:
        return array();
        break;
    }
  }

  /**
   * Converts data from source into the structure needed for JoomGallery.
   *
   * @param   array   $data   Data received from getData() method.
   * 
   * @return  array   Converted data to save into JoomGallery
   * 
   * @since   4.0.0
   */
  public function convertData(array $data): array
  {
    return array();
  }

  /**
   * Fetches an array of images from source to be used for creating the imagetypes
   * for the current image.
   *
   * @param   array   $data   Record data received from getData()
   * 
   * @return  array   List of images from sources used to create the new imagetypes
   *                  1. If imagetypes get recreated: array('image/source/path')
   *                  2. If imagetypes get copied:    array('original' => 'image/source/path1', 'detail' => 'image/source/path2', ...)
   * 
   * @since   4.0.0
   */
  public function getImageSource(array $data): array
  {
    return array();
  }

  /**
   * Returns an associative array containing the category data from source.
   *
   * @param   int      $pk     The primary key of the category
   * 
   * @return  array  Category data array
   * 
   * @since   4.0.0
   */
  public function getCategoryData(int $pk): array
  {
    return array();
  }
}