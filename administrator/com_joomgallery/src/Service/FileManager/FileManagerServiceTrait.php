<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
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
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createFileManager(): void
	{
    $this->fileManager = new FileManager();

    return;
	}
}
