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

use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Session\Session;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\User\UserFactoryInterface;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

// image params
$show_title        = $this->params['menu']->get('jg_detail_view_show_title');
$show_category     = $this->params['menu']->get('jg_detail_view_show_category');
$show_description  = $this->params['menu']->get('jg_detail_view_show_description');
$show_imgdate      = $this->params['menu']->get('jg_detail_view_show_imgdate');
$show_imgauthor    = $this->params['menu']->get('jg_detail_view_show_imgauthor');
$show_created_by   = $this->params['menu']->get('jg_detail_view_show_created_by');
$show_votes        = $this->params['menu']->get('jg_detail_view_show_votes');
$show_rating       = $this->params['menu']->get('jg_detail_view_show_rating');
$show_hits         = $this->params['menu']->get('jg_detail_view_show_hits');
$show_downloads    = $this->params['menu']->get('jg_detail_view_show_downloads');
$show_tags         = $this->params['menu']->get('jg_detail_view_show_tags');
$show_metadata     = $this->params['menu']->get('jg_detail_view_show_metadata');

if (!isset($show_title))
{
  $show_title        = $this->params['configs']->get('jg_detail_view_show_title', 0, 'INT');
}
if (!isset($show_category))
{
  $show_category        = $this->params['configs']->get('jg_detail_view_show_category', 0, 'INT');
}
if (!isset($show_description))
{
  $show_description        = $this->params['configs']->get('jg_detail_view_show_description', 0, 'INT');
}
if (!isset($show_imgdate))
{
  $show_imgdate        = $this->params['configs']->get('jg_detail_view_show_imgdate', 0, 'INT');
}
if (!isset($show_imgauthor))
{
  $show_imgauthor        = $this->params['configs']->get('jg_detail_view_show_imgauthor', 0, 'INT');
}
if (!isset($show_created_by))
{
  $show_created_by        = $this->params['configs']->get('jg_detail_view_show_created_by', 0, 'INT');
}
if (!isset($show_votes))
{
  $show_votes        = $this->params['configs']->get('jg_detail_view_show_votes', 0, 'INT');
}
if (!isset($show_rating))
{
  $show_rating        = $this->params['configs']->get('jg_detail_view_show_rating', 0, 'INT');
}
if (!isset($show_hits))
{
  $show_hits        = $this->params['configs']->get('jg_detail_view_show_hits', 0, 'INT');
}
if (!isset($show_downloads))
{
  $show_downloads        = $this->params['configs']->get('jg_detail_view_show_downloads', 0, 'INT');
}
if (!isset($show_tags))
{
  $show_tags        = $this->params['configs']->get('jg_detail_view_show_tags', 0, 'INT');
}
if (!isset($show_metadata))
{
  $show_metadata        = $this->params['configs']->get('jg_detail_view_show_metadata', 0, 'INT');
}

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.site');
$wa->useStyle('com_joomgallery.jg-icon-font');

// Access check
$canEdit    = $this->getAcl()->checkACL('edit', 'com_joomgallery.image', $this->item->id);
$canDelete  = $this->getAcl()->checkACL('delete', 'com_joomgallery.image', $this->item->id);
$canCheckin = $this->getAcl()->checkACL('editstate', 'com_joomgallery.image', $this->item->id) || $this->item->checked_out == Factory::getUser()->id;
$returnURL  = base64_encode(JoomHelper::getViewRoute('image', $this->item->id, $this->item->catid, $this->item->language, $this->getLayout()));

function getExifDataDirect ($exifJsonString='') {

    $exifName2Values = [];
    $fallBack ='not specified';

    $exifName2Values ['brand'] = $fallBack;
    $exifName2Values ['camera'] = $fallBack;
    $exifName2Values ['software'] = $fallBack;
    $exifName2Values ['size'] = $fallBack;
    $exifName2Values ['date_time'] = $fallBack;
    $exifName2Values ['width'] = $fallBack;
    $exifName2Values ['height'] = $fallBack;
    $exifName2Values ['aperture'] = $fallBack;
    $exifName2Values ['shutter_speed'] = $fallBack;
    $exifName2Values ['iso'] = $fallBack;
    $exifName2Values ['focal_length'] = $fallBack;
    $exifName2Values ['lens'] = $fallBack;

    $exifData = json_decode($exifJsonString, true);

    if ( ! empty ($exifData)) {

        if ( ! empty ($exifData["exif"]["IFD0"]["Make"])) {
            $exifName2Values ['brand'] = $exifData["exif"]["IFD0"]["Make"];
        }

        if ( ! empty ($exifData["exif"]["IFD0"]["Model"])) {
            $exifName2Values ['camera'] = $exifData["exif"]["IFD0"]["Model"];
        }

        if ( ! empty ($exifData["exif"]["IFD0"]["Software"])) {
            $exifName2Values ['software'] = $exifData["exif"]["IFD0"]["Software"];
        }
        if ( ! empty ($exifData["exif"]["IFD0"]["FileSize"])) {
            $exifName2Values ['size'] = $exifData["exif"]["FILE"]["FileSize"];
        }
        if ( ! empty ($exifData["exif"]["IFD0"]["DateTime"])) {
            $exifName2Values ['date_time'] = $exifData["exif"]["IFD0"]["DateTime"];
        }
        if ( ! empty ($exifData["exif"]["IFD0"]["Width"])) {
            $exifName2Values ['width'] = $exifData["exif"]["COMPUTED"]["Width"];
        }
        if ( ! empty ($exifData["exif"]["IFD0"]["Height"])) {
            $exifName2Values ['height'] = $exifData["exif"]["COMPUTED"]["Height"];
        }
        if ( ! empty ($exifData["exif"]["IFD0"]["ApertureFNumber"])) {
            $exifName2Values ['aperture'] = $exifData["exif"]["COMPUTED"]["ApertureFNumber"];
        }
        if ( ! empty ($exifData["exif"]["IFD0"]["ExposureTime"])) {
            $exifName2Values ['shutter_speed'] = $exifData["exif"]["EXIF"]["ExposureTime"];
        }
        if ( ! empty ($exifData["exif"]["IFD0"]["ISOSpeedRatings"])) {
            $exifName2Values ['iso'] = $exifData["exif"]["EXIF"]["ISOSpeedRatings"];
        }
        if ( ! empty ($exifData["exif"]["IFD0"]["FocalLength"])) {
            $exifName2Values ['focal_length'] = $exifData["exif"]["EXIF"]["FocalLength"];
        }
        if ( ! empty ($exifData["exif"]["IFD0"]["UndefinedTag:0xA434"])) {
            $exifName2Values ['lens'] = $exifData["exif"]["EXIF"]["UndefinedTag:0xA434"];
        }

    }

    return $exifName2Values;
}

?>

<?php if ($show_title) : ?>
  <h2><?php echo $this->item->title; ?></h2>
<?php endif; ?>

<a class="jg-link btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $this->item->catid); ?>">
  <i class="jg-icon-arrow-left-alt"></i><span><?php echo Text::_('Back to: Category') . ' ' . $this->item->cattitle; ?></span>
</a>

</br />
</br />

<?php if($canEdit || $canCheckin || $canDelete): ?>
  <div class="mb-3">
    <?php if ($canCheckin && $this->item->checked_out > 0): ?>
      <a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&task=image.checkin&id='.$this->item->id.'&return='.$returnURL.'&'.Session::getFormToken().'=1'); ?>">
        <?php echo Text::_("JLIB_HTML_CHECKIN"); ?>
      </a>
    <?php endif; ?>

    <?php if ($canEdit): ?>
      <a class="btn btn-outline-primary<?php echo ($this->item->checked_out > 0) ? ' disabled' : ''; ?>" href="<?php echo Route::_('index.php?option=com_joomgallery&task=image.edit&id='.$this->item->id.'&return='.$returnURL); ?>" <?php echo ($this->item->checked_out > 0) ? 'disabled' : ''; ?>>
        <i class="jg-icon-edit"></i><span><?php echo Text::_("JGLOBAL_EDIT"); ?></span>
      </a>
    <?php endif; ?>

    <?php if ($canDelete) : ?>
      <a class="btn btn-danger<?php echo ($this->item->checked_out > 0) ? ' disabled' : ''; ?>" rel="noopener noreferrer" href="#deleteModal" role="button" data-bs-toggle="modal" <?php echo ($this->item->checked_out > 0) ? 'disabled' : ''; ?>>
        <i class="jg-icon-delete"></i><span><?php echo Text::_("JACTION_DELETE"); ?></span>
      </a>

      <?php echo HTMLHelper::_(
                                'bootstrap.renderModal',
                                'deleteModal',
                                array(
                                    'title'  => Text::_('JACTION_DELETE'),
                                    'height' => '50%',
                                    'width'  => '20%',

                                    'modalWidth'  => '50',
                                    'bodyHeight'  => '100',
                                    'footer' => '<button class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button><a href="' . Route::_('index.php?option=com_joomgallery&task=image.remove&id='.$this->item->id.'&return='.$returnURL.'&'.Session::getFormToken().'=1', false, 2) .'" class="btn btn-danger">' . Text::_('COM_JOOMGALLERY_COMMON_DELETE_IMAGE_TIPCAPTION') .'</a>'
                                ),
                                Text::_('COM_JOOMGALLERY_COMMON_ALERT_SURE_DELETE_SELECTED_ITEM')
                              );
      ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php // Image ?>
<figure class="figure joom-image text-center center">
  <div id="jg-loader"></div>
  <img src="<?php echo JoomHelper::getImg($this->item, 'detail'); ?>" class="figure-img img-fluid rounded" alt="<?php echo $this->item->title; ?>" style="width:auto;" itemprop="image" loading="lazy">
  <?php if ($show_description) : ?>
    <figcaption class="figure-caption"><?php echo $this->item->description; ?></figcaption>
  <?php endif; ?>
</figure>

<?php // Image info and fields ?>
<div class="item_fields">
  <h3><?php echo Text::_('COM_JOOMGALLERY_IMAGE_INFO'); ?></h3>
    <table class="table">
      <tr>
        <?php if ($show_category) : ?>
          <tr>
            <th><?php echo Text::_('JCATEGORY'); ?></th>
            <td>
              <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $this->item->catid); ?>">
                <?php echo $this->escape($this->item->cattitle); ?>
              </a>
            </td>
          </tr>
        <?php endif; ?>
        <?php if ($show_imgdate) : ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_DATE'); ?></th>
            <td><?php echo HTMLHelper::_('date', $this->item->date, Text::_('DATE_FORMAT_LC4')); ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_imgauthor) : ?>
          <tr>
            <th><?php echo Text::_('JAUTHOR'); ?></th>
            <td><?php echo $this->escape($this->item->author); ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_created_by) : ?>
        <?php $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->item->created_by); ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_OWNER'); ?></th>
            <td><?php echo $this->escape($user->name); ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_votes) : ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_VOTES'); ?></th>
            <td><?php echo $this->escape($this->item->votes); ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_rating) : ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_IMAGE_RATING'); ?></th>
            <td><?php echo $this->escape($this->item->rating); ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_hits) : ?>
          <tr>
            <th><?php echo Text::_('JGLOBAL_HITS'); ?></th>
            <td><?php echo (int) $this->item->hits; ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_downloads) : ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_DOWNLOADS'); ?></th>
            <td><?php echo (int) $this->item->downloads; ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_tags) : ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_TAGS'); ?></th>
            <td><?php // todo echo $this->escape($this->item->tags); ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($show_metadata) : ?>
          <tr>
            <th><?php echo Text::_('COM_JOOMGALLERY_IMGMETADATA'); ?></th>
            <td>
              <div class="">
                <?php $exifName2Values = getExifDataDirect ($this->item->imgmetadata); ?>
                <?php foreach ($exifName2Values as $key => $value) : ?>
                  <span class=""><?php echo Text::_($key); ?></span>
                  <span class=""><?php echo $value; ?></span>
                <?php endforeach; ?>
              </div>
            </td>
          </tr>
        <?php endif; ?>
    </table>
</div>

<script>
window.onload = function() {
  const el = document.querySelector('#jg-loader');
  el.classList.add('hidden'); 
};
</script>