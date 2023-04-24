// Script to handle tu uppy upload form

import Uppy from '@uppy/core';
import Dashboard from '@uppy/dashboard';
import Tus from '@uppy/tus';

const { Sema } = require('async-sema');

// initialize formData object
window.formData = false;

// initialize sema object
window.sema = new Sema(
  4, // Allow 4 concurrent async calls
  {
    capacity: 100 // Prealloc space for 100 tokens
  }
);

/**
 * Asynchronous fetch request of form data
 *
 * @param  {Integer}   formID    The id of the form element
 * @param  {String}    uuid	     The id of the uploaded element
 *
 * @returns  {String}  Response
 */
async function uploadAjax(formID, uuid) {
  await window.sema.acquire();

  // initialize variable
  let res = '';

  try {
    // Catch form and data
    let form = document.getElementById(formID);
    let formData = window.formData;
    formData.append('jform[uuid]', uuid);
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
    popupBody = 'Upload of file "'+file.name+'" using Uppy successful.';
  } else {
    popupBody = 'Upload of file "'+file.name+'" failed.';
  }
  if(response.message) {
    popupBody = popupBody + '<br /><br />' + response.message;
  }
  if(response.messages.notice && popupBody == '') {
    popupBody = popupBody + '<br /><br />' + response.messages.notice;
  }
  if(response.messages.warning && popupBody == '') {
    popupBody = popupBody + '<br /><br />' + response.messages.warning;
  }
  if(response.messages.error && popupBody == '') {
    popupBody = popupBody + '<br /><br />' + response.messages.error;
  }

  // Create popup
  let html =    '<div class="joomla-modal modal fade" id="modal'+file.uuid+'" tabindex="-1" aria-labelledby="modal'+file.uuid+'Label" aria-hidden="true">';
  html = html +   '<div class="modal-dialog modal-lg">';
  html = html +      '<div class="modal-content">';
  html = html +           '<div class="modal-header">';
  html = html +               '<h3 class="modal-title" id="modal'+file.uuid+'Label">'+Joomla.JText._("COM_JOOMGALLERY_DEBUG_INFORMATION")+'</h3>';
  html = html +               '<button type="button" class="btn-close novalidate" data-bs-dismiss="modal" aria-label="'+Joomla.JText._("JCLOSE")+'"></button>';
  html = html +           '</div>';
  html = html +           '<div class="modal-body">';
  html = html +               '<div id="'+file.uuid+'-ModalBody">'+popupBody+'</div>';
  html = html +           '</div>';
  html = html +           '<div class="modal-footer">';
  html = html +               '<button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="event.preventDefault()" aria-label="'+Joomla.JText._("JCLOSE")+'">'+Joomla.JText._("JCLOSE")+'</button>';
  html = html +           '</div>';
  html = html +      '</div>';
  html = html +   '</div>';
  html = html + '</div>';

  return html;
}

/**
 * Add button to uppy upload form
 *
 * @param  {Object}   file	  The Uppy file that was uploaded.
 * @param  {String}   type	  Button type. success or danger
 */
function createBtn(file, type) {
  // Create button
  let btn = document.createElement('div');
  btn.innerHTML = '<button type="button" class="btn btn-'+type+' btn-sm" data-bs-toggle="modal" data-bs-target="#modal'+file.uuid+'">'+Joomla.JText._("COM_JOOMGALLERY_DEBUG_INFORMATION")+'</button>';
  btn.classList.add('uppy-Dashboard-Item-debug-msg');
  btn.classList.add('success');

  // Add button to form
  document.getElementById('uppy_'+file.id).lastChild.firstChild.appendChild(btn);
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
  
  let uppy = new Uppy({
    autoProceed: false,
    restrictions: {
      maxFileSize: window.uppyVars.maxFileSize,
      allowedFileTypes: ['image/*', 'video/*', 'audio/*', 'text/*', 'application/*'],
    }
  });

  if(uppy != null)
  {
    document.getElementById('drag-drop-area').innerHTML = '';
  }

  uppy.use(Dashboard, {
    inline: true,
    target: '#drag-drop-area',
    showProgressDetails: true,
    metaFields: [
      { id: 'imgtitle', name: Joomla.JText._("JGLOBAL_TITLE"), placeholder: Joomla.JText._("COM_JOOMGALLERY_FILE_TITLE_HINT")},
      { id: 'imgtext', name: Joomla.JText._("JGLOBAL_DESCRIPTION"), placeholder: Joomla.JText._("COM_JOOMGALLERY_FILE_DESCRIPTION_HINT")},
      { id: 'author', name: Joomla.JText._("JAUTHOR"), placeholder: Joomla.JText._("COM_JOOMGALLERY_FILE_AUTHOR_HINT")}
    ],
  });

  uppy.use(Tus, {
    endpoint: window.uppyVars.TUSlocation,
    retryDelays: [0, 1000, 3000, 5000],
    allowedMetaFields: null,
  });

  uppy.on('upload', (data) => {
    // data object consists of `id` with upload ID and `fileIDs` array
    // with file IDs in current upload
    console.log('Starting upload '+data.id+' for files '+data.fileIDs);

    // Check and validate the form
    let form = document.getElementById('image-form');
    if(!form.checkValidity()) {
      // Cancel upload if form is not valid
      uppyStopAll('Please fill in the form first!', uppy);
      console.log('Please fill in the form first!');
    }
    form.classList.add('was-validated');

    // When upload starts, save the data of the form
    window.formData = new FormData(form);
  });

  uppy.on('upload-success', (file, response) => {
    // file upload was successful
    console.log('Upload of '+file.name+' successful.');

    // Resolve uuid
    file.uuid = getUuid(response.uploadURL);

    // Save the uploaded file to the database 
    uploadAjax('image-form', file.uuid).then(response => {
      if(response.success == false)  {
        // Save record failed
        console.log('Save record to database of file '+file.name+' failed.');
        uppySetFileError('Save record to database of file '+file.name+' failed.', uppy, file.id, response);

        // Add Button to upload form
        createBtn(file, 'danger');
      }
      else  {
        // Save record successful
        console.log('Save record to database of file '+file.name+' successful.');

        // Add Button to upload form
        createBtn(file, 'success');
      }

      // Add Popup
      let div       = document.createElement('div');
      div.innerHTML = createPopup(file, response);
      document.getElementById('popup-area').appendChild(div);

      new bootstrap.Modal(document.getElementById('modal'+file.uuid));
    });
  });

  uppy.on('upload-error', (file, error, response) => {
    // file upload failed
    console.log('Upload of '+file.name+' failed.');
  });

  uppy.on('complete', (result) => {
    // complete uppy upload was successful
    console.log('Upload was successful.');
  });

  uppy.on('error', (error) => {
    // complete uppy upload failed
    console.log('Upload failed.');
  });

}; //end callback

if(document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll))
{
  callback();
} else {
  document.addEventListener("DOMContentLoaded", callback);
}
