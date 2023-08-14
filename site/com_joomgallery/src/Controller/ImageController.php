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
 * Image controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageController extends JoomBaseController
{
	/**
	 * Edit an existing image.
   * Redirect to form view.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
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
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId),false));

			return false;
		}

    // Access check
		if(!$this->acl->checkACL('edit', 'image', $editId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId),false));

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
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId),false));
			
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
	 * Add a new image: Not available
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 *
	 * @throws  Exception
	 */
	public function add()
	{
    throw new Exception('Adding a single image in the frontend is not available.', 503);
  }

  /**
	 * Remove an existing image.
   * Redirect to task=imageform.remove
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function remove()
	{
    // Check for request forgeries
		$this->checkToken();
    
    // Get ID
    $editId = $this->input->getInt('id', 0);

    // ID check
		if(!$editId)
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId),false));

			return false;
		}

    // Access check
		if(!$this->acl->checkACL('delete', 'image', $editId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId),false));

			return false;
		}

    // Redirect to imageform.remove
    $this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&task=imageform.remove&'.$this->getItemAppend($editId), false));
  }
}
