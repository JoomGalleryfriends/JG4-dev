<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\TusServer;

\defined('JPATH_PLATFORM') or die;

use Psr\Http\Message\ResponseInterface;

/**
* TUS server Interface
*
* @since  4.0.0
*/
interface ServerInterface
{
  /**
   * Constructor
   *
   * @param  string   $directory   The directory to use for save the file
   * @param  string   $location    The uri to reach the TUS server
   * @param  bool     $debug       Switches debug mode - {@see Server::debugMode}
   *
   * @throws File
   * @access public
   */
  public function __construct(string $directory, string $location, bool $debug = false);

  /**
   * Process the client request
   *
   * @param   bool            $send  True to send the response, false to return the response
   *
   * @return  void|Response   void if send = true else Response object
   * 
   */
  public function process(bool $send = false);

  /**
   * Get the PSR-7 Response Object. 
   *
   * @return  Response The response object
   */
  public function getResponse(): ResponseInterface;

  /**
   * Get the location (uri) of the TUS server
   * 
   * @return string
   */
  public function getLocation(): string;

  /**
   * Sets upload size limit
   *
   * @param int $value
   *
   * @return void
   */
  public function setAllowMaxSize(int $value);

  /**
   * Allows GET method (it means allow download uploded files)
   *
   * @param bool $allow
   *
   * @return void
   */
  public function setAllowGetMethod($allow);

  /**
   * Sets the Access-Control-Allow-Origin header (CORS)
   * 
   * @param  string  $domain  Domain to allow access from
   *
   * @return void
   */
  public function setAccessControlHeader(string $domain);

  
  /**
   * Get real name of transfered file
   *
   * @return string  Real name of file
   */
  public function getRealFileName(): string;
}
