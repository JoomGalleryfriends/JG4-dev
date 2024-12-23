<?php
/**
******************************************************************************************
**   @version    4.0.0-beta1                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Api\View\Images;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;

/**
 * The Images view
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class JsonApiView extends BaseApiView
{
	/**
	 * The fields to render item in the documents
	 *
	 * @var    array
	 * @since  4.0.0
	 */
	protected $fieldsToRenderItem = [
		'ordering', 
		'hits', 
		'downloads', 
		'approved', 
		'title', 
		'catid', 
		'published', 
		'author', 
		'language', 
		'access', 
		'created_by', 
		'id', 
		'date', 
	];

	/**
	 * The fields to render items in the documents
	 *
	 * @var    array
	 * @since  4.0.0
	 */
	protected $fieldsToRenderList = [
		'ordering', 
		'hits', 
		'downloads', 
		'approved', 
		'title', 
		'catid', 
		'published', 
		'author', 
		'language', 
		'access', 
		'created_by', 
		'id', 
		'date', 
	];
}
