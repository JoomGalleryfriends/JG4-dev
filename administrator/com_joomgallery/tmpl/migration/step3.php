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

use \Joomla\CMS\Factory;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Form\FormFactoryInterface;

// Import CSS
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')
   ->useScript('com_joomgallery.admin')
   ->useScript('com_joomgallery.migrator');

// Add language strings to JavaScript
Text::script('COM_JOOMGALLERY_ERROR_NETWORK_PROBLEM');
Text::script('ERROR');
Text::script('WARNING');
Text::script('INFO');
Text::script('SUCCESS');
?>

<div class="jg jg-migration step3">
  
  <div class="flex-center">
    <div class="btn-group navigation" aria-label="Migration navigation">
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step1'); ?>" class="btn btn-outline-primary"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 1); ?></a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step2'); ?>" class="btn btn-outline-primary"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 2); ?></a>
      <a href="#" class="btn btn-outline-primary active" aria-current="page"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 3); ?></a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step4'); ?>" disabled class="btn btn-outline-primary disabled"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 4); ?></a>
    </div>
  </div>

  <h2><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 3); ?>: <?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STEP3_TITLE'); ?></h2>
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
    <?php 
      $i = 0;
      $previousCompleted = true;
      $completed = array();
    ?>
    <?php foreach($this->migrateables as $key => $migrateable) : ?>
      <?php
        $type = $migrateable->get('type');
        $total = count($migrateable->get('queue')) + $migrateable->get('failed')->count() + $migrateable->get('successful')->count();
      ?>
      <form  name="migrationForm-<?php echo $type; ?>" id="migrationForm-<?php echo $type; ?>" action="<?php echo Route::_('index.php?option='._JOOM_OPTION); ?>" method="post">
        <div class="row align-items-start">
          <div class="col-md-12">
            <div class="card">
              <h3 class="card-header"><?php echo Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($type).'_TITLE'); ?></h3>
              <div class="card-body">
                <div class="badge-group mb-3">
                  <span class="badge bg-secondary"><?php echo Text::_('COM_JOOMGALLERY_PENDING'); ?>: <span id="badgeQueue-<?php echo $type; ?>"><?php echo count($migrateable->queue); ?></span></span>
                  <span class="badge bg-success"><?php echo Text::_('COM_JOOMGALLERY_SUCCESSFUL'); ?>: <span id="badgeSuccessful-<?php echo $type; ?>"><?php echo count($migrateable->successful); ?></span></span>
                  <span class="badge bg-danger"><?php echo Text::_('COM_JOOMGALLERY_FAILED'); ?>: <span id="badgeFailed-<?php echo $type; ?>"><?php echo count($migrateable->failed); ?></span></span>
                </div>
                <button id="migrationBtn-<?php echo $type; ?>" class="btn btn-primary mb-3 btn-migration<?php if($previousCompleted && !$migrateable->completed){echo '';}else{echo ' disabled';}; ?>" onclick="Migrator.submitTask(event, this)" <?php if($previousCompleted && !$migrateable->completed){echo '';}else{echo ' disabled';}; ?> data-type="<?php echo $type; ?>"><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_START'); ?></button>
                <button id="stopBtn-<?php echo $type; ?>" class="btn mb-3 btn-outline-secondary btn-stop disabled" onclick="Migrator.stopTask(event, this)" disabled="true" data-type="<?php echo $type; ?>"><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STOP'); ?></button>
                <button id="repairBtn-<?php echo $type; ?>" class="btn mb-3 btn-outline-secondary<?php echo ($total > 0) ? '' : ' disabled'; ?>" onclick="Migrator.repairTask(event, this)" <?php echo ($total > 0) ? '' : 'disabled'; ?> data-type="<?php echo $type; ?>"><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_MANUAL'); ?></button>
                <input type="hidden" name="type" value="<?php echo $type; ?>"/>
                <input type="hidden" name="task" value="migration.start"/>
                <input type="hidden" name="migrateable" value="<?php echo base64_encode(json_encode($migrateable, JSON_UNESCAPED_UNICODE)); ?>"/>
                <input type="hidden" name="script" value="<?php echo $this->script->name; ?>"/>
                <?php echo HTMLHelper::_('form.token'); ?>
                <div class="progress mb-2">
                  <div id="progress-<?php echo $type; ?>" class="progress-bar" style="width: <?php echo $migrateable->progress; ?>%" role="progressbar" aria-valuenow="<?php echo $migrateable->progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php if($migrateable->progress > 0){echo $migrateable->progress.'%';}; ?></div>
                </div>
                <a class="collapse-arrow mb-2" data-bs-toggle="collapse" href="#collapseLog-<?php echo $type; ?>" role="button" aria-expanded="false" aria-controls="collapseLog">
                  <i class="icon-angle-down"></i><span> <?php echo Text::_('COM_JOOMGALLERY_SHOWLOG'); ?></span>
                </a>
                <div class="collapse mt-2" id="collapseLog-<?php echo $type; ?>">
                  <div id="logOutput-<?php echo $type; ?>" class="card card-body border bg-light log-area">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
      <br />
      <?php 
        $previousCompleted = $migrateable->completed;

        if($migrateable->completed)
        {
          array_push($completed, $type);
        }

        $i++;
      ?>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php
    $total_complete = false;
    if(empty(array_diff_key($completed, array_keys($this->migrateables))) && empty(array_diff_key(array_keys($this->migrateables), $completed)))
    {
      $total_complete = true;
    }
  ?>
  <form action="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&task=migration.postcheck'); ?>" method="post" enctype="multipart/form-data" 
        name="adminForm" id="migration-form" class="form-validate">

      <input id="step4Btn" type="submit" class="btn btn-primary<?php echo $total_complete ? '' : ' disabled'; ?>" value="<?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STEP3_BTN_TXT'); ?>" <?php echo $total_complete ? '' : 'disabled'; ?>/>
      <input type="hidden" name="task" value="migration.postcheck"/>
      <input type="hidden" name="script" value="<?php echo $this->script->name; ?>"/>
      <?php echo HTMLHelper::_('form.token'); ?>
  </form>

  <?php
  // Load migrepair form
  $formFactory   = Factory::getContainer()->get(FormFactoryInterface::class);
  $migrepairForm = $formFactory->createForm('migrepairForm', array());
  $source        = _JOOM_PATH_ADMIN . '/forms/migrationrepair.xml';

  if ($migrepairForm->loadFile($source) == false)
  {
    throw new \RuntimeException('Form::loadForm could not load file');
  }

  // Migration repair modal box
  $options = array('modal-dialog-scrollable' => true,
                    'title'  => Text::_('COM_JOOMGALLERY_MIGRATION_MANUAL'),
                    'footer' => '<input type="submit" form="migrepairForm" class="btn btn-primary" value="'.Text::_('COM_JOOMGALLERY_MIGRATION_MANUAL_BTN').'"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'.Text::_('JCLOSE').'</button>',
                  );
  $data    = array('script' => $this->script->name, 'form' => $migrepairForm);
  $layout  = new FileLayout('joomgallery.migrepair', null, array('component' => 'com_joomgallery', 'client' => 1));
  $body  = $layout->render($data);

  echo HTMLHelper::_('bootstrap.renderModal', 'repair-modal-box', $options, $body);
  ?>
</div>