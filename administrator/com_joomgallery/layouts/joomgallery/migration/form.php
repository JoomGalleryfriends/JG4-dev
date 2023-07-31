<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

defined('_JEXEC') or die;

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\HTMLHelper;

extract((array) $displayData);

/**
 * Layout variables
 * -----------------
 * @var   string   $scriptName    The name of the migration script.
 * @var   string   $description   The description of the migration script.
 * @var   string   $url           The url, where to process the form.
 * @var   string   $task          The task to be executed when submitting the form.
 * @var   array    $fieldsets     List of fieldsets forming the form.
 * @var   string   $buttonTxt     Text shown in the submit button.
 */

?>

<div class="alert alert-primary" role="alert">
  <?php echo $description; ?>
</div>

<form action="<?php echo $url; ?>" method="post" enctype="multipart/form-data" 
      name="adminForm" id="migration-form" class="form-validate card" aria-label="COM_JOOMGALLERY_MIGRATION_STEP1_TITLE">

  <div class="card-body">
    <?php foreach($fieldsets as $key => $fieldset) : ?>
      <div class="row">
        <div class="col-12 col-lg-9">
          <fieldset class="options-form">
            <legend><?php echo Text::_($fieldset->label); ?></legend>
            <div>
              <?php echo $fieldset->output; ?>
            </div>
          </fieldset>
        </div>
      </div>
    <?php endforeach; ?>

    <input type="hidden" name="task" value="<?php echo $task; ?>"/>
    <input type="hidden" name="script" value="<?php echo $scriptName; ?>"/>
    <?php echo HTMLHelper::_('form.token'); ?>
    <input type="submit" class="btn btn-primary" value="<?php echo $buttonTxt; ?>"/>
  </div>
</form>