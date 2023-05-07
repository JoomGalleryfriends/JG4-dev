<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Image;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a single Image.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	protected $state;
	protected $item;
	protected $form;
  protected $config;
  protected $imagetypes;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state      = $this->get('State');
		$this->item       = $this->get('Item');
		$this->form       = $this->get('Form');
    $this->config     = JoomHelper::getService('config');
    $this->imagetypes = JoomHelper::getRecords('imagetypes');

    if($this->item->id == 0)
    {
      // create a new image record
      $this->form->setFieldAttribute('image', 'required', true);
    }

		// Check for errors.
		if(count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

    if($this->_layout == 'upload')
    {
      $this->addToolbarUpload();

      // Add variables to JavaScript
      $js_vars = new \stdClass();
      $js_vars->maxFileSize  = (100 * 1073741824); // 100GB
      $js_vars->TUSlocation  = $this->item->tus_location;
      $js_vars->allowedTypes = $this->getAllowedTypes();

      $js_vars->uppyTarget   = '#drag-drop-area';          // Id of the DOM element to apply the uppy form
      $js_vars->uppyLimit    = 5;                          // Number of concurrent tus upploads (only file upload)
      $js_vars->uppyDelays   = array(0, 1000, 3000, 5000); // Delay in ms between upload retrys

      $js_vars->semaCalls    = $this->config->get('jg_parallelprocesses', 1); // Number of concurrent async calls to save the record to DB (including image processing)
      $js_vars->semaTokens   = 100;                                           // Prealloc space for 100 tokens

      $this->js_vars = $js_vars;
    }
    else
    {
      $this->addToolbarEdit();
    }
		
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar for the image edit form.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbarEdit()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$toolbar = Toolbar::getInstance('toolbar');

		$user  = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if(isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo = JoomHelper::getActions();

		ToolbarHelper::title(Text::_('COM_JOOMGALLERY_IMAGES').' :: '.Text::_('COM_JOOMGALLERY_IMAGE_EDIT'), "image");

		// If not checked out, can save the item.
		if(!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
		{
			ToolbarHelper::apply('image.apply', 'JTOOLBAR_APPLY');
		}

		if(!$checkedOut && ($canDo->get('core.create')))
		{
			$saveGroup = $toolbar->dropdownButton('save-group');

			$saveGroup->configure
            (
				function (Toolbar $childBar) use ($checkedOut, $canDo, $isNew)
				{
					$childBar->save('image.save', 'JTOOLBAR_SAVE');

					if(!$checkedOut && ($canDo->get('core.create')))
					{
						$childBar->save2new('image.save2new');
					}

					// If an existing item, can save to a copy.
					if(!$isNew && $canDo->get('core.create'))
					{
						$childBar->save2copy('image.save2copy');
					}
				}
			);
		}

		if(empty($this->item->id))
		{
			ToolbarHelper::cancel('image.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('image.cancel', 'JTOOLBAR_CLOSE');
		}
	}

  /**
	 * Add the page title and toolbar for the upload form.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbarUpload()
	{
    Factory::getApplication()->input->set('hidemainmenu', true);

    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_IMAGES').' :: '.Text::_('COM_JOOMGALLERY_IMAGES_UPLOAD'), "image");
    ToolbarHelper::cancel('image.cancel', 'JTOOLBAR_CLOSE');

    // Create tus server
    $this->component->createTusServer();
    $server = $this->component->getTusServer();

    $this->item->tus_location = $server->getLocation();
  }

  /**
	 * Get array of all allowed filetypes based on the config parameter jg_imagetypes.
	 *
	 * @return  array  List with all allowed filetypes
	 *
	 */
  protected function getAllowedTypes()
  {
    $types = \explode(',', $this->config->get('jg_imagetypes'));

    // add different types of jpg files
    $jpg_array = array('jpg', 'jpeg', 'jpe', 'jfif');
    if (\in_array('jpg', $types) || \in_array('jpeg', $types) || \in_array('jpe', $types) || \in_array('jfif', $types))
    {
      foreach ($jpg_array as $jpg)
      {
        if(!\in_array($jpg, $types))
        {
          \array_push($types, $jpg);
        }
      }
    }

    // add point to types
    foreach ($types as $key => $type)
    {
      if(\substr($type, 0, 1) !== '.')
      {
        $types[$key] = '.'. \strtolower($type);
      }
      else
      {
        $types[$key] = \strtolower($type);
      }
    }

    return $types;
  }
}
