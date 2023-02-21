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
$wa->useScript('keepalive')
	 ->useScript('form.validate')
   ->useStyle('com_joomgallery.admin');
HTMLHelper::_('bootstrap.tooltip');

$app = Factory::getApplication();

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';

$wa->registerAndUseStyle('com_joomgallery.uppy', 'https://releases.transloadit.com/uppy/'.$uppy_version.'/uppy.min.css');
?>

<form
	action="<?php echo Route::_('index.php?option=com_joomgallery&layout='.$layout.$tmpl); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="image-form" class="form-validate"
  aria-label="<?php echo Text::_('COM_JOOMGALLERY_IMAGES_UPLOAD', true); ?>" >

  <div class="row align-items-start">
    <div class="col-12 col-md-6 mb">
      <div id="drag-drop-area"></div>
    </div>
    <div class="col-12 col-md-6 card">
      <div class="card-header">
        <h2>Options</h2>
      </div>
      <div class="card-body">
        <?php echo $this->form->renderField('catid'); ?>
      </div>
    </div>
  </div>

  <input type="hidden" name="task" value=""/>
  <?php echo HTMLHelper::_('form.token'); ?>
</form>

<script type="module">
  import { Uppy, Dashboard, Tus } from "https://releases.transloadit.com/uppy/<?php echo $uppy_version;?>/uppy.min.mjs"

  let uppy = new Uppy({
    autoProceed: false,
    restrictions: {
      maxFileSize: 10000000,
      allowedFileTypes: ['image/*'],
    }
  });

  uppy.use(Dashboard, {
    inline: true,
    target: '#drag-drop-area',
    showProgressDetails: true,
    metaFields: [
      { id: 'name', name: 'Name', placeholder: 'file name'},
      { id: 'description', name: 'Description', placeholder: 'description of the file' },
      { id: 'owner', name: 'Author', placeholder: 'author of the file' }
    ],
  });

  uppy.use(Tus, {
    endpoint: 'https://tusd.tusdemo.net/files/',
    retryDelays: [0, 1000, 3000, 5000],
    allowedMetaFields: null,
  })

  uppy.on('complete', (result) => {
    // add message for successful images
    for (let index = 0; index < result.successful.length; ++index) {
      let res = result.successful[index];
      let response = uppy.getFile(res.id).response;

      let debug = document.createElement("div");
      let debugContent = document.createElement("span");
      let text = document.createTextNode(response.body.txt);

      debug.classList.add('uppy-Dashboard-Item-debug');
      debug.classList.add('success');
      debugContent.appendChild(text);
      debug.appendChild(debugContent);
      document.getElementById('uppy_'+res.id).lastChild.firstChild.appendChild(debug);
    }

    // add message for failed images
    for (let index = 0; index < result.failed.length; ++index) {
      let res = result.failed[index];
      let response = uppy.getFile(res.id).response;

      let debug = document.createElement("div");
      let debugContent = document.createElement("span");
      let text = document.createTextNode(response.body.txt);

      debug.classList.add('uppy-Dashboard-Item-debug');
      debug.classList.add('error');
      debugContent.appendChild(text);
      debug.appendChild(debugContent);
      document.getElementById('uppy_'+res.id).lastChild.firstChild.appendChild(debug);
    }

    console.log('successful files:', result.successful);
    console.log('failed files:', result.failed);
  });
</script>
