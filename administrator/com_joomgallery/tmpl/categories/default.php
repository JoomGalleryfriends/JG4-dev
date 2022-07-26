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
use Joomla\CMS\Button\PublishedButton;

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
$saveOrder = $listOrder == 'a.lft';

if ($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_joomgallery&task=categories.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
} 
?>

<form action="<?php echo Route::_('index.php?option=com_joomgallery&view=categories'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
				<div class="clearfix"></div>
				<table class="table table-striped" id="categoryList">
					<thead>
						<tr>
              <td class="w-1 text-center">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</td>
							<?php if (isset($this->items[0]->ordering)): ?>
								<th scope="col" class="w-1 text-center d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
								</th>
							<?php endif; ?>
							<th scope="col" class="w-1 text-center">
								<?php echo HTMLHelper::_('searchtools.sort', '', 'a.lft', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
							</th>
							<th scope="col" class="w-1 text-center">
                <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
              </th>
							<th scope="col" style="min-width:100px">
								<?php echo HTMLHelper::_('searchtools.sort',  'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
							</th>
              <th scope="col" class="w-10 d-none d-md-table-cell">
                <?php echo HTMLHelper::_('searchtools.sort',  'COM_JOOMGALLERY_COMMON_PARENT_CATEGORY', 'a.parent_id', $listDirn, $listOrder); ?>
							</th>
              <th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo Text::_('Images'); ?>
							</th>
							<th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort',  'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?>
							</th>
							<?php if (Multilanguage::isEnabled()) : ?>
								<th scope="col" class="w-10 d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort',  'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
								</th>
							<?php endif; ?>
							<th scope="col" class="w-10 d-none d-md-table-cell">
								<?php echo HTMLHelper::_('searchtools.sort',  'COM_JOOMGALLERY_COMMON_OWNER', 'a.created_by', $listDirn, $listOrder); ?>
							</th>
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
							$canCreate  = $user->authorise('core.create', _JOOM_OPTION.'.category.'.$item->id);
							$canEdit    = $user->authorise('core.edit', _JOOM_OPTION.'.category.'.$item->id);
              $canEditOwn = $user->authorise('core.edit.own', _JOOM_OPTION.'.category.'.$item->id) && $item->created_by == $userId;
							$canCheckin = $user->authorise('core.manage', _JOOM_OPTION.'.category.'.$item->id);
							$canChange  = $user->authorise('core.edit.state', _JOOM_OPTION.'.category.'.$item->id);
							?>
						<tr class="row<?php echo $i % 2; ?>">
							<?php if (isset($this->items[0]->ordering)) : ?>
								<td class="order nowrap center hidden-phone">
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

              <td class="text-center">
                <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
              </td>

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
								  <input type="text" name="order[]" size="5" value="<?php echo $item->lft; ?>" class="width-20 text-area-order hidden">
								<?php endif; ?>
							</td>

							<td class="category-status text-center">
                <?php 
                  $options = [
                    'task_prefix' => 'categories.',
                    'disabled' => !$canChange,
                    'id' => 'state-' . $item->id
                  ];

                  echo (new PublishedButton)->render((int) $item->published, $i, $options); 
                ?>
							</td>

							<th scope="row" class="has-context">
                <div class="break-word">
                  <?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
                  <?php if (isset($item->checked_out) && $item->checked_out && ($canEdit || $canChange)) : ?>
                    <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'categories.', $canCheckin); ?>
                  <?php endif; ?>
                  <?php if ($canEdit || $canEditOwn) : ?>
                    <a href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.edit&id='.(int) $item->id); ?>">
                      <?php echo $this->escape($item->title); ?>
                    </a>
                  <?php else : ?>
                    <?php echo $this->escape($item->title); ?>
                  <?php endif; ?>
                  <div class="small break-word">
                      <?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
                      <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                  </div>
                </div>
							</th>

              <td class="d-none d-md-table-cell">
                <?php echo ($item->parent_title == 'Root') ? '' : $item->parent_title; ?>
							</td>

              <td class="d-none d-md-table-cell">
              <?php if($item->img_count > 0) : ?>
                <a href="<?php echo JRoute::_('index.php?option='._JOOM_OPTION.'&view=images&filter[category]='.$item->id); ?>">(<?php echo $item->img_count; ?>)</a>
              <?php else : ?>
                (0)
              <?php endif; ?>
							</td>

							<td class="small d-none d-md-table-cell">
								<?php echo $item->access; ?>
							</td>
							<?php if (Multilanguage::isEnabled()) : ?>
								<td class="small d-none d-md-table-cell">
								<?php echo $item->language; ?>
								</td>
							<?php endif; ?>
							<td class="small d-none d-md-table-cell">
								<?php echo $item->created_by; ?>
							</td>
							<td>
								<?php echo $item->id; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<input type="hidden" name="task" value=""/>
				<input type="hidden" name="boxchecked" value="0"/>
				<input type="hidden" name="list[fullorder]" value="<?php echo $listOrder; ?> <?php echo $listDirn; ?>"/>
				<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>
