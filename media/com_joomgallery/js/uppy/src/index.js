// Script to handle tu uppy upload form

import Uppy from '@uppy/core';
import Dashboard from '@uppy/dashboard';
import Tus from '@uppy/tus';

const { Sema } = require('async-sema');

// initialize formData object
window.formData = false;

// initialize filecounters array
window.filecounters = [];

// initialize sema object
window.sema = new Sema(
  window.uppyVars.semaCalls,
  {
    capacity: window.uppyVars.semaTokens 
  }
);

/**
 * Asynchronous fetch request of form data
 *
 * @param  {String}    formID    The id of the form element
 * @param  {String}    uuid	     The id of the tus upload
 * @param  {String}    fileID    The id of the uploaded file
 *
 * @returns  {String}  Response
 */
async function uploadAjax(formID, uuid, fileID) {
  await window.sema.acquire();

  // initialize variable
  let res = '';

  try {
    // Add text saving to file element
    addText(fileID, Joomla.JText._('COM_JOOMGALLERY_SAVING')+'...');
    
    // Catch form and data
    let form = document.getElementById(formID);
    let formData = window.formData;
    formData.append('jform[uuid]', uuid);
    formData.append('jform[filecounter]', window.filecounters[fileID]);
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

    if (!response.ok) {
      // Catch network error
      return {success: false, status: response.status, message: response.statusText, messages: {}, data: null};
    }

    // Resolve promise as text string
    res = await response.text();

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
    window.sema.release();
  }

  return res;
}

/**
 * Read out the uuid from the upload URL
 *
 * @param  {String}   uploadURL	   The URL
 *
 * @returns  {void}
 */
function getUuid(uploadURL) {
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
 * Create the HTML string for a bootstrap modal
 *
 * @param  {Object}   file        The Uppy file that was uploaded.
 * @param  {Object}   response	  The response of the ajax request to save the file
 *
 * @returns  {String}   The html string of the popup
 */
function createPopup(file, response) {
  // Create popup body
  let popupBody = '';
  if(response.success) {
    popupBody = Joomla.JText._('COM_JOOMGALLERY_SUCCESS_UPPY_UPLOAD').replace('{filename}', file.name);
  } else {
    popupBody = Joomla.JText._('COM_JOOMGALLERY_ERROR_UPPY_UPLOAD').replace('{filename}', file.name);
  }
  if(response.message) {
    popupBody = popupBody + '<br /><br />' + response.message;
  }
  if(response.messages.notice) {
    popupBody = popupBody + '<br /><br />' + response.messages.notice;
  }
  if(response.messages.warning) {
    popupBody = popupBody + '<br /><br />' + response.messages.warning;
  }
  if(response.messages.error) {
    popupBody = popupBody + '<br /><br />' + response.messages.error;
  }

  // Create popup
  let html =    '<div class="joomla-modal modal fade" id="modal'+file.uuid+'" tabindex="-1" aria-labelledby="modal'+file.uuid+'Label" aria-hidden="true">';
  html = html +   '<div class="modal-dialog modal-lg">';
  html = html +      '<div class="modal-content">';
  html = html +           '<div class="modal-header">';
  html = html +               '<h3 class="modal-title" id="modal'+file.uuid+'Label">'+Joomla.JText._('COM_JOOMGALLERY_DEBUG_INFORMATION')+'</h3>';
  html = html +               '<button type="button" class="btn-close novalidate" data-bs-dismiss="modal" aria-label="'+Joomla.JText._('JCLOSE')+'"></button>';
  html = html +           '</div>';
  html = html +           '<div class="modal-body">';
  html = html +               '<div id="'+file.uuid+'-ModalBody">'+popupBody+'</div>';
  html = html +           '</div>';
  html = html +           '<div class="modal-footer">';
  html = html +               '<button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="event.preventDefault()" aria-label="'+Joomla.JText._('JCLOSE')+'">'+Joomla.JText._('JCLOSE')+'</button>';
  html = html +           '</div>';
  html = html +      '</div>';
  html = html +   '</div>';
  html = html + '</div>';

  return html;
}

/**
 * Add debug button to uppy upload form
 *
 * @param  {Object}   file	  The Uppy file that was uploaded.
 * @param  {String}   type	  Button type. (success or danger)
 */
function addBtn(file, type) {
  // Remove old element
  let old = document.getElementById('uppy_'+file.id+'_msgbox');
  if(old) {
    old.remove();
  }

  // Create button element
  let div = document.createElement('div');
  div.setAttribute('id', 'uppy_'+file.id+'_msgbox');
  div.innerHTML = '<button type="button" class="btn btn-'+type+' btn-sm" data-bs-toggle="modal" data-bs-target="#modal'+file.uuid+'">'+Joomla.JText._("COM_JOOMGALLERY_DEBUG_INFORMATION")+'</button>';
  div.classList.add('uppy-Dashboard-Item-debug-msg');
  div.classList.add('success');

  // Add element to form
  document.getElementById('uppy_'+file.id).lastChild.firstChild.appendChild(div);
}

/**
 * Add text message to uppy upload form
 *
 * @param  {Ineger}   fileID	 The Uppy file that was uploaded.
 * @param  {String}   text	   The text content to be added.
 */
function addText(fileID, text) {
  // Remove old element
  let old = document.getElementById('uppy_'+fileID+'_msgbox');
  if(old) {
    old.remove();
  }

  // Create text element
  let div = document.createElement('div');
  div.setAttribute('id', 'uppy_'+fileID+'_msgbox');
  div.innerHTML = '<p>'+text+'</p>';
  div.classList.add('uppy-Dashboard-Item-text-msg');

  // Add element to form
  document.getElementById('uppy_'+fileID).lastChild.firstChild.appendChild(div);
}

/**
 * Add a new title to an uploaded file
 *
 * @param  {Integer}  fileID	 The Uppy file that was uploaded.
 * @param  {String}   title	   The new title to be added
 */
function changeFileTitle(fileID, title) {
  let elem = document.getElementById('uppy_'+fileID).querySelector('.uppy-Dashboard-Item-name');
  elem.innerHTML = title;
}

/**
 * Set an error in a specific uppy file
 * 
 * @param  {String}           error      Error message
 * @param  {object}           uppy       The uppy object
 * @param  {object|Integer}   file       Object or ID of the uppy file
 * @param  {object}           response   Response object
 */
function uppySetFileError(error, uppy, file, response) {
  let errorMsg = error || 'Unknown error';
  let fileID = (typeof file == 'number') ? file : file.id;

  // Add error to global uppy object
  uppy.setState({ error: errorMsg });

  // Add error to specific uppy file
  if (fileID in uppy.getState().files) {
    uppy.setFileState(fileID, {
      error: errorMsg,
      response,
    });
  }
}

/**
 * Cancel the upload
 * 
 * @param  {String}   error      Error message
 * @param  {object}   uppy       The uppy object
 * @param  {string}   reason     The reason why the upload was canceled
 */
function uppyStopAll (error, uppy, { reason = 'user' } = {}) {
  uppy.setState({ error: error });
  uppy.emit('cancel-all', { reason })

  // Only remove existing uploads if user is canceling
  if (reason === 'user') {
    const { files } = uppy.getState()

    const fileIDs = Object.keys(files)
    if (fileIDs.length) {
      uppy.removeFiles(fileIDs, 'cancel-all')
    }
  }
}

var callback = function() {
  // document ready function

  // Initialize the form
  document.getElementById('adminForm').classList.remove('was-validated');
  
  let uppy = new Uppy({
    autoProceed: false,
    restrictions: {
      maxFileSize: window.uppyVars.maxFileSize,
      allowedFileTypes: window.uppyVars.allowedTypes,
    }
  });

  if(uppy != null)
  {
    document.getElementById('drag-drop-area').innerHTML = '';
  }

  uppy.use(Dashboard, {
    inline: true,
    target: window.uppyVars.uppyTarget,
    showProgressDetails: true,
    metaFields: [
      { id: 'title', name: Joomla.JText._('JGLOBAL_TITLE'), placeholder: Joomla.JText._('COM_JOOMGALLERY_FILE_TITLE_HINT')},
      { id: 'description', name: Joomla.JText._('JGLOBAL_DESCRIPTION'), placeholder: Joomla.JText._('COM_JOOMGALLERY_FILE_DESCRIPTION_HINT')},
      { id: 'owner', name: Joomla.JText._('JAUTHOR'), placeholder: Joomla.JText._('COM_JOOMGALLERY_FILE_AUTHOR_HINT')}
    ],
  });

  uppy.use(Tus, {
    endpoint: window.uppyVars.TUSlocation,
    retryDelays: window.uppyVars.uppyDelays,
    allowedMetaFields: null,
    limit: window.uppyVars.uppyLimit
  });

  uppy.on('upload', (data) => {
    // data object consists of `id` with upload ID and `fileIDs` array
    // with file IDs in current upload
    console.log('Starting upload '+data.id+' for files '+data.fileIDs);

    // Initialize the form
    document.getElementById('adminForm').classList.remove('was-validated');

    // Check and validate the form
    let form = document.getElementById('adminForm');
    if(!form.checkValidity()) {
      // Cancel upload if form is not valid
      uppyStopAll(Joomla.JText._('COM_JOOMGALLERY_FILE_AUTHOR_HINT'), uppy);
      console.log('Please fill in the form before starting to upload.');
    }
    form.classList.add('was-validated');

    // When upload starts, save the data of the form
    window.formData = new FormData(form);

    // Add class to file to apply styles during saving process
    for (let i = 0; i < data.fileIDs.length; i++) {
      let item    = document.getElementById('uppy_'+data.fileIDs[i]);
      let preview = item.querySelector('.uppy-Dashboard-Item-preview');
      preview.classList.add('is-saving');

      // Add text uploading to file element
      addText(data.fileIDs[i], Joomla.JText._('COM_JOOMGALLERY_UPLOADING')+'...');

      // Store a global list to store the filecounter
      window.filecounters[data.fileIDs[i]] = i;
    };
  });

  uppy.on('upload-success', (file, response) => {
    // File upload was successful
    console.log('Upload of '+file.name+' successful.');

    // Remove is-complete class from file
    let item    = document.getElementById('uppy_'+file.id);
    let preview = item.querySelector('.uppy-Dashboard-Item-preview');
    item.classList.remove('is-complete');

    // Add text uploading to file element
    addText(file.id, Joomla.JText._('COM_JOOMGALLERY_WAITING')+'...');

    // Resolve uuid
    file.uuid = getUuid(response.uploadURL);

    // Variable to store the save state
    let successful = false;

    // Save the uploaded file to the database 
    uploadAjax('adminForm', file.uuid, file.id).then(response => {
      if(response.success == false)  {
        // Save record failed
        console.log('Save record to database of file '+file.name+' failed.');
        uppySetFileError(Joomla.JText._('COM_JOOMGALLERY_ERROR_UPPY_SAVE_RECORD').replace('{filename}', file.name), uppy, file.id, response);

        // Add text saving to file element
        addText(file.id, 'Saving failed');

        // Add Button to upload form
        addBtn(file, 'danger');
      }
      else  {
        // Save record successful
        console.log('Save record to database of file '+file.name+' successful.');

        // Change save state
        successful = true;

        // Add text saving to file element
        addText(file.id, 'Saving successful');

        // Exchange title of the upload file
        changeFileTitle(file.id, response.data.record.imgtitle);

        // Add Button to upload form
        if(window.formData.get('jform[debug]') == 1) {
          addBtn(file, 'success');
          console.log(response.data.record);
        }
      }

      // Add Popup
      if(!successful || (successful && window.formData.get('jform[debug]') == 1)) {
        let div       = document.createElement('div');
        div.innerHTML = createPopup(file, response);
        document.getElementById('popup-area').appendChild(div);

        new bootstrap.Modal(document.getElementById('modal'+file.uuid));
      }

      // Remove class from file to remove styles from saving process
      preview.classList.remove('is-saving');

      // Add is-complete class to file
      item.classList.add('is-complete');
    });
  });

  uppy.on('upload-error', (file, error, response) => {
    // file upload failed
    console.log('Upload of '+file.name+' failed.');

    // Add text saving to file element
    addText(file.id, 'Upload failed');

    // Add Button to upload form
    addBtn(file, 'danger');

    // Add Popup
    let temp_resp = {success: false};
    let div       = document.createElement('div');
    div.innerHTML = createPopup(file, temp_resp);
    document.getElementById('popup-area').appendChild(div);

    new bootstrap.Modal(document.getElementById('modal'+file.uuid));

    // Remove class from file to remove styles from saving process
    item = document.getElementById('uppy_'+file.id);
    item.classList.remove('is-saving');
  });

  uppy.on('complete', (result) => {
    // complete uppy upload was successful
    console.log('Upload completely successful.');

    // Re-initialize the form
    document.getElementById('adminForm').classList.remove('was-validated');
  });

  uppy.on('error', (error) => {
    // complete uppy upload failed
    console.log('Upload completely failed.');
  });

}; //end callback

if(document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll))
{
  callback();
} else {
  document.addEventListener("DOMContentLoaded", callback);
}
