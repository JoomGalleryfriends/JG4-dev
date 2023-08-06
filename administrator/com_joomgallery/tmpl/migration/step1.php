<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
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

<div class="jg jg-migration step1">
  
  <div class="flex-center">
    <div class="btn-group navigation" aria-label="Migration navigation">
      <a href="#" class="btn btn-outline-primary active" aria-current="page">Step 1</a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step2'); ?>" class="btn btn-outline-primary">Step 2</a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step3'); ?>" class="btn btn-outline-primary">Step 3</a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step4'); ?>" class="btn btn-outline-primary">Step 4</a>
    </div>
  </div>

  <h2>Step 1: Migration configuration</h2>
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
    <h4><?php echo $this->script->title; ?></h4>
    <?php echo $this->script->description; ?>
  </div>

  <form action="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&task=migration.precheck'); ?>" method="post" enctype="multipart/form-data" 
        name="adminForm" id="migration-form" class="form-validate card" aria-label="COM_JOOMGALLERY_MIGRATION_STEP1_TITLE">

    <div class="card-body">
      <?php foreach($this->form->getFieldsets() as $key => $fieldset) : ?>
        <div class="row">
          <div class="col-12 col-lg-9">
            <fieldset class="options-form">
              <legend><?php echo Text::_($fieldset->label); ?></legend>
              <div>
                <?php echo $this->form->renderFieldset($fieldset->name);; ?>
              </div>
            </fieldset>
          </div>
        </div>
      <?php endforeach; ?>

      <input type="hidden" name="task" value="migration.precheck"/>
      <input type="hidden" name="script" value="<?php echo $this->script->name; ?>"/>
      <?php echo HTMLHelper::_('form.token'); ?>
      <input type="submit" class="btn btn-primary" value="<?php echo $this->script->startBtnText; ?>"/>
    </div>
  </form>
</div>