<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\FileManager;

\defined('JPATH_PLATFORM') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Service\FileManager\FileManager;

/**
* Trait to implement FileManagerServiceInterface
*
* @since  4.0.0
*/
trait FileManagerServiceTrait
{
  /**
	 * Storage for the file manager class.
	 *
	 * @var FileManagerInterface
	 *
	 * @since  4.0.0
	 */
	private $fileManager = null;

  /**
	 * Returns the file manager helper class.
	 *
	 * @return  FileManagerInterface
	 *
	 * @since  4.0.0
	 */
	public function getFileManager(): FileManagerInterface
	{
		return $this->fileManager;
	}

  /**
	 * Creates the file manager helper class
   * 
   * @param   array|bool     $selection    List of imagetypes to consider or false to consider all (default: False)
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createFileManager($selection=False): void
	{
    $this->fileManager = new FileManager($selection);

    return;
	}
}
