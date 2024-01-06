<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Table\Table;
use \Joomla\Database\DatabaseDriver;

/**
 * Galleries table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class GalleriesTable extends Table
{
  use JoomTableTrait;

	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = _JOOM_OPTION.'.galleries';

		parent::__construct(_JOOM_TABLE_GALLERIES, 'id', $db);
	}

  /**
	 * Overloaded bind function to pre-process the params.
	 *
	 * @param   array  $array   Named array
	 * @param   mixed  $ignore  Optional array or list of parameters to ignore
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     Table:bind
	 * @since   4.0.0
	 * @throws  \InvalidArgumentException
	 */
	public function bind($array, $ignore = '')
	{
		$date      = Factory::getDate();
		$task      = Factory::getApplication()->input->get('task', '', 'cmd');

    // Support for title field: title
    if(\array_key_exists('title', $array))
    {
      $array['title'] = \trim($array['title']);
      if(empty($array['title']))
      {
        $array['title'] = 'Unknown';
      }
    }

		// Support for alias field: alias
		if(empty($array['alias']))
		{
			if(empty($array['title']))
			{
				$array['alias'] = OutputFilter::stringURLSafe(date('Y-m-d H:i:s'));
			}
			else
			{
				if(Factory::getConfig()->get('unicodeslugs') == 1)
				{
					$array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['title']));
				}
				else
				{
					$array['alias'] = OutputFilter::stringURLSafe(trim($array['title']));
				}
			}
		}
    else
    {
      if(Factory::getConfig()->get('unicodeslugs') == 1)
      {
        $array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['alias']));
      }
      else
      {
        $array['alias'] = OutputFilter::stringURLSafe(trim($array['alias']));
      }
    }

		// Support for multiple or not foreign key field: userid
    if(!empty($array['userid']))
    {
      if(is_array($array['userid']))
      {
        $array['userid'] = implode(',',$array['userid']);
      }
      else if(strrpos($array['userid'], ',') != false)
      {
        $array['userid'] = explode(',',$array['userid']);
      }
    }
    else
    {
      $array['userid'] = 0;
    }

		if($array['id'] == 0)
		{
			$array['created_time'] = $date->toSql();
		}

		if($array['id'] == 0 && (!\key_exists('created_by', $array) || empty($array['created_by'])))
		{
			$array['created_by'] = Factory::getUser()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_time'] = $date->toSql();
		}

		if($array['id'] == 0 && (!\key_exists('modified_by', $array) ||empty($array['modified_by'])))
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

    // Support for images
    if(!isset($this->images))
    {
      $this->images = array();
    }

    return parent::bind($array, $ignore);
	}
}
