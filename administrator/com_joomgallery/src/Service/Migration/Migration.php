<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Migration;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Layout\FileLayout;
use \Joomla\CMS\Form\FormFactoryInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\MigrationInterface;

/**
 * Migration Base Class
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class Migration implements MigrationInterface
{
  use ServiceTrait;

  /**
	 * Storage for the migration parameters.
	 *
	 * @var   \stdClass
	 *
	 * @since  4.0.0
	 */
	protected $params = null;

  /**
	 * Name of the migration script to be used.
	 *
	 * @var   string
	 *
	 * @since  4.0.0
	 */
	protected $name = '';

  /**
   * Is the migration performed from the command line
   *
   * @var    boolean
   * 
   * @since  4.0.0
   */
  protected $isCli = false;

  /**
   * Array of form objects.
   *
   * @var    Form[]
   * @since  4.0.0
   */
  protected $_forms = [];

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct()
  {
    // Load application
    $this->getApp();
    
    // Load component
    $this->getComponent();

    return;
  }

  /**
   * Step 1
   * Renders the form for configuring a migration using an XML file
   * which has the same name than the migration script
   *
   * @return  string  HTML of the rendered form
   * 
   * @since   4.0.0
   */
  public function renderForm(): string
  {
    // Prepare display data
    $displayData = new \stdClass();
    $displayData->url = Route::_('index.php?option='._JOOM_OPTION.'&task=migration.precheck');
    $displayData->description = 'FILES_JOOMGALLERY_MIGRATION_'.strtoupper($this->name).'_DESC';
    $displayData->scriptName = $this->name;
    $displayData->task = 'migration.precheck';
    $displayData->buttonTxt = 'COM_JOOMGALLERY_MIGRATION_STEP1_BTN_TXT';

    // Get form
    $form = $this->getForm();

    // Add fieldsets
    $displayData->fieldsets = $form->getFieldsets();
    foreach($displayData->fieldsets as $key => $fieldset)
    {
      $displayData->fieldsets[$key]->output = $form->renderFieldset($fieldset->name);
    }

    // Render the form
    $layout = new FileLayout('joomgallery.migration.form', JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/layouts/joomgallery/migration');
    
    return $layout->render($displayData);
  }

  /**
   * Step 2
   * Perform pre migration checks.
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  public function checkPre()
  {
    return;
  }

  /**
   * Step 4
   * Perform post migration checks.
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  public function checkPost()
  {
    return;
  }

  /**
   * Step 3
   * Perform one specific miration step and mark it as done at the end.
   *
   * @return  void
   * 
   * @since   4.0.0
   */
  public function migrate($type, $source, $dest)
  {
    return;
  }

  /**
   * Load and return the form object of the migration script
   *
   * @return  Form  Form object
   * 
   * @since   4.0.0
   */
  protected function getForm(): Form
  {
    // Try to load language file of the migration script
    $this->app->getLanguage()->load('com_joomgallery.migrate'.$this->name, JPATH_ADMINISTRATOR);

    // Form options
    $name    = _JOOM_OPTION.'.migration.'.$this->name; // The name of the form.
    $source  = $this->name; // The form source. Can be an XML string.
    $options = array('control' => 'jform_'.$this->name, 'load_data' => true); // Optional array of options for the form creation.
    $xpath   = null; // An optional xpath to search for the fields.

    // Create a signature hash. But make sure, that loading the data does not create a new instance
    $sigoptions = $options;

    if(isset($sigoptions['load_data']))
    {
      unset($sigoptions['load_data']);
    }

    $hash = md5($source . serialize($sigoptions));

    // Check if we can use a previously loaded form.
    if (isset($this->_forms[$hash]))
    {
      return $this->_forms[$hash];
    }

    // Add component form paths
    Form::addFormPath(JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/src/Service/Migration/Scripts');
    Form::addFormPath(JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/forms');

    // Create form
    $formFactory = Factory::getContainer()->get(FormFactoryInterface::class);
    $form        = $formFactory->createForm($name, $options);

    // Load the data.
    if(substr($source, 0, 1) === '<')
    {
      if($form->load($source, false, $xpath) == false)
      {
        throw new \RuntimeException('Form::loadForm could not load form');
      }
    }
    else
    {
      if($form->loadFile($source, false, $xpath) == false)
      {
        throw new \RuntimeException('Form::loadForm could not load file');
      }
    }

    if(isset($options['load_data']) && $options['load_data'])
    {
      // Get the data for the form.
      $data = $this->loadFormData();
    }
    else
    {
      $data = [];
    }

    // Load the data into the form.
    $form->bind($data);

    // Store the form for later.
    $this->_forms[$hash] = $form;

    return $form;
  }

  /**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   4.0.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState(_JOOM_OPTION.'.migration.'.$this->name.'.data', array());

		if(empty($data))
		{
			$data = array();
		}

		return $data;
	}
}
