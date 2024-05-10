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
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Session\Session;
use \Joomla\CMS\Button\PublishedButton;

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
$saveOrder = ($listOrder == 'a.ordering' && strtolower($listDirn) == 'asc');

if ($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_joomgallery&task=configs.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}

?>

<form action="<?php echo Route::_('index.php?option=com_joomgallery&view=configs'); ?>" method="post"
	  name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
			<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
				<div class="clearfix"></div>
        <div class="table-responsive">
          <table class="table table-striped" id="configList">
            <caption class="visually-hidden">
              <?php echo Text::_('COM_JOOMGALLERY_CONFIGS_TABLE_CAPTION'); ?>,
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
                <th scope="col" class="w-1 text-center">
                  <?php echo HTMLHelper::_('searchtools.sort',  'JPUBLISHED', 'a.published', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" style="min-width:140px">
                  <?php echo HTMLHelper::_('searchtools.sort',  'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-25 d-none d-md-table-cell">
                  <?php echo Text::_('JFIELD_NOTE_LABEL'); ?>
                </th>
                <th scope="col" class="w-25 d-none d-md-table-cell">
                <?php echo HTMLHelper::_('searchtools.sort',  'COM_JOOMGALLERY_USER_GROUP', 'a.group_id', $listDirn, $listOrder); ?>
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
              $canCreate  = $user->authorise('core.create', _JOOM_OPTION.'.config.'.$item->id);
                $canEdit    = $user->authorise('core.edit', _JOOM_OPTION.'.config.'.$item->id);
                $canEditOwn = $user->authorise('core.edit.own', _JOOM_OPTION.'.config.'.$item->id) && $item->created_by == $userId;
                $canCheckin = $user->authorise('core.admin', 'com_checkin') || $item->checked_out == $userId || is_null($item->checked_out);
                $canChange  = $user->authorise('core.edit.state', _JOOM_OPTION.'.config.'.$item->id);
              ?>
              <tr class="row<?php echo $i % 2; ?>">
              <td >
                  <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
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

                <td class="image-status text-center">
                  <?php 
                    $options = [
                      'task_prefix' => 'configs.',
                      'disabled' => !$canChange,
                      'id' => 'state-' . $item->id
                    ];

                    echo (new PublishedButton)->render((int) $item->published, $i, $options); 
                  ?>
                </td>

                <th scope="row" class="has-context">
                  <div class="break-word">
                    <?php if (isset($item->checked_out) && $item->checked_out && ($canEdit || $canChange)) : ?>
                      <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'configs.', $canCheckin); ?>
                    <?php endif; ?>

                    <?php if ($canEdit || $canEditOwn) : ?>
                      <?php
                        $ConfigUrl     = Route::_('index.php?option=com_joomgallery&task=config.edit&id='.(int) $item->id);
                        $EditConfigTxt = Text::_('COM_JOOMGALLERY_CONFIG_EDIT');
                      ?>
                      <a href="<?php echo $ConfigUrl; ?>" title="<?php echo $EditConfigTxt; ?>">
                        <?php echo $this->escape($item->title); ?>
                      </a>
                    <?php else : ?>
                      <?php echo $this->escape($item->title); ?>
                    <?php endif; ?>
                  </div>
                </th>
                <td class="small d-none d-md-table-cell">
                  <?php echo $this->escape($item->note); ?>
                </td>
                <td class="d-none d-lg-table-cell">
                  <?php echo $this->escape($item->group_id); ?>
                </td>
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
        <input type="hidden" name="form_submited" value="1"/>
				<input type="hidden" name="list[fullorder]" value="<?php echo $listOrder; ?> <?php echo $listDirn; ?>"/>
				<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>
