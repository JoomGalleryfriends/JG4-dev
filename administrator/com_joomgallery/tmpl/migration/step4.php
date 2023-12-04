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

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/src/Helper/');

// Import CSS
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')
   ->useScript('com_joomgallery.admin');
?>

<div class="jg jg-migration step4">
  
  <div class="flex-center">
    <div class="btn-group navigation" aria-label="Migration navigation">
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step1'); ?>" class="btn btn-outline-primary"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 1); ?></a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step2'); ?>" class="btn btn-outline-primary"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 2); ?></a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step3'); ?>" class="btn btn-outline-primary"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 3); ?></a>
      <a href="#" class="btn btn-outline-primary active" aria-current="page"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 4); ?></a>
    </div>
  </div>

  <h2><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 4); ?>: <?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STEP4_TITLE'); ?></h2>
  <br />

  <?php if(!empty($this->error)): ?>
    <div class="alert alert-warning" role="alert">
      <?php foreach($this->error as $error) : ?>      
        <p><?php echo $error; ?></p>
      <?php endforeach; ?>
    </div>
    <?php return; ?>
  <?php endif; ?>

  
</div>