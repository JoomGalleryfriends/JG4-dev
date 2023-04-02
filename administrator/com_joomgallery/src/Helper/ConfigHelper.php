<?php
/** 
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Form\Form;

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
	 * Add dropdown options to the jg_replaceinfo->source form field
   * based on its attributes
   *
   * @param   Form    $form    Form object to add replaceinfo options
	 *
	 * @return  void
	 *
	 * @since   4.0.0
   * @throws  \Exception
	 */
  public static function addReplaceinfoOptions(&$form)
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

      foreach ($exif_options as $key => $exif_option)
      {
        // add all defined exif options
        $text  = Text::_($exif_config_array[$exif_option[0]][$exif_option[1]]['Name']).' (exif)';
        $value = $exif_option[0] . '-' . $exif_option[1];
        if($formtype == 'subform')
        {
          $form->getField('source')->addOption($text, array('value'=>$value));
        }
        else
        {
          $form->getField('jg_replaceinfo')->loadSubForm()->getField('source')->addOption($text, array('value'=>$value));
        }
      }

      foreach ($iptc_options as $key => $iptc_option)
      {
        // add all defined iptc options
        $text  = Text::_($iptc_config_array[$iptc_option[0]][$iptc_option[1]]['Name']).' (iptc)';
        $value = $iptc_option[0] . '-' . $iptc_option[1];
        if($formtype == 'subform')
        {
          $form->getField('source')->addOption($text, array('value'=>$value));
        }
        else
        {
          $form->getField('jg_replaceinfo')->loadSubForm()->getField('source')->addOption($text, array('value'=>$value));
        }
      }

      return;
    }
    else
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ERROR_INVALID_FORM_OBJECT'));
    }
  }
}
