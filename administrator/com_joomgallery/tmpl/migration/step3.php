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

<div class="jg jg-migration step3">
  
  <div class="flex-center">
    <div class="btn-group navigation" aria-label="Migration navigation">
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step1'); ?>" class="btn btn-outline-primary">Step 1</a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step2'); ?>" class="btn btn-outline-primary">Step 2</a>
      <a href="#" class="btn btn-outline-primary active" aria-current="page">Step 3</a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step4'); ?>" class="btn btn-outline-primary">Step 4</a>
    </div>
  </div>

  <h2>Step 3: Perform migration</h2>
  <br />

  <?php if(!empty($this->error)): ?>
    <div class="alert alert-warning" role="alert">
      <?php foreach($this->error as $error) : ?>      
        <p><?php echo $error; ?></p>
      <?php endforeach; ?>
    </div>
    <?php return; ?>
  <?php endif; ?>

  <div class="alert alert-primary" role="alert">
    <h3><?php echo $this->script->title; ?></h3>
    <?php echo $this->script->description; ?>
  </div>

  <br />

  <?php if(empty($this->error) && !empty($this->migrateables)) : ?>
    <?php foreach($this->migrateables as $key => $migrateable) : ?>
      <form action="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&task=migration.migrate'); ?>" method="post" name="adminForm" id="adminForm">
        <div class="row align-items-start">
          <div class="col-md-12">
            <div class="card">
              <h3 class="card-header"><?php echo Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($migrateable->get('type')).'_TITLE'); ?></h3>
              <div class="card-body">
                <div class="badge-group mb-3">
                  <span class="badge bg-secondary">Pendent: <span><?php echo count($migrateable->get('queue')); ?></span></span>
                  <span class="badge bg-success">Successful: <span><?php echo count($migrateable->get('success')); ?></span></span>
                  <span class="badge bg-danger">Failed: <span><?php echo count($migrateable->get('error')); ?></span></span>
                </div>
                <input class="btn btn-primary mb-3" type="submit" value="<?php echo Text::_('Start migration'); ?>">
                <input type="hidden" name="task" value="migration.migrate"/>
                <input type="hidden" name="script" value="<?php echo $name; ?>"/>
                <?php echo HTMLHelper::_('form.token'); ?>
                <div class="progress">
                  <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </form>
      <br />
    <?php endforeach; ?>
  <?php endif; ?>

  <form action="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&task=migration.postcheck'); ?>" method="post" enctype="multipart/form-data" 
        name="adminForm" id="migration-form" class="form-validate">

      <input type="submit" class="btn btn-primary disabled" value="<?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STEP3_BTN_TXT'); ?>" disabled/>
      <input type="hidden" name="task" value="migration.postcheck"/>
      <input type="hidden" name="script" value="<?php echo $this->script->name; ?>"/>
      <?php echo HTMLHelper::_('form.token'); ?>
  </form>
</div>