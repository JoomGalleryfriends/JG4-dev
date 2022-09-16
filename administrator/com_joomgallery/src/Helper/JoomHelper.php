<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Object\CMSObject;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;

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
  protected static $content_types = array('category', 'image', 'tag', 'imagetype');

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
          throw new Exception('Too many arguments passed to getService()');
          break;
      }
    }
    catch (Exception $e)
    {
      echo 'Creation of the service failed. Error: ',  $e->getMessage(), "\n";
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
   * @param   Object          $com_obj   JoomgalleryComponent object if available
	 *
	 * @return  CMSObject|bool  Object on success, false on failure.
	 *
	 * @since   4.0.0
	 */
  public static function getRecord($name, $id, $com_obj=null)
  {
    // Check if content type is available
    self::isAvailable($name);

    // We got a valid record object
    if(\is_object($id) && $id instanceof \Joomla\CMS\Object\CMSObject && isset($id->id))
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
      $model = $com_obj->getMVCFactory()->createModel($name);

      if(\is_null($model))
      {
        throw new \Exception('Record-Type '.$name.' does not exist.');
      }

      // Attempt to load the record.
      $return = $model->getItem($id);

      return $return;
    }
    // We got nothing to work with
    else
    {
      throw new \Exception('Please provide a valid record ID, alias or filename.');

      return false;
    }
  }

  /**
	 * Returns a list of database records
   *
   * @param   string      $name      The name of the record (available: categories,images,tags,imagetypes)
   * @param   Object      $com_obj   JoomgalleryComponent object if available
	 *
	 * @return  array|bool  Array on success, false on failure.
	 *
	 * @since   4.0.0
	 */
  public static function getRecords($name, $com_obj=null)
  {
    $availables = array('categories', 'images', 'tags', 'imagetypes');

    if(!\in_array($name, $availables))
    {
      throw new \Exception('Please provide an available name of the record type.');

      return false;
    }

    // get the JoomgalleryComponent object if needed
    if(!isset($com_obj) || !\strpos('JoomgalleryComponent', \get_class($com_obj)) === false)
    {
      $com_obj = Factory::getApplication()->bootComponent('com_joomgallery');
    }

    $model = $com_obj->getMVCFactory()->createModel($name);

    if(\is_null($model))
    {
      throw new \Exception('Record-Type '.$name.' does not exist.');
    }

    // Attempt to load the record.
    $return = $model->getItems();

    return $return;
  }

	/**
	 * Gets the files attached to an item
	 *
	 * @param   int     $pk     The item's id
	 * @param   string  $table  The table's name
	 * @param   string  $field  The field's name
	 *
	 * @return  array  The files
	 */
	public static function getFiles($pk, $table, $field)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($field)
			->from($table)
			->where('id = ' . (int) $pk);

		$db->setQuery($query);

		return explode(',', $db->loadResult());
	}

	/**
	 * Gets a list of the actions that can be performed.
   * 
   * @param   string  $type   The name of the content type of the item
   * @param   int     $id     The item's id
	 *
	 * @return  CMSObject
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

    $user   = Factory::getUser(); 
		$result = new CMSObject;

		$actions = array(
			'core.admin', 'core.manage', 'joom.upload', 'joom.upload.inown', 'core.create', 'joom.create.inown',
      'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);

		foreach($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

  /**
   * Returns the URL or the path to an image
   *
   * @param   string/object/int $img    Filename, database object, ID or URL of the image
   * @param   string            $type   The image type
   * @param   bool              $url    True: image url, false: image path (default: true)
   *
   * @return  mixed             URL or path to the image on success, false otherwise
   *
   * @since   1.5.5
   */
  public static function getImg($img, $type, $url=true)
  {
    // get imagetypes
    $imagetype = self::getRecord('imagetype', array('typename' => $type));

    if($imagetype === false)
    {
      throw new Exception("Imagetype not found.");

      return false;
    }

    if(!\is_object($img))
    {
      if(\is_numeric($img))
      {
        // get image based on ID
        $img = self::getRecord('image', $img);
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
        return Uri::root(true).'/media/com_joomgallery/images/no-image.png';
      }
    }

    if(!\is_object($img) || \is_null($img->id) || $img->id === 0)
    {
      // image object not found
      return Uri::root(true).'/media/com_joomgallery/images/no-image.png';
    }

    // Check whether the image shall be output through the PHP script or with its real path
    if($url)
    {
      return Route::_('index.php?option=com_joomgallery&controller=images&view=image&format=raw&type='.$type.'&id='.$img->id);
    }
    else
    {
      // Create file manager service
			$manager = JoomHelper::getService('FileManager');

      return $manager->getImgPath($img, $type);
    }
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
    $db = Factory::getDbo();

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
  protected static function isAvailable($name)
  {
    if(!\in_array($name, self::$content_types))
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ERROR_INVALID_CONTENT_TYPE'));
    }
  }
}
