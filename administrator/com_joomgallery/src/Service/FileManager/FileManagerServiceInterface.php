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

\defined('_JEXEC') or die;

/**
* The file manager service
*
* @since  4.0.0
*/
interface FileManagerServiceInterface
{
  /**
	 * Storage for the file manager class.
	 *
	 * @var FileManagerInterface
	 *
	 * @since  4.0.0
	 */
	private $fileManager;

  /**
	 * Creates the file manager helper class
   * 
   * @param   int          $catid       Id of the category for wich the filsystem is chosen
   * @param   array|bool   $selection   List of imagetypes to consider or false to consider all (default: False)
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createFileManager($catid, $selection=False);

	/**
	 * Returns the file manager helper class.
	 *
	 * @return  FileManagerInterface
	 *
	 * @since  4.0.0
	 */
	public function getFileManager(): FileManagerInterface;
}
