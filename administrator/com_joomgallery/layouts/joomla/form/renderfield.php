<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2014 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   array   $options      Optional parameters
 * @var   string  $id           The id of the input this label is for
 * @var   string  $name         The name of the input this label is for
 * @var   string  $label        The html code for the label
 * @var   string  $input        The input field html code
 * @var   string  $description  An optional description to use as in–line help text
 * @var   string  $descClass    The class name to use for the description
 */

if (!empty($options['showonEnabled']))
{
	/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
	$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
	$wa->useScript('showon');
}

$class           = empty($options['class']) ? '' : ' ' . $options['class'];
$rel             = empty($options['rel']) ? '' : ' ' . $options['rel'];
$id              = ($id ?? $name) . '-desc';
$hideLabel       = !empty($options['hiddenLabel']);
$hideDescription = empty($options['hiddenDescription']) ? false : $options['hiddenDescription'];
$descClass       = ($options['descClass'] ?? '') ?: (!empty($options['inlineHelp']) ? 'hide-aware-inline-help d-none' : '');

if(!empty($parentclass))
{
	$class .= ' ' . $parentclass;
}

$tip = null;
if(!empty($description) && strpos($description, '{tip}') !== false)
{
  $desc_arr    = explode('{tip}',$description);
	$description = $desc_arr[0];
	$tip         = $desc_arr[1];
}

?> 
<div class="control-group<?php echo $class; ?>"<?php echo $rel; ?>>
	<?php if ($hideLabel) : ?>
		<div class="visually-hidden"><?php echo $label; ?></div>
	<?php else : ?>
		<div class="control-label"><?php echo $label; ?></div>
	<?php endif; ?>
	<div class="controls">
		<?php echo $input; ?>
		<?php if (!$hideDescription && !empty($description)) : ?>
			<div id="<?php echo $id; ?>" class="<?php echo $descClass ?>">
				<small class="form-text">
					<?php echo $description; ?>
					<?php if(!empty($tip)) : ?>
						<a data-bs-toggle="collapse" href="#collapseTip_<?php echo $id; ?>" role="button" aria-expanded="false" aria-controls="collapseTip_<?php echo $id; ?>">
							<?php echo Text::_('COM_JOOMGALLERY_FIELDS_TIP_MORE'); ?>
						</a>
					<?php endif; ?>
				</small>
				<?php if(!empty($tip)) : ?>
					<br />
					<small id="collapseTip_<?php echo $id; ?>" class="form-text collapse">
						<?php echo $tip; ?>
					</small>
				<?php endif; ?>
        <?php if($name == 'jform[jg_imgprocessor]') : ?>
          <div class="mt">
            <small id="jg_imgprocessor_supplement" class="form-text"></small>
          </div>          
        <?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
