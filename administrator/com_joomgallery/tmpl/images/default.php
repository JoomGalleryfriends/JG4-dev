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

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Button\FeaturedButton;
use Joomla\CMS\Button\PublishedButton;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/src/Helper/');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

// Import CSS
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')
   ->useScript('com_joomgallery.admin');

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $user->authorise('core.edit.state', 'com_joomgallery');
$saveOrder = $listOrder == 'a.ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_joomgallery&task=images.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}
?>

<form action="<?php echo Route::_('index.php?option=com_joomgallery&view=images'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
			<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
				<div class="clearfix"></div>
        <div class="table-responsive">
          <table class="table table-striped" id="imageList">
            <caption class="visually-hidden">
              <?php echo Text::_('COM_JOOMGALLERY_IMAGES_TABLE_CAPTION'); ?>,
              <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
              <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
            </caption>
            <thead>
              <tr>
                <td class="w-1 text-center">
                  <?php echo HTMLHelper::_('grid.checkall'); ?>
                </td>
                <?php if (isset($this->items[0]->ordering)): ?>
                  <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                    <?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                  </th>
                <?php endif; ?>
                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort', 'JFEATURED', 'a.featured', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-1 text-center">
                  <?php echo HTMLHelper::_('searchtools.sort',  'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-1 text-center">
                  <?php // Spaceholder for thumbnail image ?>
                </th>
                <th scope="col" style="min-width:180px">
                  <?php echo HTMLHelper::_('searchtools.sort',  'JGLOBAL_TITLE', 'a.imgtitle', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort',  'COM_JOOMGALLERY_COMMON_APPROVED', 'a.approved', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort',  'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort',  'COM_JOOMGALLERY_COMMON_AUTHOR', 'a.imgauthor', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort',  'JDATE', 'a.imgdate', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                  <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_HITS', 'a.hits', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                  <?php echo HTMLHelper::_('searchtools.sort',  'COM_JOOMGALLERY_COMMON_DOWNLOADS', 'a.downloads', $listDirn, $listOrder); ?>
                </th>              
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort',  'COM_JOOMGALLERY_COMMON_OWNER', 'a.created_by', $listDirn, $listOrder); ?>
                </th>
                <?php if (Multilanguage::isEnabled()) : ?>
                  <th scope="col" class="w-10 d-none d-md-table-cell">
                    <?php echo HTMLHelper::_('searchtools.sort',  'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
                  </th>
                <?php endif; ?>
                <th scope="col" class="w-3 d-none d-lg-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort',  'JGLOBAL_FIELD_ID_LABEL', 'a.id', $listDirn, $listOrder); ?>
                </th>
              </tr>
            </thead>
            <tfoot>
            <tr>
              <td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
                <?php echo $this->pagination->getListFooter(); ?>
              </td>
            </tr>
            </tfoot>
            <tbody <?php if ($saveOrder) :?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" <?php endif; ?>>
            <?php foreach ($this->items as $i => $item) :
              $ordering   = ($listOrder == 'a.ordering');

              $canCreate  = $user->authorise('core.create', 'com_joomgallery');
              $canEdit    = $user->authorise('core.edit', 'com_joomgallery');
              $canCheckin = $user->authorise('core.manage', 'com_joomgallery');
              $canChange  = $user->authorise('core.edit.state', 'com_joomgallery');

              $canEdit          = $user->authorise('core.edit',       'com_joomgallery.image.'.$item->id);
              $canCheckin       = $user->authorise('core.manage',     'com_joomgallery') || $item->checked_out == $userId || is_null($item->checked_out);
              $canEditOwn       = $user->authorise('core.edit.own',   'com_joomgallery.image.'.$item->id) && $item->created_by == $userId;
              $canChange        = $user->authorise('core.edit.state', 'com_joomgallery.image.'.$item->id) && $canCheckin;
              $canEditCat       = $user->authorise('core.edit',       'com_joomgallery.category.'.$item->catid);
              //$canEditOwnCat    = $user->authorise('core.edit.own',   'com_joomgallery.category.'.$item->catid) && $item->category_uid == $userId;
              //$canEditParCat    = $user->authorise('core.edit',       'com_joomgallery.category.'.$item->parent_category_id);
              //$canEditOwnParCat = $user->authorise('core.edit.own',   'com_joomgallery.category.'.$item->parent_category_id) && $item->parent_category_uid == $userId;
              ?>

              <tr class="row<?php echo $i % 2; ?>">
                <td >
                  <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->imgtitle); ?>
                </td>

                <?php if (isset($this->items[0]->ordering)) : ?>
                  <td class="text-center d-none d-md-table-cell">
                    <?php
                      $iconClass = '';
                      if (!$canChange)
                      {
                        $iconClass = ' inactive';
                      }
                      elseif (!$saveOrder)
                      {
                        $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                      }
                    ?>
                    <span class="sortable-handler<?php echo $iconClass ?>">
                      <span class="icon-ellipsis-v" aria-hidden="true"></span>
                    </span>
                    <?php if ($canChange && $saveOrder) : ?>
                      <input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order hidden">
                    <?php endif; ?>
                  </td>
                <?php endif; ?>

                <td class="text-center d-none d-md-table-cell">
                  <?php
                    $options = [
                      'task_prefix' => 'images.',
                      'disabled' => !$canChange,
                      'id' => 'featured-' . $item->id
                    ];

                    echo (new FeaturedButton)->render((int) $item->featured, $i, $options);
                  ?>
                </td>

                <td class="image-status text-center">
                  <?php 
                    $options = [
                      'task_prefix' => 'images.',
                      'disabled' => !$canChange,
                      'id' => 'state-' . $item->id
                    ];

                    echo (new PublishedButton)->render((int) $item->published, $i, $options); 
                  ?>
                </td>

                <td class="small d-none d-md-table-cell">
                  <img class="jg_minithumb" src="<?php echo JoomHelper::getImg($item, 'thumbnail'); ?>" alt="<?php echo Text::_('COM_JOOMGALLERY_MAIMAN_TYPE_THUMBNAIL'); ?>">
                </td>

                <th scope="row" class="has-context">
                  <div class="break-word">
                    <?php if (isset($item->checked_out) && $item->checked_out && ($canEdit || $canChange)) : ?>
                      <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'images.', $canCheckin); ?>
                    <?php endif; ?>

                    <?php if ($canEdit || $canEditOwn) : ?>
                      <?php
                        $ImgUrl     = Route::_('index.php?option=com_joomgallery&task=image.edit&id='.(int) $item->id);
                        $EditImgTxt = Text::_('COM_JOOMGALLERY_EDIT_IMAGE');
                      ?>
                      <a href="<?php echo $ImgUrl; ?>" title="<?php echo $EditImgTxt; ?>">
                        <?php echo $this->escape($item->imgtitle); ?>
                      </a>
                    <?php else : ?>
                      <?php echo $this->escape($item->imgtitle); ?>
                    <?php endif; ?>

                    <div class="small break-word">
                      <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                    </div>

                    <div class="small">
                      <?php echo Text::_('JCATEGORY') . ': '; ?>
                      <?php if ($canEditCat || $canEditOwnCat) : ?>
                        <?php
                          $CatUrl     = Route::_('index.php?option=com_joomgallery&task=category.edit&id='.(int) $item->catid);
                          $EditCatTxt = Text::_('COM_JOOMGALLERY_EDIT_CATEGORY');
                        ?>
                        <a href="<?php echo $CatUrl; ?>" title="<?php echo $EditCatTxt; ?>"><?php echo $this->escape($item->cattitle); ?></a>
                      <?php else : ?>
                        <?php echo $this->escape($item->cattitle); ?>
                      <?php endif; ?>
                    </div>
                  </div>
                </th>

                <td class="d-none d-lg-table-cell text-center">
                  <?php echo $item->approved; ?>
                </td>

                <td class="small d-none d-md-table-cell">
                  <?php echo $this->escape($item->access); ?>
                </td>

                <td class="small d-none d-md-table-cell">
                  <?php if ($item->imgauthor) : ?>
                    <?php echo $this->escape($item->imgauthor); ?>
                  <?php else : ?>
                    <?php echo Text::_('COM_JOOMGALLERY_COMMON_NO_USER'); ?>
                  <?php endif; ?>
                </td>

                <td class="small d-none d-md-table-cell text-center">
                  <?php
                    $date = $item->imgdate;
                    echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC4')) : '-';
                  ?>
                </td>

                <td class="d-none d-lg-table-cell text-center">
                  <span class="badge bg-info">
                    <?php echo (int) $item->hits; ?>
                  </span>
                </td>
                <td class="d-none d-lg-table-cell text-center">
                  <span class="badge bg-info">
                    <?php echo (int) $item->downloads; ?>
                  </span>
                </td>
                <td class="small d-none d-md-table-cell">
                  <?php if ($item->created_by) : ?>
                    <a href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->created_by); ?>">
                      <?php echo $this->escape($item->created_by); ?>
                    </a>
                  <?php else : ?>
                    <?php echo Text::_('JNONE'); ?>
                  <?php endif; ?>
                </td>
                <?php if (Multilanguage::isEnabled()) : ?>
                  <td class="small d-none d-md-table-cell">
                    <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                  </td>
                <?php endif; ?>
                <td class="d-none d-lg-table-cell">
                  <?php echo (int) $item->id; ?>
                </td>

              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
				<input type="hidden" name="task" value=""/>
				<input type="hidden" name="boxchecked" value="0"/>
				<?php echo HTMLHelper::_('form.token'); ?>
			</div> 
		</div>
	</div>
</form>
