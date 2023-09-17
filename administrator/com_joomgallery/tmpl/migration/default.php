<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                              **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

// Import CSS
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')
   ->useScript('com_joomgallery.admin');
?>

<div class="jg jg-migration">
  <h2><?php echo Text::_('COM_JOOMGALLERY_AVAILABLE_SCRIPT'); ?>:</h2>
  <br />

  <?php foreach ($this->scripts as $name => $script) : ?>
    <form action="<?php echo Route::_('index.php?option=com_joomgallery&view=migration'); ?>" method="post" name="adminForm" id="adminForm">
      <div class="row align-items-start">
        <div class="col-md-12">
          <div class="card">
            <h3 class="card-header"><?php echo Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($name).'_TITLE'); ?></h3>
            <div class="card-body row">
              <div class="col-2">
                <img src="<?php echo $script['img']; ?>" alt="<?php echo $name; ?> logo">
              </div>
              <div class="col-10">
                <p><?php echo Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($name).'_DESC'); ?></p>
                <input class="btn btn-primary" type="submit" value="<?php echo Text::_('COM_JOOMGALLERY_USE_SCRIPT'); ?>">
                <input type="hidden" name="task" value="display"/>
                <input type="hidden" name="layout" value="step1"/>
                <input type="hidden" name="script" value="<?php echo $name; ?>"/>
                <?php echo HTMLHelper::_('form.token'); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  <?php endforeach; ?>
</div>