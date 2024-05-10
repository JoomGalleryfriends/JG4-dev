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

use \Joomla\CMS\Router\Route;
use \Joomla\CMS\HTML\HTMLHelper;
?>

<div class="container">
  <form  name="migrepairForm" id="migrepairForm" action="<?php echo Route::_('index.php?option='._JOOM_OPTION); ?>" method="post">
    <div class="row">
      <div class="col-12">
          <?php echo $displayData['form']->renderField('note'); ?>
          <br />
          <?php echo $displayData['form']->renderField('src_pk'); ?>
          <?php echo $displayData['form']->renderField('state'); ?>
          <?php echo $displayData['form']->renderField('dest_pk'); ?>
          <?php echo $displayData['form']->renderField('error'); ?>
          <?php echo $displayData['form']->renderField('confirmation'); ?>
      </div>
    </div>
    <input type="hidden" name="type" value=""/>
    <input type="hidden" name="task" value="migration.applyState"/>
    <input type="hidden" name="migrateable" value=""/>
    <input type="hidden" name="script" value="<?php echo $displayData['script']; ?>"/>
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
</div>
