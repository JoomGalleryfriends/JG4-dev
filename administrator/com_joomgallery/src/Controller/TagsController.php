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
 * Tags list controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class TagsController extends AdminController
{
	/**
	 * Method to clone existing Tags
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

		$this->setRedirect('index.php?option='._JOOM_OPTION.'&view=tags');
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
	public function getModel($name = 'Tag', $prefix = 'Administrator', $config = array())
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
   * Method to search tags via AJAX
   *
   * @return  void
   */
  public function searchAjax()
  {
    // Get user
    $user = $this->app->getIdentity();

    // Receive request data
    $filters = array(
        'like'      => trim($this->input->get('like', null, 'string')),
        'title'     => trim($this->input->get('title', null, 'string')),
        'flanguage' => $this->input->get('flanguage', null, 'word'),
        'published' => $this->input->get('published', 1, 'int'),
        //'parent_id' => $this->input->get('parent_id', 0, 'int'),
        'access'    => $user->getAuthorisedViewLevels(),
    );

    if((!$user->authorise('core.edit.state', 'com_joomgallery.tag')) && (!$user->authorise('core.edit', 'com_joomgallery.tag')))
    {
        // Filter on published for those who do not have edit or edit.state rights.
        $filters['published'] = 1;
    }

    // Search for tags
    $model = $this->getModel('Tags');
    $results = $model->searchItems($filters);

    //$results = TagsHelper::searchTags($filters);
    //$results = array('Test', 'Test1', 'Test2');

    if($results)
    {
        // Output a JSON object
        echo json_encode($results);
    }

    $this->app->close();
  }
}
