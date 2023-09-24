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

<div class="jg jg-migration step2">
  
  <div class="flex-center">
    <div class="btn-group navigation" aria-label="Migration navigation">
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step1'); ?>" class="btn btn-outline-primary">Step 1</a>
      <a href="#" class="btn btn-outline-primary active" aria-current="page">Step 2</a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step3'); ?>" class="btn btn-outline-primary">Step 3</a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step4'); ?>" class="btn btn-outline-primary">Step 4</a>
    </div>
  </div>

  <h2>Step 2: Migration pre-check</h2>
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

  <form action="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&task=migration.migrate'); ?>" method="post" enctype="multipart/form-data" 
        name="adminForm" id="migration-form" class="form-validate" aria-label="COM_JOOMGALLERY_MIGRATION_STEP2_TITLE">

      <?php if(empty($this->error)) : ?>
        <h3><?php echo Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_PRECHECK_TITLE'); ?></h3>

        <?php // Loop through all available check-categories ?>
        <?php foreach ($this->precheck as $cat) : ?>
          <div class="card">
            <div class="card-body"> 
              <?php if($cat->title): ?>
                <div class="card-title">
                  <h4><?php echo $cat->title; ?></h4>
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
                        <tr>
                          <td>
                            <strong><?php echo $check->title; ?></strong><br />
                            <small><?php echo $check->desc; ?></small>
                          </td>
                          <td><span class="badge bg-<?php echo $check->result ? 'success' : 'danger'; ?>"><?php echo $check->result ? 'success' : 'failed'; ?></span></td>
                          <td><a href="#" class="btn btn-outline-secondary" data-text="<?php echo $check->help; ?>"><span class="icon-question" aria-hidden="true"></span></a></td>
                        </tr>
                      <?php endforeach; ?>
                    
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <br />
        <?php endforeach; ?>

        <input type="submit" class="btn btn-primary <?php echo $this->success ? '' : 'disabled'; ?>" value="<?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STEP2_BTN_TXT'); ?>"/>
      <?php endif; ?>

      <input type="hidden" name="task" value="migration.migrate"/>
      <input type="hidden" name="precheck" value="<?php echo $this->success ? '1' : '0'; ?>"/>
      <input type="hidden" name="script" value="<?php echo $this->script->name; ?>"/>
      <?php echo HTMLHelper::_('form.token'); ?>
  </form>

  
</div>