<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

defined('_JEXEC') or die();

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Installer\Installer;
use \Joomla\CMS\Installer\InstallerScript;
use \Joomla\CMS\Filesystem\File;
use \Joomla\CMS\Filesystem\Folder;
use \Joomla\CMS\Uri\Uri;

/**
 * Install method
 * is called by the installer of Joomla!
 *
 * @return  void
 * @since   4.0.0
 */
class com_joomgalleryInstallerScript extends InstallerScript
{
	/**
	 * The title of the component (printed on installation and uninstallation messages)
	 *
	 * @var string
	 */
	protected $extension = 'JoomGallery';

  /**
	 * Minimum PHP version required to install the extension
	 *
	 * @var  string
	 */
	protected $minPhp = '7.4.0';

  /**
   * Release code of the currently installed version
   *
   * @var  string
   */
  protected $act_code = '';

  /**
   * Release code of the new version to be installed
   *
   * @var  string
   */
  protected $new_code = '';

  /**
   * True to skip output during install() method
   *
   * @var  bool
   */
  protected $installSkipMsg = false;


	/**
	 * Method called before install/update the component. Note: This method won't be called during uninstall process.
	 *
	 * @param   string   $type     Type of process [install | update | uninstall]
	 * @param   mixed    $parent   Object who called this method
	 *
	 * @return  boolean  True if the process should continue, false otherwise
	 */
	public function preflight($type, $parent)
	{
    // Only proceed if Joomla version is correct
    if(version_compare(JVERSION, '4.0.0', '<'))
    {
      Factory::getApplication()->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_JOOMLA_COMPATIBILITY', '4.x', '4.x'), 'error');

      return false;
    }

    // Only proceed if PHP version is correct
    if(version_compare(PHP_VERSION, $this->minPhp, '<='))
    {
      Factory::getApplication()->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_PHP_COMPATIBILITY', '4.x', '7.3', $this->minPhp), 'error');

      return false;
    }

    if(!\defined('_JOOM_OPTION'))
    {
      if($type == 'install' || $type == 'update')
      {
        // use new uploaded defines.php
        $temp_dir = $parent->getParent()->getPath('source');
        $defines  = $temp_dir.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'defines.php';
      }
      else
      {
        // use old defines.php
        $defines = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'defines.php';
      }
      
      require_once $defines;
    }

		$result = parent::preflight($type, $parent);

		if (!$result)
		{
			return $result;
		}

    if($type == 'update')
    {
      // save release code information
      //-------------------------------
      if (File::exists(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'joomgallery.xml'))
      {
        $xml = simplexml_load_file(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'joomgallery.xml');
        $this->act_code = $xml->version;
      }
      else
      {
        Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_READ_XML_FILE'), 'note');
      }
    }

    $this->new_code = $parent->getManifest()->version;

    // Prepare for migration JG1-3 to JG4.x
    if($type == 'install' || ($type == 'update' && preg_match('/^([1-3]\.)(\d+\.)(\d+)*(.+)/', $this->act_code)))
    {
      // rename old JoomGallery tables (JGv1-3)
      $jgtables = $this->detectJGtables();
      if($jgtables)
      {
        $db = Factory::getDbo();
        foreach($jgtables as $oldTable)
        {
          $db->renameTable($oldTable, $oldTable.'_old');
        }
      }

      // remove old JoomGallery files and folders
      foreach($this->detectJGfolders() as $folder)
      {
        if(Folder::exists($folder))
        {
          Folder::delete($folder);
        }
      }
      foreach($this->detectJGfiles() as $file)
      {
        if(File::exists($file))
        {
          File::delete($file);
        }
      }

      // deactivate old JoomGallery extensions
      foreach($this->detectJGExtensions() as $extension_id)
      {
        $this->deactivateExtension($extension_id);
      }

      if($type == 'update')
      {
        $ext = $this->getDBextension();
        // remove records in #__schemas table
        $this->removeSchemas($ext->extension_id);
        // remove records in #__assets table
        $this->removeAssets();
        // remove records in #__content_types table
        $this->removeContentTypes();
        // remove JG3 modules
        $this->uninstallModules(array('mod_joomgithub'));
      }
    }

		// logic for preflight before install
		return $result;
	}

	/**
	 * Method to install the component
	 *
	 * @param   mixed $parent Object who called this method.
	 *
	 * @return void
	 *
	 * @since 0.2b
	 */
	public function install($parent)
	{
    $app = Factory::getApplication();

		$this->installPlugins($parent);
		$this->installModules($parent);

    $this->copyImgFiles();

    if($this->installSkipMsg)
    {
      return;
    }

    // Create news feed module
    $subdomain = '';
    $language = Factory::getLanguage();
    if(strpos($language->getTag(), 'de-') === false)
    {
      $subdomain = 'en.';
    }
    $feed_params = array('cache'=>1,
                         'cache_time'=>15,
                         'moduleclass_sfx'=>'',
                         'rssurl'=>'https://www.'.$subdomain.'joomgalleryfriends.net/?format=feed&amp;type=rss',
                         'rssrtl'=>0,
                         'rssdate'=>0,
                         'rssdesc'=>0,
                         'rssimage'=>1,
                         'rssitems'=>3,
                         'rssitemdesc'=>1,
                         'word_count'=>200);
    $feed_params = json_encode($feed_params);
    $this->createModule('JoomGallery News', 'joom_cpanel', 'mod_feed', 1, $app->getCfg('access'), 1, $feed_params, 1, '*');

    $act_version = explode('.',$this->act_code);
    $new_version = explode('.',$this->new_code);

    $install_message = $this->getInstallerMSG($act_version, $new_version, 'install');
    ?>

    <div class="text-center">
      <img src="<?php echo Uri::root(); ?>/media/com_joomgallery/images/logo.png" alt="JoomGallery Logo" width="100px">
      <p></p>
      <div class="alert alert-light">
        <h3><?php echo Text::sprintf('COM_JOOMGALLERY_SUCCESS_INSTALL', $parent->getManifest()->version); ?></h3>
        <p><?php echo Text::_('COM_JOOMGALLERY_SUCCESS_INSTALL_TXT'); ?></p>
        <p>
          <a title="<?php echo Text::_('JLIB_HTML_START'); ?>" class="btn btn-primary" onclick="location.href='index.php?option=com_joomgallery&amp;view=control'; return false;" href="#"><?php echo Text::_('JLIB_HTML_START'); ?></a>
          <a title="<?php echo Text::_('COM_JOOMGALLERY_LANGUAGES'); ?>" class="btn btn-outline-primary" onclick="location.href='index.php?option=com_joomgallery&controller=help'; return false;" href="#"><?php echo Text::_('COM_JOOMGALLERY_LANGUAGES'); ?></a>
        </p>
        <?php if ($install_message != '') : ?>
          <div><?php echo $install_message;?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php
	}

  /**
	 * Method to update the component
	 *
	 * @param   mixed $parent Object who called this method.
	 *
	 * @return void
	 */
	public function update($parent)
	{
    if(preg_match('/^([1-3]\.)(\d+\.)(\d+)*(.+)/', $this->act_code))
    {
      // We update from an old version (JG 1-3)
      $this->installSkipMsg = true;
      $this->install($parent);
    }
    else
    {
      // We update from a new version (JG 4.x)
      $this->installPlugins($parent);
		  $this->installModules($parent);
    }

    $act_version = explode('.',$this->act_code);
    $new_version = explode('.',$this->new_code);

    $update_message = $this->getInstallerMSG($act_version, $new_version, 'update');
    ?>

    <div class="text-center">
    <img src="<?php echo Uri::root(); ?>/media/com_joomgallery/images/logo.png" alt="JoomGallery Logo" width="100px">
      <p></p>
      <div class="alert alert-light">
        <h3><?php echo Text::sprintf('COM_JOOMGALLERY_SUCCESS_UPDATE', $parent->getManifest()->version); ?></h3>
        <p>
          <button class="btn btn-small btn-info" data-toggle="modal" data-target="#jg-changelog-popup"><i class="icon-list"></i> <?php echo Text::_('COM_JOOMGALLERY_CHANGELOG'); ?></button>
        </p>
        <p><?php echo Text::_('COM_JOOMGALLERY_SUCCESS_INSTALL_TXT'); ?></p>
        <p>
          <a title="<?php echo Text::_('JLIB_HTML_START'); ?>" class="btn btn-primary" onclick="location.href='index.php?option=com_joomgallery&amp;view=control'; return false;" href="#"><?php echo Text::_('JLIB_HTML_START'); ?></a>
          <a title="<?php echo Text::_('COM_JOOMGALLERY_LANGUAGES'); ?>" class="btn btn-outline-primary" onclick="location.href='index.php?option=com_joomgallery&controller=help'; return false;" href="#"><?php echo Text::_('COM_JOOMGALLERY_LANGUAGES'); ?></a>
        </p>
        <?php if ($update_message != '') : ?>
          <div><?php echo $update_message;?></div>
        <?php endif; ?>
      </div>
    </div>

    <div id="jg-changelog-popup" class="modal fade" tabindex="-1" aria-labelledby="PopupChangelogModalLabel">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 id="PopupChangelogModalLabel" class="modal-title"><?php echo Text::_('COM_JOOMGALLERY_CHANGELOG'); ?></h5>
            <button type="button" class="btn-close" data-dismiss="modal" aria-label="<?php echo Text::_('JTOOLBAR_CLOSE'); ?>">&times;</button>
          </div>
          <div class="modal-body">
            <iframe class="iframe" frameborder="0" src="<?php echo Route::_('index.php?option=com_joomgallery&controller=changelog&tmpl=component'); ?>" height="400px" width="100%"></iframe>
          </div>
          <div class="modal-footer">
            <button class="btn btn-primary" data-dismiss="modal"><?php echo Text::_('JTOOLBAR_CLOSE'); ?></button>
          </div>
        </div>
      </div>
    </div>

    <?php
	}

	/**
	 * Method to uninstall the component
	 *
	 * @param   mixed $parent Object who called this method.
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
    $app = Factory::getApplication();
    $act_version = explode('.',$this->act_code);
    $new_version = explode('.',$this->new_code);

    $uninstall_message = $this->getInstallerMSG($act_version, $new_version, 'uninstall');

		$this->uninstallPlugins($parent);
		$this->uninstallModules($parent);

    // Delete administrator module JoomGallery News
    $db    = Factory::getDbo();
    $query = $db->getQuery(true);

    $query
      ->clear()
      ->delete('#__modules')
      ->where(
        array(
          'position = ' . $db->quote('joom_cpanel'),
          'module = ' . $db->quote('mod_feed')
        )
      );

    $db->setQuery($query);
    $db->execute();

    // Delete directories
    if(!Folder::delete(JPATH_ROOT.'/images/joomgallery'))
    {
      $app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_DELETE_CATEGORY', '"/images/joomgallery"'), 'error');
    }
    ?>

    <div class="text-center">
      <div class="alert alert-light">
        <h3><?php echo Text::_('COM_JOOMGALLERY_SUCCESS_UNINSTALL'); ?></h3>
        <p><?php echo Text::_('COM_JOOMGALLERY_SUCCESS_UNINSTALL_TXT'); ?></p>

        <?php if ($uninstall_message != '') : ?>
          <div><?php echo $uninstall_message;?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php
	}

  /**
   * Runs right after any installation action is performed on the component.
   *
   * @param   string $type    Type of process [install | update | uninstall]
	 * @param   mixed  $parent  Object who called this method
   *
   * @return void
   */
  function postflight($type, $parent)
  {
    if($type == 'install')
    {
      $app = Factory::getApplication();

      // Create default Category
      if(!$this->addDefaultCategory())
      {
        $app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_CREATE_DEFAULT_CATEGORY', 'error'));
      }

      // Create image types
      $img_types = array( 'original'  => array('path' => '/images/joomgallery/originals', 'alias' => 'orig'),
                          'detail'    => array('path' => '/images/joomgallery/details', 'alias' => 'det'),
                          'thumbnail' => array('path' => '/images/joomgallery/thumbnails', 'alias' => 'thumb')
                        );
      $this->count = 0;
      foreach ($img_types as $key => $type)
      {
        // Create default Image types records
        if(!$this->addDefaultIMGtype($key, $type['alias'], $type['path']))
        {
          $app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_CREATE_DEFAULT_IMAGETYPE'), 'error');
        }

        // Create default Image types directories
        if(!Folder::create(JPATH_ROOT.$type['path'].'/uncategorised'))
        {
          $app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_CATEGORY', 'Uncategorised'), 'error');
        }
        $this->count = $this->count + 1;
      }

      // Create default Configuration-Set
      if(!$this->addDefaultConfig())
      {
        $app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_CREATE_DEFAULT_CONFIG', 'error'));
      }

      // Create default mail templates
      $suc_templates = true;
      if(!$this->addMailTemplate('newimage', array('user_id', 'user_username', 'user_name', 'img_id', 'img_title', 'cat_id', 'cat_title')))
      {
        $suc_templates = false;
      }
      if(!$suc_templates)
      {
        $app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_CREATE_DEFAULT_CONFIG', 'error'));
      }
    }
  }

  /**
	 * Add a mail template to the ´#__mail_templates´ table
   *
   * @param  string  context_id  Name of the mail template
   * @param  array   tags        List of tags that can be used as variables in this mail template
   * @param  string  language    Language tag to specify the language this template is used for (default='' : all langauges)
   * 
	 * @return  bool  true on success
	 */
	public function addMailTemplate($context_id, $tags, $language='')
  {
    $db = Factory::getDbo();

    // Create the model
    $com_mails  = Factory::getApplication()->bootComponent('com_mails');
    $tableClass = $com_mails->getMVCFactory()->createTable('template', 'administrator');

    if(class_exists($tableClass))
    {
      $table = new $tableClass($db);
    }
    else
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error load mail template table'), 'error');

      return false;
    }

    // add standard tags
    $params = new stdClass();
    $params->tags = array('sitename', 'siteurl');

    // add provided tags    
    if(is_array($tags) && count($tags) > 0)
    {
      $params->tags = array_merge($params->tags, $tags);
    }

    $data = array();
    $data["id"] = null;
    $data['template_id'] = 'com_joomgallery.'.strtolower($context_id);
    $data['extension'] = 'com_joomgallery';
    $data['language'] = $language;
    $data['subject'] = 'COM_JOOMGALLERY_MAIL_'.strtoupper($context_id).'_SUBJECT';
    $data['body'] = 'COM_JOOMGALLERY_MAIL_'.strtoupper($context_id).'_BODY';
    $data['params'] = json_encode($params);

    if (!$table->bind($data))
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error bind mail template'), 'error');

      return false;
    }
    if (!$table->store($data))
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error store mail template'), 'error');

      return false;
    }

    return true;
  }

  /**
	 * Add a category to the ´#__joomgallery_categories´ table
   *
	 * @return  bool  true on success
	 */
	public function addDefaultCategory()
	{
    $db = Factory::getDbo();

    // Load JoomTableTrait
    $trait_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Table'.DIRECTORY_SEPARATOR.'JoomTableTrait.php';
    $traitClass = '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Table\\JoomTableTrait';

    require_once $trait_path;

    // Load CategoryTable
    $class_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Table'.DIRECTORY_SEPARATOR.'CategoryTable.php';
    $tableClass = '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Table\\CategoryTable';

    require_once $class_path;

    if(class_exists($tableClass))
    {      
      $table = new $tableClass($db);
    }
    else
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error load category table'), 'error');

      return false;
    }

    $data = array();
    $data["id"] = null;
    $data["asset_id"] = null;
    $data["parent_id"] = 1;
    $data["level"] = 1;
    $data["path"] = "uncategorised";
    $data["title"] = "Uncategorised";
    $data["alias"] = "uncategorised";
    $data["description"] = "";
    $data["access"] = 1;
    $data["published"] = 1;
    $data["params"] = '{"allow_download":"-1","allow_comment":"-1","allow_rating":"-1","allow_watermark":"-1","allow_watermark_download":"-1"}';
    $data["language"] = "*";
    $data["metadesc"] = "";
    $data["metakey"] = "";

    if (!$table->bind($data))
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error bind default category'), 'error');

      return false;
    }

    $table->setLocation(1, 'last-child');

    if (!$table->store($data))
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error store default category'), 'error');

      return false;
    }

    return true;
  }

  /**
	 * Add a category to the ´#__joomgallery_configs´ table
   *
	 * @return  bool  true on success
	 */
	public function addDefaultConfig()
	{
    $db = Factory::getDbo();

    // Load JoomTableTrait
    $trait_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Table'.DIRECTORY_SEPARATOR.'JoomTableTrait.php';
    $traitClass = '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Table\\JoomTableTrait';

    require_once $trait_path;

    // Load ConfigTable
    $class_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Table'.DIRECTORY_SEPARATOR.'ConfigTable.php';
    $tableClass = '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Table\\ConfigTable';

    require_once $class_path;

    if(class_exists($tableClass))
    {
      $table = new $tableClass($db);
    }
    else
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error load configs table'), 'error');

      return false;
    }

    $data = array();
    $data["id"] = NULL;
    $data["asset_id"] = NULL;
    $data["group_id"] = 1;
    $data["title"] = 'Global Configuration';
    $data["published"] = 1;
    $data["ordering"] = 0;
    $data["checked_out"] = 0;
    $data["created_by"] = 0;
    $data["modified_by"] = 0;
    $data["jg_filesystem"] = 'local-images';
    $data["jg_wmfile"] = 'images/joomgallery/watermark.png';
    $data["jg_replaceinfo"] = '{}';
    $data["jg_staticprocessing"] = '{}';
    $data["jg_dynamicprocessing"] = '{}';
    $data["jg_imgprocessor"] = 'gd';
    $data["jg_maxusercat"] = 10;
    $data["jg_maxuserimage"] = 500;
    $data["jg_maxuserimage_timespan"] = 0;
    $data["jg_maxfilesize"] = 2000000;
    $data["jg_maxuploadfields"] = 3;
    $data["jg_maxvoting"] = 5;

    if (!$table->bind($data))
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error bind category'), 'error');

      return false;
    }
    if (!$table->store($data))
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error store category'), 'error');

      return false;
    }

    return true;
  }

  /**
	 * Add a category to the ´#__joomgallery_img_types´ table
   *
   * @param   string $type Image type name
   * @param   string $type Image type alias
   * @param   string $path Path for the image type
   *
	 * @return  bool  true on success
	 */
	public function addDefaultIMGtype($type, $alias, $path)
	{
    $db = Factory::getDbo();

    switch($type)
    {
      case 'detail':
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"3","jg_imgtypewidth":"1000","jg_imgtypeheight":"1000","jg_cropposition":"2","jg_imgtypeorinet":"1","jg_imgtypeanim":"0","jg_imgtypesharpen":"0","jg_imgtypequality":"80","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"[]"}';
        break;

      case 'thumbnail':
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"4","jg_imgtypewidth":"250","jg_imgtypeheight":"250","jg_cropposition":"2","jg_imgtypeorinet":"1","jg_imgtypeanim":"0","jg_imgtypesharpen":"1","jg_imgtypequality":"60","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"[]"}';
        break;
      
      default:
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"0","jg_imgtypewidth":"","jg_imgtypeheight":"","jg_cropposition":"2","jg_imgtypeorinet":"0","jg_imgtypeanim":"1","jg_imgtypesharpen":"0","jg_imgtypequality":"100","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"[]"}';
        break;
    }

    $record = new stdClass();
    $record->typename = $type;
    $record->type_alias = $alias;
    $record->path = $path;
    $record->params = $params;
    $record->ordering = $this->count;

    // Insert the object into the user profile table.
    if(!$db->insertObject(_JOOM_TABLE_IMG_TYPES, $record))
    {
      return false;
    }

    return true;
  }

  /**
	 * Deactivate an extension based on its id
	 *
	 * @param  int   $id  The ID of the extension to be deactivated
	 *
	 * @return void
	 */
	private function deactivateExtension($id)
	{
    $db = Factory::getDbo();
    $query = $db->getQuery(true);

    $query->update($db->quoteName('#__extensions'))
          ->set($db->quoteName('enabled'). ' = 0')
					->where($db->quoteName('extension_id') . ' = ' . $id);
		
    $db->setQuery($query);
		
    return $db->execute();
  }

	/**
	 * Installs plugins for this component
	 *
	 * @param   mixed $parent Object who called the install/update method
	 *
	 * @return void
	 */
	private function installPlugins($parent)
	{
		$installation_folder = $parent->getParent()->getPath('source');
		$app                 = Factory::getApplication();

		/* @var $plugins SimpleXMLElement */
		if (method_exists($parent, 'getManifest'))
		{
			$plugins = $parent->getManifest()->plugins;
		}
		else
		{
			$plugins = $parent->get('manifest')->plugins;
		}

    if(!$plugins || empty($plugins->children()) || count($plugins->children()) <= 0)
    {
      return;
    }

    $db    = Factory::getDbo();
    $query = $db->getQuery(true);

    foreach($plugins->children() as $plugin)
    {
      $pluginName  = (string) $plugin['plugin'];
      $pluginGroup = (string) $plugin['group'];
      $path        = $installation_folder . '/plugins/' . $pluginGroup . '/' . $pluginName;
      $installer   = new Installer;

      if (!$this->isAlreadyInstalled('plugin', $pluginName, $pluginGroup))
      {
        $result = $installer->install($path);
      }
      else
      {
        $result = $installer->update($path);
      }

      if ($result)
      {
        $app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_SUCCESS_INSTALL_EXT', 'Plugin', $pluginName));
      }
      else
      {
        $app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_INSTALL_EXT', 'Plugin', $pluginName), 'error');
      }

      $query
        ->clear()
        ->update('#__extensions')
        ->set('enabled = 1')
        ->where(
          array(
            'type LIKE ' . $db->quote('plugin'),
            'element LIKE ' . $db->quote($pluginName),
            'folder LIKE ' . $db->quote($pluginGroup)
          )
        );
      $db->setQuery($query);
      $db->execute();
    }
	}

	/**
	 * Check if an extension is already installed in the system
	 *
	 * @param   string $type   Extension type
	 * @param   string $name   Extension name
	 * @param   mixed  $folder Extension folder(for plugins)
	 *
	 * @return boolean
	 */
	private function isAlreadyInstalled($type, $name, $folder = null)
	{
		$result = false;

		switch ($type)
		{
			case 'plugin':
				$result = file_exists(JPATH_PLUGINS . '/' . $folder . '/' . $name);
				break;
			case 'module':
				$result = file_exists(JPATH_SITE . '/modules/' . $name);
				break;
		}

		return $result;
	}

	/**
	 * Installs modules for this component
	 *
	 * @param   mixed $parent Object who called the install/update method
	 *
	 * @return void
	 */
	private function installModules($parent)
	{
		$installation_folder = $parent->getParent()->getPath('source');
		$app                 = Factory::getApplication();

		if (method_exists($parent, 'getManifest'))
		{
			$modules = $parent->getManifest()->modules;
		}
		else
		{
			$modules = $parent->get('manifest')->modules;
		}

    if(!$modules || empty($modules->children()) || count($modules->children()) <= 0)
    {
      return;
    }

    foreach($modules->children() as $module)
    {
      $moduleName = (string) $module['module'];
      $path       = $installation_folder . '/modules/' . $moduleName;
      $installer  = new Installer;

      if (!$this->isAlreadyInstalled('module', $moduleName))
      {
        $result = $installer->install($path);
      }
      else
      {
        $result = $installer->update($path);
      }

      if ($result)
      {
        $app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_SUCCESS_INSTALL_EXT', 'Module', $moduleName));
      }
      else
      {
        $app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_INSTALL_EXT', 'Module', $moduleName), 'error');
      }
    }
	}

	/**
	 * Uninstalls plugins
	 *
	 * @param   mixed  $parent  Object who called the uninstall method
	 *
	 * @return void
	 */
	private function uninstallPlugins($parent)
	{
		$app = Factory::getApplication();

		if(method_exists($parent, 'getManifest'))
		{
			$plugins = $parent->getManifest()->plugins;
		}
		else
		{
			$plugins = $parent->get('manifest')->plugins;
		}

    if(!$plugins || empty($plugins->children()) || count($plugins->children()) <= 0)
    {
      return;
    }

    $db    = Factory::getDbo();
    $query = $db->getQuery(true);

    foreach($plugins->children() as $plugin)
    {
      $pluginName  = (string) $plugin['plugin'];
      $pluginGroup = (string) $plugin['group'];
      $query
        ->clear()
        ->select('extension_id')
        ->from('#__extensions')
        ->where(
          array(
            'type LIKE ' . $db->quote('plugin'),
            'element LIKE ' . $db->quote($pluginName),
            'folder LIKE ' . $db->quote($pluginGroup)
          )
        );
      $db->setQuery($query);
      $extension = $db->loadResult();

      if (!empty($extension))
      {
        $installer = new Installer;
        $result    = $installer->uninstall('plugin', $extension);

        if ($result)
        {
          $app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_SUCCESS_UNINSTALL_EXT', 'Plugin', $pluginName));
        }
        else
        {
          $app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_UNINSTALL_EXT', 'Plugin', $pluginName), 'error');
        }
      }
    }
	}

	/**
	 * Uninstalls modules
	 *
	 * @param   mixed  $parent  Object who called the uninstall method or array with module names
	 *
	 * @return void
	 */
	private function uninstallModules($parent)
	{
		$app = Factory::getApplication();

    if(is_array($parent))
    {
      // We got an array of module names
      $modules = $parent;
    }
    else
    {
      // We got the parent object
      if(method_exists($parent, 'getManifest'))
      {
        $modules = $parent->getManifest()->modules;
      }
      else
      {
        $modules = $parent->get('manifest')->modules;
      }
    }

    if(!$modules || empty($modules->children()) || count($modules->children()) <= 0)
    {
      return;
    }

    $db    = Factory::getDbo();
    $query = $db->getQuery(true);

    foreach($modules->children() as $module)
    {
      if(is_array($parent))
      {
        $moduleName = (string) $module;
      }
      else
      {
        $moduleName = (string) $module['module'];
      }
      
      $query
        ->clear()
        ->select('extension_id')
        ->from('#__extensions')
        ->where(
          array(
            'type LIKE ' . $db->quote('module'),
            'element LIKE ' . $db->quote($moduleName)
          )
        );
      $db->setQuery($query);
      $extension = $db->loadResult();

      if (!empty($extension))
      {
        $installer = new Installer;
        $result    = $installer->uninstall('module', $extension);

        if ($result)
        {
          $app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_SUCCESS_UNINSTALL_EXT', 'Module', $moduleName));
        }
        else
        {
          $app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_UNINSTALL_EXT', 'Module', $moduleName), 'error');
        }
      }
    }
	}

  /**
	 * Copies watermark files to /images/joomgallery/..
	 *
	 * @return   bool  True on success, false otherwise
	 */
	private function copyImgFiles()
	{
    // Define paths
    $files = array('watermark.png', 'logo.png', 'no-image.png');
    $src   = JPATH_ROOT.'/media/com_joomgallery/images/';
    $dst   = JPATH_ROOT.'/images/joomgallery/';

    $error = false;

    // Create destination folder if not exists
    if(!Folder::exists($dst))
    {
      Folder::create($dst);
    }

    // Copy files
    foreach ($files as $file)
    {
      if(!File::copy($src.$file, $dst.$file))
      {
        Factory::getApplication()->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_COPY_IMAGETYPE', $file, 'Watermark'), 'error');

        $error = false;
      }
    }

    return !$error;
  }

  /**
   * Generates post installer messages.
   *
   * @param  array   $act_version     Array with the currently installled version code
   * @param  array   $new_version     Array with the version code the package will be updated to
   * @param  string  $methode         install, uninstall, update
   *
   * @return string html string of the message
   */
  private function getInstallerMSG($act_version, $new_version, $methode)
  {
    $msg = '';

    if(strpos(end($new_version), 'dev'))
    {
      // We are dealing with a development version (alpha or beta)
      $msg .= Text::_('COM_JOOMGALLERY_NOTE_DEVELOPMENT_VERSION');
    }

    return $msg;
  }

  /**
	 * Detect already installed joomgallery extensions (< v4.0.0)
	 *
	 * @return  array   List of extension id's
	 */
	private function detectJGExtensions()
	{
    $db = Factory::getDbo();
    $query = $db->getQuery(true);

    // List of all extensions that could negatively impact the JoomGallery (> v4.0.0) from running.
    // Plugins of group "joomgallery" don't have to be listed.
    $extensions = array( 'plg_finderjoomgallery', 'plg_systemjgfinder', 'joomadditionalimagefields', 'joomadditionalcategoryfields', 'jgfinder',
                         'joomplu', 'joombu', 'joomautocat', 'plg_quickicon_joomgallery', 'plg_search_joomgallery', 'joommediaformfield',
                         'mod_joomstats', 'mod_joomadmstats', 'mod_joomfacebookcomments', 'mod_joomimg', 'mod_joomcat', 'mod_joomsearch',
                         'mod_jgtreeview'
                       );

    $query->select('extension_id')
					->from('#__extensions')
					->where('folder LIKE ' . $db->quote('joomgallery'))
          ->orWhere(array('element LIKE ' . $db->quote('joomgallery'), 'type != ' . $db->quote('component')));
    
    foreach($extensions as $key => $extName)
    {
      $query->orWhere(array('element LIKE ' . $db->quote(strtolower($extName)), 'element LIKE ' . $db->quote(strtoupper($extName))), 'OR')
            ->orWhere(array('name LIKE ' . $db->quote(strtolower($extName)), 'name LIKE ' . $db->quote(strtoupper($extName))), 'OR');
    }
		
    $db->setQuery($query);
		
    return $db->loadColumn();
  }

  /**
	 * Detect already installed joomgallery tables
	 *
	 * @return  array|bool   List of  table names or false if no tables detected
	 */
	private function detectJGtables()
	{
    try
    {
      $db     = Factory::getDbo();
      $tables = $db->getTableList();
      $prefix = Factory::getApplication()->get('dbprefix');

      if(empty($tables))
      {
        return false;
      }

      // remove non joomgallery tables and tables with other prefixes
      foreach($tables as $key => $table)
      {
        if(strpos($table, 'joomgallery') === false || strpos($table, $prefix) === false)
        {
          unset($tables[$key]);
        }
      }
      $tables = array_values($tables);
    }
    catch(Exception $e)
    {
      return false;
    }

    return $tables;
  }

  /**
	 * Detect old joomgallery folders (< v4.0.0)
	 *
	 * @return  array|bool   List of folder paths or false if no folders detected
	 */
	private function detectJGfolders()
	{
    $app = Factory::getApplication();

    $folders = array(
      JPATH_ROOT.'/components/com_joomgallery',
      JPATH_ROOT.'/media/joomgallery',
      JPATH_ROOT.'/administrator/components/com_joomgallery',
      JPATH_ROOT.'/layouts/joomgallery',
      JPATH_ROOT.'/views/vote',
      $app->get('tmp_path').'/joomgallerychunks'
    );

    return $folders;
  }

  /**
	 * Detect old joomgallery files (< v4.0.0)
	 *
	 * @return  array|bool   List of file paths or false if no folders detected
	 */
	private function detectJGfiles()
	{
    $files = array();

    $folders = array(
      '/administrator/language',
      '/administrator/logs',
      '/language',      
    );

    // Search folder for files containing "com_joomgallery"
    foreach($folders as $folder)
    {
      $files = array_merge($files, glob(JPATH_ROOT.$folder.'/*com*[j,J]oomgallery*'));
      $files = array_merge($files, glob(JPATH_ROOT.$folder.'/*/*com*[j,J]oomgallery*'));
      $files = array_merge($files, glob(JPATH_ROOT.$folder.'/*/*/*com*[j,J]oomgallery*'));
    }

    // Cache file of the newsfeed for the update checker JoomGallery < 3.3.5
    $files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('http://www.joomgallery.net/components/com_newversion/rss/extensions2.rss').'.spc';
    $files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('http://www.en.joomgallery.net/components/com_newversion/rss/extensions2.rss').'.spc';
    $files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('http://www.joomgallery.net/components/com_newversion/rss/extensions3.rss').'.spc';
    $files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('http://www.en.joomgallery.net/components/com_newversion/rss/extensions3.rss').'.spc';
    // Cache file of the newsfeed for the update checker JoomGallery >= 3.3.5
    $files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('https://www.joomgalleryfriends.net/components/com_newversion/rss/extensions2.rss').'.spc';
    $files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('https://www.en.joomgalleryfriends.net/components/com_newversion/rss/extensions2.rss').'.spc';
    $files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('https://www.joomgalleryfriends.net/components/com_newversion/rss/extensions3.rss').'.spc';
    $files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('https://www.en.joomgalleryfriends.net/components/com_newversion/rss/extensions3.rss').'.spc';

    return $files;
  }

  /**
	 * Get DB extension record of JoomGallery
	 *
	 * @return  object|bool   DB record on success, false otherwise
	 */
  private function getDBextension()
  {
    $db = Factory::getDbo();
    $query = $db->getQuery(true);

    $query->select('*')
					->from('#__extensions')
					->where(
						array(
							'type LIKE ' . $db->quote('component'),
							'element LIKE ' . $db->quote('com_joomgallery')
						)
					);
		
    $db->setQuery($query);
		
    return $db->loadObject();
  }

  /**
	 * Remove all schemas of a specific extension
   * 
   * @param   int           Extension id
	 *
	 * @return  object|bool   DB record on success, false otherwise
	 */
  private function removeSchemas($id)
  {
    $db = Factory::getDbo();
    $query = $db->getQuery(true);

    $query->delete($db->quoteName('#__schemas'));
    $query->where('extension_id = ' . $db->quote($id));

    $db->setQuery($query);

    return $db->execute();
  }

  /**
	 * Remove all JoomGallery related assets
	 *
	 * @return  object|bool   DB record on success, false otherwise
	 */
  private function removeAssets()
  {
    $db = Factory::getDbo();
    $query = $db->getQuery(true);

    $query->delete($db->quoteName('#__assets'));
    $query->where('name LIKE ' . $db->quote('com_joomgallery%'));
    $query->orWhere('title LIKE ' . $db->quote('%JoomGallery%'));

    $db->setQuery($query);

    return $db->execute();
  }

  /**
	 * Remove all JoomGallery related content_types
	 *
	 * @return  object|bool   DB record on success, false otherwise
	 */
  private function removeContentTypes()
  {
    $db = Factory::getDbo();
    $query = $db->getQuery(true);

    $query->delete($db->quoteName('#__content_types'));
    $query->where('type_alias LIKE ' . $db->quote('com_joomgallery%'));

    $db->setQuery($query);

    return $db->execute();
  }

  /**
   * Creates and publishes a module (extension need to be installed)
   *
   * @param   string   $title      title of the module
   * @param   string   $position   position fo the module to be placed
   * @param   string   $module     installation name of the module extension
   * @param   integer  $ordering   number of the sort order
   * @param   integer  $access     id of the access level
   * @param   integer  $showTitle  show or hide module title (0: hide, 1: show)
   * @param   string   $params     module params (json)
   * @param   integer  $client_id  module of which client (0: client, 1: admin)
   * @param   string   $lang       langage tag (language filter / *: all languages)
   *
   * @return  boolean True on success, false otherwise
   */
  private function createModule($title, $position, $module, $ordering, $access, $showTitle, $params, $client_id, $lang)
  {
    // check if the module already exists
    $db    = Factory::getDbo();
    $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__modules'))
                ->where($db->quoteName('position').' = '.$db->quote($position))
                ->where($db->quoteName('module').' = '.$db->quote($module));
    $db->setQuery($query);
    $module_id = $db->loadResult();

    // create module if it is not yet created
    if (empty($module_id))
    {
      $row = JTable::getInstance('module');
      $row->title     = $title;
      $row->ordering  = $ordering;
      $row->position  = $position;
      $row->published = 1;
      $row->module    = $module;
      $row->access    = $access;
      $row->showtitle = $showTitle;
      $row->params    = $params;
      $row->client_id = $client_id;
      $row->language  = $lang;
      if(!$row->store())
      {
        $app->enqueueMessage(JText::_('Unable to create "'.$title.'" module!'), 'error');

        return false;
      }

      $db      = Factory::getDbo();
      $query   = $db->getQuery(true);
      $columns = array('moduleid', 'menuid');
      $values  = array($row->id, 0);

      $query
          ->insert($db->quoteName('#__modules_menu'))
          ->columns($db->quoteName($columns))
          ->values(implode(',', $values));

      $db->setQuery($query);
      $db->execute();
    }

    return true;
  }
}
