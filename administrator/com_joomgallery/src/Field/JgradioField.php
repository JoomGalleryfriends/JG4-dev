<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                              **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

\defined('_JEXEC') or die;

/**
 * Radio field with useglobal option based on config service 
 * 
 * @since  4.0.0
 */
class JgradioField extends JglistField
{
  use JgMenuitemTrait;
  
  /**
   * The form field type.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $type = 'jgradio';

  /**
   * Method to attach a Form object to the field.
   *
   * @param   object  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
   * @param   mixed              $value    The form field value to validate.
   * @param   string             $group    The field name group control value.
   *
   * @return  boolean  True on success.
   *
   * @since   4.0.0
   */
  public function setup($element, $value, $group = null)
  {
    $res =  parent::setup($element, $value, $group);

    if($this->element['useglobal'])
    {
      $this->__set('default', $this->getGlobalValue());
    }

    return $res;
  }

  /**
   * Method to get the field input markup for a generic list.
   * Use the multiple attribute to enable multiselect.
   *
   * @return  string  The field input markup.
   *
   * @since   4.0.0
   */
  protected function getInput()
  {
    if($this->element['useglobal'])
    {
      $this->layout = 'joomla.form.field.radio.configbtns';
    }

    $data = $this->getLayoutData();

    $data['options']   = (array) $this->getOptions();

    if($this->element['useglobal'])
    {
      $data['globvalue'] = $this->getGlobalValue(0);
    }

    return $this->getRenderer($this->layout)->render($data);
  }

  /**
   * Method to get the data to be passed to the layout for rendering.
   *
   * @return  array
   *
   * @since   4.0.0
   */
  protected function getLayoutData()
  {
    $data = parent::getLayoutData();

    $extraData = [
        'options' => $this->getOptions(),
        'value'   => (string) $this->value,
    ];

    return array_merge($data, $extraData);
  }
}
