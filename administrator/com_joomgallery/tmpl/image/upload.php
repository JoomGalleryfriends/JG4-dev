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

// Uppy config
$uppy_version = 'v3.5.0'; // Uppy version to use

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('com_joomgallery.uppy', 'https://releases.transloadit.com/uppy/'.$uppy_version.'/uppy.min.css');
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

<script type="module">
  import { Uppy, Dashboard, Tus } from "https://releases.transloadit.com/uppy/<?php echo $uppy_version;?>/uppy.min.mjs"

  let uppy = new Uppy({
    autoProceed: false,
    restrictions: {
      maxFileSize: 262144000, // 250MB
      allowedFileTypes: ['image/*', 'video/*', 'audio/*', 'text/*', 'application/*'],
    }
  });

  if(uppy != null)
  {
    document.getElementById('drag-drop-area').innerHTML = '';
  }

  uppy.use(Dashboard, {
    inline: true,
    target: '#drag-drop-area',
    showProgressDetails: true,
    metaFields: [
      { id: 'imgtitle', name: 'Name', placeholder: 'file name'},
      { id: 'imgtext', name: 'Description', placeholder: 'description of the file' },
      { id: 'author', name: 'Author', placeholder: 'author of the file' }
    ],
  });

  uppy.use(Tus, {
    endpoint: "<?php echo $this->item->tus_location; ?>",
    retryDelays: [0, 1000, 3000, 5000],
    allowedMetaFields: null,
  })

  let getUuid = function(uploadURL) {
    let query = uploadURL.split('?')[1];
    let queryArray = query.split('&');

    for (let i = 0; i < queryArray.length; ++i) {
      if(queryArray[i].includes('uuid')) {
        return queryArray[i].replace('uuid=','');
      }
    }

    return '';
  }

  let createPopup = function(id, content) {
    let html =    '<div class="joomla-modal modal fade" id="modal'+id+'" tabindex="-1" aria-labelledby="modal'+id+'Label" aria-hidden="true">';
    html = html +   '<div class="modal-dialog modal-lg">';
    html = html +      '<div class="modal-content">';
    html = html +           '<div class="modal-header">';
    html = html +               '<h3 class="modal-title" id="modal'+id+'Label"><?php echo Text::_('COM_JOOMGALLERY_DEBUG_INFORMATION');?></h3>';
    html = html +               '<button type="button" class="btn-close novalidate" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE');?>"></button>';
    html = html +           '</div>';
    html = html +           '<div class="modal-body">';
    html = html +               '<div id="'+id+'-ModalBody">'+content+'</div>';
    html = html +           '</div>';
    html = html +           '<div class="modal-footer">';
    html = html +               '<button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="event.preventDefault()" aria-label="<?php echo Text::_('JCLOSE');?>"><?php echo Text::_('JCLOSE');?></button>';
    html = html +           '</div>';
    html = html +      '</div>';
    html = html +   '</div>';
    html = html + '</div>';

    return html;
  }

  uppy.on('upload-success', (file, response) => {
    // send form data and add record to database
    console.log(file.name, response.uploadURL)
    let tmp = '';
  })

  uppy.on('complete', (result) => {
    // add message for successful images
    for (let index = 0; index < result.successful.length; ++index) {
      let res = result.successful[index];
      let uuid = getUuid(res.uploadURL);

      // Add Button to upload form
      let btn = document.createElement('div');
      btn.innerHTML = '<button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modal'+uuid+'"><?php echo Text::_('COM_JOOMGALLERY_DEBUG_INFORMATION');?></button>';
      btn.classList.add('uppy-Dashboard-Item-debug-msg');
      btn.classList.add('success');
      document.getElementById('uppy_'+res.id).lastChild.firstChild.appendChild(btn);

      // Add Popup
      let div = document.createElement('div');
      div.innerHTML = createPopup(uuid, 'Upload of file "'+res.name+'" using Uppy successful.<br />Upload-ID: '+uuid+'<br />Debug-Info will be added here...');
      document.getElementById('popup-area').appendChild(div);

      new bootstrap.Modal(document.getElementById('modal'+uuid));
    }

    // add message for failed images
    for (let index = 0; index < result.failed.length; ++index) {
      let res = result.failed[index];
      let uuid = getUuid(res.uploadURL);

      // Add Button to upload form
      let btn = document.createElement('div');
      btn.innerHTML = '<button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modal'+uuid+'"><?php echo Text::_('COM_JOOMGALLERY_DEBUG_INFORMATION');?></button>';
      btn.classList.add('uppy-Dashboard-Item-debug-msg');
      btn.classList.add('error');
      document.getElementById('uppy_'+res.id).lastChild.firstChild.appendChild(btn);

      // Add Popup
      let div = document.createElement('div');
      div.innerHTML = createPopup(uuid, 'Upload not successful.<br />Debug-Info flow. To be added...');
      document.getElementById('popup-area').appendChild(div);

      new bootstrap.Modal(document.getElementById('modal'+uuid));
    }

    console.log('successful files:', result.successful);
    console.log('failed files:', result.failed);
  });
</script>
