<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string   $class           Classes for the input.
 * @var   boolean  $hidden          Is this field hidden in the form?
 * @var   string   $id              DOM id of the field.
 * @var   string   $value           Value attribute of the field.
 */

 $class  = empty($class) ? '' : $class;
 $hidden = empty($hidden) ? '' : $hidden;
 $id     = empty($id) ? '' : $id;
 $value  = empty($value) ? '' : $value;
?>

  <span id="<?php echo $id; ?>" class="<?php echo $class; ?> <?php if($hidden){echo 'hidden';}; ?>">
    <?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>
  </span>
