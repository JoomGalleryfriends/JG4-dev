<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Object\CMSObject;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\Registry\Registry;

/**
* Trait for Table methods
*
* @since  4.0.0
*/
trait JoomTableTrait
{
  /**
   * Form element to the table
   *
   * @var Form
  */
  public $form = false;

  /**
	 * Get the type alias for the history table
	 *
	 * @return  string  The alias as described above
	 *
	 * @since   4.0.0
	 */
	public function getTypeAlias()
	{
		return $this->typeAlias;
	}

  /**
	 * Get an array of all record fields and their current values
	 *
   * @param   array   $exclude   Array with properties to be excluded (default: [])
   * 
	 * @return  array  Fields with values
	 *
	 * @since   4.0.0
	 */
  public function getFieldsValues($exclude=array())
  {
    // Convert to the CMSObject before adding other data.
		$properties = $this->getProperties(1);
		$item = ArrayHelper::toObject($properties, CMSObject::class);

		if(property_exists($item, 'params'))
		{
			$registry = new Registry($item->params);
			$item->params = $registry->toArray();
		}

		if(isset($item->params))
		{
		  $item->params = json_encode($item->params);
		}

    // Delete excluded properties
    if(\count($exclude) > 0)
    {
      foreach($exclude as $property)
      {
        unset($item->{$property});
      }
    }

		return $item;
  }

  /**
	 * Check if a field is unique
	 *
	 * @param   string   $field    Name of the field
   * @param   integer  $catid    Category id (default=null)
	 *
	 * @return  bool    True if unique
	 */
	protected function isUnique ($field, $catid=null)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->quoteName($field))
			->from($db->quoteName($this->_tbl))
			->where($db->quoteName($field) . ' = ' . $db->quote($this->$field))
			->where($db->quoteName('id') . ' <> ' . (int) $this->{$this->_tbl_key});

    if($catid > 0)
    {
      $query->where($db->quoteName('catid') . ' = ' . $db->quote($catid));
    }

		$db->setQuery($query);
		$db->execute();

		return ($db->getNumRows() == 0) ? true : false;
	}

  /**
   * Support for multiple field
   *
   * @param   array   $data       Form data
   * @param   string  $fieldName  Name of the field
   *
   * @return  void
   */
  protected function multipleFieldSupport(&$data, $fieldName)
  {
    if(isset($data[$fieldName]))
		{
			if(is_array($data[$fieldName]))
			{
				$data[$fieldName] = implode(',',$data[$fieldName]);
			}
			elseif(strpos($data[$fieldName], ',') != false)
			{
				$data[$fieldName] = explode(',',$data[$fieldName]);
			}
			elseif(strlen($data[$fieldName]) == 0)
			{
				$data[$fieldName] = '';
			}
		}
		else
		{
			$data[$fieldName] = '';
		}
  }

  /**
   * Support for number field
   *
   * @param   array   $data       Form data
   * @param   string  $fieldName  Name of the field
   *
   * @return  void
   */
  protected function numberFieldSupport(&$data, $fieldName)
  {
    if($data[$fieldName] === '')
		{
			$data[$fieldName] = null;
			$this->{$fieldName} = null;
		}
  }

  /**
   * Support for number field
   *
   * @param   array   $data       Form data
   * @param   string  $fieldName  Name of the field
   *
   * @return  void
   */
  protected function subformFieldSupport(&$data, $fieldName)
  {
    if((!empty($data[$fieldName]) && (is_array($data[$fieldName]))))
    {
      \array_push($this->_jsonEncode, $fieldName);
    }
  }

  /**
	 * This function convert an array of Access objects into an rules array.
	 *
	 * @param   array  $jaccessrules  An array of Access objects.
	 *
	 * @return  array
	 */
	protected function JAccessRulestoArray($jaccessrules)
	{
		$rules = array();

		foreach($jaccessrules as $action => $jaccess)
		{
			$actions = array();

			if($jaccess)
			{
				foreach($jaccess->getData() as $group => $allow)
				{
					$actions[$group] = ((bool)$allow);
				}
			}

			$rules[$action] = $actions;
		}

		return $rules;
	}

  /**
	 * Mthod to load the default value of a field in a xml form
	 *
	 * @param   string  $form   The filename of the xml form.
   * @param   string  $field  The name of the field to get the default from.
	 *
	 * @return  array
	 */
  protected function loadDefaultField($field, $form=null)
  {
    // Get form name
    if(!$form)
    {
      $typeArr = \explode('.', $this->typeAlias);
      $form    = $typeArr[1];
    }

    // Load form
    $this->loadForm($form);

    // Get field type
    $field_type = $this->form->getField($field)->getAttribute('type', 'text');

    // Load default from xml field
    if($field_type == 'subform')
    {
      // Load subform
      $subform = $this->form->getField($field)->loadSubForm();

      // Load array with defaults
      $defaults = array();
      foreach($subform->getFieldset('general') as $key => $field)
      {
        $defaults[$field->getAttribute('name')] = $field->getAttribute('default', '');
      }

      return \json_encode($defaults, JSON_FORCE_OBJECT);
    }
    else
    {
      return $this->form->getField($field)->getAttribute('default', '');
    }
  }

  /**
	 * Mthod to load a Form object coupled to an xml form
	 *
	 * @param   string  $form   The filename of the xml form.
	 *
	 * @return  void
	 */
  protected function loadForm($form)
  {
    // Get xml file path
    if(\file_exists(JPATH_COMPONENT_ADMINISTRATOR . '/forms/'.$form.'.xml'))
    {
      $xml_file  = JPATH_COMPONENT_ADMINISTRATOR . '/forms/'.$form.'.xml';
      $form_name = $form;
    }
    elseif(\file_exists(JPATH_COMPONENT_ADMINISTRATOR . '/forms/'.$form))
    {
      $xml_file  = JPATH_COMPONENT_ADMINISTRATOR . '/forms/'.$form;
      $form_name = \str_replace('.xml', '', $form);
    }

    // Load form
    $this->form = new Form($form_name);
    $this->form->loadFile($xml_file);
  }
}
