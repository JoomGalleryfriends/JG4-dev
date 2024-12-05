<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                              **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
******************************************************************************************/

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

// image params
$category_class   = $this->params['configs']->get('jg_category_view_class', 'column', 'STRING');
$num_columns      = $this->params['configs']->get('jg_category_view_num_columns', 3, 'INT');

$image_link       = $this->params['configs']->get('jg_category_view_image_link', 'defaultview', 'STRING');
$title_link       = $this->params['configs']->get('jg_category_view_title_link', 'defaultview', 'STRING');

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.site');
$wa->useStyle('com_joomgallery.jg-icon-font');
?>

<?php // Gallery ?>
<?php if(count($this->item->images->items) > 0) : ?>
  <div class="jg-gallery<?php echo ' ' . $category_class; ?>" itemscope="" itemtype="https://schema.org/ImageGallery">

  <?php if ($this->params['menu']->get('show_page_heading')) : ?>
    <div class="page-header">
      <h1> <?php echo $this->escape($this->params['menu']->get('page_heading')); ?> </h1>
    </div>
  <?php endif; ?>

    <div id="lightgallery-<?php echo $this->item->id; ?>" class="jg-images <?php echo $category_class; ?>-<?php echo $num_columns; ?> jg-category" data-masonry="{ pollDuration: 175 }">


  <?php foreach ($this->item->images->items as $key => $item) : ?>

        <div class="jg-image">
          <div class="jg-image-thumbnail boxed">

            <?php // if($image_link == 'defaultview') : ?>
              <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=image&id='.(int) $item->id); ?>">
                <img src="<?php echo JoomHelper::getImg($item, 'thumbnail'); ?>" class="jg-image-thumb" alt="<?php echo $item->title; ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image"<?php if ( $category_class != 'justified') : ?> loading="lazy"<?php endif; ?>>
              </a>
                  <div class="jg-image-caption <?php echo $caption_align; ?>">
                  <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=image&id='.(int) $item->id); ?>">
                    <?php echo $this->escape($item->title); ?>
                  </a>
                  </div>

                  <div><?php echo $this->escape($item->cattitle); ?></div>

            <?php // endif; ?>

       </div>
       </div>


  <?php endforeach; ?>

    </div>

</div>

<?php endif; ?>

<script>
let images = document.getElementsByClassName('jg-image-thumb');
for (let image of images) {
  image.addEventListener('load', loadImg);
}
function loadImg () {
  this.closest('.jg-image').classList.add('loaded');
}
</script>