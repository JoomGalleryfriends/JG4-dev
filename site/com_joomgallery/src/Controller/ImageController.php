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

use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\Input\Input;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Application\CMSApplication;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;

/**
 * Image class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageController extends JoomBaseController
{
	/**
	 * Method to check out an item for editing and redirect to the edit form.
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
		$previousId = (int) $this->app->getUserState(_JOOM_OPTION.'.edit.image.id');
    $cid        = (array) $this->input->post->get('cid', [], 'int');
		$editId     = $this->input->getInt('id', 0);

    // ID check
		if(!$editId)
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
			$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=image&'.$this->getItemAppend($editId),false));

			return false;
		}

    // Access check
		if(!$this->acl->checkACL('edit', 'image', $editId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=image&'.$this->getItemAppend($editId),false));

			return false;
		}

		// Set the current edit id in the session.
		$this->app->setUserState(_JOOM_OPTION.'.edit.image.id', $editId);

		// Get the model.
		$model = $this->getModel('Image', 'Site');

		// Check out the item
		if(!$model->checkout($editId))
		{
			// Check-out failed, display a notice but allow the user to see the record.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKOUT_FAILED', $model->getError()), 'error');
			$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=image&'.$this->getItemAppend($editId),false));
			
			return false;
		}

		// Check in the previous user.
		if($previousId && $previousId !== $editId)
		{
			$model->checkin($previousId);
		}

		// Redirect to the form screen.
		$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=imageform&'.$this->getItemAppend($editId), false));
	}

	/**
	 * Remove data
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
			$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=image&'.$this->getItemAppend($removeId),false));

			return false;
		}

		// Access check
		if(!$this->acl->checkACL('delete', 'image', $removeId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=image&'.$this->getItemAppend($removeId),false));

			return false;
		}

    // Get the model.
		$model = $this->getModel('Image', 'Site');

		// Attempt to save the data.
		$return = $model->delete($removeId);

		// Check for errors.
		if($return === false)
		{
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_DELETE_FAILED', $model->getError()), 'error');
			$this->app->redirect(Route::_('index.php?option=com_joomgallery&view=image'.$this->getItemAppend($removeId), false));
		}
		else
		{
			// Check in the profile.
			if($return)
			{
				$model->checkin($return);
			}

			$this->app->setUserState('com_joomgallery.edit.image.id', null);
			$this->app->setUserState('com_joomgallery.edit.image.data', null);

			$this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ITEM_DELETED_SUCCESSFULLY'), 'success');
			$this->app->redirect(Route::_('index.php?option=com_joomgallery&view=image'.$this->getItemAppend($parentId), false));
		}

	}
}
