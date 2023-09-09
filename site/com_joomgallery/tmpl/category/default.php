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
use \Joomla\CMS\Session\Session;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.site');

$canEdit    = $this->acl->checkACL('edit', 'com_joomgallery.category', $this->item->id);
$canAdd     = $this->acl->checkACL('add', 'com_joomgallery.category', $this->item->id, true);
$canAddImg  = $this->acl->checkACL('add', 'com_joomgallery.image', $this->item->id, true);
$canDelete  = $this->acl->checkACL('delete', 'com_joomgallery.category', $this->item->id);
$canCheckin = $this->acl->checkACL('editstate', 'com_joomgallery.category', $this->item->id) || $this->item->checked_out == Factory::getUser()->id;
$returnURL  = base64_encode(JoomHelper::getViewRoute('category', $this->item->id, $this->item->parent_id, $this->item->language, $this->getLayout()));
?>

<?php if($this->item->parent_id > 0) : ?>
  <h2><?php echo Text::_('JCATEGORY').': '.$this->escape($this->item->title); ?></h2>
<?php else : ?>
  <h2><?php echo Text::_('COM_JOOMGALLERY') ?></h2>
<?php endif; ?>

<?php if($this->item->parent_id > 0) : ?>
  <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $this->item->parent_id); ?>">
    <?php echo Text::_('Back to: Parent Category'); ?>
  </a>
  </br />
<?php endif; ?>

</br />

<?php if($canEdit || $canAdd || $canDelete): ?>
  <div class="mb-3">
    <?php if($canEdit): ?>
      <a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.edit&id='.$this->item->id.'&return='.$returnURL); ?>">
        <?php echo Text::_("JACTION_EDIT"); ?>
      </a>
    <?php endif; ?>

    <?php /*if($canAdd): ?>
      <a class="btn btn-outline-success" href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.add&id=0&catid='.$this->item->id.'&return='.$returnURL); ?>">
        <?php echo Text::_("JGLOBAL_FIELD_ADD"); ?>
      </a>
    <?php endif; */?>

    <?php if($canDelete) : ?>
      <a class="btn btn-danger" rel="noopener noreferrer" href="#deleteModal" role="button" data-bs-toggle="modal">
        <?php echo Text::_("JACTION_DELETE"); ?>
      </a>
      <?php echo HTMLHelper::_( 'bootstrap.renderModal',
                                'deleteModal',
                                array(
                                    'title'  => Text::_('COM_JOOMGALLERY_COMMON_DELETE_CATEGORY_TIPCAPTION'),
                                    'height' => '50%',
                                    'width'  => '20%',

                                    'modalWidth'  => '50',
                                    'bodyHeight'  => '100',
                                    'footer' => '<button class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button><a href="' . Route::_('index.php?option=com_joomgallery&task=category.remove&id='. $this->item->id.'&return='.$returnURL.'&'.Session::getFormToken().'=1', false, 2) .'" class="btn btn-danger">' . Text::_('COM_JOOMGALLERY_COMMON_DELETE_CATEGORY_TIPCAPTION') .'</a>'
                                ),
                                Text::_('COM_JOOMGALLERY_COMMON_ALERT_SURE_DELETE_SELECTED_ITEM')
                              );
      ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<p><?php echo nl2br($this->item->description); ?></p>

<?php if(count($this->item->children) == 0 && count($this->item->images) == 0) : ?>
  <p><?php echo Text::_('No elements in this category...') ?></p>
<?php endif; ?>

<?php // Subcategories ?>
<?php if(count($this->item->children) > 0) : ?>
  <?php if($this->item->parent_id > 0) : ?>
    <h3><?php echo Text::_('COM_JOOMGALLERY_SUBCATEGORIES') ?></h3>
  <?php else : ?>
    <h3><?php echo Text::_('COM_JOOMGALLERY_CATEGORIES') ?></h3>
  <?php endif; ?>
  <ul>
    <?php foreach($this->item->children as $key => $subcat) : ?>
      <li>
        <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $subcat->id); ?>">
          <?php echo $this->escape($subcat->title); ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php // Images ?>
<?php if(count($this->item->images) > 0) : ?>
  <h3>Images</h3>
  <div class="jg-gallery" itemscope="" itemtype="https://schema.org/ImageGallery">
    <div class="jg-images columns-3">
      <?php foreach($this->item->images as $key => $image) : ?>
        <div class="jg-image">
          <div class="jg-image-thumbnail">
            <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=image&id='.(int) $image->id); ?>">
              <img src="<?php echo JoomHelper::getImg($image, 'thumbnail'); ?>" class="dev" alt="<?php echo $image->imgtitle; ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image" loading="lazy">
            </a>
          </div>
          <div class="jg-image-caption">
            <a class="jg-link" href="<?php echo Route::_('index.php?option=com_joomgallery&view=image&id='.(int) $image->id); ?>">
              <?php echo $this->escape($image->imgtitle); ?>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php /*if($canAddImg) : ?>
  <div class="mb-2">
    <a href="<?php echo Route::_('index.php?option=com_joomgallery&task=image.add&id=0&catid='.$this->item->id.'&return='.$returnURL, false, 0); ?>" class="btn btn-success btn-small">
      <i class="icon-plus"></i>
      <?php echo Text::_('COM_JOOMGALLERY_IMG_UPLOAD_IMAGE'); ?>
    </a>
  </div>
<?php endif; */?>
