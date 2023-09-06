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
use \Joomla\CMS\Layout\FileLayout;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
$wa = $this->document->getWebAssetManager();
//$wa->registerAndUseStyle('com_joomgallery.uppy', _JOOM_OPTION.'/uppy/uppy-'.$uppy_version.'.min.css');
//$wa->registerAndUseScript('com_joomgallery.uppy', _JOOM_OPTION.'/uppy/uppy-'.$uppy_version.'.js', [], ['type' => 'module', 'defer' => true]);
$wa->useScript('keepalive')
	 ->useScript('form.validate')
   ->useScript('com_joomgallery.uppy-uploader')
   ->useStyle('com_joomgallery.uppy')
   ->useStyle('com_joomgallery.admin');

HTMLHelper::_('bootstrap.tooltip');

$app = Factory::getApplication();

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';

// Add language strings to JavaScript
Text::script('JCLOSE');
Text::script('JAUTHOR');
Text::script('JGLOBAL_TITLE');
Text::script('JGLOBAL_DESCRIPTION');
Text::script('JGLOBAL_VALIDATION_FORM_FAILED');
Text::script('COM_JOOMGALLERY_UPLOADING');
Text::script('COM_JOOMGALLERY_SAVING');
Text::script('COM_JOOMGALLERY_WAITING');
Text::script('COM_JOOMGALLERY_DEBUG_INFORMATION'); 
Text::script('COM_JOOMGALLERY_FILE_TITLE_HINT');
Text::script('COM_JOOMGALLERY_FILE_DESCRIPTION_HINT');
Text::script('COM_JOOMGALLERY_FILE_AUTHOR_HINT');
Text::script('COM_JOOMGALLERY_SUCCESS_UPPY_UPLOAD');
Text::script('COM_JOOMGALLERY_ERROR_UPPY_UPLOAD');
Text::script('COM_JOOMGALLERY_ERROR_UPPY_FORM');
Text::script('COM_JOOMGALLERY_ERROR_UPPY_SAVE_RECORD');
Text::script('COM_JOOMGALLERY_ERROR_FILL_REQUIRED_FIELDS');

$wa->addInlineScript('window.uppyVars = JSON.parse(\''. json_encode($this->js_vars) . '\');', ['position' => 'before'], [], ['com_joomgallery.uppy-uploader']);
?>

<div class="jg jg-upload">
  <form
    action="<?php echo Route::_('index.php?option=com_joomgallery&controller=image'); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="needs-validation"
    novalidate aria-label="<?php echo Text::_('COM_JOOMGALLERY_IMAGES_UPLOAD', true); ?>" >

    <div class="row align-items-start">
      <div class="col-xxl-auto col-md-6 mb"> 
        <div class="card">
          <div class="card-header">
            <h2><?php echo Text::_('COM_JOOMGALLERY_IMAGE_SELECTION'); ?></h2>
          </div>
          <div id="drag-drop-area">
            <div class="card-body"><?php echo Text::_('COM_JOOMGALLERY_INFO_UPLOAD_FORM_NOT_LOADED'); ?></div>
          </div>
          <hr />
          <div class="card-body">
            <?php echo $this->form->renderField('debug'); ?>
          </div>
        </div>
      </div>
      <div class="col card">
        <div class="card-header">
          <h2><?php echo Text::_('JOPTIONS'); ?></h2>
        </div>
        <div class="card-body">
          <p>
            <?php
              $displayData = [
                  'description' => Text::_('COM_JOOMGALLERY_GENERIC_UPLOAD_DATA'),
                  'id'          => 'adminForm-desc',
                  'small'       => true
              ];
              $renderer = new FileLayout('joomgallery.tip');
            ?>
            <?php echo $renderer->render($displayData); ?>
          </p>
          <?php echo $this->form->renderField('catid'); ?>
          <?php if(!$this->config->get('jg_useorigfilename')): ?>
            <?php echo $this->form->renderField('imgtitle'); ?>
            <?php if($this->config->get('jg_filenamenumber')): ?>
              <?php echo $this->form->renderField('nmb_start'); ?>
            <?php endif; ?>
          <?php endif; ?>
          <?php echo $this->form->renderField('imgauthor'); ?>
          <?php echo $this->form->renderField('published'); ?>
          <?php echo $this->form->renderField('access'); ?>
          <?php echo $this->form->renderField('language'); ?>
          <fieldset class="adminform">
            <?php echo $this->form->getLabel('imgtext'); ?>
            <?php echo $this->form->getInput('imgtext'); ?>
          </fieldset>
          <input type="text" id="jform_id" class="hidden form-control readonly" name="jform[id]" value="" readonly/>
        </div>
      </div>
    </div>

    <input type="hidden" name="task" value="image.ajaxsave"/>
    <input type="hidden" name="jform[uploader]" value="tus" />
    <input type="hidden" name="jform[multiple]" value="1" />
    <?php if($this->config->get('jg_useorigfilename')): ?>
      <input type="hidden" name="jform[imgtitle]" value="title" />
    <?php endif; ?>
    <input type="hidden" name="id" value="0" />
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
  <div id="popup-area"></div>
</div>
