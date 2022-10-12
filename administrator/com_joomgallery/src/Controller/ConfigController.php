<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Config controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigController extends FormController
{
	protected $view_list = 'configs';

  /**
	 * Method to restore a record to its default values.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   4.0.0
	 */
	public function reset($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		$this->checkToken();

		$data  = $this->input->getArray(array());

    foreach ($data->jform as $key => $value)
    {
      // Do something...
    }

    parent::save($key, $urlVar);
  } 
}
