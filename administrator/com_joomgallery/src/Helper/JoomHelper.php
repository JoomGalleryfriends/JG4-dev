<?php
/** 
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Log\Log;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Access\Access;
use \Joomla\CMS\Filesystem\Path;
use \Joomla\CMS\Language\Multilanguage;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\User\UserFactoryInterface;

/**
 * JoomGallery Helper for the Backend
 *
 * @static
 * @package JoomGallery
 * @since   4.0.0
 */
class JoomHelper
{
  /**
   * List of available content types
   *
   * @var array
   */
  public static $content_types = array(   'category'  => _JOOM_TABLE_CATEGORIES,
                                          'comment'   => _JOOM_TABLE_COMMENTS,
                                          'config'    => _JOOM_TABLE_CONFIGS,
                                          'faulty'    => _JOOM_TABLE_FAULTIES,
                                          'field'     => _JOOM_TABLE_FIELDS,
                                          'gallery'   => _JOOM_TABLE_GALLERIES,
                                          'image'     => _JOOM_TABLE_IMAGES,
                                          'imagetype' => _JOOM_TABLE_IMG_TYPES,
                                          'tag'       => _JOOM_TABLE_TAGS,
                                          'user'      => _JOOM_TABLE_USERS,
                                          'vote'      => _JOOM_TABLE_VOTES
                                        );

  /**
	 * Gets the JoomGallery component object
	 *
	 * @return  Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
	 *
	 * @since   4.0.0
	 */
  public static function getComponent()
  {
    return Factory::getApplication()->bootComponent('com_joomgallery');
  }

  /**
	 * Gets a JoomGallery service
   *
   * @param   string   $name      The name of the service
   * @param   array    $arg       Arguments passed to the cunstructor of the service (optional)
   * @param   Object   $com_obj   JoomgalleryComponent object if available
	 *
	 * @return  JoomService
	 *
	 * @since   4.0.0
	 */
  public static function getService($name, $arg=array(), $com_obj=null)
  {
    // get the JoomgalleryComponent object if needed
    if(!isset($com_obj) || !\strpos('JoomgalleryComponent', \get_class($com_obj)) === false)
    {
      $com_obj = Factory::getApplication()->bootComponent('com_joomgallery');
    }

    // create the service
    try
    {
      $createService = 'create'.\ucfirst($name);
      switch (\count($arg))
      {
        case 5:
          $com_obj->{$createService}($arg[0], $arg[1], $arg[2], $arg[3], $arg[4]);
          break;
        case 4:
          $com_obj->{$createService}($arg[0], $arg[1], $arg[2], $arg[3]);
          break;
        case 3:
          $com_obj->{$createService}($arg[0], $arg[1], $arg[2]);
          break;
        case 2:
          $com_obj->{$createService}($arg[0], $arg[1]);
          break;
        case 1:
          $com_obj->{$createService}($arg[0]);
          break;
        case 0:
          $com_obj->{$createService}();
          break;
        default:
          $this->component->addLog('Too many arguments passed to getService()', 'error', 'jerror');
          throw new \Exception('Too many arguments passed to getService()');
          break;
      }
    }
    catch (\Exception $e)
    {
      echo 'Creation of the service failed. Error: ',  $e->getMessage(), "\n";
      $this->component->addLog('Creation of the service failed. Error: ' . $e->getMessage(), 'error', 'jerror');
    }

    // get the service
    $getService = 'get'.\ucfirst($name);

    return $com_obj->{$getService}();
  }

  /**
	 * Returns a database record
   *
   * @param   string          $name      The name of the record (available: category,image,tag, imagetype)
   * @param   int|string      $id        The id of the primary key, the alias or the filename
   * @param   object          $com_obj   JoomgalleryComponent object if available
	 *
	 * @return  \stdClass|bool  Object on success, false on failure.
	 *
	 * @since   4.0.0
	 */
  public static function getRecord($name, $id, $com_obj=null)
  {
    // Check if content type is available
    self::isAvailable($name);

    // We got a valid record object
    if(\is_object($id) && $id instanceof \stdClass && isset($id->id))
    {
      return $id;
    }
    // We got a record ID, an alias or a filename
    elseif(!empty($id) && ((\is_numeric($id) && $id > 0) || \is_string($id) || ($name == 'imagetype' && \is_array($id))))
    {
      if(\is_string($id) && (int) $id == 0)
      {
        $id = self::getRecordIDbyAliasOrFilename($name, $id);
      }

      if($name != 'imagetype' || !\is_array($id))
      {
        $id = intval($id);
      }

      // Get the JoomgalleryComponent object if needed
      if(!isset($com_obj) || !\strpos('JoomgalleryComponent', \get_class($com_obj)) === false)
      {
        $com_obj = Factory::getApplication()->bootComponent('com_joomgallery');
      }

      // Create the model
      $model = $com_obj->getMVCFactory()->createModel($name, 'administrator');

      if(\is_null($model))
      {
        $this->component->addLog('Record-Type '.$name.' does not exist.', 'error', 'jerror');
        throw new \Exception('Record-Type '.$name.' does not exist.');
      }

      // Attempt to load the record.
      $return = $model->getItem($id);

      return $return;
    }
    // We got nothing to work with
    else
    {
      $this->component->addLog('Please provide a valid record ID, alias or filename.', 'error', 'jerror');
      throw new \Exception('Please provide a valid record ID, alias or filename.');

      return false;
    }
  }

  /**
	 * Returns the creator of a database record
   *
   * @param   string          $name      The name of the record (available: category,image,tag,imagetype)
   * @param   int|string      $id        The id of the primary key
   * @param   bool            $parent    True to get the creator of the parent record (default:false)
	 *
	 * @return  int             User id of the creator on success, false on failure.
	 *
	 * @since   4.0.0
	 */
  public static function getCreator($name, $id, $parent=false)
  {
    // Check if content type is available
    self::isAvailable($name);

    $id = intval($id);

    // We got a record id
    if(\is_numeric($id) && $id > 0)
    {
      $db = Factory::getContainer()->get(DatabaseInterface::class);
      $query = $db->getQuery(true);

      if($parent && \in_array($name, array('image', 'category')))
      {
        // Get join selector id
        $parent_id   = ($name == 'category') ? 'a.parent_id' : 'a.catid';

        // Create query
        $query
          ->select($db->quoteName('parent.created_by', 'created_by'))
          ->join('LEFT', $db->quoteName(self::$content_types['category'], 'parent'), $db->quoteName('parent.id') . ' = ' . $db->quoteName($parent_id))
          ->from($db->quoteName(self::$content_types[$name], 'a'))
          ->where($db->quoteName('a.id') . ' = ' . $id);
      }
      else
      {
        // Create query
        $query
          ->select($db->quoteName('a.created_by', 'created_by'))
          ->from($db->quoteName(self::$content_types[$name], 'a'))
          ->where($db->quoteName('a.id') . ' = ' . $id);
      }

      $db->setQuery($query);

      return $db->loadResult();
    }

    return false;
  }

  /**
	 * Returns the id of the parent database record
   *
   * @param   string        $name      The name of the record (available: category,image)
   * @param   int|string    $id        The id of the primary key
	 *
	 * @return  int           Parent id of the record on success, false on failure.
	 *
	 * @since   4.0.0
	 */
  public static function getParent($name, $id)
  {
    if(!\in_array($name, array('image', 'category')))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_INVALID_CONTENT_TYPE'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_ERROR_INVALID_CONTENT_TYPE'));
    }

    $id = \intval($id);

    // We got a record id
    if(\is_numeric($id) && $id > 0)
    {
      $db = Factory::getContainer()->get(DatabaseInterface::class);
      $query = $db->getQuery(true);

      // Get selector id
      $parent_id = ($name == 'category') ? 'parent_id' : 'catid';

      // Create query
      $query
      ->select($db->quoteName($parent_id))
      ->from($db->quoteName(self::$content_types[$name]))
      ->where($db->quoteName('id') . ' = ' . $id);

      $db->setQuery($query);

      return $db->loadResult();
    }

    return false;
  }

  /**
	 * Returns a list of database records
   *
   * @param   string      $name      The name of the record (available: categories,images,tags,imagetypes)
   * @param   Object      $com_obj   JoomgalleryComponent object if available
   * @param   string      $key       Index the returning array by key
	 *
	 * @return  array|bool  Array on success, false on failure.
	 *
	 * @since   4.0.0
	 */
  public static function getRecords($name, $com_obj=null, $key=null)
  {
    $availables = array('categories', 'images', 'tags', 'imagetypes');

    if(!\in_array($name, $availables))
    {
      $this->component->addLog('Please provide an available name of the record type.', 'error', 'jerror');
      throw new \Exception('Please provide an available name of the record type.');

      return false;
    }

    // Get the JoomgalleryComponent object if needed
    if(!isset($com_obj) || !\strpos('JoomgalleryComponent', \get_class($com_obj)) === false)
    {
      $com_obj = Factory::getApplication()->bootComponent('com_joomgallery');
    }

    $model = $com_obj->getMVCFactory()->createModel($name, 'administrator');

    if(\is_null($model))
    {
      $this->component->addLog('Record-Type '.$name.' does not exist.', 'error', 'jerror');
      throw new \Exception('Record-Type '.$name.' does not exist.');
    }

    // Attempt to load the record.
    $return = $model->getItems();

    // Indexing the array if needed
    if($return && !\is_null($key))
    {
      $ind_array = array();
      foreach($return as $obj)
      {
        if(\property_exists($obj, $key))
        {
          $ind_array[$obj->{$key}] = $obj;
        }
      }

      if(\count($ind_array) > 0)
      {
        $return = $ind_array;
      }
    }

    return $return;
  }

	/**
	 * Gets a list of the actions that can be performed.
   * 
   * @param   string  $type   The name of the content type of the item
   * @param   int     $id     The item's id
	 *
	 * @return  Registry
	 *
	 * @since   4.0.0
	 */
	public static function getActions($type=null, $id=null)
	{
    // Create asset name
		$assetName = _JOOM_OPTION;
    if($type)
    {
      // Check if content type is available
      self::isAvailable($type);

      $assetName .= '.'.$type;
    }
    if($id)
    {
      $assetName .= '.'.$id;
    }

    // Initialise variables
    $acl    = self::getService('Access');
    $result = new Registry;

    // Fill actions list based on access XML
		foreach(self::getActionsList($type) as $action)
		{
			$result->set($action, $acl->checkACL($action, $assetName));
		}

		return $result;
	}

  /**
   * Returns the URL or the path to an image
   *
   * @param   string/object/int $img     Filename, database object, ID or URL of the image
   * @param   string            $type    The image type or rnd_cat:<type> to load a random image
   * @param   bool              $url     True to return an image URL, false for a system path (default: true)
   * @param   bool              $root    True to add the system root to path. Only if $url=false. (default: true)
   *
   * @return  mixed             URL or path to the image on success, false otherwise
   *
   * @since   4.0.0
   */
  public static function getImg($img, $type, $url=true, $root=true)
  {
    // Create file config service based on current user
		$config = self::getService('Config');

    // Adjust type when in compatibility mode
    if($config->get('jg_compatibility_mode', 0))
    {
      switch($type)
      {
        case 'thumb':
          $type = 'thumbnail';
          break;
        
        case 'img':
          $type = 'detail';
          break;

        case 'orig':
          $type = 'original';
          break;
        
        default:
          break;
      }
    }

    if(\strpos($type, 'rnd_cat:') !== false && $config->get('jg_category_view_subcategories_random_image', 1))
    {
      // we want to get a random image from a category
      $type_array = explode(':', $type, 2);
      $type       = $type_array[1];
      $img        = self::getRndImageID($img, $config->get('jg_category_view_subcategories_random_subimages', 0, 'int'));
    }

    // get imagetypes
    $imagetype = self::getRecord('imagetype', array('typename' => $type));

    if($imagetype === false)
    {
      $this->component->addLog('Imagetype not found.', 'error', 'jerror');
      throw new \Exception("Imagetype not found.");

      return false;
    }

    if(!\is_object($img))
    {
      if(\is_numeric($img))
      {
        if($img == 0)
        {
          // ID = 0 given
          return self::getImgZero($type, $url, $root);          
        }
        else
        {
          // get image based on ID
          $img = self::getRecord('image', $img);
        }
      }
      elseif(\is_string($img))
      {
        if(\strlen($img) > 5 && (\strpos($img, '/') !== false || \strpos($img, \DIRECTORY_SEPARATOR) !== false))
        {
          // already image url given
          if(strpos($img, '/') === 0)
          {
            // url starts with '/'
            return Uri::root(true).$img;
          }
          else
          {
            return Uri::root(true).'/'.$img;
          }
        }
        else
        {
          // get image id based on filename
          $img = self::getRecord('image', array('filename' => $img));
        }
      }
      else
      {
        // no image given
        return self::getImgZero($type, $url, $root); 
      }
    }

    if(!\is_object($img) || \is_null($img->id) || $img->id === 0)
    {
      // image object not found
      return self::getImgZero($type, $url, $root);
    }

    // Check whether the image shall be output through the PHP script or with its real path
    if($url)
    {
      if($config->get('jg_use_real_paths', 0) == 0)
      {
        // Joomgallery internal URL
        // Example: https://www.example.org/index.php?option=com_joomgallery&controller=images&view=image&format=raw&type=orig&id=3&catid=1
        return Route::_('index.php?option=com_joomgallery&view=image&format=raw&type='.$type.'&id='.$img->id.'&catid='.$img->catid);
      }
      else
      {
        // Create file manager service
			  $manager    = self::getService('FileManager', array($img->catid));
        // Create file manager service
			  $filesystem = self::getService('Filesystem');
        
        // Real URL
        // Example: https://www.example.org/images/joomgallery/orig/test.jpg
        return $filesystem->getUrl($manager->getImgPath($img, $type));
      }
    }
    else
    {
      // Create file manager service
			$manager = self::getService('FileManager', array($img->catid));

      if($root)
      {
        // Complete system path
        // Example: D:/xampp/joomla/images/joomgallery/orig/test.jpg
        return $manager->getImgPath($img, $type, false, false, true);
      }
      else
      {
        // Relative system path
        // Example: /images/joomgallery/orig/test.jpg
        return $manager->getImgPath($img, $type, false, false, false);
      }
    }
  }

  /**
   * Returns the URL or the path to a category-image
   *
   * @param   string/object/int $cat     Alias, database object or ID of the category
   * @param   string            $type    The image type
   * @param   bool              $url     True to return an image URL, false for a system path (default: true)
   * @param   bool              $root    True to add the system root to path. Only if $url=false. (default: true)
   *
   * @return  mixed             URL or path to the image on success, false otherwise
   *
   * @since   4.0.0
   */
  public static function getCatImg($cat, $type, $url=true, $root=true)
  {
    if (!\is_object($cat))
    {
      if ((!\is_numeric($cat) && !\is_string($cat)) ||$cat == 0)
      {
        // no actual category given
        return self::getImgZero($type, $url, $root);
      }
  
      $cat = self::getRecord('category', $cat);
    }

    if($cat->thumbnail == 0)
    {
      // Create file config service based on current user
      $config = self::getService('Config');

      if($config->get('jg_category_view_subcategories_random_image', 1, 'int'))
      {
        return self::getImg($cat, 'rnd_cat:'.$type, $url, $root);
      }
    }

    return self::getImg($cat->thumbnail, $type, $url, $root);
  }

  /**
   * Returns the table name of a content type
   *
   * @param   string   $type    Name of the content type
   *
   * @return  string   Table name
   *
   * @since   4.0.0
   */
  public static function getTableName(string $type)
  {
    return self::$content_types[$type];
  }

  /**
   * Returns the ID of a random image ID in a category
   *
   * @param   string/object/int   $cat          Alias, database object or ID of the category
   * @param   bool                $inc_subcats  True to include subcategories in the search
   *
   * @return  int                        Image ID on success, 0 otherwise
   *
   * @since   4.0.0
   */
  public static function getRndImageID($cat, $inc_subcats=false)
  {
    $id = 0;

    if (!\is_object($cat))
    {
      if ((!\is_numeric($cat) && !\is_string($cat)) ||$cat == 0)
      {
        // no actual category given
        return $id;
      }
  
      $cat = self::getRecord('category', $cat);
    }    

    try {
      if($inc_subcats)
      {
        // Create the category table
        $com_obj = self::getComponent();
        if(!$table = $com_obj->getMVCFactory()->createTable('category', 'administrator'))
        {
          return $id;
        }

        // Load subcategories
        $table->load($cat->id);
        $nodes = $table->getNodeTree('children', true);

        // Rearrange it into a list of category ids
        $categories = array();
        foreach ($nodes as $node)
        {
          \array_push($categories, $node['id']);
        }
      }
      else
      {
        $categories = array($cat->id);
      }

      // Load the random image id
      $db    = Factory::getContainer()->get(DatabaseInterface::class);
      $query = $db->getQuery(true);

      // Get view levels of current user
      $user = Factory::getApplication()->getIdentity();
      $allowedViewLevels = Access::getAuthorisedViewLevels($user->id);

      $query->select('id')
            ->from($db->quoteName(_JOOM_TABLE_IMAGES))
            ->where($db->quoteName('catid') . ' IN (' . implode(',', $categories) .')')
            ->where($db->quoteName('published') . '= 1')
            ->where($db->quoteName('approved') . '= 1')
            ->where($db->quoteName('hidden') . '= 0')
            ->where($db->quoteName('access') . ' IN (' . implode(',', $allowedViewLevels) . ')')
            ->order('RAND()')
            ->setLimit(1);
      $db->setQuery($query);

      $res = $db->loadResult();
      $id = \is_null($res) ? 0 : (int) $res;
    }
    catch (\Exception $e) {
      return $id;
    }

    return $id;
  }

  /**
   * Get the route to a site item view.
   *
   * @param   string   $type      Name of the content type.
   * @param   integer  $id        The id of the content item.
   * @param   integer  $catid     The category ID.
   * @param   string   $language  The language code.
   * @param   string   $layout    The layout value.
   *
   * @return  string  The route.
   *
   * @since   4.0.0
   */
  public static function getViewRoute($view, $id, $catid = 0, $language = 0, $layout = null)
  {
    if(\is_object($id))
    {
      $id = (int) $id->id;
    }
    else
    {
      $id = (int) $id;
    }

    // Create the link
    $link = 'index.php?option=com_joomgallery&view='.$view.'&id=' . $id;

    if((int) $catid > 0)
    {
      $link .= '&catid=' . $catid;
    }

    if($language && $language !== '*' && Multilanguage::isEnabled())
    {
      $link .= '&lang=' . $language;
    }

    if($layout)
    {
      $link .= '&layout=' . $layout;
    }

    return $link;
  }

  /**
   * Get the route to a site list view.
   *
   * @param   string   $type      Name of the content type.
   * @param   string   $language  The language code.
   * @param   string   $layout    The layout value.
   *
   * @return  string  The route.
   *
   * @since   4.0.0
   */
  public static function getListRoute($view, $language = 0, $layout = null)
  {
    // Create the link
    $link = 'index.php?option=com_joomgallery&view='.$view;

    if($language && $language !== '*' && Multilanguage::isEnabled())
    {
      $link .= '&lang=' . $language;
    }

    if($layout && $layout != 'default')
    {
      $link .= '&layout=' . $layout;
    }

    return $link;
  }

  /**
	 * Returns a record ID based on a given alias
   *
   * @param   string      $record   The name of the record (available: category,image,tag,imagetype)
   * @param   string      $name     The alias or the filename of the image
	 *
	 * @return  int|bool    Record ID on success, false otherwise.
	 *
	 * @since   4.0.0
	 */
  public static function getRecordIDbyAliasOrFilename($record, $name)
  {
    $tables = array('category'  => _JOOM_TABLE_CATEGORIES,
                    'image'     => _JOOM_TABLE_IMAGES,
                    'imagetype' => _JOOM_TABLE_IMG_TYPES,
                   );
    
    // Does imagetype support alias
    if(!\array_key_exists($record, $tables))
    {
      $this->component->addLog('Record does not support alias.', 'error', 'jerror');
      throw new \Exception('Record does not support alias.');

      return false;
    }

    // Get alias row name
    $row_name = 'alias';
    $filename = false;
    if($record == 'imagetype')
    {
      $row_name = 'type_alias';
    }
    elseif($record == 'image')
    {
      if(\strpos($name, '.') !== false)
      {
        // We assume that $name is a filename
        $filename = true;
        $row_name = 'filename';
      }
    }

    // Create database connection
    $db = Factory::getContainer()->get(DatabaseInterface::class);

    // Create query
    $query = $db->getQuery(true);
    $query->select($db->quoteName('id'));
    $query->from($db->quoteName($tables[$record]));

    if(!$filename)
    {
      $query->where($db->quoteName($row_name) . " = " . $db->quote($name));
    }
    else
    {
      $query->where($db->quoteName($row_name) . " LIKE " . $db->quote($name));
    }

    // Reset the query using our newly populated query object.
    $db->setQuery($query);

    $result = $db->loadResult();

    if($result)
    {
      return $result;
    }
    else
    {
      return false;
    }     
  }

  /**
	 * Checks if a specific content type is available
   *
   * @param   string    $name   Content type name
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
  public static function isAvailable($name)
  {
    if(!\in_array($name, \array_keys(self::$content_types)))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_INVALID_CONTENT_TYPE'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_ERROR_INVALID_CONTENT_TYPE'));
    }
  }

  /**
	 * Returns the image url or path for image with id=0
   *
   * @param   string   $type    The image type
   * @param   bool     $url     True to return an image URL, false for a system path (default: true)
   * @param   bool     $root    True to add the system root to path. Only if $url=false. (default: true)
	 *
	 * @return  string     Image path or url
	 *
	 * @since   4.0.0
	 */
  public static function getImgZero($type, $url=true, $root=true)
  {
    if($url)
    {
      return Route::_('index.php?option=com_joomgallery&controller=images&view=image&format=raw&type='.$type.'&id=0');
    }
    else
    {
      // Create file manager service
			$manager = self::getService('FileManager', array(1));

      return $manager->getImgPath(0, $type, false, false, $root);
    }
  }

  /**
   * Returns the rating clause for an SQL - query dependent on the rating calculation method selected.
   *
   * @param   string  $tablealias   Table alias
   * 
   * @return  string  Rating clause
   * 
   * @since   4.0.0
   */
  public static function getSQLRatingClause($tablealias = '')
  {
    $db                   = Factory::getContainer()->get(DatabaseInterface::class);
    $config               = self::getService('config');
    static $avgimgvote    = 0.0;
    static $avgimgrating  = 0.0;
    static $avgdone       = false;

    $maxvoting            = $config->get('jg_maxvoting');
    $votesum           = 'votesum';
    $votes             = 'votes';
    if($tablealias != '')
    {
      $votesum = $tablealias.'.'.$votesum;
      $votes   = $tablealias.'.'.$votes;
    }

    // Standard rating clause
    $clause = 'ROUND(LEAST(IF(votes > 0, '.$votesum.'/'.$votes.', 0.0), '.(float)$maxvoting.'), 2)';

    // Advanced (weigthed) rating clause (Bayes)
    if($config->get('jg_ratingcalctype') == 1)
    {
      // throw new \Exception(Text::_('Calctype ist 1'));
      if(!$avgdone)
      {
        // Needed values for weighted rating calculation
        $query = $db->getQuery(true)
              ->select('count(*) As imgcount')
              ->select('SUM(votes) As sumvotes')
              ->select('SUM(votesum/votes) As sumimgratings')
              ->from(_JOOM_TABLE_IMAGES)
              ->where('votes > 0');

        $db->setQuery($query);
        $row = $db->loadObject();
        if($row != null)
        {
          if($row->imgcount > 0)
          {
            $avgimgvote   = round($row->sumvotes / $row->imgcount, 2 );
            $avgimgrating = round($row->sumimgratings / $row->imgcount, 2);
            $avgdone      = true;
          }
        }
      }
      if($avgdone)
      {
        $clause = 'ROUND(LEAST(IF(votes > 0, (('.$avgimgvote.'*'.$avgimgrating.') + '.$votesum.') / ('.$avgimgvote.' + '.$votes.'), 0.0), '.(float)$maxvoting.'), 2)';
      }
    }

    return $clause;
  }

  /**
   * Returns the rating of an image
   *
   * @param   string  $imgid   Image id to get the rating for
   * 
   * @return  float   Rating
   * 
   * @since   4.0.0
   */
  public static function getRating($imgid)
  {
    $rating = 0.0;
    $db     = Factory::getContainer()->get(DatabaseInterface::class);

    $query = $db->getQuery(true)
          ->select(self::getSQLRatingClause() . ' AS rating')
          ->from(_JOOM_TABLE_IMAGES)
          ->where($db->quoteName('id') . ' = ' . (int) $imgid);

    $db->setQuery($query);
    if(($result = $db->loadResult()) != null)
    {
      $rating = $result;
    }

    return $rating;
  }

  /**
   * Returns a list of all available access action names available
   *
   * @param   string  $type   The name of the content type
   * @param   string  $comp   Component name for which the actions are returned
   * 
   * @return  array   List of access action names
   * 
   * @since   4.0.0
   */
  protected static function getActionsList($type = null, $comp = 'com_joomgallery')
  {
    $file = Path::clean(JPATH_ADMINISTRATOR . '/components/' . $comp . '/access.xml');

    if(!$xml = \simplexml_load_file($file))
    {
      // Access XML not available
      return array();
    }    

    if($type)
    {
      $result = $xml->xpath('/access/section[@name="'.\strtolower($type).'"]/action');
    }
    else
    {
      $result = $xml->xpath('/access/section/action');
    }    

    $list = array();
    foreach($result as $node)
    {
      if(!\in_array(\strval($node['name']), $list))
      {
        \array_push($list, \strval($node['name']));
      }
    }

    return $list;
  }
}
