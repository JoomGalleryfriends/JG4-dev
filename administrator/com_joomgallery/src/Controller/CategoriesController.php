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

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;

/**
 * Categories list controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoriesController extends AdminController
{
	/**
	 * Method to clone existing Categories
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	public function duplicate()
	{
		// Check for request forgeries
		$this->checkToken();

		// Get id(s)
		$pks = $this->input->post->get('cid', array(), 'array');

		try
		{
			if(empty($pks))
			{
				throw new \Exception(Text::_('JERROR_NO_ITEMS_SELECTED'));
			}

			ArrayHelper::toInteger($pks);
			$model = $this->getModel();
			$model->duplicate($pks);
      
      if(\count($pks) > 1)
      {
        $this->setMessage(Text::_('COM_JOOMGALLERY_ITEMS_SUCCESS_DUPLICATED'));
      }
      else
      {
        $this->setMessage(Text::_('COM_JOOMGALLERY_ITEM_SUCCESS_DUPLICATED'));
      }
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

		$this->setRedirect('index.php?option='._JOOM_OPTION.'&view=categories');
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
	public function getModel($name = 'Category', $prefix = 'Administrator', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}	

	/**
	 * Rebuild the nested set tree.
	 * @return  boolean  False on failure or error, true on success.
	 * @since   1.6
	 */
	public function rebuild()
	{
		$this->checkToken();
		$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=categories', false));
		$model = $this->getModel();

		if($model->rebuild())
		{
			$this->setMessage(Text::_('COM_JOOMGALLERY_CATEGORIES_REBUILD_SUCCESS'));

			return true;
		}

		$this->setMessage(Text::_('COM_JOOMGALLERY_CATEGORIES_REBUILD_FAILURE'));
    
		return false;
	}
}
