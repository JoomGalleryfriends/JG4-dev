<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;


HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	 ->useScript('form.validate')
   ->useStyle('com_joomgallery.admin');
HTMLHelper::_('bootstrap.tooltip');

$app = Factory::getApplication();

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
?>

<form
	action="<?php echo Route::_('index.php?option=com_joomgallery&layout='.$layout.$tmpl.'&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="tag-form" class="form-validate"
  aria-label="<?php echo Text::_('COM_JOOMGALLERY_TAG_FORM_TITLE_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" >

  <div class="row title-alias form-vertical mb-3">
    <div class="col-12 col-md-6">
      <?php echo $this->form->renderField('title'); ?>
    </div>
    <div class="col-12 col-md-6">
      <?php echo $this->form->renderField('alias'); ?>
    </div>
  </div>
	
  <div class="main-card">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'Details', 'recall' => true, 'breakpoint' => 768)); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Details', Text::_('JDETAILS', true)); ?>
    <div class="row">
      <div class="col-lg-9">
        <fieldset class="adminform">
          <?php echo $this->form->getLabel('description'); ?>
          <?php echo $this->form->getInput('description'); ?>
        </fieldset>
      </div>
      <div class="col-lg-3">
        <fieldset class="form-vertical">
          <legend class="visually-hidden"><?php echo Text::_('JGLOBAL_FIELDSET_GLOBAL'); ?></legend>
          <?php echo $this->form->renderField('published'); ?>
          <?php echo $this->form->renderField('access'); ?>
          <?php echo $this->form->renderField('language'); ?>
        </fieldset>
      </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
    <div class="row">
      <div class="col-12">
        <fieldset id="fieldset-publishingdata" class="options-form">
          <legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
          <div>
            <?php echo $this->form->renderField('created_time'); ?>
            <?php echo $this->form->renderField('created_by'); ?>
            <?php echo $this->form->renderField('modified_time'); ?>
            <?php echo $this->form->renderField('modified_by'); ?>
            <?php echo $this->form->renderField('id'); ?>
          </div>          
        </fieldset>
      </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php if (Factory::getUser()->authorise('core.admin','joomgallery')) : ?>
      <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
        <?php echo $this->form->getInput('rules'); ?>
      <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endif; ?>
    
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
  </div>

	<input type="hidden" name="task" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>

</form>
