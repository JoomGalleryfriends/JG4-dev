<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Form;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Form\FormFactoryInterface;
use \Joomla\Database\DatabaseInterface;
use \Joomla\Database\DatabaseAwareTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Form\ConfigForm;

// No direct access
defined('_JEXEC') or die;

/**
 * Custom factory for creating ConfigForm objects
 *
 * @since  4.0.0
 */
class FormFactory implements FormFactoryInterface
{
    use DatabaseAwareTrait;

    /**
     * Method to get an instance of the config form.
     *
     * @param   string  $name     The name of the form.
     * @param   array   $options  An array of form options.
     *
     * @return  Form
     *
     * @since   4.0.0
     */
    public function createForm(string $name, array $options = array()): Form
    {
      if(\strpos($name, 'config') !== false)
      {
        // The config form needs a special form class
        $form = new ConfigForm($name, $options);
      }
      else
      {
        // All other forms can use the default form class
        $form = new Form($name, $options);
      }

      $form->setDatabase(Factory::getContainer()->get(DatabaseInterface::class));

      return $form;
    }
}
