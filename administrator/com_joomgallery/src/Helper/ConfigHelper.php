<?php
/** 
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Form\Form;

use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Helper for the configuration manager
 *
 * @static
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigHelper
{
  /**
   * Get a list of options for the jg_replaceinfo->source form field
   * based on its attributes
   *
   * @param   Form    $form    Form object containing jg_replaceinfo->source form field
	 *
	 * @return  array   List of options
	 *
	 * @since   4.0.0
   * @throws  \Exception
	 */
  public static function getReplaceinfoOptions($form)
  {
    // Check if we got a valid form
    if(\is_object($form) && $form instanceof Form)
    {
      if($form->getName() == 'com_joomgallery.config')
      {
        // We got the complete config form
        $formtype     = 'form';
        $exif_options = $form->getField('jg_replaceinfo')->loadSubForm()->getField('source')->getAttribute('EXIF');
        $iptc_options = $form->getField('jg_replaceinfo')->loadSubForm()->getField('source')->getAttribute('IPTC');
      }
      elseif(strpos($form->getName(), 'subform.jg_replaceinfo') !== false)
      {
        // We got a jg_replaceinfo subform
        $formtype     = 'subform';
        $exif_options = $form->getField('source')->getAttribute('EXIF');
        $iptc_options = $form->getField('source')->getAttribute('IPTC');
      }
      else
      {
        throw new \Exception(Text::_('COM_JOOMGALLERY_ERROR_INVALID_FORM_OBJECT'));
      }

      require JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/iptcarray.php';
      require JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/exifarray.php';
      
      $lang = Factory::getLanguage();
      $lang->load(_JOOM_OPTION.'.exif', JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION);
      $lang->load(_JOOM_OPTION.'.iptc', JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION);

      // create dropdown list of metadata sources
      $exif_options = \json_decode(\str_replace('\'', '"', $exif_options));
      $iptc_options = \json_decode(\str_replace('\'', '"', $iptc_options));

      // initialise options array
      $options = array();

      foreach ($exif_options as $key => $exif_option)
      {
        // add all defined exif options
        $text  = Text::_($exif_config_array[$exif_option[0]][$exif_option[1]]['Name']).' (exif)';
        $value = $exif_option[0] . '-' . $exif_option[1];

        \array_push($options, array('text' => $text, 'value'=>$value));
      }

      foreach ($iptc_options as $key => $iptc_option)
      {
        // add all defined iptc options
        $text  = Text::_($iptc_config_array[$iptc_option[0]][$iptc_option[1]]['Name']).' (iptc)';
        $value = $iptc_option[0] . '-' . $iptc_option[1];

        \array_push($options, array('text' => $text, 'value'=>$value));
      }

      return $options;
    }
    else
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ERROR_INVALID_FORM_OBJECT'));
    }
  }

  /**
	 * Get a list of options for the jg_filesystem form field
   *
   * @param   Form    $form    Form object containing jg_filesystem form field
	 *
	 * @return  array   List of options
	 *
	 * @since   4.0.0
   * @throws  \Exception
	 */
  public static function getFilesystemOptions($form)
  {
    // Check if we got a valid form object
    if(\is_object($form) && $form instanceof Form && $form->getName() == 'com_joomgallery.config')
    {
      $filesystem = JoomHelper::getService('filesystem');
      $providers  = $filesystem->getProviders();

      $options = array();
      foreach($providers as $provider)
      {
        foreach ($provider->adapterNames as $adapter)
        {
          $val  = $provider->name . '-' . $adapter;
          $text = $provider->displayName . ' (' . $adapter . ')';

          \array_push($options, array('text' => $text, 'value'=>$val));
        }
      }

      return $options;
    }
    else
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ERROR_INVALID_FORM_OBJECT'));
    }
  }
}
