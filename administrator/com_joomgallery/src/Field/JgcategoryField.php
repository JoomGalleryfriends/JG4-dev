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

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Field to select a JoomGallery category ID from a modal list.
 *
 * @since  4.0.0
 */
class JgcategoryField extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.6
	 */
	public $type = 'jgcategory';

	/**
	 * Filtering category
	 *
	 * @var   array
	 * @since 3.5
	 */
	protected $category = null;

	/**
	 * Category to exclude from the list of categories
	 *
	 * @var   array
	 * @since 3.5
	 */
	protected $excluded = null;

	/**
	 * Layout to render
	 *
	 * @var   string
	 * @since 3.5
	 */
	protected $layout = 'joomla.form.field.jgcategory';

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as an array container for the field.
	 *                                       For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                       full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.7.0
	 *
	 * @see     FormField::setup()
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		// If user can't access com_joomgallery the field should be readonly.
		if ($return && !$this->readonly)
		{
			$this->readonly = !Factory::getUser()->authorise('core.manage', 'com_joomgallery');
		}

		return $return;
	}

	/**
	 * Method to get the category field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.6
	 */
	protected function getInput()
	{
		if (empty($this->layout))
		{
			throw new \UnexpectedValueException(sprintf('%s has no layout assigned.', $this->name));
		}

		return $this->getRenderer($this->layout)->render($this->getLayoutData());

	}

	/**
	 * Get the data that is going to be passed to the layout
	 *
	 * @return  array
	 *
	 * @since   3.5
	 */
	public function getLayoutData()
	{
		// Get the basic field data
		$data = parent::getLayoutData();

		// Initialize value
		$name = Text::_('COM_JOOMGALLERY_FIELDS_SELECT_CATEGORY');

		if(is_numeric($this->value))
		{
			if($this->value > 0)
			{
				$cat = JoomHelper::getRecord('category', $this->value);
			}

			// Interprete root category
			if($this->value == 1)
			{
				$cat->title = Text::_('JGLOBAL_ROOT_PARENT');
			}

			if($this->value == 0 || !$cat)
			{
				$name = '';
			}
			else
			{
				$name = $cat->title;
			}
		}

		// User lookup went wrong, we assign the value instead.
		if($name === null && $this->value)
		{
			$name = $this->value;
		}

		$extraData = array(
			'categoryName'  => $name,
			'category' => $this->getCat(),
			'excluded'   => $this->getExcluded(),
		);

		return array_merge($data, $extraData);
	}

	/**
	 * Allow to override renderer include paths in child fields
	 *
	 * @return  array
	 *
	 * @since   3.5
	 */
	protected function getLayoutPaths()
	{
		$renderer = new FileLayout('default');
		$paths    = $renderer->getDefaultIncludePaths();

		if(Factory::getApplication()->isClient('site'))
		{
			// Add layouts in the administrator section
			$paths[] = JPATH_ADMINISTRATOR . '/components/com_joomgallery/layouts';
		}

		return $paths;
	}

	/**
	 * Method to get the filtering categories (null means no filtering)
	 *
	 * @return  mixed  Array of filtering categories or null.
	 *
	 * @since   1.6
	 */
	protected function getCat()
	{
		if (isset($this->element['category']))
		{
			return intval($this->element['category']);
		}
	}

	/**
	 * Method to get the images to exclude from the list of images
	 *
	 * @return  mixed  Array of images to exclude or null to to not exclude them
	 *
	 * @since   1.6
	 */
	protected function getExcluded()
	{
		if (isset($this->element['exclude']))
		{
			return intval($this->element['exclude']);
		}
	}
}
