<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Messenger;

// No direct access
\defined('_JEXEC') or die;

use Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessengerInterface;

/**
 * Template Messenger Base Class
 *
 * Provides methods to send template based messages.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class Messenger implements MessengerInterface
{
  use ServiceTrait;

  /**
   * Number of messanges successfully sent
   * 
   * @var integer
   */
  protected $sent = 0;

  /**
   * Language the message is written
   * 
   * @var Language
   */
  public $language = null;

  /**
   * Template id to use
   * 
   * @var Language
   */
  public $template_id = 'com_jomgallery.newimage';

  /**
   * List with variables available in the template
   * 
   * @var array
   */
  public $data = array();

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct()
  {
    $this->jg       = Factory::getApplication()->bootComponent('com_joomgallery');
    $this->language = Factory::getApplication()->getLanguage();

    $this->addTemplateData(array('sitename' => Factory::getApplication()->get('sitename'), 'siteurl' => Uri::root()));
  }

  /**
   * Method to add one ore more variables to be used in the template
   *
   * @param   array   $data   An array of key value pairs with variables to be used in the template
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  public function addTemplateData($data)
  {
    if(is_array($data))
    {
      $this->data = array_merge($this->data, $data);
    }
    else
    {
      array_push($this->data, $data);
    }
  }

  /**
     * Replace tags with their values recursively
     *
     * @param   string  $text  The template to process
     * @param   array   $tags  An associative array to replace in the template
     *
     * @return  string  Rendered mail template
     *
     * @since   4.0.0
     */
    protected function replaceTags($text, $tags)
    {
        foreach ($tags as $key => $value) {
            if (is_array($value)) {
                $matches = array();

                if (preg_match_all('/{' . strtoupper($key) . '}(.*?){\/' . strtoupper($key) . '}/s', $text, $matches)) {
                    foreach ($matches[0] as $i => $match) {
                        $replacement = '';

                        foreach ($value as $subvalue) {
                            if (is_array($subvalue)) {
                                $replacement .= $this->replaceTags($matches[1][$i], $subvalue);
                            }
                        }

                        $text = str_replace($match, $replacement, $text);
                    }
                }
            } else {
                $text = str_replace('{' . strtoupper($key) . '}', $value, $text);
            }
        }

        return $text;
    }
}
