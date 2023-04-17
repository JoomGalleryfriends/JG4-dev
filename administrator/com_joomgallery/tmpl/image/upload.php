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

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

$uppy_version = '3.7.0'; // Uppy version to use

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('com_joomgallery.uppy', _JOOM_OPTION.'/uppy/uppy-'.$uppy_version.'.min.css');
$wa->registerAndUseScript('com_joomgallery.uppy', _JOOM_OPTION.'/uppy/uppy-'.$uppy_version.'.js', [], ['type' => 'module', 'defer' => true]);
$wa->useScript('keepalive')
	 ->useScript('form.validate')
   ->useStyle('com_joomgallery.admin');

HTMLHelper::_('bootstrap.tooltip');

$app = Factory::getApplication();

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';

// Add language strings to JavaScript
Text::script('COM_JOOMGALLERY_DEBUG_INFORMATION');
Text::script('JCLOSE');

// Add variables to JavaScript
$js_vars = new stdClass();
$js_vars->maxFileSize = 262144000;
$js_vars->TUSlocation = $this->item->tus_location;

$wa->addInlineScript('window.uppyVars = JSON.parse(\''. json_encode($js_vars) . '\');', ['position' => 'before'], [], ['com_joomgallery.uppy']);
?>

<div class="jg jg-upload">
  <form
    action="<?php echo Route::_('index.php?option=com_joomgallery&layout='.$layout.$tmpl); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="image-form" class="form-validate"
    aria-label="<?php echo Text::_('COM_JOOMGALLERY_IMAGES_UPLOAD', true); ?>" >

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
          <p><?php echo Text::_('COM_JOOMGALLERY_GENERIC_UPLOAD_DATA'); ?></p>
          <?php echo $this->form->renderField('catid'); ?>
          <?php if(!$this->config->get('jg_useorigfilename')): ?>
            <?php echo $this->form->renderField('imgtitle'); ?>
            <?php if($this->config->get('jg_filenamenumber')): ?>
              <?php echo $this->form->renderField('numbering'); ?>
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
        </div>
      </div>
    </div>

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
  <div id="popup-area"></div>
</div>
