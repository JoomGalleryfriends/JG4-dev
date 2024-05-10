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

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

HTMLHelper::_('bootstrap.framework');

HTMLHelper::_('stylesheet', 'com_joomgallery/admin.css', array('version' => 'auto', 'relative' => true));

?>
<div class="d-flex flex-row">
  <div class="flex-fill">
    <div id="j-main-container" class="j-main-container">
      <div class"jg-control-head">
        <?php // echo 'Kopfbereich für wichtige Meldungen.'; ?>
      </div>
      <div class="card jg-controlpanel-content">
        <div>
          <?php echo Text::_('COM_JOOMGALLERY_HLPINFO'); ?>
        </div>
        <div>
          <?php echo HTMLHelper::_('image', 'com_joomgallery/watermark.png', Text::_('COM_JOOMGALLERY_LOGO'), ['class' => 'jg-controlpanel-logo', 'style' => 'padding: 1.2rem'], true); ?>
        </div>
        <div>
          <?php echo Text::_('COM_JOOMGALLERY_HLPINFO_DONATIONS_LONG'); ?>
        </div>
        <div>
          <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LVQBAFEZHPL2J" title="<?php echo Text::_('COM_JOOMGALLERY_HLPINFO_DONATIONS_PAYPAL'); ?>" target="_blank">
            <?php echo Text::_('COM_JOOMGALLERY_HLPINFO_DONATIONS_PAYPAL'); ?> <span class="icon-heart"></span></a>
        </div>
        <div>
          <?php echo Text::_('COM_JOOMGALLERY_HLPINFO_SPONSORS'); ?><a href="mailto:team@joomgalleryfriends.net">team@joomgalleryfriends.net</a>
        </div>
        <div>
          <?php echo Text::_('COM_JOOMGALLERY_HLPINFO_JED'); ?>
        </div>
      </div>
      <hr>

      <?php // Render admin modules in position joom_cpanel
      foreach ($this->modules as $module)
      {
        echo ModuleHelper::renderModule($module, array('style' => 'well'));
      }
      ?>

      <div class="card-columns">
        <div class="card">
          <?php
          // Display small gallery statistic (categories, images)
          DisplayGalleryStatistic($this->statisticdata);
          ?>
        </div>
        <div class="card">
          <?php
          // Display JoomGallery info data
          DisplayGalleryInfo($this->galleryinfodata);
          ?>
        </div>
      </div>

      <?php // Display available extensions 
      DisplayAvailableExtensions($this->galleryavailableextensionsdata); ?>
      <hr>

      <?php // Display installed extensions 
      DisplayInstalledExtensions($this->galleryinstalledextensionsdata); ?>
      <hr>

      <?php // Display system settings
      $title    = Text::_('PHP system settings');
      $settings = $this->php_settings;
      $id      = '200';

      DisplaySystemSettings($title, $settings); ?>
      <hr>

      <?php // Display Footer ?>
      <div class"jg-control-footer">
        <?php
        // Display copyright ?>
        <div class="row">
          <div class="col-md-12 jg-copyright">
            <?php echo HTMLHelper::_('image', 'com_joomgallery/logo.png', Text::_('COM_JOOMGALLERY_LOGO'), ['class' => 'joom-logo-small', 'style' => 'max-width: 40px'], true); ?>
            <p>
              <?php echo Text::_('COM_JOOMGALLERY'); ?> <?php echo $this->galleryinfodata['version']; ?> by <a href="https://www.en.joomgalleryfriends.net" target="_blank">JoomGallery::ProjectTeam</a>
              <br /><span>Copyright &copy; 2008-<?php echo date("Y"); ?>. All rights reserved.</span>
            </p>
          </div>
        </div>
      </div>
    </div><!-- /j-main-container -->
  </div><!-- /flex-fill -->
</div><!-- /d-flex flex-row -->
<?php 

/**
 * Display a small gallery statistic
 *
 * @param   array  $statisticdata  Array with hold the statistic data
 *
 * @since 4.0.0
 */
function DisplayGalleryStatistic($statisticdata)
{
  ?>
  <div class="table-responsive">
    <h3 class="card-header"><?php echo Text::_('COM_JOOMGALLERY_CONTROL_STATISTIC'); ?></h3>
    <table class="table table-striped">
      <thead>
        <tr>
          <th scope="col" class="w-10">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_CONTENT'); ?>
          </th>
          <td class="w-1 text-center">
            <?php echo Text::_('JUNPUBLISHED'); ?><br />
            <span class="icon-delete text-center" title="<?php echo Text::_('JUNPUBLISHED'); ?>" aria-label="unpublished" data-bs-original-title="unpublished"></span>
          </td>
          <td class="w-1 text-center">
            <?php echo Text::_('JPUBLISHED'); ?><br />
            <span class="icon-publish text-center" title="<?php echo Text::_('JPUBLISHED'); ?>"></span><br />
          </td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <th scope="col" class="w-10 d-md-table-cell">
            <?php echo Text::_('JCATEGORIES'); ?>
          </th>
          <td class="d-md-table-cell text-center">
          <?php if($statisticdata['unpublishedcategories'] > 0) : ?>
            <a href="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&view=categories&filter[published]=0'); ?>">
              <span class="badge bg-info"><?php echo (int) $statisticdata['unpublishedcategories']; ?></span>
            </a>
          <?php else : ?>
            <span class="badge bg-info">0</span>
          <?php endif; ?>
          </td>
          <td class="d-md-table-cell text-center">
          <?php if($statisticdata['publishedcategories'] > 0) : ?>
            <a href="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&view=categories&filter[published]=1'); ?>">
              <span class="badge bg-info"><?php echo (int) $statisticdata['publishedcategories']; ?></span>
            </a>
          <?php else : ?>
            <span class="badge bg-info">0</span>
          <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th scope="col" class="w-10 d-md-table-cell">
            <?php echo Text::_('COM_JOOMGALLERY_IMAGES'); ?>
          </th>
          <td class="d-md-table-cell text-center">
          <?php if($statisticdata['unpublishedimages'] > 0) : ?>
            <a href="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&amp;view=images&amp;filter[published]=2'); ?>">
              <span class="badge bg-info"><?php echo (int) $statisticdata['unpublishedimages']; ?></span>
            </a>
          <?php else : ?>
            <span class="badge bg-info">0</span>
          <?php endif; ?>
          </td>
          <td class="d-md-table-cell text-center">
          <?php if($statisticdata['publishedimages'] > 0) : ?>
            <a href="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&amp;view=images&amp;filter[published]=1'); ?>">
              <span class="badge bg-info"><?php echo (int) $statisticdata['publishedimages']; ?></span>
            </a>
          <?php else : ?>
            <span class="badge bg-info">0</span>
          <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th scope="col" class="w-10 d-md-table-cell">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_IMAGES_UNAPPROVED'); ?>
          </th>
          <td class="d-md-table-cell text-center">
          <?php if($statisticdata['unapprovedimages'] > 0) : ?>
            <a href="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&amp;view=images&amp;filter[published]=4'); ?>">
              <span class="badge bg-info"><?php echo (int) $statisticdata['unapprovedimages']; ?></span>
            </a>
          <?php else : ?>
            <span class="badge bg-info">0</span>
          <?php endif; ?>
          </td>
          <td class="d-md-table-cell">
          </td>
        </tr>
        <tr>
          <th scope="col" class="w-100 d-md-table-cell">
            <a href="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&amp;view=images&amp;list[fullordering]=a.id DESC'); ?>"> <?php echo Text::_('COM_JOOMGALLERY_CONTROL_NEWEST_IMAGES'); ?></a>
          </th>
          <td class="d-md-table-cell">
          </td>
          <td class="d-md-table-cell">
          </td>
        </tr>
        <tr>
          <th scope="col" class="w-100 d-md-table-cell">
            <a href="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&amp;view=images&amp;list[fullordering]=a.hits DESC'); ?>"> <?php echo Text::_('COM_JOOMGALLERY_CONTROL_MOST_VIEWED_IMAGES'); ?></a>
          </th>
          <td class="d-md-table-cell">
          </td>
          <td class="d-md-table-cell">
          </td>
        </tr>
        <tr>
          <th scope="col" class="w-100 d-md-table-cell">
            <a href="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&amp;view=images&amp;list[fullordering]=a.downloads DESC'); ?>"> <?php echo Text::_('COM_JOOMGALLERY_CONTROL_MOST_DOWNLOADED_IMAGES'); ?></a>
          </th>
          <td class="d-md-table-cell">
          </td>
          <td class="d-md-table-cell">
          </td>
        </tr>

      </tbody>
    </table>
  </div>

<?php return;

}

/**
 * Display infos about the gallery
 *
 * @param   array  $manifest  Array with hold the info data
 *
 * @since 4.0.0
 */
function DisplayGalleryInfo($manifest)
{
  ?>
  <h3 class="card-header"><?php echo Text::_('INfo'); ?></h3>
  <div class="table-responsive">
    <table class="table w-auto">
      <tbody>
        <tr>
          <td scope="col" class="w-40">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_VERSION'); ?>
          </td>
          <td class="w-60">
            <b><?php echo $manifest['version']; ?></b>
          </td>
        </tr>
        <tr>
          <td scope="col" class="w-40">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_CREATION_DATE'); ?>
          </td>
          <td class="w-60">
            <?php echo $manifest['creationDate']; ?>
          </td>
        </tr>
        <tr>
          <td scope="col" class="w-40">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_LICENSE'); ?>
          </td>
          <td class="w-60">
            <a href="<?php echo 'https://www.gnu.org/licenses/gpl-3.0.html'; ?>" target="_blank">GNU General Public License v3</a>
          </td>
        </tr>
        <tr>
          <td scope="col" class="w-40">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_WEBSITE'); ?>
          </td>
          <td class="w-60">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_WEBSITE_LINK'); ?>
          </td>
        </tr>
        <tr>
          <td scope="col" class="w-40">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_WEBSITE_SUPPORT'); ?>
          </td>
          <td class="w-60">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_WEBSITE_SUPPORT_LINK'); ?>
          </td>
        </tr>
        <tr>
          <td scope="col" class="w-40">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_WEBSITE_DOCUMENTATION'); ?>
          </td>
          <td class="w-60">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_WEBSITE_DOCUMENTATION_LINK'); ?>
          </td>
        </tr>
        <tr>
          <td scope="col" class="w-40">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_GITHUB'); ?>
          </td>
          <td class="w-60">
            <a href="<?php echo 'https://github.com/joomgalleryfriends'; ?>" target="_blank">github.com/joomgalleryfriends</a>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

<?php return;

}

/**
 * Display most viewed images as collapsed
 *
 * @param   array   $header      Array with column header, $header[0]=columheader, $header[1]=first column...
 * @param   array   $data        Array with hold the Images data, $data[0]=image, $data[1]=title, $data[2]=value, $data[3]=imgid
 * @param   int     $id          Unique id
   *
 * @since 4.0.0
 */
function DisplayMostViewedImages($header, $data, $id)
{
  $itemId = $id . '-item'; ?>

  <div class="accordion" id="<?php echo $id; ?>">
    <div class="accordion-item">
      <h2 class="accordion-header" id="<?php echo $itemId; ?>Header">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
          data-bs-target="#<?php echo $itemId; ?>" aria-expanded="false" aria-controls="<?php echo $itemId; ?>">
          <?php echo $header[0]; ?>
        </button>
      </h2>
      <div id="<?php echo $itemId; ?>" class="accordion-collapse collapse"
        aria-labelledby="<?php echo $itemId; ?>Header" data-bs-parent="#<?php echo $id; ?>">
        <div class="accordion-body">
          <table class="table table-striped">
            <thead>
              <tr>
                <td class="w-20">
                  <?php echo $header[1]; ?>
                </td>
                <td class="w-40">
                  <?php echo $header[2]; ?>
                </td>
                <td class="w-20">
                  <?php echo $header[3]; ?>
                </td>
                <td class="w-20">
                  <?php echo $header[4]; ?>
                </td>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($data as $value) { ?>
                <tr>
                <td class="d-md-table-cell small">
                  <img class="jg_minithumb" src="<?php echo JoomHelper::getImg($value[2], 'thumbnail'); ?>" alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
                </td>
                  <td class="d-md-table-cell">
                    <?php $ImgUrl   = Route::_('index.php?option=com_joomgallery&task=image.edit&id='.(int) $value[2]);
                      $EditImgTxt = Text::_('COM_JOOMGALLERY_IMAGE_EDIT');
                    ?>
                    <a href="<?php echo $ImgUrl; ?>" title="<?php echo $EditImgTxt; ?>">
                      <?php echo $value[0]; ?>
                    </a>
                  </td>
                  <td class="d-md-table-cell">
                    <?php echo $value[1]; ?>
                  </td>
                  <td class="d-md-table-cell">
                    <?php echo $value[2]; ?>
                  </td>
                </tr>
                <?php } ?>
            </tbody>
          </table>
        </div><!--/accordion-body-->
      </div><!--/accordion-collapse-->
    </div><!--/accordion-item-->
  </div><!--/accordion -->

  <?php return;

}

/**
 * Display newest images as collapsed
 *
 * @param   array   $header      Array with column header, $header[0]=columheader, $header[1]=first column...
 * @param   array   $data        Array with hold the Images data, $data[0]=image, $data[1]=title, $data[2]=value, $data[3]=imgid
 * @param   int     $id          Unique id
 *
 * @since 4.0.0
 */
function DisplayNewestImages($header, $data, $id)
{
  $itemId = $id . '-item'; ?>

  <div class="accordion" id="<?php echo $id; ?>">
    <div class="accordion-item">
      <h2 class="accordion-header" id="<?php echo $itemId; ?>Header">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
          data-bs-target="#<?php echo $itemId; ?>" aria-expanded="false" aria-controls="<?php echo $itemId; ?>">
          <?php echo $header[0]; ?>
        </button>
      </h2>
      <div id="<?php echo $itemId; ?>" class="accordion-collapse collapse"
        aria-labelledby="<?php echo $itemId; ?>Header" data-bs-parent="#<?php echo $id; ?>">
        <div class="accordion-body">
          <table class="table table-striped">
            <thead>
              <tr>
                <td class="w-20">
                  <?php echo $header[1]; ?>
                </td>
                <td class="w-30">
                  <?php echo $header[2]; ?>
                </td>
                <td class="w-30">
                  <?php echo $header[3]; ?>
                </td>
                <td class="w-20">
                  <?php echo $header[4]; ?>
                </td>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($data as $value) { ?>
                <tr>
                <td class="d-md-table-cell small">
                  <img class="jg_minithumb" src="<?php echo JoomHelper::getImg($value[2], 'thumbnail'); ?>" alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
                </td>
                  <td class="d-md-table-cell">
                    <?php $ImgUrl   = Route::_('index.php?option=com_joomgallery&task=image.edit&id='.(int) $value[2]);
                      $EditImgTxt = Text::_('COM_JOOMGALLERY_IMAGE_EDIT');
                    ?>
                    <a href="<?php echo $ImgUrl; ?>" title="<?php echo $EditImgTxt; ?>">
                      <?php echo $value[0]; ?>
                    </a>
                  </td>
                  <td class="d-md-table-cell">
                    <?php $date = Factory::getDate($value[1], 'UTC'); ?>
                    <?php $date->setTimezone(new \DateTimeZone(Factory::getApplication()->get('offset'))); ?>
                    <?php echo $date->format('Y-m-d H:i:s', true, false); ?>
                  </td>
                  <td class="d-md-table-cell">
                    <?php echo $value[2]; ?>
                  </td>
                </tr>
                <?php } ?>
            </tbody>
          </table>
        </div><!--/accordion-body-->
      </div><!--/accordion-collapse-->
    </div><!--/accordion-item-->
  </div><!--/accordion -->

  <?php return;

}

/**
 * Display best rated images as collapsed
 *
 * @param   array   $header      Array with column header, $header[0]=columheader, $header[1]=first column...
 * @param   array   $data        Array with hold the Images data, $data[0]=image, $data[1]=title, $data[2]=value, $data[3]=imgid
 * @param   int     $id          Unique id
 *
 * @since 4.0.0
 */
function DisplayBestRatedImages($header, $data, $id)
{
  $itemId = $id . '-item'; ?>

  <div class="accordion" id="<?php echo $id; ?>">
    <div class="accordion-item">
      <h2 class="accordion-header" id="<?php echo $itemId; ?>Header">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
          data-bs-target="#<?php echo $itemId; ?>" aria-expanded="false" aria-controls="<?php echo $itemId; ?>">
          <?php echo $header[0]; ?>
        </button>
      </h2>
      <div id="<?php echo $itemId; ?>" class="accordion-collapse collapse"
        aria-labelledby="<?php echo $itemId; ?>Header" data-bs-parent="#<?php echo $id; ?>">
        <div class="accordion-body">
          <table class="table table-striped">
            <thead>
              <tr>
                <td class="w-20">
                  <?php echo $header[1]; ?>
                </td>
                <td class="w-40">
                  <?php echo $header[2]; ?>
                </td>
                <td class="w-20">
                  <?php echo $header[3]; ?>
                </td>
                <td class="w-20">
                  <?php echo $header[4]; ?>
                </td>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($data as $value) { ?>
                <tr>
                <td class="d-md-table-cell small">
                  <img class="jg_minithumb" src="<?php echo JoomHelper::getImg($value[2], 'thumbnail'); ?>" alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
                </td>
                  <td class="d-md-table-cell">
                    <?php $ImgUrl   = Route::_('index.php?option=com_joomgallery&task=image.edit&id='.(int) $value[2]);
                      $EditImgTxt = Text::_('COM_JOOMGALLERY_IMAGE_EDIT');
                    ?>
                    <a href="<?php echo $ImgUrl; ?>" title="<?php echo $EditImgTxt; ?>">
                      <?php echo $value[0]; ?>
                    </a>
                  </td>
                  <td class="d-md-table-cell">
                    <?php echo round(floatval($value[1]), 2); ?>
                  </td>
                  <td class="d-md-table-cell">
                    <?php echo $value[2]; ?>
                  </td>
                </tr>
                <?php } ?>
            </tbody>
          </table>
        </div><!--/accordion-body-->
      </div><!--/accordion-collapse-->
    </div><!--/accordion-item-->
  </div><!--/accordion -->

  <?php return;

}

/**
 * Display most downloaded Images as collapsed
 *
 * @param   array   $header      Array with column header, $header[0]=columheader, $header[1]=first column...
 * @param   array   $data        Array with hold the Images data, $data[0]=image, $data[1]=title, $data[2]=value, $data[3]=imgid
 * @param   int     $id          Unique id
 *
 * @since 4.0.0
 */
function DisplayMostDownloadedImages($header, $data, $id)
{
  $itemId = $id . '-item'; ?>

  <div class="accordion" id="<?php echo $id; ?>">
    <div class="accordion-item">
      <h2 class="accordion-header" id="<?php echo $itemId; ?>Header">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
          data-bs-target="#<?php echo $itemId; ?>" aria-expanded="false" aria-controls="<?php echo $itemId; ?>">
          <?php echo $header[0]; ?>
        </button>
      </h2>
      <div id="<?php echo $itemId; ?>" class="accordion-collapse collapse"
        aria-labelledby="<?php echo $itemId; ?>Header" data-bs-parent="#<?php echo $id; ?>">
        <div class="accordion-body">
          <table class="table table-striped">
            <thead>
              <tr>
                <td class="w-20">
                  <?php echo $header[1]; ?>
                </td>
                <td class="w-40">
                  <?php echo $header[2]; ?>
                </td>
                <td class="w-20">
                  <?php echo $header[3]; ?>
                </td>
                <td class="w-20">
                  <?php echo $header[4]; ?>
                </td>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($data as $value) { ?>
                <tr>
                <td class="d-md-table-cell small">
                  <img class="jg_minithumb" src="<?php echo JoomHelper::getImg($value[2], 'thumbnail'); ?>" alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
                </td>
                  <td class="d-md-table-cell">
                    <?php $ImgUrl   = Route::_('index.php?option=com_joomgallery&task=image.edit&id='.(int) $value[2]);
                      $EditImgTxt = Text::_('COM_JOOMGALLERY_IMAGE_EDIT');
                    ?>
                    <a href="<?php echo $ImgUrl; ?>" title="<?php echo $EditImgTxt; ?>">
                      <?php echo $value[0]; ?>
                    </a>
                  </td>
                  <td class="d-md-table-cell">
                    <?php echo $value[1]; ?>
                  </td>
                  <td class="d-md-table-cell">
                    <?php echo $value[2]; ?>
                  </td>
                </tr>
                <?php } ?>
            </tbody>
          </table>
        </div><!--/accordion-body-->
      </div><!--/accordion-collapse-->
    </div><!--/accordion-item-->
  </div><!--/accordion -->

  <?php return;

}

/**
 * Display available extensions as collapsed
 *
 * @param   array  $manifest  Array with hold the extensions data, $manifest[0}=extension id, $manifest[1]=state, $manifest[2]=array of data
 *
 * @since 4.0.0
 */
function DisplayAvailableExtensions($manifest)
{

  $id     = 'availableextensions-100';
  $itemId = $id . '-item'; ?>

  <div class="card">
    <div class="accordion" id="<?php echo $id; ?>">
      <div class="accordion-item">
        <h2 class="accordion-header" id="<?php echo $itemId; ?>Header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
            data-bs-target="#<?php echo $itemId; ?>" aria-expanded="false" aria-controls="<?php echo $itemId; ?>">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_EXTENSIONS'); ?>
          </button>
        </h2>
        <div id="<?php echo $itemId; ?>" class="accordion-collapse collapse"
          aria-labelledby="<?php echo $itemId; ?>Header" data-bs-parent="#<?php echo $id; ?>">
          <div class="accordion-body">
            <table class="table table-striped">
              <thead>
                <tr>
                  <td class="w-25">
                    <?php echo Text::_('JGLOBAL_TITLE'); ?>
                  </td>
                  <td class="w-10">
                    <?php echo Text::_('JVERSION'); ?>
                  </td>
                  <td class="w-50">
                    <?php echo Text::_('COM_JOOMGALLERY_FILE_DESCRIPTION_HINT'); ?>
                  </td>
                  <td class="w-15">
                    <?php echo Text::_('COM_JOOMGALLERY_DOWNLOAD'); ?>
                  </td>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($manifest as $name => $extension) {
                  // $value = json_decode($extension, true); ?>
                  <tr>
                    <td class="d-md-table-cell">
                      <?php echo $name; ?>
                    </td>
                    <td class="d-md-table-cell">
                      <?php echo $extension['version']; ?>
                    </td>
                    <td class="d-md-table-cell">
                      <?php echo $extension['description']; ?>
                    </td>
                    <td class="d-md-table-cell small">
                        <a href="<?php echo $extension['downloadlink'];?>" title="<?php echo Text::_($extension['downloadlink']); ?>" target="_blank">
                          <?php echo Text::_('COM_JOOMGALLERY_DOWNLOAD'); ?></a>
                    </td>
                  </tr>
                  <?php } ?>
              </tbody>
            </table>
          </div>
        </div><!--/accordion-collapse-->
      </div><!--/accordion-item-->
    </div><!--/accordion -->
  </div><!--/card -->

  <?php return;
}

/**
 * Display installed extensions as collapsed
 *
 * @param   array  $manifest  Array with hold the extensions data, $manifest[0}=extension id, $manifest[1]=state, $manifest[2]=array of data
 *
 * @since 4.0.0
 */
function DisplayInstalledExtensions($manifest)
{

  $id     = 'installedextensions-100';
  $itemId = $id . '-item'; ?>

  <div class="card">
    <div class="accordion" id="<?php echo $id; ?>">
      <div class="accordion-item">
        <h2 class="accordion-header" id="<?php echo $itemId; ?>Header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
            data-bs-target="#<?php echo $itemId; ?>" aria-expanded="false" aria-controls="<?php echo $itemId; ?>">
            <?php echo Text::_('COM_JOOMGALLERY_CONTROL_INSTALLED_EXTENSIONS'); ?>
          </button>
        </h2>
        <div id="<?php echo $itemId; ?>" class="accordion-collapse collapse"
          aria-labelledby="<?php echo $itemId; ?>Header" data-bs-parent="#<?php echo $id; ?>">
          <div class="accordion-body">
            <table class="table table-striped">
              <thead>
                <tr>
                  <td class="w-5">
                    <?php echo Text::_('Name'); ?>
                  </td>
                  <td class="w-10">
                    <?php echo Text::_('JVERSION'); ?>
                  </td>
                  <td class="w-10">
                    <?php echo Text::_('JDate'); ?>
                  </td>
                  <td class="w-10">
                    <?php echo Text::_('JAUTHOR'); ?>
                  </td>
                  <td class="w-10">
                    <?php echo Text::_('JENABLED'); ?>
                  </td>
                  <td class="w-10">
                    <?php echo Text::_('ID'); ?>
                  </td>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($manifest as list($extension_id, $enabled, $extension_data)) {
                  $value = json_decode($extension_data, true); ?>
                  <tr>
                    <td class="d-md-table-cell">
                      <?php echo $value['name']; ?>
                    </td>
                    <td class="d-md-table-cell">
                      <?php echo $value['version']; ?>
                    </td>
                    <td class="d-md-table-cell">
                      <?php echo $value['creationDate']; ?>
                    </td>
                    <td class="d-md-table-cell">
                      <?php echo $value['author']; ?>
                    </td>
                    <td class="d-md-table-cell">
                      <?php if ($enabled === 1) : ?>
                        <span class="icon-publish text-center" title="<?php echo Text::_('JENABLED'); ?>"></span>
                      <?php else : ?>
                        <span class="icon-delete text-center" title="<?php echo Text::_('JDISABLED'); ?>"></span>
                      <?php endif; ?>
                    </td>
                    <td class="d-md-table-cell">
                      <?php echo $extension_id; ?>
                    </td>
                  </tr>
                  <?php } ?>
              </tbody>
            </table>
          </div>
        </div><!--/accordion-collapse-->
      </div><!--/accordion-item-->
    </div><!--/accordion -->
  </div><!--/card -->

  <?php return;

}

/**
 * Display system settings as collapsed
 *
 * @param   string  $title     The displayed title of the content
 * @param   array   $settings  Array with hold the data
 *
 * @since 4.0.0
 */
function DisplaySystemSettings($title, $settings)
{
  $id     = 'systeminfo-100';
  $itemId = $id . '-item'; ?>

  <div class="card">
    <div class="accordion" id="<?php echo $id; ?>">
      <div class="accordion-item">
        <h2 class="accordion-header" id="<?php echo $itemId; ?>Header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
            data-bs-target="#<?php echo $itemId; ?>" aria-expanded="false" aria-controls="<?php echo $itemId; ?>">
            <?php echo Text::_($title); ?>
          </button>
        </h2>
        <div id="<?php echo $itemId; ?>" class="accordion-collapse collapse"
          aria-labelledby="<?php echo $itemId; ?>Header" data-bs-parent="#<?php echo $id; ?>">
          <div class="accordion-body">
            <table class="table table-striped">
              <thead>
                <tr>
                </tr>
              </thead>
              <tbody>
                <?php foreach($settings as $key => $value) { ?>
                  <tr>
                    <td class="d-md-table-cell">
                      <?php echo $key; ?>
                    </td>
                    <td class="d-md-table-cell">
                      <?php switch ($value)
                        {
                          case '':
                          case '0':
                            echo Text::_('JNO');
                            break;

                          case '1':
                            echo Text::_('JYES');
                            break;

                          default:
                            echo $value;
                            break;
                        } ?>
                    </td>
                  </tr>
                  <?php } ?>
              </tbody>
            </table>
          </div>
        </div><!--/accordion-collapse-->
      </div><!--/accordion-item-->
    </div><!--/accordion -->
  </div><!--/card -->

  <?php return;

}

  /**
   * Display collapsed content
   *
   * @param   string  $title    The displayed title of the content
   * @param   string  $content  The content that can be collapsed
   * @param   int     $id       Unique id
   *
   * @since   4.0.0
   */

function collapseContent($title, $content, $id)
{
  $id     = 'accordion-' . $id;
  $itemId = $id . '-item'; ?>

  <div class="accordion" id="<?php echo $id; ?>">
    <div class="accordion-item">
      <h2 class="accordion-header" id="<?php echo $itemId; ?>Header">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
          data-bs-target="#<?php echo $itemId; ?>" aria-expanded="false" aria-controls="<?php echo $itemId; ?>">
          <?php echo $title; ?>
        </button>
      </h2>
      <div id="<?php echo $itemId; ?>" class="accordion-collapse collapse"
        aria-labelledby="<?php echo $itemId; ?>Header" data-bs-parent="#<?php echo $id; ?>">
        <div class="accordion-body">
          <?php echo $content; ?>
        </div>
      </div><!--/accordion-collapse-->
    </div><!--/accordion-item-->
  </div><!--/accordion -->

  <?php return;

}
