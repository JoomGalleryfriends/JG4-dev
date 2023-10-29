<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\Input\Input;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Log\Log;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Response\JsonResponse;
use \Joomla\CMS\MVC\Controller\BaseController;
use \Joomla\CMS\Application\CMSApplication;
use \Joomla\CMS\Form\FormFactoryAwareInterface;
use \Joomla\CMS\Form\FormFactoryAwareTrait;
use \Joomla\CMS\Form\FormFactoryInterface;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;

/**
 * Migration controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class MigrationController extends BaseController implements FormFactoryAwareInterface
{
  use FormFactoryAwareTrait;

  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @var     JoomgalleryComponent
   * @since   4.0.0
   */
  protected $component;

  /**
   * The context for storing internal data, e.g. record.
   *
   * @var    string
   * @since  1.6
   */
  protected $context = _JOOM_OPTION.'.migration';

  /**
   * The URL option for the component.
   *
   * @var    string
   * @since  1.6
   */
  protected $option = _JOOM_OPTION;

  /**
   * The prefix to use with controller messages.
   *
   * @var    string
   * @since  1.6
   */
  protected $text_prefix = _JOOM_OPTION_UC;

  /**
   * Constructor.
   *
   * @param   array                 $config       An optional associative array of configuration settings.
   *                                              Recognized key values include 'name', 'default_task', 'model_path', and
   *                                              'view_path' (this list is not meant to be comprehensive).
   * @param   MVCFactoryInterface   $factory      The factory.
   * @param   CMSApplication        $app          The Application for the dispatcher
   * @param   Input                 $input        Input
   * @param   FormFactoryInterface  $formFactory  The form factory.
   *
   * @since   3.0
   */
  public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null, FormFactoryInterface $formFactory = null)
  {
    parent::__construct($config, $factory, $app, $input);

    $this->setFormFactory($formFactory);
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
    $this->component->createAccess();

    // As copy should be standard on forms.
    $this->registerTask('check', 'precheck');
  }

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    Optional. Model name
	 * @param   string  $prefix  Optional. Class prefix
	 * @param   array   $config  Optional. Configuration array for model
	 *
	 * @return  object	The Model
	 *
	 * @since   4.0.0
	 */
	public function getModel($name = 'Migration', $prefix = 'Administrator', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

  /**
   * Method to cancel a migration.
   *
   * @return  boolean  True if access level checks pass, false otherwise.
   *
   * @since   4.0.0
   */
  public function cancel()
  {
    $this->checkToken();

    $model   = $this->getModel();
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      throw new Exception('Requested migration script does not exist.', 1);      
    }

    // Clean the session data and redirect.
    $this->app->setUserState(_JOOM_OPTION.'.migration.script', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.params', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step2.data', null);    
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step2.results', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step2.success', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step3.results', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step3.success', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step4.results', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step4.success', null);

    // Redirect to the list screen.
    $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

    return true;
  }

  /**
   * Step 2
   * Validate the form input data and perform the pre migration checks.
	 *
	 * @return  void
	 *
   * @since   4.0.0
	 * @throws  \Exception
	 */
	public function precheck()
	{
    // Check for request forgeries
    $this->checkToken();

    $model   = $this->getModel();
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      throw new \Exception('Requested migration script does not exist.', 1);      
    }

    $data    = $this->input->post->get('jform_'.$script, [], 'array');
    $context = _JOOM_OPTION.'.migration.'.$script.'.step2';
    $task    = $this->getTask();

    // Access check.
    $acl = $this->component->getAccess();
    if(!$acl->checkACL('admin', 'com_joomgallery'))
    {
      $this->setMessage(Text::_('COM_JOOMGALLERY_ERROR_MIGRATION_NOT_PERMITTED'), 'error');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    // Validate the posted data.
    $form = $model->getForm($data, false);

    // Send an object which can be modified through the plugin event
    $objData = (object) $data;
    $this->app->triggerEvent('onContentNormaliseRequestData', [$context, $objData, $form]);
    $data = (array) $objData;

    // Test whether the data is valid.
    $validData = $model->validate($form, $data);

    // Check for validation errors.
    if($validData === false)
    {
      // Get the validation messages.
      $errors = $model->getErrors();

      // Push up to three validation messages out to the user.
      for($i = 0, $n = \count($errors); $i < $n && $i < 3; $i++)
      {
          if($errors[$i] instanceof \Exception)
          {
            $this->app->enqueueMessage($errors[$i]->getMessage(), 'warning');
          }
          else
          {
            $this->app->enqueueMessage($errors[$i], 'warning');
          }
      }

      // Save the form data in the session.
      $this->app->setUserState($context . '.data', $data);

      // Redirect back to the edit screen.
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    // Save the script name in the session.
    $this->app->setUserState(_JOOM_OPTION.'.migration.script', $script);

    // Save the migration parameters in the session.
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.params', $validData);

    // Perform the pre migration checks
    list($success, $res, $msg) = $model->precheck($validData);
    if(!$success)
    {
      // Pre-checks not successful. Show error message.
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_STEP2', $msg), 'error');
    }
    else
    {
      // Pre-checks successful. Show success message.
      $this->setMessage(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_SUCCESS_MIGRATION_STEP2'));
    }

    // Save the results of the pre migration checks in the session.
    $this->app->setUserState($context . '.results', $res);
    $this->app->setUserState($context . '.success', $success);

    // Redirect to the screen to show the results (View of Step 2)
    $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step2', false));

    return;
  }

  /**
   * Step 3
   * Enter the migration view.
	 *
	 * @return  void
	 *
   * @since   4.0.0
	 * @throws  \Exception
	 */
	public function migrate()
	{
    // Check for request forgeries
    $this->checkToken();

    // Access check.
    $acl = $this->component->getAccess();
    if(!$acl->checkACL('admin', 'com_joomgallery'))
    {
      $this->setMessage(Text::_('COM_JOOMGALLERY_ERROR_MIGRATION_NOT_PERMITTED'), 'error');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    $model   = $this->getModel();
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      throw new \Exception('Requested migration script does not exist.', 1);      
    }

    $precheck = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$script.'.step2.success', false);

    // Check if no errors detected in precheck (step 2)
    if(!$precheck)
    {
      // Pre-checks not successful. Show error message.
      $msg = Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_STEP2_CHECKS_FAILED');
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_STEP2', $msg), 'error');
      // Redirect to the step 2 screen
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step2', false));
    }

    // Redirect to the step 3 screen
    $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step3', false));
  }

  /**
   * Perform a migration
	 *
	 * @return  void
	 *
   * @since   4.0.0
	 */
	public function start()
	{
    // Check for request forgeries
    $this->checkToken();

    // Get request format
    $format  = strtolower($this->app->getInput()->getWord('format', 'json'));

    // Access check.
    $acl = $this->component->getAccess();
    if(!$acl->checkACL('admin', 'com_joomgallery'))
    {
      $msg = Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start');
      $this->ajaxRespond($msg, $format);

      return false;
    }

    $model   = $this->getModel();
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      $msg = new \Exception('Requested migration script does not exist.', 1);

      $this->ajaxRespond($msg, $format);

      return false;
    }

    // Check if no errors detected in precheck (step 2)
    $precheck = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$script.'.step2.success', false);
    if(!$precheck)
    {
      // Pre-checks not successful. Show error message.
      $msg = Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_STEP2_CHECKS_FAILED');
      $this->ajaxRespond($msg, $format);

      return false;
    }

    // Get input params for migration
    $type  = $this->app->getInput()->get('type', '', 'string');
    $id    = $this->app->getInput()->get('id', '', 'int');
    $json  = \json_decode(\base64_decode($this->app->getInput()->get('migrateable', '', 'string')), true);

    // Check if a record id to be migrated is given
    if(empty($id) || $id == 0)
    {
      // No record id given. Show error message.
      $msg = Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_RECORD_ID_MISSING');
      $this->ajaxRespond($msg, $format);

      return false;
    }
    
    // Attempt ot load migrateable from database
    $item = $model->getItem($json->id);

    // Insert migrateable in database if not existing
    if(\is_null($item->id))
    {
      // Save migration record to database
      $model->save($json);

      // Attempt ot load migrateable from database
      $item = $model->getItem($model->getState('migration.id'));
    }

    // Check out migration record if not already checked out
    if(\is_null($item->checked_out) || \intval($item->checked_out) < 1)
    {
      // Check out record
      $model->checkout($item->id);

      // Attempt ot load migrateable from database
      $item = $model->getItem($model->getState('migration.id'));
    }    

    // Perform the migration
    $msg = $model->migrate($type, $id, $item);

    // Send migration results
    $msg = json_encode($msg);
    $this->ajaxRespond($msg, $format);
  }

  /**
   * Returns an ajax response
   * 
   * @param   mixed   $results  The result to be returned
   * @param   string  $format   The format in which the result should be returned
	 *
	 * @return  void
	 *
   * @since   4.0.0
	 */
  protected function ajaxRespond($results, $format=null)
  {
    $this->app->allowCache(false);
    $this->app->setHeader('X-Robots-Tag', 'noindex, nofollow');

    if(\is_null($format))
    {
      $format = strtolower($this->app->getInput()->getWord('format', 'raw'));
    }

    // Return the results in the desired format
    switch($format)
    {
      // JSONinzed
      case 'json':
        echo new JsonResponse($results, null, false, $this->app->getInput()->get('ignoreMessages', true, 'bool'));

        break;

      // Raw format
      default:
        // Output exception
        if($results instanceof \Exception)
        {
          // Log an error
          Log::add($results->getMessage(), Log::ERROR);

          // Set status header code
          $this->app->setHeader('status', $results->getCode(), true);

          // Echo exception type and message
          $out = \get_class($results) . ': ' . $results->getMessage();
        }
        elseif(\is_scalar($results))
        {
          // Output string/ null
          $out = (string) $results;
        }
        else
        {
          // Output array/ object
          $out = \implode((array) $results);
        }

        echo $out;

        break;
    }
  }
}
