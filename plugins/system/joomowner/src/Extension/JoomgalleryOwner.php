<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @subpackage plg_privacyjoomgalleryimages                                           **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/
namespace Joomgallery\Plugin\System\Joomowner\Extension;

\defined('_JEXEC') or die;

use Joomla\Event\Event;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Event\Priority;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Result\ResultAwareInterface;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * System plugin managing ownership of JoomGallery content
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
final class JoomgalleryOwner extends CMSPlugin implements SubscriberInterface
{
  /**
   * Global database object
   *
   * @var    \JDatabaseDriver
   * 
   * @since  1.0.0
   */
  protected $db = null;

  /**
   * Global application object
   *
   * @var     CMSApplication
   * 
   * @since   4.0.0
   */
  protected $app = null;

  /**
   * True if JoomGallery component is installed
   *
   * @var     int|bool
   * 
   * @since   4.0.0
   */
  protected static $jg_exists = null;

  /**
   * Load the language file on instantiation.
   *
   * @var    boolean
   * 
   * @since  4.0.0
   */
  protected $autoloadLanguage = true;

  /**
   * List of tables connected to Joomla user table
   *
   * @var     array
   * 
   * @since   4.0.0
   */
  protected $tables = array('category' => array('pl_name' => 'categories'),
                            'comment'  => array('pl_name' => 'comments'),
                            'config'   => array('pl_name' => 'configs'),
                            'field'    => array('pl_name' => 'fields'),
                            'gallery'  => array('pl_name' => 'galleries'),
                            'image'    => array('pl_name' => 'images'),
                            'tag'      => array('pl_name' => 'tags'),
                            'user'     => array('pl_name' => 'users')
                          );

  /**
   * Constructor
   * 
   * @param   DispatcherInterface  $dispatcher  The event dispatcher
   * @param   array                $config      An optional associative array of configuration settings.
   *
   * @return  void
   * @since   4.0.0
   */
  function __construct($dispatcher, $config)
  {
    parent::__construct($dispatcher, $config);

    if($this->isJGExists())
    {
      $defines = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'defines.php';
      require_once $defines;

      foreach($this->tables as $name => $value)
      {
        $fieldname = 'created_by';
        $pkname    = 'id';

        if($name == 'user')
        {
          $fieldname = 'cmsuser';
        }

        $this->tables[$name] = array( 'sing_name' => $name,
                                      'pl_name'   => $value['pl_name'],
                                      'tablename' => JoomHelper::getTableName($name),
                                      'pk'        => $pkname,
                                      'owner'     => $fieldname
                                    );
      }
    }
  }

  /**
   * Returns an array of events this subscriber will listen to.
   *
   * @return array
   *
   * @since   4.0.0
   */
  public static function getSubscribedEvents(): array
  {
    if(self::$jg_exists)
    {
      return [
        'onMigrationBeforeSave' => ['onMigrationBeforeSave', Priority::ABOVE_NORMAL],
        'onContentBeforeSave'   => ['onContentBeforeSave', Priority::ABOVE_NORMAL],
        'onUserBeforeDelete'    => ['onUserBeforeDelete', Priority::NORMAL],
      ];
    }
    else
    {
      return array();
    }    
  }

  /**
   * Event triggered before a migrated record gets saved into the db.
   * Check if owner of JG record is valid and exists.
   *
   * @param   Event   $event
   *
   * @return  boolean  True to continue the save process, false to stop it
   *
   * @since   4.0.0
   */
  public function onMigrationBeforeSave(Event $event)
  {
    // Joomla 4
    [$context, &$table] = $event->getArguments();
    if(!$context)
    {
      // Joomla 5
      extract($event->getArguments());
      $table = &$event->getItem();      
    }

    if(\strpos($context, 'com_joomgallery') !== 0)
    {
      // Do nothing if we are not handling joomgallery content
      $this->setResult($event, true);

      return;
    }

    // Guess the type of content
    $typeAlias = isset($table->typeAlias) ? $table->typeAlias : $context;
    if(!$ownerField = $this->guessType($typeAlias))
    {
      // We couldnt guess the type of content we are dealing with
      $this->setResult($event, true);

      return;
    }
    
    if(isset($table->{$ownerField}) && !$this->isUserExists($table->{$ownerField}))
    {
      // Provided user does not exist. Use fallback user instead.
      $table->{$ownerField} = (int) $this->params->get('fallbackUser');
    }

    // Return the result
		$this->setResult($event, true);
  }

  /**
   * Event triggered before an item gets saved into the db.
   * Check if owner of JG record is valid and exists.
   *
   * @param   Event   $event
   *
   * @since   4.0.0
   */
  public function onContentBeforeSave(Event $event)
  {
    // Joomla 4
    [$context, &$table, $isNew, $data] = $event->getArguments();
    if(!$context)
    {
      // Joomla 5
      extract($event->getArguments());
      $table = &$event->getItem();      
    }

    if($context == 'com_plugins.plugin' && $table->name == 'plg_system_joomowner')
    {
      $newParams             = new Registry($table->params);
      $userIdToChangeManualy = $newParams->get('userIdToChangeManualy', '');

      // Reset the fields
      $newParams->set('userIdToChangeManualy', '');
      $table->params = (string) $newParams;

      if(empty($userIdToChangeManualy))
      {
        return;
      }

      if($this->isUserExists($userIdToChangeManualy))
      {
        $this->app->enqueueMessage(Text::sprintf('PLG_SYSTEM_JOOMOWNER_ERROR_USER_ID_TO_CHANGE_MANUALY_EXISTS', $userIdToChangeManualy), 'error');

        return;
      }

      if(!empty($userIdToChangeManualy))
      {
        $this->params = $newParams;
        $user = array('id' => $userIdToChangeManualy);

        $this->changeUser($user);
      }
    }

    if(\strpos($context, 'com_joomgallery') !== 0)
    {
      // Do nothing if we are not handling joomgallery content
      $this->setResult($event, true);

      return;
    }

    // Get the owner field
    $typeAlias = isset($table->typeAlias) ? $table->typeAlias : $context;
    if(!$ownerField = $this->guessType($typeAlias))
    {
      // We couldnt get the owner field. It probably does not exist.
		  $this->setResult($event, true);

      return;
    }

    if(isset($table->{$ownerField}) && !$this->isUserExists($table->{$ownerField}))
    {
      // Provided user does not exist. Use fallback user instead.
      $table->{$ownerField} = (int) $this->params->get('fallbackUser');
    }

    // Return the result
		$this->setResult($event, true);
  }

  /**
   * Event triggered before the user is deleted.
   * Handle JG records that are owned by the deleted user.
   *
   * @param   Event   $event
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function onUserBeforeDelete(Event $event)
  {
    // Joomla 4
    [$user] = $event->getArguments();
    if(!$user)
    {
      // Joomla 5
      $user = $event->getUser();
    }

    $fallbackUser = $this->params->get('fallbackUser');

    if($user['id'] == $fallbackUser)
    {
      $this->app->enqueueMessage(Text::_('PLG_SYSTEM_JOOMOWNER_ERROR_FALLBACK_USER_CONNECTED_MSG'), 'error');

      $url = Uri::getInstance()->toString(array('path', 'query', 'fragment'));
      $this->app->redirect($url, 500);
    }

    if(!$this->changeUser($user))
    {
      $this->app->enqueueMessage(Text::_('PLG_SYSTEM_JOOMOWNER_ERROR_USER_NOT_DELETED_MSG'), 'error');

      $url = Uri::getInstance()->toString(array('path', 'query', 'fragment'));
      $this->app->redirect($url, 500);
    }
  }

  /**
   * Changes the user in all dependent records before deleting them.
   *
   * @param   array  $user
   *
   * @return  bool
   *
   * @since   4.0.0
   */
  protected function changeUser(array $user): bool
  {
    $return         = true;
    $currentUserId  = Factory::getContainer()->get(UserFactoryInterface::class)->id;
    $fallbackUserId = (int) $this->params->get('fallbackUser', $currentUserId);
    $oldUserId      = (int) $user['id'];

    foreach($this->tables as $name => $table)
    {
      $selectQuery = $this->db->getQuery(true);
      $selectQuery->select($this->db->quoteName($table['pk']))
                  ->from($table['tablename'])
                  ->where($this->db->quoteName($table['owner']) . ' = ' . $this->db->quote($oldUserId))
                  ->set('FOR UPDATE');

      $updateQuery = $this->db->getQuery(true);
      $updateQuery->update($this->db->quoteName($table['tablename']))
                  ->set($this->db->quoteName($table['owner']) . ' = ' . $this->db->quote($fallbackUserId))
                  ->where($this->db->quoteName($table['owner']) . ' = ' . $this->db->quote($oldUserId));

      try
      {
        $selectResult = $this->db->setQuery($selectQuery)->loadColumn();

        if(!empty($selectResult))
        {
          $elementList = \implode(', ', $selectResult);
          $tname       = \count($selectResult) > 1 ? $table['pl_name'] : $table['sing_name'];

          $this->db->setQuery($updateQuery)->execute();
          $this->app->enqueueMessage(Text::sprintf('PLG_SYSTEM_JOOMOWNER_USER_DELETED_MSG', $tname, $elementList, $oldUserId, $fallbackUserId), 'info');
        }
      }
      catch(\RuntimeException $e)
      {
        $this->app->enqueueMessage($e->getMessage(), 'error');

        $return = false;
      }
    }

    return $return;
  }

  /**
   * Check if a user exists.
   *
   * @param   int  $userId
   *
   * @return  bool
   *
   * @since   4.0.0
   */
  protected function isUserExists($userId): bool
  {
    $userTable = User::getTable();

    return $userTable->load((int) $userId) === true;
  }

  /**
   * Check if JoomGallery component is installed.
   *
   * @return  int|bool   Extension id on success, false otherwise
   *
   * @since   4.0.0
   */
  protected function isJGExists()
  {
    if(\is_null(self::$jg_exists))
    {
      $query = $this->db->getQuery(true);

      $query->select('extension_id')
            ->from('#__extensions')
            ->where( array( 'type LIKE ' . $this->db->quote('component'),
                            'element LIKE ' . $this->db->quote('com_joomgallery')
                          ));
        
      $this->db->setQuery($query);

      if(!$res = $this->db->loadResult())
      {
        $res = false;
      }

      self::$jg_exists = $res;
    }

    return self::$jg_exists;
  }


  /**
   * Guess the content type based on a dot separated string.
   *
   * @param   string        $string  Context like string
   *
   * @return  string|false  Guessed type on success, false otherwise   
   *
   * @since   4.0.0
   */
  protected function guessType(string $string)
  {
    $pieces = \explode('.', $string);

    if(\count($pieces) > 1)
    {
      if(\key_exists($pieces[1], $this->tables))
      {
        return $this->tables[$pieces[1]]['owner'];
      }      
    }

    return false;
  }


  /**
   * Returns the plugin result
   *
   * @param   Event  $event  The event object
   * @param   mixed  $value  The value to be added to the result
   *
   * @return  void
   *
   * @since   4.0.0
   */
  private function setResult(Event $event, $value): void
	{
		if($event instanceof ResultAwareInterface)
    {
			$event->addResult($value);
			
			return;
		}

		$result   = $event->getArgument('result', []) ?: [];
		$result   = is_array($result) ? $result : [];
		$result[] = $value;
		$event->setArgument('result', $result);
	}
}
