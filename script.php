<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

defined('_JEXEC') or die();

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Installer\Installer;
use \Joomla\CMS\Installer\InstallerScript;
use \Joomla\CMS\Filesystem\File;
use \Joomla\CMS\Filesystem\Folder;

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
	protected $minPhp = '7.3.0';

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
	 * Method called before install/update the component. Note: This method won't be called during uninstall process.
	 *
	 * @param   string $type   Type of process [install | update]
	 * @param   mixed  $parent Object who called this method
	 *
	 * @return boolean True if the process should continue, false otherwise
   * @throws Exception
	 */
	public function preflight($type, $parent)
	{
    // Only proceed if Joomla version is correct
    if(version_compare(JVERSION, '5.0.0', '>=') || version_compare(JVERSION, '4.0.0', '<'))
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

      $this->new_code    = $parent->getManifest()->version;
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
    $act_version = explode('.',$this->act_code);
    $new_version = explode('.',$this->new_code);

    $install_message = $this->getInstallerMSG($act_version, $new_version, 'install');

    // Create default Category
    if(!$this->addDefaultCategory())
    {
      $app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_CREATE_DEFAULT_CATEGORY', 'error'));
    }

    // Create image types
    $img_types = array('original'  => array('path' => '/images/joomgallery/originals', 'alias' => 'orig'),
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

		$this->installPlugins($parent);
		$this->installModules($parent);

    $this->copyWatermarkfile();
    ?>

    <div class="text-center">
      <img src="../media/com_joomgallery/images/joom_logo.png" alt="JoomGallery Logo">
      <p></p>
      <div class="alert alert-light">
        <h3><?php echo Text::sprintf('COM_JOOMGALLERY_SUCCESS_INSTALL', $parent->getManifest()->version); ?></h3>
        <p><?php echo Text::_('COM_JOOMGALLERY_SUCCESS_INSTALL_TXT'); ?></p>
        <p>
          <a title="<?php echo Text::_('JLIB_HTML_START'); ?>" class="btn btn-primary" onclick="location.href='index.php?option=com_joomgallery'; return false;" href="#"><?php echo Text::_('JLIB_HTML_START'); ?></a>
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
    $act_version = explode('.',$this->act_code);
    $new_version = explode('.',$this->new_code);

    $update_message = $this->getInstallerMSG($act_version, $new_version, 'update');

		$this->installPlugins($parent);
		$this->installModules($parent);
    ?>

    <div class="text-center">
      <img src="../media/com_joomgallery/images/joom_logo.png" alt="JoomGallery Logo">
      <p></p>
      <div class="alert alert-light">
        <h3><?php echo Text::sprintf('COM_JOOMGALLERY_SUCCESS_UPDATE', $parent->getManifest()->version); ?></h3>
        <p>
          <button class="btn btn-small btn-info" data-toggle="modal" data-target="#jg-changelog-popup"><i class="icon-list"></i> <?php echo Text::_('COM_JOOMGALLERY_CHANGELOG'); ?></button>
        </p>
        <p><?php echo Text::_('COM_JOOMGALLERY_SUCCESS_INSTALL_TXT'); ?></p>
        <p>
          <a title="<?php echo Text::_('JLIB_HTML_START'); ?>" class="btn btn-primary" onclick="location.href='index.php?option=com_joomgallery'; return false;" href="#"><?php echo Text::_('JLIB_HTML_START'); ?></a>
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
	 * Add a category to the ´#__joomgallery_categories´ table
   *
	 * @return  bool  true on success
	 */
	public function addDefaultCategory()
	{
    $db = Factory::getDbo();

    $path       = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Table'.DIRECTORY_SEPARATOR.'CategoryTable.php';
    $tableClass = '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Table\\CategoryTable';

    require_once $path;

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
    $data["id"] = NULL;
    $data["asset_id"] = NULL;
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
    if (!$table->store($data))
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error store default category'), 'error');

      return false;
    }

    // Set level and parent_id
    $fields = array(
      $db->quoteName('parent_id') . ' = ' . $db->quote($data['parent_id']),
      $db->quoteName('level') . ' = ' . $db->quote($data['level'])
    );
    $conditions = array (
      $db->quoteName('alias') . ' = ' . $db->quote($data['alias'])
    );
    // insert to database
    $query = $db->getQuery(true);
    $query->update($db->quoteName(_JOOM_TABLE_CATEGORIES))->set($fields)->where($conditions);
    $db->setQuery($query);
    $db->execute();

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

    $path       = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Table'.DIRECTORY_SEPARATOR.'ConfigTable.php';
    $tableClass = '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Table\\ConfigTable';

    require_once $path;

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
    $data["jg_filesystem"] = 'localhost';
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

		if (count($plugins->children()))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			foreach ($plugins->children() as $plugin)
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

		if (!empty($modules))
		{

			if (count($modules->children()))
			{
				foreach ($modules->children() as $module)
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
		}
	}

	/**
	 * Uninstalls plugins
	 *
	 * @param   mixed $parent Object who called the uninstall method
	 *
	 * @return void
	 */
	private function uninstallPlugins($parent)
	{
		$app     = Factory::getApplication();

		if (method_exists($parent, 'getManifest'))
		{
			$plugins = $parent->getManifest()->plugins;
		}
		else
		{
			$plugins = $parent->get('manifest')->plugins;
		}

		if (count($plugins->children()))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			foreach ($plugins->children() as $plugin)
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
	}

	/**
	 * Uninstalls modules
	 *
	 * @param   mixed $parent Object who called the uninstall method
	 *
	 * @return void
	 */
	private function uninstallModules($parent)
	{
		$app = Factory::getApplication();

		if (method_exists($parent, 'getManifest'))
		{
			$modules = $parent->getManifest()->modules;
		}
		else
		{
			$modules = $parent->get('manifest')->modules;
		}

		if (!empty($modules))
		{

			if (count($modules->children()))
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true);

				foreach ($modules->children() as $plugin)
				{
					$moduleName = (string) $plugin['module'];
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
		}
	}

  /**
	 * Copies watermark files to /images/joomgallery/..
	 *
	 * @return   bool  True on success, false otherwise
	 */
	private function copyWatermarkfile()
	{
    // Define paths
    $files = array('watermark.png', 'logo.png');
    $src   = JPATH_ROOT.'/media/com_joomgallery/images/';
    $dst   = JPATH_ROOT.'/images/joomgallery/';

    $error = false;

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

    return $msg;
  }
}
