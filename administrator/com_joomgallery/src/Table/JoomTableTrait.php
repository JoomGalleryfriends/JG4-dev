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

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Table\Asset;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Access\Rules;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Object\CMSObject;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Filter\OutputFilter;

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
   * Delete a record by id
   *
   * @param   mixed  $pk  Primary key value to delete. Optional
   *
   * @return bool
   */
  public function delete($pk = null)
  {
    $this->load($pk);
    $result = parent::delete($pk);

    return $result;
  }

  /**
	 * Overloaded check function
	 *
	 * @return bool
	 */
	public function check()
	{
		// If there is an ordering column and this is a new row then get the next ordering value
		if(\property_exists($this, 'ordering') && $this->id == 0)
		{
			$this->ordering = self::getNextOrder();
		}

		// Check if alias is unique
    if( \property_exists($this, 'alias') &&
        (\property_exists($this, '_checkAliasUniqueness') ? $this->_checkAliasUniqueness : true)
      )
    {
      if(!$this->isUnique('alias'))
      {
        $count = 2;
        $currentAlias =  $this->alias;

        while(!$this->isUnique('alias'))
        {
          $this->alias = $currentAlias . '-' . $count++;
        }
      }
    }

    // Support for field description
    if(\property_exists($this, 'description'))
    {
      if(empty($this->description))
      {
        $this->description = $this->loadDefaultField('description');
      }
    }

		// Support for subform field params
    if(\property_exists($this, 'params'))
    {
      if(empty($this->params))
      {
        $this->params = $this->loadDefaultField('params');
      }
      elseif(\is_array($this->params))
      {
        $this->params = \json_encode($this->params, JSON_UNESCAPED_UNICODE);
      }
    }

    // Support for field metadesc
    if(\property_exists($this, 'metadesc'))
    {
      if(empty($this->metadesc))
      {
        $this->metadesc = $this->loadDefaultField('metadesc');
      }
    }

    // Support for field metakey
    if(\property_exists($this, 'metakey'))
    {
      if(empty($this->metakey))
      {
        $this->metakey = $this->loadDefaultField('metakey');
      }
    }

		return parent::check();
	}

  /**
	 * Overloaded bind function to pre-process the params.
	 *
	 * @param   array  $array   Named array
	 * @param   mixed  $ignore  Optional array or list of parameters to ignore
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     Table:bind
	 * @since   4.0.0
	 * @throws  \InvalidArgumentException
	 */
	public function bind($array, $ignore = '')
	{
		$date = Factory::getDate();
		$task = Factory::getApplication()->input->get('task', '', 'cmd');

    // Support for title field: title
    if(\array_key_exists('title', $array))
    {
      $array['title'] = \trim($array['title']);
      if(empty($array['title']))
      {
        $array['title'] = 'Unknown';
      }
    }

    // Support for alias field: alias
    if(\array_key_exists('alias', $array))
    {
      if(empty($array['alias']))
      {
        if(empty($array['title']))
        {
          $array['alias'] = OutputFilter::stringURLSafe(date('Y-m-d H:i:s'));
        }
        else
        {
          if(Factory::getConfig()->get('unicodeslugs') == 1)
          {
            $array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['title']));
          }
          else
          {
            $array['alias'] = OutputFilter::stringURLSafe(trim($array['title']));
          }
        }
      }
      else
      {
        if(Factory::getConfig()->get('unicodeslugs') == 1)
        {
          $array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['alias']));
        }
        else
        {
          $array['alias'] = OutputFilter::stringURLSafe(trim($array['alias']));
        }
      }
    }

		if(\array_key_exists('params', $array) && isset($array['params']) && \is_array($array['params']))
		{
			$registry = new Registry($array['params']);
			$array['params'] = (string) $registry;
		}

		if(\array_key_exists('metadata', $array) && isset($array['metadata']) && \is_array($array['metadata']))
		{
			$registry = new Registry($array['metadata']);
			$array['metadata'] = (string) $registry;
		}

    // Bind the rules for ACL where supported.
		if(isset($array['rules']))
		{
      $rules = new Rules($array['rules']);
			$this->setRules($rules);
		}

		return parent::bind($array, $ignore);
	}

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
	 * Define a namespaced asset name for inclusion in the #__assets table
	 *
	 * @return string The asset name
	 *
   * @since 4.0.0
	 * @see Joomla\CMS\Table\Table::_getAssetName
	 */
	protected function _getAssetName($itemtype = null)
	{
		$k = $this->_tbl_key;

		return $this->typeAlias . '.' . (int) $this->$k;
	}

  /**
	 * Method to return the title to use for the asset table.
	 *
	 * @return  string
	 *
   * @since 4.0.0
	 * @see Joomla\CMS\Table\Table::_getAssetTitle
	 */
	protected function _getAssetTitle($itemtype = null)
	{
		return $this->title;
	}

  /**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
	 *
	 * @param   Table    $table  Table name
	 * @param   integer  $id     Id
	 *
	 * @return mixed The id on success, false on failure.
   * 
   * @since 4.0.0
   * @see Joomla\CMS\Table\Table::_getAssetParentId
	 */
	protected function _getAssetParentId($table = null, $id = null, $itemtype = null)
	{
		// We will retrieve the parent-asset from the Asset-table
		$assetTable = new Asset($this->getDbo());

		// The item has the component as asset-parent
		$assetTable->loadByName(_JOOM_OPTION);

		// Return the found asset-parent-id
		if($assetTable->id)
		{
			$assetParentId = $assetTable->id;
		}
		else
		{
			// If no asset-parent can be found we take the global asset
			$assetParentId = $assetTable->getRootId();
		}

		return $assetParentId;
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
  public function getFieldsValues($exclude = array())
  {
    // Convert to the CMSObject before adding other data.
		$properties = $this->getProperties(1);
		$item = ArrayHelper::toObject($properties, CMSObject::class);

		if(\property_exists($item, 'params'))
		{
			$registry = new Registry($item->params);
			$item->params = $registry->toArray();
		}

		if(isset($item->params))
		{
		  $item->params = \json_encode($item->params);
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
	 * @param   string   $field         Name of the field
   * @param   integer  $parent        Parent id (default=null)
   * @param   string   $parentfield   Field name of parent id (default='parent_id')
	 *
	 * @return  bool    True if unique
	 */
	protected function isUnique($field, $parent=null, $parentfield='parent_id')
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->quoteName($field))
			->from($db->quoteName($this->_tbl))
			->where($db->quoteName($field) . ' = ' . $db->quote($this->$field))
			->where($db->quoteName('id') . ' <> ' . (int) $this->{$this->_tbl_key});

    if($parent > 0)
    {
      $query->where($db->quoteName($parentfield) . ' = ' . $db->quote($parent));
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
			if(\is_array($data[$fieldName]))
			{
				$data[$fieldName] = \implode(',',$data[$fieldName]);
			}
			elseif(\strpos($data[$fieldName], ',') != false)
			{
				$data[$fieldName] = \explode(',',$data[$fieldName]);
			}
			elseif(\strlen($data[$fieldName]) == 0)
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
    if((!empty($data[$fieldName]) && (\is_array($data[$fieldName]))))
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
