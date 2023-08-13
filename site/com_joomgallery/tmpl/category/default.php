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
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

$canEdit   = $this->acl->checkACL('edit', 'com_joomgallery.category', $this->item->id);
$canAdd    = $this->acl->checkACL('add', 'com_joomgallery.category', $this->item->id, true);
$canDelete = $this->acl->checkACL('delete', 'com_joomgallery.category', $this->item->id);
?>

<h2><?php echo $this->item->title; ?></h2>
<p><?php echo nl2br($this->item->description); ?></p>

<?php if($this->item->parent_id > 0) : ?>
  <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $this->item->parent_id); ?>">
    <?php echo Text::_('Parent Category'); ?>
  </a>
  </br />
<?php endif; ?>

</br />

<?php if($canEdit || $canAdd || $canDelete): ?>
  <div class="btn-group mb-3" role="group">
    <?php if($canEdit): ?>
      <a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.edit&id='.$this->item->id); ?>">
        <?php echo Text::_("JACTION_EDIT"); ?>
      </a>
    <?php endif; ?>

    <?php if($canAdd): ?>
      <a class="btn btn-outline-success" href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.edit&id=0'); ?>">
        <?php echo Text::_("JGLOBAL_FIELD_ADD"); ?>
      </a>
    <?php endif; ?>

    <?php if($canDelete) : ?>

      <a class="btn btn-danger" rel="noopener noreferrer" href="#deleteModal" role="button" data-bs-toggle="modal">
        <?php echo Text::_("JACTION_DELETE"); ?>
      </a>

      <?php echo HTMLHelper::_( 'bootstrap.renderModal',
                                'deleteModal',
                                array(
                                    'title'  => Text::_('COM_JOOMGALLERY_COMMON_DELETE_CATEGORY_TIPCAPTION'),
                                    'height' => '50%',
                                    'width'  => '20%',

                                    'modalWidth'  => '50',
                                    'bodyHeight'  => '100',
                                    'footer' => '<button class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button><a href="' . Route::_('index.php?option=com_joomgallery&task=category.remove&id=' . $this->item->id, false, 2) .'" class="btn btn-danger">' . Text::_('COM_JOOMGALLERY_COMMON_DELETE_CATEGORY_TIPCAPTION') .'</a>'
                                ),
                                Text::_('COM_JOOMGALLERY_COMMON_ALERT_SURE_DELETE_SELECTED_ITEM')
                              );
      ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php // Subcategories ?>
<h3>Subcategories</h3>
<ul>
  <?php foreach($this->item->children as $key => $subcat) : ?>
    <li>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $subcat->id); ?>">
				<?php echo $this->escape($subcat->title); ?>
			</a>
    </li>
  <?php endforeach; ?>
</ul>

<?php // Images ?>
<h3>Images</h3>
<ul>
  <?php foreach($this->item->images as $key => $image) : ?>
    <li>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=image&id='.(int) $image->id); ?>">
        <?php echo $this->escape($image->imgtitle); ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>