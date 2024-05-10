<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Session\Session;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

// Import CSS
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_joomgallery.list')
   ->useStyle('com_joomgallery.site')
   ->useScript('com_joomgallery.list-view')
   ->useScript('multiselect');

$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canEdit   = $this->acl->checkACL('edit', 'com_joomgallery.image');
$canAdd    = $this->acl->checkACL('add', 'com_joomgallery.image', 1, true);
$canDelete = $this->acl->checkACL('delete', 'com_joomgallery.image');
$canOrder  = $this->acl->checkACL('editstate', 'com_joomgallery.image');
$saveOrder = ($listOrder == 'a.ordering' && strtolower($listDirn) == 'asc');
$returnURL = base64_encode(JoomHelper::getListRoute('categories', null, $this->getLayout()));

if($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_joomgallery&task=images.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}
?>

<?php if ($this->params['menu']->get('show_page_heading')) : ?>
    <div class="page-header page-title">
        <h1> <?php echo $this->escape($this->params['menu']->get('page_heading')); ?> </h1>
    </div>
<?php endif; ?>

<form class="jg-images" action="<?php echo Route::_('index.php?option=com_joomgallery&view=images'); ?>" method="post" name="adminForm" id="adminForm">
	<?php if(!empty($this->filterForm)) { echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); } ?>
  <div class="row">
		<div class="col-md-12">

      <?php if (empty($this->items)) : ?>
        <div class="alert alert-info">
          <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
          <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
        </div>
      <?php else : ?>
        <div class="clearfix"></div>
        <div class="table-responsive">
          <table class="table table-striped itemList" id="imageList">
            <caption class="visually-hidden">
              <?php echo Text::_('COM_JOOMGALLERY_IMAGES_TABLE_CAPTION'); ?>,
              <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
              <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
            </caption>
            <thead>
              <tr>
                  <?php if($canOrder && $saveOrder && isset($this->items[0]->ordering)): ?>
                    <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                      <?php echo HTMLHelper::_('grid.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                    </th>
                  <?php else : ?>
                    <th scope="col" class="w-1 d-md-table-cell"></th>
                  <?php endif; ?>

                  <th></th>

                  <th scope="col" style="min-width:180px">
                    <?php echo HTMLHelper::_('grid.sort',  'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                  </th>

                  <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                    <?php echo HTMLHelper::_('grid.sort',  'JGLOBAL_HITS', 'a.hits', $listDirn, $listOrder); ?>
                  </th>

                  <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                    <?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_DOWNLOADS', 'a.downloads', $listDirn, $listOrder); ?>
                  </th>

                  <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                    <?php echo HTMLHelper::_('grid.sort',  'JCATEGORY', 'a.catid', $listDirn, $listOrder); ?>
                  </th>

                  <?php if($canEdit || $canDelete): ?>
                    <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                      <?php echo Text::_('COM_JOOMGALLERY_ACTIONS'); ?>
                    </th>
                  <?php endif; ?>

                  <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                    <?php echo HTMLHelper::_('grid.sort',  'JPUBLISHED', 'a.published', $listDirn, $listOrder); ?>
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
            <tbody <?php if($saveOrder) :?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" <?php endif; ?>>
              <?php foreach ($this->items as $i => $item) :
                  $ordering   = ($listOrder == 'a.ordering');
                  $canEdit    = $this->acl->checkACL('edit', 'com_joomgallery.image', $item->id);
                  $canDelete  = $this->acl->checkACL('delete', 'com_joomgallery.image', $item->id);
                  $canChange  = $this->acl->checkACL('editstate', 'com_joomgallery.image', $item->id);
                  $canCheckin = $canChange || $item->checked_out == Factory::getUser()->id;
                  $disabled   = ($item->checked_out > 0) ? 'disabled' : '';
                ?>

                <tr class="row<?php echo $i % 2; ?>">

                  <?php if (isset($this->items[0]->ordering)) : ?>
                    <td class="text-center d-none d-md-table-cell sort-cell">
                      <?php
                        $iconClass = '';
                        if(!$canChange)
                        {
                          $iconClass = ' inactive';
                        }
                        elseif(!$saveOrder)
                        {
                          $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                        }
                      ?>
                      <?php if($canChange && $saveOrder) : ?>
                        <span class="sortable-handler<?php echo $iconClass ?>">
                          <span class="icon-ellipsis-v" aria-hidden="true"></span>
                        </span>
                        <input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order hidden">
                      <?php endif; ?>

                      <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                    </td>
                  <?php endif; ?>

                  <td class="small d-none d-md-table-cell">
                    <img class="jg_minithumb" src="<?php echo JoomHelper::getImg($item, 'thumbnail'); ?>" alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
                  </td>

                  <th scope="row" class="has-context title-cell">
                    <?php if($canCheckin && $item->checked_out > 0) : ?>
                      <button class="js-grid-item-action tbody-icon" data-item-id="cb<?php echo $i; ?>" data-item-task="imageform.checkin">
                        <span class="icon-checkedout" aria-hidden="true"></span>
                      </button>
                    <?php endif; ?>
                    <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=image&id='.(int) $item->id); ?>">
                      <?php echo $this->escape($item->title); ?>
                    </a>
                  </th>

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

                  <td class="d-none d-lg-table-cell text-center">
                    <?php echo $this->escape($item->cattitle); ?>
                  </td>

                  <?php if($canEdit || $canDelete): ?>
                    <td class="d-none d-lg-table-cell text-center">
                      <?php if($canEdit): ?>
                        <button class="js-grid-item-action tbody-icon <?php echo $disabled; ?>" data-item-id="cb<?php echo $i; ?>" data-item-task="image.edit" <?php echo $disabled; ?>>
                          <span class="icon-edit" aria-hidden="true"></span>
                        </button>
                      <?php endif; ?>
                      <?php if ($canDelete): ?>
                        <button class="js-grid-item-delete tbody-icon <?php echo $disabled; ?>" data-item-confirm="<?php echo Text::_('JGLOBAL_CONFIRM_DELETE'); ?>" data-item-id="cb<?php echo $i; ?>" data-item-task="imageform.remove" <?php echo $disabled; ?>>
                          <span class="icon-trash" aria-hidden="true"></span>
                        </button>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>
                  
                  <td class="d-none d-lg-table-cell text-center">
                    <?php if($canChange): ?>
                      <?php $statetask = ((int) $item->published) ? 'unpublish': 'publish'; ?>
                      <button class="js-grid-item-action tbody-icon <?php echo $disabled; ?>" data-item-id="cb<?php echo $i; ?>" data-item-task="imageform.<?php echo $statetask; ?>" <?php echo $disabled; ?>>
                        <span class="icon-<?php echo (int) $item->published ? 'check': 'cancel'; ?>" aria-hidden="true"></span>
                      </button>
                    <?php else : ?>
                      <i class="icon-<?php echo (int) $item->published ? 'check': 'cancel'; ?>"></i>
                    <?php endif; ?>
                  </td>

                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <input type="hidden" name="task" value=""/>
      <input type="hidden" name="return" value="<?php echo $returnURL; ?>"/>
      <input type="hidden" name="boxchecked" value="0"/>
      <input type="hidden" name="form_submited" value="1"/>
      <input type="hidden" name="filter_order" value=""/>
      <input type="hidden" name="filter_order_Dir" value=""/>
      <?php echo HTMLHelper::_('form.token'); ?>

      <?php /*if($canAdd) : ?>
        <div class="mb-2">
          <a href="<?php echo Route::_('index.php?option=com_joomgallery&task=image.add', false, 0); ?>" class="btn btn-success btn-small">
            <i class="icon-plus"></i>
            <?php echo Text::_('COM_JOOMGALLERY_IMG_UPLOAD_IMAGE'); ?>
          </a>
        </div>
      <?php endif; */?>
    </div>
  </div>
</form>

<?php
	if($canDelete) {
		$wa->addInlineScript("
			jQuery(document).ready(function () {
				jQuery('.delete-button').click(deleteItem);
			});

			function deleteItem() {

				if (!confirm(\"" . Text::_('COM_JOOMGALLERY_DELETE_MESSAGE') . "\")) {
					return false;
				}
			}
		", [], [], ["jquery"]);
	}
?>
