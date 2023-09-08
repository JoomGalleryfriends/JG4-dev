<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Extension;

\defined('JPATH_PLATFORM') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Application\CMSApplicationInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;

/**
* Trait to implement basic methods
* for JoomGallery services
*
* @since  4.0.0
*/
trait ServiceTrait
{
  /**
   * JoomGallery extension class
   * 
   * @var JoomgalleryComponent
   */
  protected $component = null;

  /**
   * Current application object
   *
   * @var    CMSApplicationInterface
   */
  protected $app = null;

  /**
	 * Sets a default value if not already assigned
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $default   The default value.
	 *
	 * @return  mixed
	 *
	 * @since   4.0.0
	 */
	public function def($property, $default = null)
	{
		$value = $this->get($property, $default);

		return $this->set($property, $value);
	}

  /**
	 * Returns a property of the object or the default value if the property is not set.
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $default   The default value.
	 *
	 * @return  mixed    The value of the property.
	 *
	 * @since   4.0.0
	 */
	public function get($property, $default = null)
	{
		if(isset($this->$property))
		{
			return $this->$property;
		}

		return $default;
	}

  /**
	 * Returns an associative array of object properties.
	 *
	 * @param   boolean  $public  If true, returns only the public properties.
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	public function getProperties($public = true)
	{
		if($public)
		{
			$vars = \Closure::fromCallable("get_object_vars")->__invoke($this);
		}
		else
		{
			$vars = \get_object_vars($this);
		}

		return $vars;
	}

  /**
	 * Modifies a property of the object, creating it if it does not already exist.
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $value     The value of the property to set.
	 *
	 * @return  mixed  Previous value of the property.
	 *
	 * @since   4.0.0
	 */
	public function set($property, $value = null)
	{
		$previous = $this->$property ?? null;
		$this->$property = $value;

		return $previous;
	}

  /**
	 * Set the object properties based on a named array/hash.
	 *
	 * @param   mixed  $properties  Either an associative array or another object.
	 *
	 * @return  boolean
	 *
	 * @since   1.7.0
	 */
	public function setProperties($properties)
	{
		if(\is_array($properties) || \is_object($properties))
		{
			foreach((array) $properties as $k => $v)
			{
				// Use the set function which might be overridden.
				$this->set($k, $v);
			}

			return true;
		}

		return false;
	}

  /**
	 * Gets the JoomGallery component object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
  public function getComponent()
  {
    $this->component = Factory::getApplication()->bootComponent('com_joomgallery');
  }

  /**
	 * Gets the current application object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
  public function getApp()
  {
    $this->app = Factory::getApplication();
  }
}
