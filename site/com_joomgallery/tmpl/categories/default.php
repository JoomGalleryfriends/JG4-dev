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
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Session\Session;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Import CSS
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_joomgallery.list')
   ->useStyle('com_joomgallery.site')
   ->useScript('table.columns')
   ->useScript('multiselect');

$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canEdit   = $this->acl->checkACL('edit', 'com_joomgallery.category');
$canAdd    = $this->acl->checkACL('add', 'com_joomgallery.category', 1, true);
$canDelete = $this->acl->checkACL('delete', 'com_joomgallery.category');
$canOrder  = $this->acl->checkACL('editstate', 'com_joomgallery.category');
$saveOrder = ($listOrder == 'a.lft' && strtolower($listDirn) == 'asc');

if($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_joomgallery&task=categories.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}
?>

<form class="jg-images" action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">
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
					<table class="table table-striped itemList" id="categoryList">
						<caption class="visually-hidden">
              <?php echo Text::_('COM_JOOMGALLERY_CATEGORIES_TABLE_CAPTION'); ?>,
              <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
              <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
            </caption>
						<thead>
							<tr>
									<th scope="col" class="w-1 text-center d-none d-md-table-cell">
										<?php echo HTMLHelper::_('grid.sort', '', 'a.lft', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
									</th>

									<th scope="col" style="min-width:180px">
										<?php echo HTMLHelper::_('grid.sort',  'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
									</th>

									<th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                    <?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES', 'a.img_count', $listDirn, $listOrder); ?>
                  </th>

									<th scope="col" style="min-width:180px" class="w-3 d-none d-lg-table-cell text-center">
										<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_PARENT_CATEGORY', 'a.parent_title', $listDirn, $listOrder); ?>
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
						<tbody>
							<?php foreach ($this->items as $i => $item) :
                  $ordering   = ($listOrder == 'a.ordering');
                  $canEdit    = $this->acl->checkACL('edit', 'com_joomgallery.category', $item->id);
                  $canCheckin = $this->acl->checkACL('manage', 'com_joomgallery') || $item->checked_out == Factory::getUser()->id;
                  $canDelete  = $this->acl->checkACL('delete', 'com_joomgallery.category', $item->id);
                  $canChange  = $this->acl->checkACL('editstate', 'com_joomgallery.category', $item->id);
                
									// Get the parents of item for sorting
									if ($item->level > 1)
									{
										$parentsStr = '';
										$_currentParentId = $item->parent_id;
										$parentsStr = ' ' . $_currentParentId;
										for ($i2 = 0; $i2 < $item->level; $i2++)
										{
											foreach ($this->ordering as $k => $v)
											{
												$v = implode('-', $v);
												$v = '-' . $v . '-';
												if (strpos($v, '-' . $_currentParentId . '-') !== false)
												{
													$parentsStr .= ' ' . $k;
													$_currentParentId = $k;
													break;
												}
											}
										}
									}
									else
									{
										$parentsStr = '';
									}
								?>

								<tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $item->parent_id; ?>"
                	data-item-id="<?php echo $item->id ?>" data-parents="<?php echo $parentsStr ?>"
                	data-level="<?php echo $item->level ?>">

									<?php if (isset($this->items[0]->lft)) : ?>
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
												<span class="icon-ellipsis-v"></span>
											</span>
											<?php if ($canChange && $saveOrder) : ?>
												<input type="text" name="order[]" size="5" value="<?php echo $item->lft; ?>" class="hidden">
											<?php endif; ?>
										</td>
                  <?php endif; ?>


									<th scope="row" class="has-context">
										<?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
										<?php if($canCheckin && $item->checked_out > 0) : ?>
											<a href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.checkin&id=' . $item->id .'&'. Session::getFormToken() .'=1'); ?>">
												<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'category.', false); ?>
											</a>
										<?php endif; ?>
										<a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $item->id); ?>">
											<?php echo $this->escape($item->title); ?>
										</a>
									</th>

									<td class="d-none d-lg-table-cell text-center">
                    <span class="badge bg-info">
                      <?php echo (int) $item->img_count; ?>
                    </span>
                  </td>

									<td class="d-none d-lg-table-cell text-center">
										<?php echo ($item->parent_title == 'Root') ? '--' : $this->escape($item->parent_title); ?>
									</td>

									<?php if($canEdit || $canDelete): ?>
										<td class="d-none d-lg-table-cell text-center">
											<?php if($canEdit && $item->checked_out == 0): ?>
												<a href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.edit&id=' . $item->id, false, 2); ?>" class="btn btn-mini" type="button"><i class="icon-edit" ></i></a>
											<?php endif; ?>
											<?php if ($canDelete): ?>
												<a href="<?php echo Route::_('index.php?option=com_joomgallery&task=categoryform.remove&id=' . $item->id, false, 2); ?>" class="btn btn-mini delete-button" type="button"><i class="icon-trash" ></i></a>
											<?php endif; ?>
										</td>
									<?php endif; ?>

									<td class="d-none d-lg-table-cell text-center">
                    <i class="icon-<?php echo (int) $item->published ? 'check': 'cancel'; ?>"></i>
                  </td>

								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<?php if($canAdd) : ?>
				<a href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.add', false, 0); ?>" class="btn btn-success btn-small">
					<i class="icon-plus"></i> <?php echo Text::_('JGLOBAL_ADD_CUSTOM_CATEGORY'); ?>
				</a>
			<?php endif; ?>

			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="boxchecked" value="0"/>
      <input type="hidden" name="form_submited" value="1"/>
			<input type="hidden" name="filter_order" value=""/>
			<input type="hidden" name="filter_order_Dir" value=""/>
			<?php echo HTMLHelper::_('form.token'); ?>
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
