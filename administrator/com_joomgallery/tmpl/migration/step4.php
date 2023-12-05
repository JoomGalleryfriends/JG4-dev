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

  <div class="alert alert-primary" role="alert">
    <h3><?php echo $this->script->title; ?></h3>
    <?php echo $this->script->description; ?>
  </div>

  <br />

  <?php // Postcheck results ?>
  <?php if(empty($this->error)) : ?>
    <?php // Loop through all available check-categories ?>
    <?php foreach ($this->postcheck as $cat) : ?>
      <div class="card">
        <div class="card-body"> 
          <?php if($cat->title): ?>
            <div class="card-title">
              <h3><?php echo Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_CHECK_TITLE') . ': ' . $cat->title; ?></h3>
              <?php if($cat->desc): ?>
                <span><?php echo $cat->desc; ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <div class="card-text">
            <table class="table">
              <caption class="visually-hidden"><?php echo $cat->title; ?></caption>
              <thead>
                <tr>
                  <th class="w-70" scope="col"><?php echo $cat->colTitle; ?></th>
                  <th scope="w-20"><?php echo Text::_('JSTATUS'); ?></th>
                  <th class="w-10" scope="col"><?php echo Text::_('JTOOLBAR_HELP'); ?></th>
                </tr>
              </thead>
              <tbody>

                  <?php // Loop through all available check-categories ?>
                  <?php foreach ($cat->checks as $check) : ?>
                    <?php
                      if($check->result)
                      {
                        if($check->warning)
                        {
                          // Check successful, but marked as warning
                          $badgeClass = 'warning';
                          $badgeText  = Text::_('COM_JOOMGALLERY_WARNING');
                        }
                        else
                        {
                          // Check successful
                          $badgeClass = 'success';
                          $badgeText  = Text::_('COM_JOOMGALLERY_SUCCESSFUL');
                        }
                      }
                      else
                      {
                        // Check failed
                        $badgeClass = 'danger';
                        $badgeText  = Text::_('COM_JOOMGALLERY_FAILED');
                      }                          
                    ?>
                    <tr>
                      <td>
                        <strong><?php echo $check->title; ?></strong><br />
                        <small><?php echo $check->desc; ?></small>
                      </td>
                      <td><span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span></td>
                      <td>
                        <button class="btn btn-outline-secondary<?php if(empty($check->help)) { echo ' disabled';};?>" <?php if(empty($check->help)) { echo 'disabled';};?>
                                data-title="<?php echo $check->title; ?>" data-text="<?php echo $check->help; ?>" onclick="openModal(event, this)">
                          <span class="icon-question" aria-hidden="true"></span>
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <br />
    <?php endforeach; ?>
  <?php endif; ?>

  <br />

  <?php if($this->success) : ?>
    <form action="<?php echo Route::_('index.php?option=com_joomgallery&view=migration'); ?>" method="post" name="adminForm" id="adminForm">
      <?php // Option: Delete source data ?>
      <?php if($this->sourceDeletion) : ?>
      <div class="card">
        <div class="card-body">
          <div class="card-title">
            <h3><?php echo Text::_('COM_JOOMGALLERY_SOURCE'); ?></h3>
            <span><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_REMOVE_SOURCE_DATA_DESC'); ?></span>
          </div>
          <div class="card-text">
            <br />
            <joomla-button id="migration-removesource" task="migration.removesource" confirm-message="<?php echo Text::_('COM_JOOMGALLERY_MIGRATION_BTN_REMOVE_SOURCE_CONFIRM'); ?>">
              <button class="btn btn-primary" data-submit-task="migration.removesource" type="button"><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_BTN_REMOVE_SOURCE'); ?></button>
            </joomla-button>
          </div>
        </div>
      </div>
      <br /><br />
      <?php endif; ?>

      <?php // Option: Remove/Abort migration ?>
      <joomla-button id="migration-abort" task="migration.delete">
        <button class="btn btn-primary" data-submit-task="migration.delete" type="button"><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_BTN_END_MIGRATION'); ?></button>
      </joomla-button>
    
      <input type="hidden" name="script" value="<?php echo $this->script->name; ?>"/>
      <?php foreach ($this->openMigrations as $openMigration) : ?>
        <input type="hidden" name="cid[]" value="<?php echo $openMigration->id; ?>"/>
      <?php endforeach; ?>
      <?php echo HTMLHelper::_('form.token'); ?>
    </form>
  <?php else: ?>
    <?php // Show back to step 3 button ?>
    <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step3'); ?>" class="btn btn-primary"><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STEP4_BTN_TXT'); ?></a>
  <?php endif; ?>

</div>