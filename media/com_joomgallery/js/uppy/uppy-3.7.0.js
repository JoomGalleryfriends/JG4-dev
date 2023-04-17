// Script to handle tu uppy upload form

import { Uppy, Dashboard, Tus } from "./uppy-3.7.0.min.js"

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
      { id: 'imgtitle', name: 'Name', placeholder: 'file name'},
      { id: 'imgtext', name: 'Description', placeholder: 'description of the file' },
      { id: 'author', name: 'Author', placeholder: 'author of the file' }
    ],
  });

  uppy.use(Tus, {
    endpoint: window.uppyVars.TUSlocation,
    retryDelays: [0, 1000, 3000, 5000],
    allowedMetaFields: null,
  })

  let getUuid = function(uploadURL) {
    let query = uploadURL.split('?')[1];
    let queryArray = query.split('&');

    for (let i = 0; i < queryArray.length; ++i) {
      if(queryArray[i].includes('uuid')) {
        return queryArray[i].replace('uuid=','');
      }
    }

    return '';
  }

  let createPopup = function(id, content) {
    let html =    '<div class="joomla-modal modal fade" id="modal'+id+'" tabindex="-1" aria-labelledby="modal'+id+'Label" aria-hidden="true">';
    html = html +   '<div class="modal-dialog modal-lg">';
    html = html +      '<div class="modal-content">';
    html = html +           '<div class="modal-header">';
    html = html +               '<h3 class="modal-title" id="modal'+id+'Label">'+Joomla.JText._("COM_JOOMGALLERY_DEBUG_INFORMATION")+'</h3>';
    html = html +               '<button type="button" class="btn-close novalidate" data-bs-dismiss="modal" aria-label="'+Joomla.JText._("JCLOSE")+'"></button>';
    html = html +           '</div>';
    html = html +           '<div class="modal-body">';
    html = html +               '<div id="'+id+'-ModalBody">'+content+'</div>';
    html = html +           '</div>';
    html = html +           '<div class="modal-footer">';
    html = html +               '<button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="event.preventDefault()" aria-label="'+Joomla.JText._("JCLOSE")+'">'+Joomla.JText._("JCLOSE")+'</button>';
    html = html +           '</div>';
    html = html +      '</div>';
    html = html +   '</div>';
    html = html + '</div>';

    return html;
  }

  uppy.on('upload-success', (file, response) => {
    // send form data and add record to database
    console.log(file.name, response.uploadURL)
    let tmp = '';
  })

  uppy.on('complete', (result) => {
    // add message for successful images
    for (let index = 0; index < result.successful.length; ++index) {
      let res = result.successful[index];
      let uuid = getUuid(res.uploadURL);

      // Add Button to upload form
      let btn = document.createElement('div');
      btn.innerHTML = '<button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modal'+uuid+'">'+Joomla.JText._("COM_JOOMGALLERY_DEBUG_INFORMATION")+'</button>';
      btn.classList.add('uppy-Dashboard-Item-debug-msg');
      btn.classList.add('success');
      document.getElementById('uppy_'+res.id).lastChild.firstChild.appendChild(btn);

      // Add Popup
      let div = document.createElement('div');
      div.innerHTML = createPopup(uuid, 'Upload of file "'+res.name+'" using Uppy successful.<br />Upload-ID: '+uuid+'<br />Debug-Info will be added here...');
      document.getElementById('popup-area').appendChild(div);

      new bootstrap.Modal(document.getElementById('modal'+uuid));
    }

    // add message for failed images
    for (let index = 0; index < result.failed.length; ++index) {
      let res = result.failed[index];
      let uuid = getUuid(res.uploadURL);

      // Add Button to upload form
      let btn = document.createElement('div');
      btn.innerHTML = '<button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modal'+uuid+'">'+Joomla.JText._("COM_JOOMGALLERY_DEBUG_INFORMATION")+'</button>';
      btn.classList.add('uppy-Dashboard-Item-debug-msg');
      btn.classList.add('error');
      document.getElementById('uppy_'+res.id).lastChild.firstChild.appendChild(btn);

      // Add Popup
      let div = document.createElement('div');
      div.innerHTML = createPopup(uuid, 'Upload not successful.<br />Debug-Info flow. To be added...');
      document.getElementById('popup-area').appendChild(div);

      new bootstrap.Modal(document.getElementById('modal'+uuid));
    }

    console.log('successful files:', result.successful);
    console.log('failed files:', result.failed);
  });

}; //end callback

if(document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll))
{
  callback();
} else {
  document.addEventListener("DOMContentLoaded", callback);
}
