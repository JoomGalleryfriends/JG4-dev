<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Table\Table;
use \Joomla\Registry\Registry;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\Database\ParameterType;
use \Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\Language\Multilanguage;
use \Joomla\CMS\Form\FormFactoryInterface;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface;

/**
 * Base model class for JoomGallery administration views
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class JoomAdminModel extends AdminModel
{
  /**
	 * Alias to manage history control
	 *
	 * @access  public
   * @var     string
	 */
	public $typeAlias = '';

  /**
   * Joomla application class
   *
   * @access  protected
   * @var     Joomla\CMS\Application\AdministratorApplication
   */
  protected $app;

  /**
   * Joomla user object
   *
   * @access  protected
   * @var     Joomla\CMS\User\User
   */
  protected $user;

  /**
   * JoomGallery extension calss
   *
   * @access  protected
   * @var     Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   */
  protected $component;

  /**
	 * The prefix to use with controller messages.
	 *
	 * @access  protected
   * @var     string
	 */
	protected $text_prefix = _JOOM_OPTION_UC;

	/**
   * Item object
   *
   * @access  protected
   * @var     object
   */
	protected $item = null;

  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'image';

  /**
   * Constructor.
   *
   * @param   array                 $config       An array of configuration options (name, state, dbo, table_path, ignore_request).
   * @param   MVCFactoryInterface   $factory      The factory.
   * @param   FormFactoryInterface  $formFactory  The form factory.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
  {
    parent::__construct($config, $factory, $formFactory);

    $this->app       = Factory::getApplication('administrator');
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
    $this->user      = Factory::getUser();
    $this->typeAlias = _JOOM_OPTION.'.'.$this->type;
  }

  /**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 *
	 * @since   4.0.0
	 */
	public function getTable($type = 'Image', $prefix = 'Administrator', $config = array())
	{
		return parent::getTable($this->type, $prefix, $config);
	}

  /**
	 * Method to get parameters from model state.
	 *
	 * @return  Registry[]   List of parameters
   * @since   4.0.0
	 */
	public function getParams(): array
	{
		$params = array('component' => $this->getState('parameters.component'),
										'menu'      => $this->getState('parameters.menu'),
									  'configs'   => $this->getState('parameters.configs')
									);

		return $params;
	}

	/**
	 * Method to get the access service class.
	 *
	 * @return  AccessInterface   Object on success, false on failure.
   * @since   4.0.0
	 */
	public function getAcl(): AccessInterface
	{
		$this->component->createAccess();

		return $this->component->getAccess();
	}

  /**
	 * Method to load component specific parameters into model state.
   * 
   * @param   int   $id   ID of the content if needed (default: 0)
	 *
	 * @return  void
   * @since   4.0.0
	 */
  protected function loadComponentParams(int $id=0)
  {
    // Load the parameters.
		$params       = Factory::getApplication('com_joomgallery')->getParams();
		$params_array = $params->toArray();

		if(isset($params_array['item_id']))
		{
			$this->setState($this->type.'.id', $params_array['item_id']);
		}

		$this->setState('parameters.component', $params);

    // Load the configs from config service
    $id = ($id === 0) ? null : $id;

		$this->component->createConfig(_JOOM_OPTION.'.'.$this->type, $id, true);
		$configArray = $this->component->getConfig()->getProperties();
		$configs     = new Registry($configArray);

		$this->setState('parameters.configs', $configs);
  }

   /**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   Table  $table  Table Object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function prepareTable($table)
	{
		if(empty($table->id))
		{
			// Set ordering to the last item if not set
			if(@$table->ordering === '')
			{
        $tablename = JoomHelper::getTableName($this->type);

				$db = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM '.$tablename);
        
				$max             = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

  /**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   Form    $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function preprocessForm(Form $form, $data, $group = 'joomgallery')
	{
		if (!Multilanguage::isEnabled())
		{
			$form->setFieldAttribute('language', 'type', 'hidden');
			$form->setFieldAttribute('language', 'default', '*');
		}

		parent::preprocessForm($form, $data, $group);
	}

  /**
	 * Set or update associations.
	 *
   * @param   Table  &$table        Table object (with reference)
	 * @param   array  $associations  List of associated ids
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
  protected function createAssociations(Table &$table, array $associations)
  {
    $key = $table->getKeyName();

    // Unset any invalid associations
    $associations = ArrayHelper::toInteger($associations);

    // Unset any invalid associations
    foreach($associations as $tag => $id)
    {
      if(!$id)
      {
        unset($associations[$tag]);
      }
    }

    // Show a warning if the item isn't assigned to a language but we have associations.
    if($associations && $table->language === '*')
    {
      Factory::getApplication()->enqueueMessage(Text::_(strtoupper($this->option) . '_ERROR_ALL_LANGUAGE_ASSOCIATED'),	'warning');
    }

    // Get associationskey for edited item
    $db    = $this->getDbo();
    $id    = (int) $table->$key;
    $query = $db->getQuery(true)
      ->select($db->quoteName('key'))
      ->from($db->quoteName('#__associations'))
      ->where($db->quoteName('context') . ' = :context')
      ->where($db->quoteName('id') . ' = :id')
      ->bind(':context', $this->associationsContext)
      ->bind(':id', $id, ParameterType::INTEGER);
    $db->setQuery($query);
    $oldKey = $db->loadResult();

    if($associations || $oldKey !== null)
    {
      // Deleting old associations for the associated items
      $query = $db->getQuery(true)
        ->delete($db->quoteName('#__associations'))
        ->where($db->quoteName('context') . ' = :context')
        ->bind(':context', $this->associationsContext);

      $where = [];

      if($associations)
      {
        $where[] = $db->quoteName('id') . ' IN (' . implode(',', $query->bindArray(array_values($associations))) . ')';
      }

      if($oldKey !== null)
      {
        $where[] = $db->quoteName('key') . ' = :oldKey';
        $query->bind(':oldKey', $oldKey);
      }

      $query->extendWhere('AND', $where, 'OR');
      $db->setQuery($query);
      $db->execute();
    }

    // Adding self to the association
    if($table->language !== '*')
    {
      $associations[$table->language] = (int) $table->$key;
    }

    if(\count($associations) > 1)
    {
      // Adding new association for these items
      $key   = md5(json_encode($associations));
      $query = $db->getQuery(true)
        ->insert($db->quoteName('#__associations'))
        ->columns(
          [
            $db->quoteName('id'),
            $db->quoteName('context'),
            $db->quoteName('key'),
          ]
        );

      foreach($associations as $id)
      {
        $query->values(
          implode(
            ',',
            $query->bindArray(
              [$id, $this->associationsContext, $key],
              [ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING]
            )
          )
        );
      }

      $db->setQuery($query);
      $db->execute();
    }
  }

  /**
   * Method to test whether a record can be deleted.
   *
   * @param   object  $record  A record object.
   *
   * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
   *
   * @since   1.6
   */
  protected function canDelete($record)
  {
    $acl = $this->component->getAccess();
    return $acl->checkACL('core.delete', $this->option);
  }

  /**
   * Method to test whether a record can have its state changed.
   *
   * @param   object  $record  A record object.
   *
   * @return  boolean  True if allowed to change the state of the record. Defaults to the permission for the component.
   *
   * @since   1.6
   */
  protected function canEditState($record)
  {
    $acl = $this->component->getAccess();
    return $acl->checkACL('core.edit.state', $this->option);
  }
}
