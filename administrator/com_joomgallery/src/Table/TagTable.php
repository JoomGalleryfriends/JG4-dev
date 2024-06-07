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

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Access\Rules;
use \Joomla\CMS\Access\Access;
use \Joomla\Registry\Registry;
use \Joomla\Database\DatabaseDriver;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Tag table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class TagTable extends Table
{
  use JoomTableTrait;

	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = _JOOM_OPTION.'.tag';

		parent::__construct(_JOOM_TABLE_TAGS, 'id', $db);

		$this->setColumnAlias('published', 'published');

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
		$date = Factory::getDate();
		$task = Factory::getApplication()->input->get('task', '', 'cmd');

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

		if($array['id'] == 0)
		{
			$array['created_time'] = $date->toSql();
		}

		if(!\key_exists('created_by', $array) || empty($array['created_by']))
		{
			$array['created_by'] = Factory::getApplication()->getIdentity()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_time'] = $date->toSql();
		}

		if($array['id'] == 0 && empty($array['modified_by']))
		{
			$array['modified_by'] = Factory::getApplication()->getIdentity()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_by'] = Factory::getApplication()->getIdentity()->id;
		}

		if(isset($array['params']) && \is_array($array['params']))
		{
			$registry = new Registry($array['params']);
			$array['params'] = (string) $registry;
		}

		if(isset($array['metadata']) && \is_array($array['metadata']))
		{
			$registry = new Registry($array['metadata']);
			$array['metadata'] = (string) $registry;
		}

    // // Get access service
    // JoomHelper::getComponent()->createAccess();
    // $acl = JoomHelper::getComponent()->getAccess();

		// if(!$acl->checkACL('core.admin'))
		// {
		// 	$actions         = Access::getActionsFromFile(_JOOM_PATH_ADMIN.'/access.xml',	"/access/section[@name='tag']/");
		// 	$default_actions = Access::getAssetRules(_JOOM_OPTION.'.tag.'.$array['id'])->getData();
		// 	$array_jaccess   = array();

		// 	foreach($actions as $action)
		// 	{
		// 		if(key_exists($action->name, $default_actions))
		// 		{
		// 			$array_jaccess[$action->name] = $default_actions[$action->name];
		// 		}
		// 	}

		// 	$array['rules'] = $this->JAccessRulestoArray($array_jaccess);
		// }

		// Bind the rules for ACL where supported.
		if(isset($array['rules']))
		{
      $rules = new Rules($array['rules']);
			$this->setRules($rules);
		}

		return parent::bind($array, $ignore);
	}
}
