<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string  $label              The field label
 * @var   Form    $form               The form instance for render the section
 * @var   string  $basegroup          The base group name
 * @var   string  $group              Current group name
 * @var   array   $buttons            Array of the buttons that will be rendered
 * @var   boolean $is_global_config   True if the global configuration is loaded in config.edit view
 */

if($group == 'jg_staticprocessing1' || $group == 'jg_staticprocessing2')
{
  $form->setFieldAttribute('jg_imgtype','readonly',true);
}
?>

<tr class="subform-repeatable-group" data-base-name="<?php echo $basegroup; ?>" data-group="<?php echo $group; ?>">
	<?php
  $i = 0;
  foreach ($form->getGroup('') as $k => $field) : ?>
    <?php if($i == 1)
    {
      $fieldname = $field->value;
    }
    ?>
    <?php if($i < 2): ?>
      <td data-column="<?php echo strip_tags($field->label); ?>">
        <?php echo $field->renderField(array('hiddenLabel' => true, 'hiddenDescription' => true)); ?>
      </td>
    <?php endif; ?>
	<?php 
  $i++;
  endforeach; ?>
  <td data-column="<?php echo Text::_('Settings-Popup'); ?>">
    <div class="control-group">
      <div class="visually-hidden"><label><?php echo Text::_('Settings-Popup'); ?></label></div>
      <div class="controls">
        <a href="#" data-bs-toggle="modal" class="btn btn-secondary" data-bs-target="#<?php echo $group; ?>_modal"><?php echo Text::_('Show Settings'); ?></a>
      </div>
    </div>
  </td>
	<?php if (!empty($buttons)) : ?>
	<td>
    <?php if ($group != 'jg_staticprocessing0' && $group != 'jg_staticprocessing1' && $group != 'jg_staticprocessing2') : ?>
      <div class="btn-group">
        <?php if (!empty($buttons['add'])) : ?>
          <button type="button" class="group-add btn btn-sm btn-success" aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>">
            <span class="icon-plus" aria-hidden="true"></span>
          </button>
        <?php endif; ?>
        <?php if (!empty($buttons['remove'])) : ?>
          <button type="button" class="group-remove btn btn-sm btn-danger" aria-label="<?php echo Text::_('JGLOBAL_FIELD_REMOVE'); ?>">
            <span class="icon-minus" aria-hidden="true"></span>
          </button>
        <?php endif; ?>
        <?php if (!empty($buttons['move'])) : ?>
          <button type="button" class="group-move btn btn-sm btn-primary" aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE'); ?>">
            <span class="icon-arrows-alt" aria-hidden="true"></span>
          </button>
        <?php endif; ?>
      </div>
    <?php endif; ?>
	</td>
	<?php endif; ?>

  <?php
    $modalData = array(
      'selector' => $group . '_modal',
      'params'   => array('title'  => $label.': '.ucfirst($fieldname)),
      'body' => LayoutHelper::render('joomla.form.field.subform.repeatable-config.modal', $displayData)
    );
    echo LayoutHelper::render('libraries.html.bootstrap.modal.main', $modalData);
  ?>
</tr>
