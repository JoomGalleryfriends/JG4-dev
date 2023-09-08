<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

\defined('JPATH_BASE') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Form\FormField;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\Language\Text;

/**
 * Supports a config field whose content is defined in com_config
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ExternalconfigField extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $type = 'externalconfig';

  /**
   * Method to attach a Form object to the field.
   *
   * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
   * @param   mixed              $value    The form field value to validate.
   * @param   string             $group    The field name group control value. This acts as as an array container for the field.
   *                                       For example if the field has name="foo" and the group value is set to "bar" then the
   *                                       full field name would end up being "bar[foo]".
   *
   * @return  boolean  True on success.
   *
   * @since   1.7.0
   */
  public function setup(\SimpleXMLElement $element, $value, $group = null)
  {
    $res = parent::setup($element, $value, $group);

    // Get data
    $data = $this->getLayoutData();

    // // Load external form
    $array      = \explode('.', $data['label']);
    $config_xml = JPATH_ADMINISTRATOR . '/components/' . $array[0] . '/config.xml';
    $config_form = new Form($array[0].'.config');
    $config_form->loadFile($config_xml, false, '//config//fieldset');

    // Add external field values
    $this->external = $config_form->getField($array[1]);

    // Load external language
    $lang = Factory::getLanguage();
    $lang->load($array[0], JPATH_ADMINISTRATOR);

    return $res;
  }

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string    The field input markup.
	 *
	 * @since   4.0.0
	 */
	protected function getInput()
	{
    $data = $this->getLayoutData();

    // Get externalconfig
    $array  = \explode('.', $data['label']);

    $this->value       = ComponentHelper::getParams($array[0])->get($array[1]);
    $this->readonly    = true;
    $this->description = Text::_(\strval($this->external->element->attributes()->description)) . ' ('.Text::_('COM_JOOMGALLERY_IMAGE_SOURCE').': '.$array[0].')';

    $html  = '<a class="btn btn-secondary inline" target="_blank" href="index.php?option=com_config&view=component&component='.$array[0].'">'.Text::_('JACTION_EDIT').'</a>';
    $html .= '<input id="'.$this->id.'" disabled class="form-control sensitive-input" type="text" name="'.$this->name.'" value="'.$this->value.'" aria-describedby="'.$this->id.'-desc">';

    return $html;
	}

  /**
   * Method to get the field label markup.
   *
   * @return  string  The field label markup.
   *
   * @since   1.7.0
   */
  protected function getLabel()
  {
    $data = $this->getLayoutData();

    $label = \strval($this->external->element->attributes()->label);

    $extraData = [
      'text'        => Text::_($label),
      'for'         => $this->id,
      'classes'     => explode(' ', $data['labelclass']),
    ];

    return $this->getRenderer($this->renderLabelLayout)->render(array_merge($data, $extraData));
  }
}
