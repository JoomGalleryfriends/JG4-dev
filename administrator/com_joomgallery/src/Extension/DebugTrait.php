<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Extension;

\defined('JPATH_PLATFORM') or die;

use \Joomla\CMS\Factory;

/**
* Trait to implement debugging and
* messaging tools
*
* @since  4.0.0
*/
trait DebugTrait
{
  /**
	 * Debug information
	 *
	 * @var array
	 *
	 * @since  4.0.0
	 */
	protected $debug = array();

  /**
   * Warnings and messages
   *
   * @var array
   * 
   * @since  4.0.0
   */
  protected $warnings = array();

  /**
   * Add text to the debugoutput
   *
   * @param   string   $txt         Text to add to the debugoutput
   * @param   bool     $new_line    True to add text to a new line (default: true)
   * @param   bool     $margin_top  True to add an empty line in front (default: false)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function addDebug($txt, $new_line=true, $margin_top=false)
  {
    if(!$new_line && !empty($this->debug))
    {
      $last_line = \array_pop($this->debug);
      $txt = $last_line.$txt;
    }

    if($margin_top && $new_line && !empty($this->debug))
    {
      $txt = '<br />'.$txt;
    }

    \array_push($this->debug, $txt);
  }

  /**
   * Add text to the warningoutput
   *
   * @param   string   $txt         Text to add to the debugoutput
   * @param   bool     $new_line    True to add text to a new line (default: true)
   * @param   bool     $margin_top  True to add an empty line in front (default: false)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function addWarning($txt, $new_line=true, $margin_top=false)
  {
    if(!$new_line && !empty($this->warnings))
    {
      $last_line = \array_pop($this->warnings);
      $txt = $last_line.$txt;
    }

    if($margin_top && $new_line && !empty($this->warnings))
    {
      $txt = '<br />'.$txt;
    }

    \array_push($this->warnings, $txt);
  }

  /**
	 * Method to get the debugoutput
	 *
	 * @return  string
	 *
	 * @since  4.0.0
	 */
	public function getDebug()
  {
    return $this->debug;
  }

  /**
	 * Method to get the warningoutput
	 *
	 * @return  string
	 *
	 * @since  4.0.0
	 */
	public function getWarning()
  {
    return $this->warnings;
  }

  /**
   * Add the debug to the message queue
   *
   * @param   string   $type     Type of the message (optional)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function printDebug($type='warning')
  {
    // Check if there is anything in the debg to be printed
    if(empty($this->debug))
    {
      return;
    }

    // Assemble debug string
    $debugoutput  = '<strong>Debug information:</strong><br />';
    $debugoutput .= '---------------------------------<br />';
    $debugoutput .= \implode('<br />', $this->debug);

    // Use warning if type not existent
    $available = array('message', 'success', 'notice', 'note', 'warning', 'error');
    if(!\in_array($type, $available))
    {
      $type = 'warning';
    }

    // Add debug string to message queue
    Factory::getApplication()->enqueueMessage($debugoutput, $type);

    // Reset debug array
    $this->debug = array();
  }

  /**
   * Add the warning to the message queue
   *
   * @param   string   $type     Type of the message (optional)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function printWarning($type='notice')
  {
    // Assemble debug string
    $warningoutput  = '<strong>Information:</strong><br />';
    $warningoutput .= '---------------------<br />';
    $warningoutput .= \implode('<br />', $this->warnings);

    // Use warning if type not existent
    $available = array('message', 'success', 'notice', 'note', 'warning', 'error');
    if(!\in_array($type, $available))
    {
      $type = 'notice';
    }

    // Add debug string to message queue
    Factory::getApplication()->enqueueMessage($warningoutput, $type);

    // Reset debug array
    $this->warnings = array();
  }
}
