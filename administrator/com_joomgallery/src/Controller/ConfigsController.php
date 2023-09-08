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
 * Configs list controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigsController extends AdminController
{
	/**
	 * Method to clone existing Configs
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

		$this->setRedirect('index.php?option='._JOOM_OPTION.'&view=configs');
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
	public function getModel($name = 'Config', $prefix = 'Administrator', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}	

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 *
	 * @throws  Exception
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$input = Factory::getApplication()->input;
		$pks   = $input->post->get('cid', array(), 'array');
		$order = $input->post->get('order', array(), 'array');

		// Sanitize the input
		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if($return)
		{
			echo "1";
		}

		// Close the application
		Factory::getApplication()->close();
	}

  /**
	 * Removes an item.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function delete()
	{
    // Get items to remove from the request.
		$cid = $this->input->get('cid', array(), 'array');    

    if(\is_array($cid) && \in_array(1, $cid))
    {
      echo 'asd';
      $glob_id = array_search(1, $cid);
      unset($cid[$glob_id]);

      $this->input->set('cid', $cid);

      $this->setMessage(Text::_('COM_JOOMGALLERY_ERROR_DELETE_GLOBCONFIG'), 'warning');
    }

    return parent::delete();
  }
}
