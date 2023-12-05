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
use \Joomla\CMS\User\UserFactoryInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\Checks;
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
   * True to offer the task migration.removesource for this script
   *
   * @var    boolean
   * 
   * @since  4.0.0
   */
  protected $sourceDeletion = true;

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
   * A list of content type definitions depending on migration source
   * 
   * @param   bool    $names_only  True to load type names only. No migration parameters required.
   * 
   * @return  array   The source types info
   *                  array(tablename, primarykey, isNested, isCategorized, prerequirements, pkstoskip, ismigration, recordname)
   *                  Needed: tablename, primarykey, isNested, isCategorized
   *                  Optional: prerequirements, pkstoskip, ismigration, recordname
   * 
   * @since   4.0.0
   */
  public function defineTypes($names_only = false): array
  {
    // Content type definition array
    // Order of the content types must correspond to the migration order
    // Pay attention to the prerequirements when ordering here !!!
    $types = array( 'category' => array('#__joomgallery_catg', 'cid', true, false, array(), array(1)),
                    'image' =>    array('#__joomgallery', 'id', false, true, array('category')),
                    'catimage' => array(_JOOM_TABLE_CATEGORIES, 'id', false, false, array('category', 'image'), array(1), false, 'category')
                  );

    if($names_only)
    {
      return \array_keys($types);
    }

    if($this->params->get('same_db'))
    {
      foreach($types as $key => $value)
      {
        if(\count($value) < 7 || (\count($value) > 6 && $value[6] !== false))
        {
          $types[$key][0] = $value[0] . '_old';
        }
      }
    }

    return $types;
  }

  /**
   * Converts data from source into the structure needed for JoomGallery.
   *
   * @param   string  $type   Name of the content type
   * @param   array   $data   Data received from getData() method.
   * 
   * @return  array   Converted data to save into JoomGallery
   * 
   * @since   4.0.0
   */
  public function convertData(string $type, array $data): array
  {
    /* How mappings work:
       - Key not in the mapping array:              Nothing changes. Field value can be magrated as it is.
       - 'old key' => 'new key':                    Field name has changed. Old values will be inserted in field with the provided new key.
       - 'old key' => false:                        Field does not exist anymore or value has to be emptied to create new record in the new table.
       - 'old key' => array(string, string, bool):  Field will be merget into another field of type json.
                                                    1. ('destination field name'): Name of the field to be merged into.
                                                    2. ('new field name'): New name of the field created in the destination field. (default: false / retain field name)
                                                    3. ('create child'): True, if a child node shall be created in the destination field containing the field values. (default: false / no child)
    */

    // The fieldname of owner (created_by)
    $ownerFieldName = 'owner';

    // Parameter dependet mapping fields
    $id    = \boolval($this->params->get('source_ids', 0)) ? 'id' : false;
    $owner = \boolval($this->params->get('check_owner', 0)) ? 'created_by' : false;

    // Configure mapping for each content type
    switch($type)
    {
      case 'category':
        // Apply mapping for category table
        $mapping  = array( 'cid' => $id, 'asset_id' => false, 'name' => 'title', 'alias' => false, 'lft' => false, 'rgt' => false, 'level' => false,
                           'owner' => $owner, 'img_position' => false, 'catpath' => 'path', 'params' => array('params', false, false), 
                           'allow_download' => array('params', 'jg_download', false), 'allow_comment' => array('params', 'jg_showcomment', false),
                           'allow_rating' => array('params', 'jg_showrating', false), 'allow_watermark' => array('params', 'jg_dynamic_watermark', false),
                           'allow_watermark_download' => array('params', 'jg_downloadwithwatermark', false)
                          );

        // Adjust parent_id based on already created categories
        if(!\boolval($this->params->get('source_ids', 0)) && $data['parent_id'] > 0)
        {
          $data['parent_id'] = $this->migrateables['category']->successful->get($data['parent_id']);
        }
        
        break;

      case 'image':
        // Apply mapping for image table
        $mapping  = array( 'id' => $id, 'asset_id' => false, 'alias' => false, 'imgfilename' => 'filename', 'imgthumbname' => false,
                           'owner' => $owner, 'params' => array('params', false, false)
                          );

        // Check difference between imgfilename and imgthumbname
        if($data['imgfilename'] !== $data['imgthumbname'])
        {
          $this->component->setError(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_FILENAME_DIFF', $data['id'], $data['alias']));

          return false;
        }

        // Adjust catid with new created categories
        if(!\boolval($this->params->get('source_ids', 0)))
        {
          $data['catid'] = $this->migrateables['category']->successful->get($data['catid']);
        }

        break;
      
      case 'catimage':
        // Dont change the record data
        $mapping = array();

        // Adjust category thumbnail
        if(!empty($data['thumbnail']))
        {
          if($this->migrateables['image']->successful->get($data['thumbnail'], false))
          {
            // Change category thumbnail id based on migrated image id
            $data['thumbnail'] = $this->migrateables['image']->successful->get($data['thumbnail']);
          }
          else
          {
            // Migrated image id not available, set id to 0
            $data['thumbnail'] = 0;
          }          
        }

        break;
      
      default:
        // The table structure is the same
        $mapping = array('id' => $id, 'owner' => $owner);

        break;
    }

    // Check owner
    if(\boolval($this->params->get('check_owner', 0)))
    {
      // Check if user with the provided userid exists
      $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($data[$ownerFieldName]);
      if(!$user || !$user->id)
      {
        $data[$ownerFieldName] = 0;
      }
    }

    // Apply mapping
    return $this->applyConvertData($data, $mapping);
  }

  /**
   * Returns an associative array containing the record data from source.
   *
   * @param   string   $type   Name of the content type
   * @param   int      $pk     The primary key of the content type
   * 
   * @return  array    Associated array of a record data
   * 
   * @since   4.0.0
   */
  public function getData(string $type, int $pk): array
  {
    // Get source table info
    list($tablename, $primarykey) = $this->getSourceTableInfo($type);

    // Get db object
    list($db, $prefix) = $this->getDB('source');
    $query             = $db->getQuery(true);

    // Create the query
    $query->select('*')
          ->from($db->quoteName($tablename))
          ->where($db->quoteName($primarykey) . ' = ' . $db->quote($pk));

    // Reset the query using our newly populated query object.
    $db->setQuery($query);

    // Attempt to load the array
    try
    {
      return $db->loadAssoc();
    }
    catch(\Exception $e)
    {
      $this->component->setError($e->getMessage());

      return array();
    }
  }

  /**
   * Fetches an array of images from source to be used for creating the imagetypes
   * for the current image.
   *
   * @param   array   $data   Source record data received from getData() - before convertData()
   * 
   * @return  array   List of images from sources used to create the new imagetypes
   *                  1. If imagetypes get recreated:    array('image/source/path')
   *                  2. If imagetypes get copied/moved: array('original' => 'image/source/path1', 'detail' => 'image/source/path2', ...)
   * 
   * @since   4.0.0
   */
  public function getImageSource(array $data): array
  {
    $directories = $this->getSourceDirs();
    $cat         = $this->getData('category', $data['catid']);

    switch($this->params->get('image_usage'))
    {
      // Recreate images
      case 1:
        if(!empty($directories[0]))
        {
          // use original image if not empty
          $dir = $directories[0];
        }
        else
        {
          // use detail image
          $dir = $directories[1];
        }

        // Assemble path to source image with complete system root
        return array(Path::clean($this->getSourceRootPath() . '/' . $dir . '/' . $cat['catpath'] . '/' . $data['imgfilename']));
        break;

      // Copy/Move images
      case 2:
      case 3:
        $imagetypes = JoomHelper::getRecords('imagetypes', $this->component);
        $dirs_map   = array('original' => 0, 'detail' => 1, 'thumbnail' => 2);

        $paths = array();
        foreach($imagetypes as $key => $type)
        {
          // Choose source type based on params
          $source_type = 'detail';
          foreach($this->params->get('image_mapping') as $key => $map)
          {
            if($map['destination'] == $type->typename)
            {
              $source_type = $map['source'];
              break;
            }
          }

          // Assemble path to source image
          $paths[$type->typename] = Path::clean($this->getSourceRootPath() . '/' . $directories[$dirs_map[$source_type]]. '/' . $cat['catpath'] . '/' . $data['imgfilename']);
        }

        return $paths;
        break;
      
      // Direct usage
      default:
        return array();
        break;
    }
  }

  /**
   * Perform script specific checks
   * 
   * @param  string   $type       Type of checks (pre or post)
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function scriptSpecificChecks(string $type, Checks &$checks, string $category)
  {
    if($type == 'pre')
    {
      // Check if imgfilename and imgthumbname are the same
      list($db, $dbPrefix)      = $this->getDB('source');
      list($tablename, $pkname) = $this->getSourceTableInfo('image');

      // Create the query
      $query = $db->getQuery(true)
              ->select($db->quoteName(array('id')))
              ->from($tablename)
              ->where($db->quoteName('imgfilename') . ' != ' . $db->quoteName('imgthumbname'));
      $db->setQuery($query);

      // Load a list of ids that have different values for imgfilename and imgthumbname
      $res = $db->loadColumn();

      if(!empty(\count($res)))
      {
        $checks->addCheck($category, 'src_table_image_filename', true, true, Text::_('FILES_JOOMGALLERY_MIGRATION_CHECK_IMAGE_FILENAMES_TITLE'), Text::sprintf('FILES_JOOMGALLERY_MIGRATION_CHECK_IMAGE_FILENAMES_DESC', \count($res)), Text::sprintf('FILES_JOOMGALLERY_MIGRATION_CHECK_IMAGE_FILENAMES_HELP', \implode(', ', $res)));
      }
    }

    if($type == 'post')
    {

    }    

    return;
  }
}