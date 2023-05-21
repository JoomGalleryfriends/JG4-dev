import { BasePlugin } from '@uppy/core';
import Translator from '@uppy/utils/lib/Translator';

export default class JGPostProcessor extends BasePlugin {
  /**
   * Constructor
   * Doc: https://uppy.io/docs/guides/building-plugins/#creating-a-plugin
   *
   * @param   {Object}   uppy	   Uppy instance
   * @param   {Object}   opts	   Options object passed in to the uppy.use()
   *
   * @returns {Viod}
   * @throws Error
   */
  constructor(uppy, opts) {
    super(uppy, opts);
    this.type     = 'saver';
    this.id       = this.opts.id || 'JGPostProcessor';
    this.title    = 'JGPostProcessor';
    this.sema     = this.opts.sema || false;
    this.uploadID = this.opts.uploadID || '';

    if(!this.sema) {
      throw new Error('Sema has to be provided as option to be able to use JGPostProcessor plugin!');
    }

    // Bind functions to the plugin object
    this.awaitSaveRequest = this.awaitSaveRequest.bind(this);
    this.startSaveRequest = this.startSaveRequest.bind(this);
    this.resolveUuid      = this.resolveUuid.bind(this);
    this.sendFetchRequest = this.sendFetchRequest.bind(this);

    // Define language strings
    this.defaultLocale = {
      strings: {
          savingImages: 'Saving image to database...',
      },
    };

    // Initialize finished files array
    this.finishedFiles = new Array();

    //delete this.opts.success;
    //delete this.opts.error;

    this.i18nInit();
  }

  /**
   * Setter for options
   *
   * @param   {Object}   newOpts	  Options object
   *
   * @returns {Viod}
   */
  setOptions (newOpts) {
    super.setOptions(newOpts);
  }

  /**
   * Read out the uuid from the upload URL
   *
   * @param   {String}   uploadURL	   The URL
   *
   * @returns {String}   The uuid
   */
  resolveUuid(uploadURL) {
    let query = uploadURL.split('?')[1];
    let queryArray = query.split('&');

    for (let i = 0; i < queryArray.length; ++i) {
      if(queryArray[i].includes('uuid')) {
        return queryArray[i].replace('uuid=','');
      }
    }

    return '';
  }

  /**
   * Sends an asynchronous fetch request with form data from formID
   * for a corresponding file
   *
   * @param   {String}   formID      ID of the form
   * @param   {String}   tusID       ID of the tus connection (upload)
   * @param   {String}   fileID      ID of the corresponding file
   *
   * @returns {Object}   Result object 
   *                     {success: bool, status: int, message: string, messages: array, data: object}
   *                      data: response data created by JSON.parse
   */
  async sendFetchRequest(formID, tusID, fileID) {
    await this.sema.acquire();

    // initialize variable
    let res = {error: 'Nothing sent...'};

    try {
      // Catch form and data
      let form = document.getElementById(formID);
      let formData = window.formData;
      formData.append('jform[uuid]', tusID);
      formData.append('jform[filecounter]', window.filecounters[fileID]);
      if(formData.get('jform[imgtext]').trim().length === 0) {
        // Receive text content from editor
        let txt = Joomla.editors.instances['jform_imgtext'].getValue();
        formData.set('jform[imgtext]', txt);
      }
      let url = form.getAttribute('action');

      // Set request parameters
      let parameters = {
        method: 'POST',
        mode: 'same-origin',
        cache: 'default',
        redirect: 'follow',
        referrerPolicy: 'no-referrer-when-downgrade',
        body: formData,
      };

      // Perform the fetch request
      let response = await fetch(url, parameters);

      // Resolve promise as text string
      res = await response.text();

      if (!response.ok) {
        // Catch network error
        console.log(res);
        return {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: res}};
      }

      if(res.startsWith('{"success"')) {
        // Response is of type json --> everything fine
        res = JSON.parse(res);
        res.status = response.status;
        res.data   = JSON.parse(res.data);
      } else {
        // Response is not of type json --> probably some php warnings/notices
        let split = res.split('\n{"');
        let temp  = JSON.parse('{"'+split[1]);
        let data  = JSON.parse(temp.data);
        res = {success: true, status: response.status, message: split[0], messages: temp.messages, data: data};
      }

    } finally {
      this.sema.release();
    }
  
    return res;
  }

  /**
   * Start the image records saving process
   *
   * @param   {UppyFile} file        Uppy file object
   * @param   {Object}   response    Response object
   *
   * @returns {Void}
   */
  startSaveRequest = (file, response) => {
    if(this.uploadID == '' || this.uploadID != this.resolveUuid(response.uploadURL)) {
      this.uploadID = this.resolveUuid(response.uploadURL);
    }

    // File upload was successful
    console.log('Upload of '+file.name+' successful.');

    // Variable to store the save state
    let successful = false;

    // Save the uploaded file to the database 
    this.sendFetchRequest('adminForm', this.uploadID, file.id).then(response => {
      if(response.success == false)  {
        // Ajax request failed
        console.log('Ajax request for file '+file.name+' failed.');
        console.log(response.message);
        console.log(response.messages);
        //uppySetFileError(Joomla.JText._('COM_JOOMGALLERY_ERROR_UPPY_SAVE_RECORD').replace('{filename}', file.name), this.uppy, file.id, response);

        // Add text saving to file element
        //addText(file.id, 'Saving failed');

        // Add Button to upload form
        //addBtn(file, 'danger');
      }
      else  {
        // Ajax request successful
        if(!response.data.success)
        {
          // Save record failed
          console.log('Save record to database of file '+file.name+' failed.');
          console.log(response.data.error);
          //uppySetFileError(Joomla.JText._('COM_JOOMGALLERY_ERROR_UPPY_SAVE_RECORD').replace('{filename}', file.name), uppy, file.id, response);

          // Add text saving to file element
          //addText(file.id, 'Saving failed');

          // Add Button to upload form
          //addBtn(file, 'danger');
        }
        else
        {
          // Save record successful
          console.log('Save record to database of file '+file.name+' successful.');

          // Change save state
          successful = true;

          // Add text saving to file element
          //addText(file.id, 'Saving successful');

          // Exchange title of the upload file
          //changeFileTitle(file.id, response.data.record.imgtitle);

          // Add Button to upload form
          // if(window.formData.get('jform[debug]') == 1) {
          //   addBtn(file, 'success');
          //   console.log(response.data.record);
          // }
        }
      }

      // Add file ID to array of finished files
      this.finishedFiles.push(file.id);
    });
  }

  /**
   * Postprocessing function which is hooked into the uppy pipeline
   * Doc: https://uppy.io/docs/guides/building-plugins/#upload-hooks
   *
   * @param   {Array}    fileIDs     List of file IDs that are being uploaded
   * @param   {String}   uploadID    ID of the current upload 
   *
   * @returns {Promise}  Promise to signal completion
   */
  async awaitSaveRequest(fileIDs, uploadID) {

    // If we're still restoring state, wait for that to be done.
    if (this.restored) {
      return this.restored.then(() => {
        return this.awaitSaveRequest(fileIDs, uploadID)
      })
    }



    const promises = fileIDs.map((fileID) => {
      
      // Start request handling
      const file = this.uppy.getFile(fileID);

      this.uppy.emit('postprocess-progress', file, {
        mode: 'indeterminate',
        message: this.i18n('savingImages'),
      });

      return this.sendFetchRequest('adminForm', uploadID, fileID).then((fileResponse) => {
        this.uppy.log(`[PostProcessor] File ${file.id} updated sucessfully`);
        this.uppy.setFileState(fileID, { fileInfo: fileResponse });
      }).catch((error) => {
        this.uppy.setFileState(fileID, { error });
        this.uppy.log(`[PostProcessor] Failed to update ${file.id} record`, 'error');
        this.uppy.log(error, 'error');
      });
    });

    const emitPostprocessCompleteForAll = () => {
      fileIds.forEach((fileId) => {
        const file = this.uppy.getFile(fileID);
        this.uppy.emit('postprocess-complete', file);
      })
    };

    return Promise.all(promises)
      .then(emitPostprocessCompleteForAll);
  }

  /**
   * Installation of the plugin
   * Doc: https://uppy.io/docs/guides/building-plugins/#install
   *
   * @returns {Void}
   */
  install () {
    this.uppy.addPostProcessor(this.awaitSaveRequest);
    this.uppy.on('upload-success', this.startSaveRequest);
  }

  /**
   * Deinstallation of the plugin
   * Doc: https://uppy.io/docs/guides/building-plugins/#uninstall
   *
   * @returns {Void}
   */
  uninstall () {
    this.uppy.removePostProcessor(this.awaitSaveRequest);
  }
}