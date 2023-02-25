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
use Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Server;

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
   * @param  bool     $debug       Switches debug mode - {@see Server::debugMode}
   *
   * @since   4.0.0
   */
  public function __construct(string $directory, bool $debug = false);

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
   * Get the domain of the server
   * 
   * @return string
   */
  public function getDomain(): string;

  /**
   * Sets the domain of the server
   * 
   * @param string $domain
   *
   * @return void
   */
  public function setDomain(string $domain);

  /**
   * Sets upload size limit
   *
   * @param int $value
   *
   * @return void
   */
  public function setAllowMaxSize(int $value);

  /**
   * Get real name of transfered file
   *
   * @return string  Real name of file
   */
  public function getRealFileName(): string;

  /**
     * Allows GET method (it means allow download uploded files)
     *
     * @param bool $allow
     *
     * @return void
     */
    public function setAllowGetMethod($allow);
}
