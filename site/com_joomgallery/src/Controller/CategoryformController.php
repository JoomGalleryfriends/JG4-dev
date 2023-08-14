<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Category class.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryformController extends FormController
{
  use RoutingTrait;

  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   */
  var $component;

	/**
   * Joomgallery\Component\Joomgallery\Administrator\Service\Access\Access
   *
   * @access  protected
   * @var     object
   */
  var $acl;

  /**
   * Constructor.
   *
   * @param   array    $config   An optional associative array of configuration settings.
   * @param   object   $factory  The factory.
   * @param   object   $app      The Application for the dispatcher
   * @param   object   $input    Input
   *
   * @since   4.0.0
   */
  public function __construct($config = [], $factory = null, $app = null, $input = null)
  {
    parent::__construct($config, $factory, $app, $input);

    $this->default_view = 'category';

    // JoomGallery extension class
		$this->component = $this->app->bootComponent(_JOOM_OPTION);

		// Access service class
		$this->component->createAccess();
		$this->acl = $this->component->getAccess();
  }

	/**
	 * Method to save data.
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @since   4.0.0
	 */
	public function save($key = NULL, $urlVar = NULL)
	{
		// Check for request forgeries.
		$this->checkToken();

		// Get the user data.
		$data = Factory::getApplication()->input->get('jform', array(), 'array');

    // Data check
		if(!$data)
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend(),false));

			return false;
		}

    // Access check
		if(!$this->acl->checkACL('edit', 'category', (int) $data['id']))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($data->id),false));

			return false;
		}

    // Initialise variables.
		$app   = Factory::getApplication();
		$model = $this->getModel('Categoryform', 'Site');

		// Validate the posted data.
		$form = $model->getForm();

		if(!$form)
		{
			throw new \Exception($model->getError(), 500);
		}

		// Validate the posted data.
		$data = $model->validate($form, $data);

		// Check for errors.
		if($data === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if($errors[$i] instanceof \Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			$input = $app->input;
			$jform = $input->get('jform', array(), 'ARRAY');

			// Save the data in the session.
			$app->setUserState('com_joomgallery.edit.category.data', $jform);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_joomgallery.edit.category.id');
			$this->setRedirect(Route::_('index.php?option=com_joomgallery&view=categoryform&layout=edit&id=' . $id, false));

			$this->redirect();
		}

		// Attempt to save the data.
		$return = $model->save($data);

		// Check for errors.
		if($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_joomgallery.edit.category.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_joomgallery.edit.category.id');
			$this->setMessage(Text::sprintf('Save failed', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_joomgallery&view=categoryform&layout=edit&id=' . $id, false));
		}

		// Check in the profile.
		if($return)
		{
			$model->checkin($return);
		}

		// Clear the profile id from the session.
		$app->setUserState('com_joomgallery.edit.category.id', null);

		// Redirect to the list screen.
		$this->setMessage(Text::_('COM_JOOMGALLERY_ITEM_SAVED_SUCCESSFULLY'));
		$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($data->id),false));

		// Flush the data from the session.
		$app->setUserState('com_joomgallery.edit.category.data', null);
	}

	/**
	 * Method to abort current operation
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function cancel($key = NULL)
	{
		// Get the current edit id.
		$editId = (int) Factory::getApplication()->getUserState('com_joomgallery.edit.category.id');

		// Get the model.
		$model = $this->getModel('Categoryform', 'Site');

		// Check in the item
		if($editId)
		{
			$model->checkin($editId);
		}

		// Redirect to the list screen.
		$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId),false));
	}

	/**
	 * Method to remove data
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   4.0.0
	 */
	public function remove()
	{
    // Check for request forgeries
		$this->checkToken();

    // Get record id
		$cid      = (array) $this->input->post->get('cid', [], 'int');
		$removeId = (int) (\count($cid) ? $cid[0] : $this->input->getInt('id', 0));

    // ID check
		if(!$removeId)
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend(),false));

			return false;
		}

		// Access check
		if(!$this->acl->checkACL('delete', 'image', $removeId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($removeId),false));

			return false;
		}

    // Get the model.
    $model = $this->getModel('Categoryform', 'Site');

    // Attempt to save the data.
		$return = $model->delete($removeId);

		// Check for errors.
		if($return === false)
		{
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_DELETE_FAILED', $model->getError()), 'error');
			$this->app->redirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($removeId), false));
		}
		else
		{
			// Check in the profile.
			if($return)
			{
				$model->checkin($return);
			}

			$this->app->setUserState('com_joomgallery.edit.category.id', null);
			$this->app->setUserState('com_joomgallery.edit.category.data', null);

			$this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ITEM_DELETED_SUCCESSFULLY'), 'success');
			$this->app->redirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($removeId), false));
		}
	}

  /**
   * Method to run batch operations.
   *
   * @param  object  $model  The model of the component being processed.
   *
   * @throws \Exception
   */
  public function batch($model)
  {
    throw new Exception('Batch operations are not available in the frontend.', 503);
  }

  /**
   * Method to reload a record.
   *
   * @param   string  $key     The name of the primary key of the URL variable.
   * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
   *
   * @throws \Exception
   */
  public function reload($key = null, $urlVar = null)
  {
    throw new Exception('Reload operation not available.', 503);
  }
}
