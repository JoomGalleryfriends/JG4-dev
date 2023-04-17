<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Form;

use Joomla\CMS\Form\Form;

// No direct access
defined('_JEXEC') or die;

/**
 * Form Class for the Joomla Platform.
 *
 * This class implements a robust API for constructing, populating, filtering, and validating forms.
 * It uses XML definitions to construct form fields and a variety of field and rule classes to
 * render and validate the form.
 *
 * @link   https://www.w3.org/TR/html4/interact/forms.html
 * @link   https://html.spec.whatwg.org/multipage/forms.html
 * @since  1.7.0
 */
class ConfigForm extends Form
{
  /**
   * Method to get an array of FormField objects in a given fieldset by name.  If no name is
   * given then all fields are returned.
   *
   * @param   string  $set  Name of the fieldset.
   *
   * @return  FormField[]  The array of FormField objects in the fieldset.
   *
   * @since   1.7.0
   * @throws  \Exception
   */
  public function getFieldset($set = null)
  {
    $fields = [];

    // Get all of the field elements in the fieldset.
    if($set)
    {
      $elements = $this->findFieldsByFieldset($set);
    }

    // If no field elements were found return empty.
    if(empty($elements))
    {
      return $fields;
    }

    // Build the result array from the found field elements.
    foreach ($elements as $element)
    {
      // Get the field groups for the element.
      $attrs = $element->xpath('ancestor::fields[@name]/@name');
      $groups = array_map('strval', $attrs ?: []);
      $group = implode('.', $groups);

      // If the field is successfully loaded add it to the result array.
      if ($field = $this->loadField($element, $group))
      {
        $fields[$field->id] = $field;
      }
    }

    return $fields;
  }

  /**
   * Method to get an array of `<field>` elements from the form XML document which are in a specified fieldset by name.
   *
   * @param   string  $name  The name of the fieldset.
   *
   * @return  \SimpleXMLElement[]|boolean  Boolean false on error or array of SimpleXMLElement objects.
   *
   * @since   1.7.0
   */
  protected function &findFieldsByFieldset($name)
  {
    // Make sure there is a valid Form XML document.
    if(!($this->xml instanceof \SimpleXMLElement))
    {
      throw new \UnexpectedValueException(sprintf('%s::%s `xml` is not an instance of SimpleXMLElement', \get_class($this), __METHOD__));
    }

    /*
    * Get an array of <field /> elements that are underneath a <fieldset /> element
    * with the appropriate name attribute, and also any <field /> elements with
    * the appropriate fieldset attribute. To allow repeatable elements only fields
    * which are not descendants of other fields are selected.
    */
    $fields = $this->xml->xpath('(//fieldset[@name="' . $name . '"]/field | //field[@fieldset="' . $name . '"])[not(ancestor::field)]');

    return $fields;
  }
}
