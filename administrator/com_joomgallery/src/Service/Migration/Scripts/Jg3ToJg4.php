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
use \Joomla\CMS\Filesystem\File;
use \Joomla\CMS\User\UserFactoryInterface;
use \Joomla\Component\Media\Administrator\Exception\FileExistsException;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Table\ImageTable;
use \Joomgallery\Component\Joomgallery\Administrator\Table\CategoryTable;
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
   * @return  \SimpleXMLElement   Extension XML object or False on failure
   * 
   * @since   4.0.0
   */
  public function getSourceXML()
  {
    if($this->params->get('same_joomla'))
    {
      return \simplexml_load_file(Path::clean(JPATH_ADMINISTRATOR . '/components/com_joomgallery/joomgallery_old.xml'));
    }
    else
    {
      if(\file_exists(Path::clean($this->params->get('joomla_path') . '/components/com_joomgallery/joomgallery.xml')))
      {
        return \simplexml_load_file(Path::clean($this->params->get('joomla_path') . '/components/com_joomgallery/joomgallery.xml'));
      }
      else
      {
        return \simplexml_load_file(Path::clean($this->params->get('joomla_path') . '/components/com_joomgallery/joomgallery_old.xml'));
      }
    }
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
   * (Required in migration scripts. The order of the content types must correspond to its migration order)
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
    $path  = false;
    
    if($this->params->get('same_joomla', 1) == 1 && $this->params->get('image_usage', 0) == 0)
    {
      // Special case: Direct usage of images in the same joomla installation
      $path  = 'path';
    }

    // Configure mapping for each content type
    switch($type)
    {
      case 'category':
        // Apply mapping for category table
        $mapping  = array( 'cid' => $id, 'asset_id' => false, 'name' => 'title', 'alias' => false, 'lft' => false, 'rgt' => false, 'level' => false,
                           'owner' => $owner, 'img_position' => false, 'catpath' => $path, 'params' => array('params', false, false), 
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
   * Performs the neccessary steps to migrate an image in the filesystem
   *
   * @param   ImageTable   $img    ImageTable object, already stored
   * @param   array        $data   Source data received from getData()
   * 
   * @return  bool         True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function migrateFiles(ImageTable $img, array $data): bool
  {
    $img_source = $this->getImageSource($data);

    // Set old catid for image creation
    $img->catid = $data['catid'];

    if($this->params->get('image_usage', 1) == 1)
    {
      // Recreate imagetypes based on given image
      $res = $this->createImages($img, $img_source[0]);
    }
    elseif($this->params->get('image_usage', 1) == 2 || $this->params->get('image_usage', 1) == 3)
    {
      $copy = false;
      if($this->params->get('image_usage', 1) == 2)
      {
        $copy = true;
      }

      // Copy/Move images from source based on mapping
      $res = $this->reuseImages($img, $img_source, $copy);
    }
    else
    {
      // Direct usage of images
      // Nothing to do
      $res = true;
    }

    return $res;
  }

  /**
   * Performs the neccessary steps to migrate a category in the filesystem
   *
   * @param   CategoryTable   $cat    CategoryTable object, already stored
   * @param   array           $data   Source data received from getData()
   * 
   * @return  bool            True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function migrateFolder(CategoryTable $cat, array $data): bool
  {
    // Create file manager service
    $this->component->createFileManager();

    if($this->params->get('image_usage', 1) == 0)
    {
      // Direct usage
      // Store new foldername
      $newName = $cat->path;

      // Reset old foldername
      $cat->path = $data['catpath'];

      // Rename existing folders
      $res = $this->component->getFileManager()->renameCategory($cat, $newName);
    }
    else
    {
      // Recreate, copy or move
      // Create new folders
      $res = $this->component->getFileManager()->createCategory($cat->alias, $cat->parent_id);
    }

    return $res;
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

    switch($this->params->get('image_usage', 0))
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
   * Creation of imagetypes based on one source file.
   * Source file has to be given with a full system path.
   *
   * @param   ImageTable     $img        ImageTable object, already stored
   * @param   string         $source     Source file with which the imagetypes shall be created
   * 
   * @return  bool           True on success, false otherwise
   * 
   * @since   4.0.0
   */
  protected function createImages(ImageTable $img, string $source): bool
  {
    // Create file manager service
    $this->component->createFileManager();

    // Update catid based on migrated categories
    $migrated_cats  = $this->get('migrateables')['category']->successful;
    $migrated_catid = $migrated_cats->get($img->catid);

    // Create imagetypes
    return $this->component->getFileManager()->createImages($source, $img->filename, $migrated_catid);
  }

  /**
   * Creation of imagetypes based on images already available on the server.
   * Source files has to be given for each imagetype with a full system path.
   *
   * @param   ImageTable   $img        ImageTable object, already stored
   * @param   array        $sources    List of source images for each imagetype availabe in JG4
   * @param   bool         $copy       True: copy, False: move
   *  
   * @return  bool         True on success, false otherwise
   * 
   * @since   4.0.0
   * @throws  \Exception
   */
  protected function reuseImages(ImageTable $img, array $sources, bool $copy = false): bool
  {
    // Create services
    $this->component->createFileManager();
    $this->component->createFilesystem($this->component->getConfig()->get('jg_filesystem','local-images'));

    // Fetch available imagetypes
    $imagetypes = $this->get('imagetypes_dict');

    // Check the source mapping
    if(\count(\array_diff_key($imagetypes, $sources)) !== 0 || \count(\array_diff_key($sources, $imagetypes)) !== 0)
    {
      throw new \Exception('Imagetype mapping from migration script does not match component configuration!', 1);
    }

    // Update catid based on migrated categories
    $migrated_cats  = $this->get('migrateables')['category']->successful;
    $migrated_catid = $migrated_cats->get($img->catid);

    // Loop through all sources
    $error = false;
    foreach($imagetypes as $type => $tmp)
    {
      // Get image source path (with system root)
      $img_src = $sources[$type];

      // Get category destination path
      $cat_dst = $this->component->getFileManager()->getCatPath($migrated_catid, $type);

      // Create image destination path
      $img_dst = $cat_dst . '/' . $img->filename;

      // Create destination folder if not existent
      $folder_dst = \dirname($img_dst);
      try
      {
        $this->component->getFilesystem()->createFolder(\basename($folder_dst), \dirname($folder_dst));
      }
      catch(FileExistsException $e)
      {
        // Do nothing
      }
      catch(\Exception $e)
      {
        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_CATEGORY', \ucfirst($folder_dst)));
        $error = true;

        continue;
      }

      // Move / Copy image
      try
      {
        if($this->component->getFilesystem()->get('filesystem') == 'local-images')
        {
          // Sorce and destination on the local filesystem
          if($img_src == Path::clean(JPATH_ROOT . '/' . $img_dst))
          {
            // Sorce and destination are identical. Do nothing.
            continue;
          }

          if($copy)
          {
            if(!File::copy($img_src, Path::clean(JPATH_ROOT . '/' . $img_dst)))
            {
              $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_COPY_IMAGETYPE', \basename($img_src), $type));
              $error = true;
              continue;
            }
          }
          else
          {
            if(!File::move($img_src, Path::clean(JPATH_ROOT . '/' . $img_dst)))
            {
              $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVE_IMAGETYPE', \basename($img_src), $type));
              $error = true;
              continue;
            }
          }
        }
        else
        {
          // Destination not on the local filesystem. Upload required
          $this->component->getFilesystem()->createFile($img->filename, $cat_dst, \file_get_contents($img_src));

          if(!$copy)
          {
            // When image shall be moved, source have to be deleted
            File::delete($img_src);
          }
        }
      }
      catch(\Exception $e)
      {
        // Operation failed
        if($copy)
        {
          $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_COPY_IMAGETYPE', \basename($img_src), $type));
        }
        else
        {
          $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVE_IMAGETYPE', \basename($img_src), $type));
        }
        
        $error = true;

        continue;
      }
    }
    
    if($error)
    {
      return false;
    }
    else
    {
      return true;
    }
  }

  /**
   * Perform script specific checks at the end of pre and postcheck.
   * 
   * @param  string   $type       Type of checks (pre or post)
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function scriptSpecificChecks(string $type, Checks &$checks, string $category)
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