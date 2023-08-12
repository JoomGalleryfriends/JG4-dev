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

use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

/**
 * Category class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryController extends JoomBaseController
{
	/**
	 * Edit a category
	 * Checkout and redirect to from view
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 *
	 * @throws  Exception
	 */
	public function edit()
	{
		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $this->app->getUserState(_JOOM_OPTION.'.edit.category.id');
		$cid        = (array) $this->input->post->get('cid', [], 'int');
		$editId     = (int) (\count($cid) ? $cid[0] : $this->input->getInt('id', 0));

		// ID check
		if(!$editId)
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
			$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=category&'.$this->getItemAppend($editId),false));

			return false;
		}

		// Access check
		if(!$this->acl->checkACL('edit', 'category', $editId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=category&'.$this->getItemAppend($editId),false));

			return false;
		}

		// Set the current edit id in the session.
		$this->app->setUserState(_JOOM_OPTION.'.edit.category.id', $editId);

		// Get the model.
		$model = $this->getModel('Category', 'Site');

		// Check out the item
		if(!$model->checkout($editId))
		{
			// Check-out failed, display a notice but allow the user to see the record.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKOUT_FAILED', $model->getError()), 'error');
			$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=category&'.$this->getItemAppend($editId),false));
			
			return false;
		}

		// Check in the previous user.
		if($previousId && $previousId !== $editId)
		{
			$model->checkin($previousId);
		}

		// Redirect to the form screen.
		$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=categoryform&'.$this->getItemAppend($editId), false));
	}

	/**
	 * Add a new category
	 * Redirect to form view
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 *
	 * @throws  Exception
	 */
	public function add()
	{
		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $this->app->getUserState(_JOOM_OPTION.'.add.category.id');
		$editId     = (int) $this->input->getInt('id', 0);

		// ID check
		if(!$editId)
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
			$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=category&'.$this->getItemAppend($editId),false));

			return false;
		}

		// Access check
		if(!$this->acl->checkACL('add', 'category', $editId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=category&'.$this->getItemAppend($editId),false));

			return false;
		}

		// Set the current edit id in the session.
		$this->app->setUserState(_JOOM_OPTION.'.add.category.id', $editId);

		// Get the model.
		$model = $this->getModel('Category', 'Site');

		// Check in the previous user.
		if($previousId && $previousId !== $editId)
		{
			$model->checkin($previousId);
		}

		// Redirect to the form screen.
		$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=categoryform&'.$this->getItemAppend(0, $editId), false));

	}

	/**
	 * Remove a category
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function remove()
	{
		// Check for request forgeries
		$this->checkToken();

		// Get record id
		$cid      = (array) $this->input->post->get('cid', [], 'int');
		$removeId = (int) (\count($cid) ? $cid[0] : $this->input->getInt('id', 0));
		$parentId = (int) $this->input->getInt('parentId', 0);

		// ID check
		if(!$removeId)
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
			$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=category&'.$this->getItemAppend($removeId),false));

			return false;
		}

		// Access check
		if(!$this->acl->checkACL('delete', 'category', $removeId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=category&'.$this->getItemAppend($removeId),false));

			return false;
		}

		// Get the model.
		$model = $this->getModel('Category', 'Site');

		// Attempt to save the data.
		$return = $model->delete($removeId);

		// Check for errors.
		if($return === false)
		{
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_DELETE_FAILED', $model->getError()), 'error');
			$this->app->redirect(Route::_('index.php?option=com_joomgallery&view=category'.$this->getItemAppend($removeId), false));
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
			$this->app->redirect(Route::_('index.php?option=com_joomgallery&view=category'.$this->getItemAppend($parentId), false));
		}
	}
}
