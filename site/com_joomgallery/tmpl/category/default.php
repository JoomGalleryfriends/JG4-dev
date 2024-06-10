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

// subcategory params
$subcategory_class          = $this->params['menu']->get('jg_category_view_subcategory_class');
$subcategory_num_columns    = $this->params['menu']->get('jg_category_view_subcategory_num_columns');
$subcategory_image_class    = $this->params['menu']->get('jg_category_view_subcategory_image_class');
$numb_subcategories         = $this->params['menu']->get('jg_category_view_numb_subcategories');
$subcategories_pagination   = $this->params['menu']->get('jg_category_view_subcategories_pagination');
$subcategories_random_image = $this->params['menu']->get('jg_category_view_subcategories_random_image');

// image params
$category_class    = $this->params['menu']->get('jg_category_view_class');
$num_columns       = $this->params['menu']->get('jg_category_view_num_columns');
$caption_align     = $this->params['menu']->get('jg_category_view_caption_align');
$image_class       = $this->params['menu']->get('jg_category_view_image_class');
$justified_height  = $this->params['menu']->get('jg_category_view_justified_height');
$justified_gap     = $this->params['menu']->get('jg_category_view_justified_gap');
$show_title        = $this->params['menu']->get('jg_category_view_images_show_title');
$numb_images       = $this->params['menu']->get('jg_category_view_numb_images');
$use_pagination    = $this->params['menu']->get('jg_category_view_pagination');
$reloaded_images   = $this->params['menu']->get('jg_category_view_number_of_reloaded_images');
$image_link        = $this->params['menu']->get('jg_category_view_image_link');
$title_link        = $this->params['menu']->get('jg_category_view_title_link');
$show_description  = $this->params['menu']->get('jg_category_view_show_description');
$show_imgdate      = $this->params['menu']->get('jg_category_view_show_imgdate');
$show_imgauthor    = $this->params['menu']->get('jg_category_view_show_imgauthor');
$show_tags         = $this->params['menu']->get('jg_category_view_show_tags');

// subcategory params
if (!isset($subcategory_class))
{
  $subcategory_class = $this->params['configs']->get('jg_category_view_subcategory_class', 'masonry', 'STRING');
}
if (!isset($subcategory_num_columns))
{
  $subcategory_num_columns = $this->params['configs']->get('jg_category_view_subcategory_num_columns', 3, 'INT');
}
if (!isset($subcategory_image_class))
{
  $subcategory_image_class = $this->params['configs']->get('jg_category_view_subcategory_image_class', 0, 'INT');
}
if (!isset($numb_subcategories))
{
  $numb_subcategories = $this->params['configs']->get('jg_category_view_numb_subcategories', 12, 'INT');
}
if (!isset($subcategories_pagination))
{
  $subcategories_pagination = $this->params['configs']->get('jg_category_view_subcategories_pagination', 'pagination', 'STRING');
}
if (!isset($subcategories_random_image))
{
  $subcategories_random_image = $this->params['configs']->get('jg_category_view_subcategories_random_image', 0, 'INT');
}

// image params
if (!isset($category_class))
{
  $category_class = $this->params['configs']->get('jg_category_view_class', 'masonry', 'STRING');
}
if (!isset($num_columns))
{
  $num_columns = $this->params['configs']->get('jg_category_view_num_columns', 6, 'INT');
}
if (!isset($caption_align))
{
  $caption_align = $this->params['configs']->get('jg_category_view_caption_align', 'right', 'STRING');
}
if (!isset($image_class))
{
  $image_class = $this->params['configs']->get('jg_category_view_image_class', 0, 'INT');
}
if (!isset($justified_height))
{
  $justified_height = $this->params['configs']->get('jg_category_view_justified_height', 320, 'INT');
}
if (!isset($justified_gap))
{
  $justified_gap = $this->params['configs']->get('jg_category_view_justified_gap', 5, 'INT');
}
if (!isset($show_title))
{
  $show_title = $this->params['configs']->get('jg_category_view_images_show_title', 0, 'INT');
}
if (!isset($numb_images))
{
  $numb_images = $this->params['configs']->get('jg_category_view_numb_images', 16, 'INT');
}
if (!isset($use_pagination))
{
  $use_pagination = $this->params['configs']->get('jg_category_view_pagination', 0, 'INT');
}
if (!isset($reloaded_images))
{
  $reloaded_images = $this->params['configs']->get('jg_category_view_number_of_reloaded_images', 3, 'INT');
}
if (!isset($image_link))
{
  $image_link = $this->params['configs']->get('jg_category_view_image_link', 'defaultview', 'STRING');
}
if (!isset($title_link))
{
  $title_link = $this->params['configs']->get('jg_category_view_title_link', 'defaultview', 'STRING');
}
if (!isset($show_description))
{
  $show_description = $this->params['configs']->get('jg_category_view_show_description', 0, 'INT');
}
if (!isset($show_imgdate))
{
  $show_imgdate = $this->params['configs']->get('jg_category_view_show_imgdate', 0, 'INT');
}
if (!isset($show_imgauthor))
{
  $show_imgauthor = $this->params['configs']->get('jg_category_view_show_imgauthor', 0, 'INT');
}
if (!isset($show_tags))
{
  $show_tags = $this->params['configs']->get('jg_category_view_show_tags', 0, 'INT');
}

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.site');
$wa->useStyle('com_joomgallery.jg-icon-font');

if ($subcategory_class == 'masonry' || $category_class == 'masonry') {
  $wa->useScript('com_joomgallery.masonry');
}

if ($category_class == 'justified') {
  $wa->useScript('com_joomgallery.justified');
  $wa->addInlineStyle('.jg-images[class*=" justified-"] .jg-image-caption-hover { right: ' . $justified_gap . 'px; }');
}

$lightbox = false;
if($image_link == 'lightgallery' || $title_link == 'lightgallery') {
  $lightbox = true;
}

if($lightbox) {
  $wa->useScript('com_joomgallery.lightgallery');
  $wa->useStyle('com_joomgallery.lightgallery-bundle');
}

if (!empty($use_pagination)) {
  // $wa->useScript('com_joomgallery.infinite-scroll');
}

$canEdit    = $this->getAcl()->checkACL('edit', 'com_joomgallery.category', $this->item->id);
$canAdd     = $this->getAcl()->checkACL('add', 'com_joomgallery.category', $this->item->id, true);
$canAddImg  = $this->getAcl()->checkACL('add', 'com_joomgallery.image', $this->item->id, true);
$canDelete  = $this->getAcl()->checkACL('delete', 'com_joomgallery.category', $this->item->id);
$canCheckin = $this->getAcl()->checkACL('editstate', 'com_joomgallery.category', $this->item->id) || $this->item->checked_out == Factory::getUser()->id;
$returnURL  = base64_encode(JoomHelper::getViewRoute('category', $this->item->id, $this->item->parent_id, $this->item->language, $this->getLayout()));

?>

<?php if($this->item->parent_id > 0) : ?>
  <h2><?php echo Text::_('JCATEGORY').': '.$this->escape($this->item->title); ?></h2>
<?php else : ?>
  <h2><?php echo Text::_('COM_JOOMGALLERY') ?></h2>
<?php endif; ?>

<?php if($this->item->parent_id > 0) : ?>
  <a class="jg-link btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $this->item->parent_id); ?>">
    <i class="jg-icon-arrow-left-alt"></i><span><?php echo Text::_('Back to: Parent Category'); ?></span>
  </a>
  </br />
<?php endif; ?>

</br />

<?php if($canEdit || $canAdd || $canDelete): ?>
  <div class="mb-3">
    <?php if($canEdit): ?>
      <a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.edit&id='.$this->item->id.'&return='.$returnURL); ?>">
        <i class="jg-icon-edit"></i><span><?php echo Text::_("JACTION_EDIT"); ?></span>
      </a>
    <?php endif; ?>

    <?php /*if($canAdd): ?>
      <a class="btn btn-outline-success" href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.add&id=0&catid='.$this->item->id.'&return='.$returnURL); ?>">
        <?php echo Text::_("JGLOBAL_FIELD_ADD"); ?>
      </a>
    <?php endif; */?>

    <?php if($canDelete) : ?>
      <a class="btn btn-danger" rel="noopener noreferrer" href="#deleteModal" role="button" data-bs-toggle="modal">
        <i class="jg-icon-delete"></i><span><?php echo Text::_("JACTION_DELETE"); ?></span>
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

<p><?php echo $this->item->description; ?></p>

<?php if(count($this->item->children->items) == 0 && count($this->item->images->items) == 0) : ?>
  <p><?php echo Text::_('No elements in this category...') ?></p>
<?php endif; ?>

<?php // Subcategories ?>
<?php if(count($this->item->children->items) > 0) : ?>
  <?php if($this->item->parent_id > 0) : ?>
    <h3><?php echo Text::_('COM_JOOMGALLERY_SUBCATEGORIES') ?></h3>
  <?php else : ?>
    <h3><?php echo Text::_('COM_JOOMGALLERY_CATEGORIES') ?></h3>
  <?php endif; ?>
  <div class="jg-gallery" itemscope="" itemtype="https://schema.org/ImageGallery">
    <?php if(!($this->item->parent_id > 0)) : ?>
      <div id="jg-loader"></div>
    <?php endif; ?>
    <div class="jg-images <?php echo $subcategory_class; ?>-<?php echo $subcategory_num_columns; ?> jg-subcategories" data-masonry="{ pollDuration: 175 }">
      <?php foreach($this->item->children->items as $key => $subcat) : ?>
        <div class="jg-image">
          <div class="jg-image-thumbnail<?php if($subcategory_image_class && $subcategory_class != 'justified') : ?><?php echo ' boxed'; ?><?php endif; ?>">
            <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $subcat->id); ?>">
              <img src="<?php echo JoomHelper::getImg($subcat->thumbnail, 'thumbnail'); ?>" class="jg-image-thumb" alt="<?php echo $this->escape($subcat->title); ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image"<?php if ( $subcategory_class != 'justified') : ?> loading="lazy"<?php endif; ?>>

              <?php if($subcategory_class == 'justified') : ?>
              <div class="jg-image-caption-hover <?php echo $caption_align; ?>">
              <?php echo $this->escape($subcat->title); ?>
              </div>
              <?php endif; ?>
            </a>
          </div>
          <?php if($subcategory_class != 'justified') : ?>
          <div class="jg-image-caption <?php echo $caption_align; ?>">
            <a class="jg-link" href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $subcat->id); ?>">
              <?php echo $this->escape($subcat->title); ?>
            </a>
          </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php // Category ?>
<?php if(count($this->item->images->items) > 0) : ?>
  <h3>Images</h3>
  <?php if(!empty($this->item->images->filterForm) && $use_pagination == '0') : ?>
    <?php // Show image filters ?>
    <form action="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.$this->item->id.'&Itemid='.$this->menu->id.'&limitstart=0'); ?>" method="post" name="adminForm" id="adminForm">
      <?php
        {
          echo LayoutHelper::render('joomla.searchtools.default', array(
            'view' => $this->item->images, 
            'options' => array('showSelector' => false, 'filterButton' => false, 'showNoResults' => false, 'showSearch' => false, 'barClass' => 'flex-end')
          ));
        }
      ?>
      <input type="hidden" name="task" value=""/>
      <input type="hidden" name="filter_order" value=""/>
      <input type="hidden" name="filter_order_Dir" value=""/>
      <?php echo HTMLHelper::_('form.token'); ?>
    </form>
  <?php endif; ?>
  <div class="jg-gallery<?php echo ' ' . $category_class; ?>" itemscope="" itemtype="https://schema.org/ImageGallery">
    <div id="jg-loader"></div>
    <div id="lightgallery-<?php echo $this->item->id; ?>" class="jg-images <?php echo $category_class; ?>-<?php echo $num_columns; ?> jg-category" data-masonry="{ pollDuration: 175 }">
      <?php foreach($this->item->images->items as $key => $image) : ?>
        <div class="jg-image">
          <div class="jg-image-thumbnail<?php if($image_class && $category_class != 'justified') : ?><?php echo ' boxed'; ?><?php endif; ?>">
            <?php if($category_class != 'justified') : ?>
              <div class="jg-image-caption-hover <?php echo $caption_align; ?>">
            <?php endif; ?>

            <?php if($image_link == 'lightgallery') : ?>
              <a class="item" href="#" data-src="<?php echo JoomHelper::getImg($image, 'detail'); ?>" data-sub-html="#jg-image-caption-<?php echo $image->id; ?>">
                <img src="<?php echo JoomHelper::getImg($image, 'thumbnail'); ?>" class="jg-image-thumb" alt="<?php echo $image->title; ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image"<?php if ( $category_class != 'justified') : ?> loading="lazy"<?php endif; ?>>
                <?php if($show_title && $category_class == 'justified') : ?>
                  <div class="jg-image-caption-hover <?php echo $caption_align; ?>">
                    <?php echo $this->escape($image->title); ?>
                  </div>
                <?php endif; ?>
                <?php if($show_title) : ?>
                  <div id="jg-image-caption-<?php echo $image->id; ?>" style="display: none">
                    <div class="jg-image-caption <?php echo $caption_align; ?>">
                      <?php echo $this->escape($image->title); ?>
                    </div>
                  </div>
                <?php endif; ?>
              </a>
            <?php endif; ?>

            <?php if($image_link == 'defaultview') : ?>
              <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=image&id='.(int) $image->id); ?>">
                <img src="<?php echo JoomHelper::getImg($image, 'thumbnail'); ?>" class="jg-image-thumb" alt="<?php echo $image->title; ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image"<?php if ( $category_class != 'justified') : ?> loading="lazy"<?php endif; ?>>
                <?php if($show_title && $category_class == 'justified') : ?>
                  <div class="jg-image-caption-hover <?php echo $caption_align; ?>">
                    <?php echo $this->escape($image->title); ?>
                  </div>
                <?php endif; ?>
              </a>
            <?php endif; ?>

            <?php if($image_link == 'none') : ?>
              <img src="<?php echo JoomHelper::getImg($image, 'thumbnail'); ?>" class="jg-image-thumb" alt="<?php echo $image->title; ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image"<?php if ( $category_class != 'justified') : ?> loading="lazy"<?php endif; ?>>
            <?php endif; ?>

            <?php if($category_class != 'justified') : ?>
              </div>
            <?php endif; ?>

            <?php if($category_class == 'justified') : ?>
              <div class="jg-image-caption-hover <?php echo $caption_align; ?>">
                <?php echo $this->escape($image->title); ?>
              </div>
            <?php endif; ?>

          </div>

          <?php if($category_class != 'justified') : ?>
          <div class="jg-image-caption <?php echo $caption_align; ?>">
            <?php if ($show_title) : ?>
              <?php if($title_link == 'lightgallery' && $image_link != 'lightgallery') : ?>
                <a class="item" href="#" data-src="<?php echo JoomHelper::getImg($image, 'detail'); ?>" data-sub-html="#jg-image-caption-<?php echo $image->id; ?>">
                  <?php echo $this->escape($image->title); ?>
                </a>
              <?php else : ?>
                <?php if($title_link == 'defaultview') : ?>
                  <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=image&id='.(int) $image->id); ?>">
                    <?php echo $this->escape($image->title); ?>
                  </a>
                <?php else : ?>
                  <?php echo $this->escape($image->title); ?>
                <?php endif; ?>
              <?php endif; ?>
            <?php endif; ?>

            <?php if($show_description) : ?>
              <div><?php echo Text::_('JGLOBAL_DESCRIPTION') . ': ' . $image->description; ?></div>
            <?php endif; ?>
            <?php if($show_imgdate) : ?>
              <div><?php echo Text::_('COM_JOOMGALLERY_DATE') . ': ' . HTMLHelper::_('date', $image->date, Text::_('DATE_FORMAT_LC4')); ?></div>
            <?php endif; ?>
            <?php if($show_imgauthor) : ?>
              <div><?php echo Text::_('JAUTHOR') . ': ' . $this->escape($image->author); ?></div>
            <?php endif; ?>
            <?php if($show_tags) : ?>
              <div><?php echo Text::_('COM_JOOMGALLERY_TAGS') . ': '; ?></div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ( $use_pagination == '0' ) : ?>
    <?php echo $this->item->images->pagination->getListFooter(); ?>
  <?php endif; ?>

  <?php if ( $use_pagination == '1' ) : ?>
    <div class="load-more-container">
      <div class="infinite-scroll"></div>
      <div id="noMore" class="btn btn-outline-primary no-more-images hidden"><?php echo Text::_('COM_JOOMGALLERY_NO_MORE_IMAGES') ?></div>
    </div>
  <?php endif; ?>

  <?php if ( $use_pagination == '2') : ?>
    <div class="load-more-container">
      <div id="loadMore" class="btn btn-outline-primary load-more"><span><?php echo Text::_('COM_JOOMGALLERY_LOAD_MORE') ?></span><i class="jg-icon-expand-more"></i></div>
      <div id="noMore" class="btn btn-outline-primary no-more-images hidden"><?php echo Text::_('COM_JOOMGALLERY_NO_MORE_IMAGES') ?></div>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php /*if($canAddImg) : ?>
  <div class="mb-2">
    <a href="<?php echo Route::_('index.php?option=com_joomgallery&task=image.add&id=0&catid='.$this->item->id.'&return='.$returnURL, false, 0); ?>" class="btn btn-success btn-small">
      <i class="icon-plus"></i>
      <?php echo Text::_('COM_JOOMGALLERY_IMG_UPLOAD_IMAGE'); ?>
    </a>
  </div>
<?php endif; */?>

<?php if ( $lightbox ) : ?>
<script>
const jgallery<?php echo $this->item->id; ?> = lightGallery(document.getElementById('lightgallery-<?php echo $this->item->id; ?>'), {
  selector: '.item',
  speed: 500,
  loop: false,
  download: false,
  mobileSettings: {
    controls: false,
    showCloseIcon: true,
    download: false,
  },
  licenseKey: '1111-1111-111-1111',
});
jgallery<?php echo $this->item->id; ?>.outer.on('click', (e) => {
  const $item = jgallery<?php echo $this->item->id; ?>.outer.find('.lg-current .lg-image');
  if (
    e.target.classList.contains('lg-image') ||
    $item.get().contains(e.target)
  ) {
    jgallery<?php echo $this->item->id; ?>.goToNextSlide();
  }
});
</script>
<?php endif; ?>

<?php if ( $category_class != 'justified') : ?>
<script>
let images = document.getElementsByTagName('img');
for (let image of images) {
  image.addEventListener('load', loadImg);
}
function loadImg () {
  this.closest('.jg-image').classList.add('loaded');
}
</script>
<?php endif; ?>

<?php if(count($this->item->children->items) > 0 && $category_class == 'justified') : ?>
<?php /* zerstört subcategory layout
<script>
window.addEventListener('load', function () {
  const container = document.querySelector('.jg-subcategories');
  const imgs = document.querySelectorAll('.jg-subcategories img');
  const options = {
    idealHeight: <?php echo $justified_height; ?>,
    maxRowImgs: 32,
    rowGap: <?php echo $justified_gap; ?>,
    columnGap: <?php echo $justified_gap; ?>,
  };
  const imgjust = new ImgJust(container, imgs, options);
});
</script>
*/ ?>
<?php endif; ?>

<?php if ( $category_class == 'justified') : ?>
<script>
window.addEventListener('load', function () {
  const container = document.querySelector('.jg-category');
  const imgs = document.querySelectorAll('.jg-category img');
  const options = {
    idealHeight: <?php echo $justified_height; ?>,
    maxRowImgs: 32,
    rowGap: <?php echo $justified_gap; ?>,
    columnGap: <?php echo $justified_gap; ?>,
  };
  const imgjust = new ImgJust(container, imgs, options);
});
</script>
<?php endif; ?>

<?php if ( $use_pagination == '1') : ?>
<script>
const category = document.querySelector('.jg-category');
const items = Array.from(category.querySelectorAll('.jg-image'));

maxImages = <?php echo $num_columns * 2; ?>;
loadImages = <?php echo $num_columns * 3; ?>;
hiddenClass = 'hidden-jg-image';
hiddenImages = Array.from(document.querySelectorAll('.hidden-jg-image'));

items.forEach(function (item, index) {
  if (index > maxImages - 1) {
    item.classList.add(hiddenClass);
  }
});

const observerOptions = {
  root: null,
  rootMargin: '200px',
  threshold: 0
};

function observerCallback(entries, observer) {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      [].forEach.call(document.querySelectorAll('.' + hiddenClass), function (
        item,
        index
      ) {
        if (index < loadImages) {
          item.classList.remove(hiddenClass);
        }
        if (document.querySelectorAll('.' + hiddenClass).length === 0) {
          noMore.classList.remove('hidden');
        }
      });
      // console.log('enter');
    }
  });
}

const fadeElms = document.querySelectorAll('.infinite-scroll');
const observer = new IntersectionObserver(observerCallback, observerOptions);
fadeElms.forEach(el => observer.observe(el));

</script>
<?php endif; ?>

<?php if ( $use_pagination == '2') : ?>
<script>
const category = document.querySelector('.jg-category');
const items = Array.from(category.querySelectorAll('.jg-image'));
const loadMore = document.getElementById('loadMore');

maxImages = <?php echo $num_columns * 2; ?>;
loadImages = <?php echo $reloaded_images; ?>;
hiddenClass = 'hidden-jg-image';
hiddenImages = Array.from(document.querySelectorAll('.hidden-jg-image'));

items.forEach(function (item, index) {
  if (index > maxImages - 1) {
    item.classList.add(hiddenClass);
  }
});

loadMore.addEventListener('click', function () {
  [].forEach.call(document.querySelectorAll('.' + hiddenClass), function (
    item,
    index
  ) {
    if (index < loadImages) {
      item.classList.remove(hiddenClass);
    }
    if (document.querySelectorAll('.' + hiddenClass).length === 0) {
      loadMore.style.display = 'none';
      noMore.classList.remove('hidden');
    }
  });
});
</script>
<?php endif; ?>

<script>
window.onload = function() {
  const el = document.querySelector('#jg-loader');
  el.classList.add('hidden');
};
</script>