<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Config;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Filesystem\Path;
use \Joomla\CMS\Form\FormHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a single Config.
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	protected $state;

	protected $item;

	protected $form;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state            = $this->get('State');
		$this->item             = $this->get('Item');
		$this->form             = $this->get('Form');
    $this->fieldsets        = array();
    $this->is_global_config = ($this->item->id === 1) ? true : false;
    
		// Add options to the replaceinfo field
		JoomHelper::addReplaceinfoOptions($this->form);

		// Fill fieldset array
		foreach($this->form->getFieldsets() as $key => $fieldset)
		{
			$parts = \explode('-',$key);
			$level = \count($parts);

			$fieldset->level = $level;
			$fieldset->title = \end($parts);

			$this->setFieldset($key, array('this'=>$fieldset));
		}

		// Add permissions fieldset to level 1 fieldsets
		$permissions = array('name' => 'permissions',
							'label' => 'JGLOBAL_ACTION_PERMISSIONS_LABEL',
							'description' => '',
							'type' => 'tab',
							'level' => 1,
							'title' => 'permissions');
		$this->fieldsets['permissions'] = array('this' => (object) $permissions);


		// Check for errors
		if(count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);
    	$toolbar = Toolbar::getInstance('toolbar');

		$user  = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if(isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo = JoomHelper::getActions();

		ToolbarHelper::title(Text::_('COM_JOOMGALLERY_CONFIG_SETS').' :: '.Text::_('COM_JOOMGALLERY_CONFIG_EDIT'), "sliders-h");

		// If not checked out, can save the item.
		if(!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
		{
			ToolbarHelper::apply('config.apply', 'JTOOLBAR_APPLY');
			ToolbarHelper::save('config.save', 'JTOOLBAR_SAVE');
		}

		if(!$checkedOut && ($canDo->get('core.create')))
		{
			ToolbarHelper::custom('config.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}

		// If an existing item, can save to a copy.
		if(!$isNew && $canDo->get('core.create'))
		{
			ToolbarHelper::custom('config.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
		}

		if(!$isNew)
		{
			// $resetGroup = $toolbar->dropdownButton('reset-group')
			// 	->text('Settings')
			// 	->toggleSplit(false)
			// 	->icon('fas fa-ellipsis-h')
			// 	->buttonClass('btn btn-action')
			// 	->listCheck(false);

			// $childBar = $resetGroup->getChildToolbar();

			$toolbar->confirmButton('reset')
				->text('JRESET')
				->task('config.reset')
				->message('COM_JOOMGALLERY_RESET_CONFIRM')
				->icon('icon-refresh')
				->listCheck(false);
				
			$toolbar->standardButton('export')
				->text('JTOOLBAR_EXPORT')
				->task('config.export')
				->icon('icon-download')
				->listCheck(false);
		
			$import_modal_opt = array(
				'selector'=> 'import_modal',
				'doTask' => '',
				'btnClass' => 'button-import btn btn-primary',
				'htmlAttributes' => '',
				'class' => 'icon-upload',
				'text' => Text::_('COM_JOOMGALLERY_IMPORT'));
			$import_modal_btn = LayoutHelper::render('joomla.toolbar.popup', $import_modal_opt);
			$toolbar->appendButton('Custom', $import_modal_btn);
		}

		if(empty($this->item->id))
		{
			ToolbarHelper::cancel('config.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('config.cancel', 'JTOOLBAR_CLOSE');
		}
	}

  /**
	 * Add a fieldset to the fieldset array.
   * source: https://stackoverflow.com/questions/13308968/create-infinitely-deep-multidimensional-array-from-string-in-php
   *
   * @param  string  $key    path for the value in the array
   * @param  string  $value  the value to be placed at the defined path
	 *
	 * @return void
	 *
	 */
	protected function setFieldset($key, $value)
	{
    if(false === ($levels = explode('-',$key)))
    {
      return;
    }

    $pointer = &$this->fieldsets;
    for ($i=0; $i < sizeof($levels); $i++)
    {
      if(!isset($pointer[$levels[$i]]))
      {
        $pointer[$levels[$i]] = array();
      }

      $pointer = &$pointer[$levels[$i]];
    }

    $pointer = $value;
  }

  /**
	 * Method to get an array of JFormField objects in a given fieldset by name.
   *
   * @param    string  $name   name of the fieldset
	 *
	 * @return   array   Array with field names
	 *
	 */
  public function getFieldset($name)
	{
    $xml = null;

    // Attempt to load the XML file.
    $filename = JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR.'forms'.DIRECTORY_SEPARATOR.'config.xml';
    if(file_exists($filename))
    {
      $xml = simplexml_load_file($filename);
    }

    // Initialise fields array
    $fields = array();

    // Make sure there is a valid Form XML document.
		if(!($xml instanceof \SimpleXMLElement))
		{
			throw new \UnexpectedValueException('XML is not an instance of SimpleXMLElement');
		}

    /*
		 * Get an array of <field /> elements that are underneath a <fieldset /> element
		 * with the appropriate name attribute, and also any <field /> elements with
		 * the appropriate fieldset attribute. To allow repeatable elements only fields
		 * which are not descendants of other fields are selected.
		 */
    $elements = $xml->xpath('(//fieldset[@name="' . $name . '"]/field | //field[@fieldset="' . $name . '"])[not(ancestor::field)]');

    // If no field elements were found return empty.
		if(empty($elements))
		{
			return $fields;
		}

    // Build the result array from the found field elements.
		foreach($elements as $element)
		{
			// Get the field groups for the element.
			$attrs = $element->xpath('ancestor::fields[@name]/@name');
			$groups = array_map('strval', $attrs ? $attrs : array());
			$group = implode('.', $groups);

			// If the field is successfully loaded add it to the result array.
      // Get the field type.
      $type = $element['type'] ? (string) $element['type'] : 'text';

      // Load the FormField object for the field.
      $field = FormHelper::loadFieldType($type);

      // If the object could not be loaded, get a text field object.
      if($field === false)
      {
        $field = FormHelper::loadFieldType('text');
      }

      /*
      * Get the value for the form field if not set.
      * Default to the translated version of the 'default' attribute
      * if 'translate_default' attribute if set to 'true' or '1'
      * else the value of the 'default' attribute for the field.
      */
      $default = (string) ($element['default'] ? $element['default'] : $element->default);

      if(($translate = $element['translate_default']) && ((string) $translate === 'true' || (string) $translate === '1'))
      {
        $lang = Factory::getLanguage();

        if($lang->hasKey($default))
        {
          $debug = $lang->setDebug(false);
          $default = Text::_($default);
          $lang->setDebug($debug);
        }
        else
        {
          $default = Text::_($default);
        }
      }

      $value = $this->form->getValue((string) $element['name'], $group, $default);

      // Setup the FormField object.
      $field->setForm($this->form);
      $field->setup($element, $value, $group);

			if($field)
			{
				$fields[$field->id] = $field;
			}
		}

		return $fields;
  }

  /**
  * Add the page title and toolbar.
  *
  * @param   object  $field   Field object to render
  *
  * @return  string  html code for field output
  */
  public function renderField($field)
  {
	$global_only = false;
	if(!$this->is_global_config && !empty($field->getAttribute('global_only')) && $field->getAttribute('global_only') == true)
	{
		$global_only = true;
	}

	$sensitive = false;
	if(!empty($field->getAttribute('sensitive')) && $field->getAttribute('sensitive') == true)
	{
		$sensitive = true;
	}

	if($global_only)
	{
		// Fields with global_only attribute --> Not editable
		$field_data = array(
      'id' => $field->id,
			'name' => $field->name,
			'label' => LayoutHelper::render('joomla.form.renderlabel', array('text'=>Text::_($field->getAttribute('label')), 'for'=>$field->id, 'required'=>false, 'classes'=>array(), 'sensitive'=>$sensitive)),
			'input' => LayoutHelper::render('joomla.form.field.value', array('id'=>$field->id, 'value'=>$field->value, 'class'=>'')),
			'description' => Text::_('COM_JOOMGALLERY_CONFIG_EDIT_ONLY_IN_GLOBAL'),
		);
		echo LayoutHelper::render('joomla.form.renderfield', $field_data);
	}
	else
	{
		echo $field->renderField(array('sensitive' => $sensitive));
	}
  }
}
