<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('bootstrap.framework');

HTMLHelper::_('stylesheet', 'com_joomgallery/admin.css', array('version' => 'auto', 'relative' => true));

?>

<div class="d-flex flex-row">
  <div class="flex-fill">
    <div id="j-main-container" class="j-main-container">
      <div class"jg-control-head">
        <div class="jg-messages-logo">
          <?php echo HTMLHelper::_('image', 'com_joomgallery/logo.png', Text::_('COM_JOOMGALLERY_LOGO'), ['class' => 'joom-logo-small'], true); ?>
        </div>
        <div class="jg-messages-inner">
          <?php // echo 'Kopfbereich für wichtige Meldungen.'; ?>
          <hr>
        </div>
      </div>
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

      <?php // Render admin modules in position joom_cpanel ?>
      <?php foreach ($this->modules as $module)
      {
        echo ModuleHelper::renderModule($module, array('style' => 'well'));
      }

      // Display extensions
      $title   = Text::_('COM_JOOMGALLERY_CONTROL_EXTENSIONS');
      $content = 'Module<br /> <br /><hr>Plugins<br /> <br /><hr>Sonstiges<br /> <br /><hr>Sprachdateien<br /> ';
      $id      = '123';

      collapseContent($title, $content, $id);

      ?>
      <hr>
      <?php

      // Display system info
      $title   = Text::_('System info');
      $content = 'PHP version<br /> <br />memory_limit<br /> <br />Post Max Size<br /> <br />max_upload_size<br /> <br />max_input_vars<br /> <br /GD Available<br />...? ';
      $id      = '234';

      collapseContent($title, $content, $id);

      // Display Footer 
      ?>
      <div class"jg-control-footer">
        <?php
        // Display copyright 
        ?>
        <hr>
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
    </div>
  </div>
</div>

<?php 



// Shows a small gallery statistic

/**
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
                  <td class="w-1">
                    <span class="icon-delete" title="<?php echo Text::_('JUNPUBLISHED'); ?>" aria-label="unpublished" data-bs-original-title="unpublished"></span>
                  </td>
                  <td class="w-1">
                    <span class="icon-publish" title="<?php echo Text::_('JPUBLISHED'); ?>"></span>
                  </td>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <th scope="col" class="w-10 d-md-table-cell">
                    <?php echo Text::_('JCATEGORIES'); ?>
                  </th>
                  <td class="d-md-table-cell">
                  <?php if($statisticdata['unpublishedcategories'] > 0) : ?>
                    <a href="<?php echo JRoute::_('index.php?option='._JOOM_OPTION.'&view=categories&filter[published]=0'); ?>">
                    <span class="badge bg-info"><?php echo (int) $statisticdata['unpublishedcategories']; ?></span>
                    </a>
                  <?php else : ?>
                    <span class="badge bg-info">0</span>
                  <?php endif; ?>
                  </td>
                  <td class="d-md-table-cell">
                  <?php if($statisticdata['publishedcategories'] > 0) : ?>
                    <a href="<?php echo JRoute::_('index.php?option='._JOOM_OPTION.'&view=categories&filter[published]=1'); ?>">
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
                  <td class="d-md-table-cell">
                  <?php if($statisticdata['unpublishedimages'] > 0) : ?>
                    <a href="<?php echo JRoute::_('index.php?option='._JOOM_OPTION.'&amp;view=images&amp;filter[published]=2'); ?>">
                      <span class="badge bg-info"><?php echo (int) $statisticdata['unpublishedimages']; ?></span>
                    </a>
                  <?php else : ?>
                    <span class="badge bg-info">0</span>
                  <?php endif; ?>
                  </td>
                  <td class="d-md-table-cell">
                  <?php if($statisticdata['publishedimages'] > 0) : ?>
                    <a href="<?php echo JRoute::_('index.php?option='._JOOM_OPTION.'&amp;view=images&amp;filter[published]=1'); ?>">
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
                  <td class="d-md-table-cell">
                  <?php if($statisticdata['unapprovedimages'] > 0) : ?>
                    <a href="<?php echo JRoute::_('index.php?option='._JOOM_OPTION.'&amp;view=images&amp;filter[published]=4'); ?>">
                      <span class="badge bg-info"><?php echo (int) $statisticdata['unapprovedimages']; ?></span>
                    </a>
                  <?php else : ?>
                    <span class="badge bg-info">0</span>
                  <?php endif; ?>
                  </td>
                  <td class="d-md-table-cell">
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

<?php   return;
}

/**
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
                  <td scope="col" class="w-10">
                    <?php echo Text::_('COM_JOOMGALLERY_CONTROL_VERSION'); ?>
                  </td>
                  <td class="w-10">
                    <b><?php echo $manifest['version']; ?></b>
                  </td>
                </tr>
                <tr>
                  <td scope="col" class="w-10">
                    <?php echo Text::_('COM_JOOMGALLERY_CONTROL_CREATION_DATE'); ?>
                  </td>
                  <td class="w-10">
                    <?php echo $manifest['creationDate']; ?>
                  </td>
                </tr>
                <tr>
                  <td scope="col" class="w-10">
                    <?php echo Text::_('COM_JOOMGALLERY_CONTROL_LICENSE'); ?>
                  </td>
                  <td class="w-10">
                    <a href="<?php echo 'https://www.gnu.org/licenses/gpl-3.0.html'; ?>" target="_blank">GNU General Public License v3</a>
                  </td>
                </tr>
                <tr>
                  <td scope="col" class="w-10">
                    <?php echo Text::_('COM_JOOMGALLERY_CONTROL_WEBSITE'); ?>
                  </td>
                  <td class="w-10">
                    <a href="<?php echo 'https://www.en.joomgalleryfriends.net'; ?>" target="_blank">en.joomgalleryfriends.net</a>
                  </td>
                </tr>
                <tr>
                  <td scope="col" class="w-10">
                    <?php echo Text::_('COM_JOOMGALLERY_CONTROL_WEBSITE_SUPPORT'); ?>
                  </td>
                  <td class="w-10">
                    <a href="<?php echo 'https://www.forum.en.joomgalleryfriends.net/forum'; ?>" target="_blank">forum.en.joomgalleryfriends.net/forum</a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

<?php   return;
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
  $itemId = $id . '-item';

  ?>
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
