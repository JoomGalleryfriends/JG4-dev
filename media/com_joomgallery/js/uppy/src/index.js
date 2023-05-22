// Script to handle tu uppy upload form

// Modify the prototype's method of the HTMLButton elements
HTMLButtonElement.prototype.realAddEventListener = HTMLButtonElement.prototype.addEventListener;
HTMLButtonElement.prototype.addEventListener = function(a,b,c) {
  if(!this.lastListenerInfo) { this.lastListenerInfo = new Array() };
  this.lastListenerInfo.push({a:a, b:b, c:c});
  this.realAddEventListener(a,b,c);
}

import Uppy from '@uppy/core';
import Dashboard from '@uppy/dashboard';
import Tus from '@uppy/tus';
import JGprocessor from './jgprocessor.js';

/**
 * Apply validity class to catid choices select field
 * 
 * @param  {Boolean}   ini      True to remove all validity classes
 */
function catidFieldValidity (ini = false) {
  let catid = document.getElementById('jform_catid');

  if(ini) {
    catid.parentElement.classList.remove('is-invalid');
    catid.parentElement.classList.remove('is-valid');

    return;
  }

  if(catid.checkValidity()) {
    // is-valid
    catid.parentElement.classList.remove('is-invalid');
    catid.parentElement.classList.add('is-valid');
  }
  else {
    // is-invalid
    catid.parentElement.classList.remove('is-valid');
    catid.parentElement.classList.add('is-invalid');
  }
}

/**
 * Steps to do before start uploading the listed files
 */
function clickUppyUploadBtn (event) {
  let btn = document.querySelector(window.uppyVars.uppyTarget).querySelector('.uppy-StatusBar-actionBtn--upload');
  
  // Initialize the form
  document.getElementById('adminForm').classList.remove('was-validated');
  document.getElementById('system-message-container').innerHTML = '';
  catidFieldValidity(true);

  // Check and validate the form
  let form = document.getElementById('adminForm');
  
  if(!form.checkValidity()) {
    // Form falidation failed
    // Cancel upload, render message
    Joomla.renderMessages({'error':[Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED')+'. '+Joomla.JText._('COM_JOOMGALLERY_ERROR_FILL_REQUIRED_FIELDS')]});
    console.log(Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED')+'. '+Joomla.JText._('COM_JOOMGALLERY_ERROR_FILL_REQUIRED_FIELDS'));
    form.classList.add('was-validated');
    catidFieldValidity();
    window.scrollTo(0, 0);
  }
  else
  {
    // Form falidation successful
    // Start upload
    form.classList.add('was-validated');
    catidFieldValidity();
    window.scrollTo(0, 0);

    // Exchange the event on the uppy upload button
    btn.removeEventListener('click', clickUppyUploadBtn, false);
    btn.addEventListener(btn.lastListenerInfo[0].a, btn.lastListenerInfo[0].b, btn.lastListenerInfo[0].c);

    // Click the button
    btn.click();
  }
}

var callback = function() {
  // document ready function

  // Initialize the form
  document.getElementById('adminForm').classList.remove('was-validated');
  catidFieldValidity(true);
  
  let uppy = new Uppy({
    autoProceed: false,
    onBeforeUpload: (files) => {return onBeforeUpload(files);},
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
      { id: 'jtitle', name: Joomla.JText._('JGLOBAL_TITLE'), placeholder: Joomla.JText._('COM_JOOMGALLERY_FILE_TITLE_HINT')},
      { id: 'jdescription', name: Joomla.JText._('JGLOBAL_DESCRIPTION'), placeholder: Joomla.JText._('COM_JOOMGALLERY_FILE_DESCRIPTION_HINT')},
      { id: 'jauthor', name: Joomla.JText._('JAUTHOR'), placeholder: Joomla.JText._('COM_JOOMGALLERY_FILE_AUTHOR_HINT')}
    ],
  });

  uppy.use(Tus, {
    endpoint: window.uppyVars.TUSlocation,
    retryDelays: window.uppyVars.uppyDelays,
    allowedMetaFields: null,
    limit: window.uppyVars.uppyLimit
  });

  uppy.use(JGprocessor, {
    formID: 'adminForm',
    semaCalls: window.uppyVars.semaCalls,
    semaTokens: window.uppyVars.semaTokens
  });

  /**
   * Function called before upload is initiated
   * Doc: https://uppy.io/docs/uppy/#onbeforeuploadfiles
   *
   * @param   {Array}    files      List of files that will be uploaded
   *
   * @returns {Boolean}  True to continue the upload, false to cancel it
   */
  function onBeforeUpload(files) {
    console.log('onBeforeUpload function');
    console.log(files);

    return true;
  }

  // Change the event happening when clicking the uppy upload button
  uppy.on('file-added', (file) => {
    setTimeout(function() {
      let btn = document.querySelector(window.uppyVars.uppyTarget).querySelector('.uppy-StatusBar-actionBtn--upload');

      // Exchange the event on the uppy upload button
      btn.removeEventListener(btn.lastListenerInfo[0].a, btn.lastListenerInfo[0].b, btn.lastListenerInfo[0].c);
      btn.addEventListener('click', clickUppyUploadBtn, false);
    }, 200);
  });

  uppy.on('complete', (result) => {
    // complete uppy upload was successful
    console.log('Upload completely successful.');

    // Re-initialize the form
    document.getElementById('adminForm').classList.remove('was-validated');
    document.getElementById('system-message-container').innerHTML = '';
    catidFieldValidity(true);
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
