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
 * Category controller class.
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
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId),false));

			return false;
		}

		// Access check
		if(!$this->acl->checkACL('edit', 'category', $editId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId),false));

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
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId),false));
			
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
    $cid        = (array) $this->input->post->get('cid', [], 'int');
		$editId     = (int) (\count($cid) ? $cid[0] : $this->input->getInt('id', 0));
		$addCatId   = (int) $this->input->getInt('catid', 0);

		// ID check
		if(!$addCatId)
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId),false));

			return false;
		}

		// Access check
		if(!$this->acl->checkACL('add', 'category', $addCatId, true))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId),false));

			return false;
		}

		// Set the current edit id in the session.
		$this->app->setUserState(_JOOM_OPTION.'.add.category.id', $addCatId);

		// Check in the previous user.
		if($previousId && $previousId !== $addCatId)
		{
      // Get the model.
		  $model = $this->getModel('Category', 'Site');

			$model->checkin($previousId);
		}

		// Redirect to the form screen.
		$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=categoryform&'.$this->getItemAppend(0, $addCatId), false));
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

		// Get ID
    $removeId = $this->input->getInt('id', 0);

    // ID check
		if(!$removeId)
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($removeId),false));

			return false;
		}

    // Access check
		if(!$this->acl->checkACL('delete', 'category', $removeId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($removeId),false));

			return false;
		}

    // Redirect to imageform.remove
    $this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&task=categoryform.remove&'.$this->getItemAppend($removeId), false));
	}
}
