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

\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;

/**
* Trait to implement messaging tools
*
* @since  4.0.0
*/
trait MessageTrait
{
  /**
   * Set to true if a error occured
   *
   * @var bool
  */
  public $error = false;

  /**
	 * Session storage path
	 *
	 * @var string
	 *
	 * @since  4.0.0
	*/
  public $msgUserStateKey = 'com_joomgallery.messages';

  /**
	 * Debug information storage
	 *
	 * @var array
	 *
	 * @since  4.0.0
	*/
	protected $debug = array();

  /**
   * Warnings and messages storage
   *
   * @var array
   * 
   * @since  4.0.0
  */
  protected $warnings = array();

  /**
   * Errors storage
   *
   * @var array
   * 
   * @since  4.0.0
  */
  protected $errors = array();

  /**
   * Adds the storages to the session
   * 
   * @return  void
   *
   * @since   4.0.0
  */
  public function toSession()
  {
    $app = Factory::getApplication();

    $app->setUserState($this->msgUserStateKey.'.debug', $this->debug);
    $app->setUserState($this->msgUserStateKey.'.warnings', $this->warnings);
    $app->setUserState($this->msgUserStateKey.'.errors', $this->errors);
  }

  /**
   * Loads the storages from the session
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function fromSession()
  {
    $app = Factory::getApplication();

    $this->debug    = $app->getUserState($this->msgUserStateKey.'.debug', array());
    $this->warnings = $app->getUserState($this->msgUserStateKey.'.warnings', array());
    $this->errors   = $app->getUserState($this->msgUserStateKey.'.errors', array());
  }

  /**
   * Add text to the debug information storage
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
    $this->setMsg('debug', $txt, $new_line, $margin_top);
  }

  /**
   * Add text to the warnings storage
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
    $this->setMsg('warning', $txt, $new_line, $margin_top);
  }

  /**
   * Set error and add it to the error storage
   *
   * @param   string   $txt         Text to add to the error storage
   * @param   bool     $new_line    True to add text to a new line (default: true)
   * @param   bool     $margin_top  True to add an empty line in front (default: false)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function setError($txt, $new_line=true, $margin_top=false)
  {
    $this->setMsg('error', $txt, $new_line, $margin_top);
  }

  /**
	 * Method to get the debugoutput
   * 
   * @param   bool   $implode   True, if youi want to implode the array (optional)
	 *
	 * @return  string|array  Debugoutput
	 *
	 * @since  4.0.0
	*/
	public function getDebug($implode=false)
  {
    return $this->getMsg('debug', $implode);
  }

  /**
	 * Method to get the warningoutput
	 *
	 * @param   bool   $implode   True, if youi want to implode the array (optional)
	 *
	 * @return  string|array  Warningoutput
	 *
	 * @since  4.0.0
	*/
	public function getWarning($implode=false)
  {
    return $this->getMsg('warning', $implode);
  }

  /**
	 * Method to get the erroroutput
	 *
	 * @param   bool   $implode   True, if youi want to implode the array (optional)
	 *
	 * @return  string|array  Erroroutput
	 *
	 * @since  4.0.0
	*/
	public function getError($implode=false)
  {
    return $this->getMsg('error', $implode);
  }

  /**
   * Add the debug to the message queue
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function printDebug()
  {
    return $this->printMsg('debug', 'warning', true);
  }

  /**
   * Add the warning to the message queue
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function printWarning()
  {
    return $this->printMsg('warning', 'notice', true);
  }

  /**
   * Add the error to the message queue
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function printError()
  {
    return $this->printMsg('error', 'error', true);
  }

  /**
   * Clear the debug storage
   * 
   * @param   bool  $session   True if the session storage should be cleared too (optional)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function clearDebug($session=true)
  {
    return $this->clearMsgStorage('debug', $session);
  }

  /**
   * Clear the warning storage
   * 
   * @param   bool  $session   True if the session storage should be cleared too (optional)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function clearWarning($session=true)
  {
    return $this->clearMsgStorage('warning', $session);
  }

  /**
   * Clear the error storage
   * 
   * @param   bool  $session   True if the session storage should be cleared too (optional)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function clearError($session=true)
  {
    return $this->clearMsgStorage('error', $session);
  }

  /**
   * Add text to a storage
   *
   * @param   string   $txt         Text to add to the debugoutput
   * @param   string   $storage     Select storage to add text
   * @param   bool     $new_line    True to add text to a new line (default: true)
   * @param   bool     $margin_top  True to add an empty line in front (default: false)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function setMsg($txt, $storage, $new_line=true, $margin_top=false)
  {
    $storage = $this->selectStorage($storage);

    if(!$new_line && !empty($storage))
    {
      $last_line = \array_pop($storage);
      $txt = $last_line.$txt;
    }

    if($margin_top && $new_line && !empty($storage))
    {
      $txt = '<br />'.$txt;
    }

    \array_push($storage, $txt);
  }

  /**
	 * Get from storage
	 *
	 * @param   bool   $implode   True, if youi want to implode the array (optional)
	 *
	 * @return  string|array  Debugoutput
	 *
	 * @since  4.0.0
	*/
	public function getMsg($storage, $implode=false)
  {
    $storage = $this->selectStorage($storage);

    if($implode)
    {
      return \implode('<br />', $storage);
    }
    else
    {
      return $storage;
    }
  }

  /**
   * Enqueue messages from storage to be printed
   *
   * @param   string   $storage    Storage to be printed
   * @param   string   $type       Type of the message (optional)
   * @param   bool     $title      True if a title should be printed (optional)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function printMsg($storage, $type='warning', $title=true)
  {
    // Create output title
    $storage_title = 'COM_JOOMGALLERY_';
    switch($storage)
    {
      case 'debug':
        $storage_title .= 'DEBUG_';
        break;

      case 'error':
      case 'errors':
        $storage_title .= 'ERROR_';
        break;
      
      default:
        break;
    }
    $storage_title .= 'INFORMATION';

    // Collect storage info
    $storage = $this->selectStorage($storage);

    // Check if there is anything in the storage to be printed
    if(empty($storage))
    {
      return;
    }

    // Assemble the output
    $output = '';
    if($title)
    {
      $output .= '<strong>'.Text::_($storage_title).':</strong><br />';
      $output .= '---------------------------------<br />';
    }
    $output .= \implode('<br />', $storage);

    // Use warning if type not existent
    $existing_types = array('message', 'success', 'notice', 'note', 'warning', 'error');
    if(!\in_array($type, $existing_types))
    {
      $type = 'warning';
    }

    // Add output to message queue
    Factory::getApplication()->enqueueMessage($output, $type);

    // Reset storage array
    $storage = array();
  }

  /**
   * Clears a message storage
   *
   * @param   string   $storage    Storage to be cleared
   * @param   bool     $session    True if the session storage should be cleared too (optional)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function clearMsgStorage($storage, $session=true)
  {
    $session_path = $this->selectStoragePath($storage);
    $storage      = $this->selectStorage($storage);

    $storage = array();

    if($session)
    {
      Factory::getApplication()->setUserState($session_path, array());
    }
  }

  /**
	 * Select the storage
	 *
	 * @param   string   $selection  The storage name to select
	 *
	 * @return  array    The selected storge array
	 *
	 * @since   4.0.0 
   * @throws  Exception
	*/
  protected function selectStorage($selection)
  {
    switch($selection)
    {
      case 'debug':
        return $this->debug;
        break;

      case 'warnings':
      case 'warning':
        return $this->warnings;
        break;

      case 'error':
      case 'errors':
        return $this->errors;
        break;

      default:
        throw new Exception("Selected storage does not exist.");
        return false;
    }
  }

  /**
	 * Select the session path of the storage
	 *
	 * @param   string   $selection  The storage name to select
	 *
	 * @return  string   The selected session path
	 *
	 * @since   4.0.0 
   * @throws  Exception
	*/
  protected function selectStoragePath($selection)
  {
    switch($selection)
    {
      case 'debug':
        return $this->msgUserStateKey.'.debug';
        break;

      case 'warnings':
      case 'warning':
        return $this->msgUserStateKey.'.warnings';
        break;

      case 'error':
      case 'errors':
        return $this->msgUserStateKey.'.errors';
        break;

      default:
        throw new Exception("Selected storage does not exist.");
        return false;
    }
  }
}
