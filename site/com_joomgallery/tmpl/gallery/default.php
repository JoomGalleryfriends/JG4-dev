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
$wa->useScript('com_joomgallery.infinite-scroll');
$wa->useScript('com_joomgallery.advanced-filter-system');
$wa->useStyle('com_joomgallery.site');
$wa->useStyle('com_joomgallery.jg-icon-font');
?>

<?php // Gallery ?>
<?php if(count($this->item->images->items) > 0) : ?>

    <!-- Counter -->
    <div class="filter-counter"></div>

    <!-- Search -->
    <input type="text" class="filter-search" placeholder="Search...">

    <!-- Hits Filter -->
    <div id="hits-range"></div>


  <div class="jg-gallery<?php echo ' ' . $category_class; ?>" itemscope="" itemtype="https://schema.org/ImageGallery">

  <?php if ($this->params['menu']->get('show_page_heading')) : ?>
    <div class="page-header">
      <h1> <?php echo $this->escape($this->params['menu']->get('page_heading')); ?> </h1>
    </div>
  <?php endif; ?>

    <div id="lightgallery-<?php echo $this->item->id; ?>" class="jg-images <?php echo $category_class; ?>-<?php echo $num_columns; ?> jg-category" data-masonry="{ pollDuration: 175 }">


  <?php foreach ($this->item->images->items as $key => $item) : ?>

        <div class="jg-image" data-hits="<?php echo $this->escape($item->hits); ?>">
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

    <?php echo $this->item->images->pagination->getListFooter(); ?>

    <div class="load-more-container">
      <div id="noMore" class="btn btn-outline-primary no-more-images hidden"><?php echo Text::_('COM_JOOMGALLERY_NO_MORE_IMAGES') ?></div>
    </div>

</div>

<?php endif; ?>

<?php // fadein images; ?>
<script>
let images = document.getElementsByClassName('jg-image-thumb');
for (let image of images) {
  image.addEventListener('load', loadImg);
}
function loadImg () {
  this.closest('.jg-image').classList.add('loaded');
}
</script>

<?php // infinite scroll; ?>
<script>
const infiniteScroll = new InfiniteScroll.default({
  element       : '.jg-images',
  next          : '.page-link.next',
  item          : '.jg-image',
  disabledClass : 'disabled',
  hiddenClass   : 'hidden',
  responseType  : 'text/html',
  requestMethod: 'GET',
  viewportTriggerPoint: window.innerHeight - 100,
  debounceTime: 500,
  onComplete(container, html) {
    console.log('scroll');
    // Here you query the link to the next page
    const next = html.querySelector('a.page-link.next');
    console.log(next);
    // If the link does not exist
    if (!next) {
        // Here you show your "No more posts are available" message 
        document.querySelector('.no-more-images').classList.remove('hidden');
        console.log('no more');
    }
  }
});
document.addEventListener('click', function (event) {
    if (!event.target.matches('.loadMore')) return;
    infiniteScroll.loadMore();
}, false);
</script>

<?php // advanced filter system; ?>
    <script>

        const afs = new AFS({
            containerSelector: '.jg-images',
            itemSelector: '.jg-image',
            searchInputSelector: '.filter-search',
            // filterButtonSelector: '.btn-filter',
            // sortButtonSelector: '.btn-sort',
            counterSelector: '.filter-counter',
            debug: true,
            responsive: true,
            preserveState: true,
            animation: {
                type: 'fade',
                duration: 300
            },
            pagination: {
                enabled: false,
                itemsPerPage: 10
            }
        });

        // Add range filter
        afs.rangeFilter.addRangeSlider({
            key: 'hits',
            container: document.querySelector('#hits-range'),
            min: 0,
            max: 1000,
            step: 10
        });
    </script>