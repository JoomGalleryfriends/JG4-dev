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

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.site');
$wa->useStyle('com_joomgallery.jg-icon-font');
?>

<div class="com-joomgallery-gallery">
  <?php if ($this->params['menu']->get('show_page_heading')) : ?>
    <div class="page-header">
      <h1> <?php echo $this->escape($this->params['menu']->get('page_heading')); ?> </h1>
    </div>
  <?php endif; ?>

  <p>This is the default JoomGallery-Page. In the future, this will become a beautiful image wall page with search bar and filters. A perfect entry point to your gallery.</p>
  <ul>
    <li><a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id=1'); ?>">Category View</a></li>
    <?php if($this->params['configs']->get('jg_userspace', 1, 'int') == 1): ?>
      <li><a href="<?php echo Route::_('index.php?option=com_joomgallery&view=categories'); ?>">Categories List View</a></li>
      <li><a href="<?php echo Route::_('index.php?option=com_joomgallery&view=images'); ?>">Images List View</a></li>
    <?php endif; ?>
  </ul>
</div>
