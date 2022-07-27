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

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	 ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');

$app = Factory::getApplication();

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';

?>

<form
	action="<?php echo Route::_('index.php?option=com_joomgallery&layout='.$layout.$tmpl.'&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="image-form" class="form-validate"
  aria-label="<?php echo Text::_('COM_JOOMGALLERY_IMAGE_FORM_TITLE_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" >

  <div class="row title-alias form-vertical mb-3">
    <div class="col-12 col-md-4">
      <?php echo $this->form->renderField('imgtitle'); ?>
    </div>
    <div class="col-12 col-md-4">
      <?php echo $this->form->renderField('alias'); ?>
    </div>
    <div class="col-12 col-md-4">
      <?php echo $this->form->renderField('image'); ?>
			<?php echo $this->form->renderField('filename'); ?>
    </div>
  </div>

  <div class="main-card">
	<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'Details', 'recall' => true, 'breakpoint' => 768)); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Details', Text::_('JDETAILS', true)); ?>	
  <div class="row">
		<div class="col-lg-9">
			<fieldset class="adminform">
        <?php echo $this->form->getLabel('imgtext'); ?>
				<?php echo $this->form->getInput('imgtext'); ?>
			</fieldset>
		</div>
    <div class="col-lg-3">
      <fieldset class="form-vertical">
        <legend class="visually-hidden"><?php echo Text::_('JGLOBAL_FIELDSET_GLOBAL'); ?></legend>
        <?php echo $this->form->renderField('published'); ?>
				<?php echo $this->form->renderField('catid'); ?>
        <?php echo $this->form->renderField('featured'); ?>
        <?php echo $this->form->renderField('hidden'); ?>
        <?php echo $this->form->renderField('access'); ?>				
				<?php echo $this->form->renderField('language'); ?>
      </fieldset>
    </div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
  <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Images', Text::_('COM_JOOMGALLERY_COMMON_IMAGE', true)); ?>
	<div class="row">
    <div class="col-12 col-lg-6">
      <fieldset id="fieldset-images" class="options-form">
				<legend><?php echo Text::_('COM_JOOMGALLERY_IMGMAN_IMAGE_PREVIEW'); ?></legend>
        <div class="text-center">
          <img src="<?php echo JoomHelper::getImg($this->item, 'thumbnail'); ?>" class="img-thumbnail" alt="<?php echo Text::_('COM_JOOMGALLERY_TYPE_THUMBNAIL'); ?>">
        </div>
        <div class="text-center">
          <div class="btn-group joom-imgtypes" role="group" aria-label="<?php echo Text::_('COM_JOOMGALLERY_COMMON_SHOWIMAGE_LBL'); ?>">
            <?php foreach($this->imagetypes as $key => $imagetype) : ?>
              <a class="btn btn-outline-primary" style="cursor:pointer;" onclick="openModal('<?php echo $imagetype->typename; ?>')"><?php echo Text::sprintf('COM_JOOMGALLERY_COMMON_SHOWIMAGE_IMGTYPE', \ucfirst($imagetype->typename)); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
			</fieldset>
    </div>
    <div class="col-12 col-lg-6">
      <fieldset id="fieldset-images-data" class="options-form">
        <legend><?php echo Text::_('COM_JOOMGALLERY_COMMON_IMAGE_INFO'); ?></legend>
        <div>
          <?php echo $this->form->renderField('imgauthor'); ?>
          <?php echo $this->form->renderField('imgdate'); ?>
          <?php echo $this->form->renderField('hits'); ?>
          <?php echo $this->form->renderField('downloads'); ?>
          <?php echo $this->form->renderField('imgvotesum'); ?>
          <?php echo $this->form->renderField('imgmetadata'); ?>
        </div>          
      </fieldset>
    </div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
	<div class="row">
    <div class="col-12 col-lg-6">
			<fieldset id="fieldset-publishingdata" class="options-form">
				<legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
        <div>
          <?php echo $this->form->renderField('approved'); ?>
          <?php echo $this->form->renderField('created_time'); ?>
          <?php echo $this->form->renderField('created_by'); ?>
          <?php echo $this->form->renderField('modified_time'); ?>
          <?php echo $this->form->renderField('modified_by'); ?>
          <?php echo $this->form->renderField('id'); ?>
        </div>				
			</fieldset>
    </div>
    <div class="col-12 col-lg-6">
			<fieldset id="fieldset-metadata" class="options-form">
				<legend><?php echo Text::_('JGLOBAL_FIELDSET_METADATA_OPTIONS'); ?></legend>
        <div>
          <?php echo $this->form->renderField('metadesc'); ?>
				  <?php echo $this->form->renderField('metakey'); ?>
				  <?php echo $this->form->renderField('robots'); ?>
        </div>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'DisplayParams', Text::_('COM_JOOMGALLERY_COMMON_PARAMETERS', true)); ?>
	<div class="row">
  <div class="col-lg-12">
      <fieldset class="form-vertical">
				<legend class="visually-hidden"><?php echo Text::_('COM_JOOMGALLERY_COMMON_PARAMETERS'); ?></legend>
				<?php echo $this->form->renderField('params'); ?>
				<?php if ($this->state->params->get('save_history', 1)) : ?>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('version_note'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('version_note'); ?></div>
					</div>
				<?php endif; ?>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
  <input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
	<input type="hidden" name="jform[imgvotes]" value="<?php echo $this->item->imgvotes; ?>" />
	<input type="hidden" name="jform[useruploaded]" value="<?php echo $this->item->useruploaded; ?>" />

	<?php if (Factory::getUser()->authorise('core.admin','joomgallery')) : ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
      <?php echo $this->form->getInput('rules'); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
  <?php endif; ?>
	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

	<input type="hidden" name="task" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>

</form>

<?php
$options = array('modal-dialog-scrollable' => true,
                  'title'  => 'Test Title',
                  'footer' => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'.Text::_('JCLOSE').'</button>',
                );

echo HTMLHelper::_('bootstrap.renderModal', 'image-modal-box', $options, '<div id="modal-body">Content set by ajax.</div>');
?>

<script>
  function openModal(typename)
	{
    let modal = document.getElementById('image-modal-box');

    let modalTitle = modal.querySelector('.modal-title');
    let modalBody  = modal.querySelector('.modal-body');

    <?php
      $imgURL   = '{';
      $imgTitle = '{';

      foreach($this->imagetypes as $key => $imagetype)
      {
        $imgURL   .= $imagetype->typename.':"'.JoomHelper::getImg($this->item, $imagetype->typename).'",';
        $imgTitle .= $imagetype->typename.':"'.Text::_('COM_JOOMGALLERY_MAIMAN_TYPE_'.strtoupper($imagetype->typename)).'",';
      }

      $imgURL .= '}';
      $imgTitle .= '}';
    ?>
    let imgURL   = <?php echo $imgURL; ?>;
    let imgTitle = <?php echo $imgTitle; ?>;

    modalTitle.innerHTML = imgTitle[typename];
    let body  = '<div class="joom-image center">'
    body      = body + '<div class="joom-loader"><img src="<?php echo Uri::root(true); ?>/media/system/images/ajax-loader.gif" alt="loading..."></div>';
    body      = body + '<img src="' + imgURL[typename] + '" alt="' + imgTitle[typename] + '">';
    body      = body + '</div>';
    modalBody.innerHTML  = body;

    let bsmodal = new bootstrap.Modal(document.getElementById('image-modal-box'), {keyboard: false});
    bsmodal.show();
	}
</script>
