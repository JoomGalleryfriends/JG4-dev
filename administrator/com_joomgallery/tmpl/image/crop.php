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

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate')
    ->useStyle('com_joomgallery.admin')
    ->useScript('com_joomgallery.cropper')
    ->useStyle('com_joomgallery.cropper');
HTMLHelper::_('bootstrap.tooltip');

$app = Factory::getApplication();

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
?>

<div class="row">
    <div class="col-12 col-lg-3">
        <fieldset id="fieldset-crop" class="options-form">
            <legend><?php echo "Crop"; ?></legend>
            <div>
                <?php echo $this->form->renderField('crop_xaxis'); ?>
                <?php echo $this->form->renderField('crop_yaxis'); ?>
                <?php echo $this->form->renderField('crop_width'); ?>
                <?php echo $this->form->renderField('crop_height'); ?>
            </div>
        </fieldset>
    </div>
    <div class="col-12 col-lg-9">
        <div>
            <img src="<?php echo JoomHelper::getImg($this->item, 'original'); ?>" id="cropImage" style="max-height:658px;">
        </div>
       <!-- <a class="btn btn-outline-primary" style="cursor:pointer;" href="<?php /*Route::_('index.php?option=com_joomgallery&view=image&layout=crop&id='.(int) $this->item->id) */?>">Crop Image</a>
        <button id="cropImageBtn">Cropimage</button>
        <img src="" id="output">-->
    </div>
</div>
<script>
    const image = document.getElementById("cropImage");
    const cropper = new Cropper(image,{
        viewMode: 1,
        responsive: true,
        restore: true,
        autoCrop: true,
        movable: false,
        zoomable: false,
        rotatable: false,
        autoCropArea: 1,
        crop(event) {
            document.getElementById("jform_crop_xaxis").value = event.detail.x
            document.getElementById("jform_crop_yaxis").value = event.detail.y
            document.getElementById("jform_crop_width").value = event.detail.width
            document.getElementById("jform_crop_height").value = event.detail.height
            console.log("--crop data--")
            console.log(event.detail.x);
            console.log(event.detail.y);
            console.log(event.detail.width);
            console.log(event.detail.height);
        },
    });

    var data = cropper.getData();
    console.log(data)

    $('#cropImage').change(function(event){
        var data = cropper.getData();
        console.log(data)
        $("#cropdata").val(data)
    });

    // document.getElementById("cropImageBtn").addEventListener('click',function(){
    //     var cropImage = cropper.getCroppedCanvas().toDataURL("image/png");
    //     document.getElementById("cropoutput").src = cropImage
    // })
</script>