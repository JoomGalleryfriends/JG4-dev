// Selectors used by this script
let typeSelector = 'data-type';
let formIdTmpl   = 'migrationForm';

/**
 * Storage for migrateables
 * @var {Object}  migrateablesList
 */
let migrateablesList = {};

/**
 * Submit a migration task
 * 
 * @param {Object}  event     Event object
 * @param {Object}  element   DOM element object
 */
export let submitTask = function(event, element) {
  event.preventDefault();

  let type   = element.getAttribute(typeSelector);
  let formId = formIdTmpl + '-' + type;
  let task   = element.parentNode.querySelector('[name="task"]').value;

  ajax(formId, task)
    .then(res => {
      // Handle the successful result here
      responseHandler(res);
    })
    .catch(error => {
      // Handle any errors here
      console.error(error);
    });
};

/**
 * Perform an ajax request in json format
 * 
 * @param   {String}   formId   Id of the form element
 * @param   {String}   task     Name of the task
 * 
 * @returns {Object}   Result object
 *          {success: true, status: 200, message: '', messages: {}, data: { {success: bool, message: string, data: mixed} }}
 */
let ajax = async function(formId, task) {

  // Catch form and data
  let formData = new FormData(document.getElementById(formId));
  formData.append('format', 'json');

  if(task == 'migration.start') {
    formData.append('id', getNextMigrationID(formId));
  }

  // Set request parameters
  let parameters = {
    method: 'POST',
    mode: 'same-origin',
    cache: 'default',
    redirect: 'follow',
    referrerPolicy: 'no-referrer-when-downgrade',
    body: formData,
  };

  // Set the url
  let url = document.getElementById(formId).getAttribute('action');

  // Perform the fetch request
  let response = await fetch(url, parameters);

  // Resolve promise as text string
  let txt = await response.text();
  let res = null;

  if (!response.ok) {
    // Catch network error
    console.log(txt);
    return {success: false, status: response.status, message: response.message, messages: {}, data: {message: txt}};
  }

  if(txt.startsWith('{"success"')) {
    // Response is of type json --> everything fine
    res = JSON.parse(txt);
    res.status = response.status;
    res.data   = JSON.parse(res.data);
  } else if (txt.includes('Fatal error')) {
    // PHP fatal error occurred
    res = {success: false, status: response.status, message: response.statusText, messages: {}, data: {message: txt}};
  } else {
    // Response is not of type json --> probably some php warnings/notices
    let split = txt.split('\n{"');
    let temp  = JSON.parse('{"'+split[1]);
    let data  = JSON.parse(temp.data);
    res = {success: true, status: response.status, message: split[0], messages: temp.messages, data: data};
  }

  return res;
}

/**
 * Perform a migration task
 * @param   {String}   formId   Id of the form element
 * 
 * @returns {String}   Id of the database record to be migrated
 */
let getNextMigrationID = function(formId) {
  let type  = formId.replace(formIdTmpl + '-', '');
  let form  = document.getElementById(formId);

  let migrateable = atob(form.querySelector('[name="migrateable"]').value);
  migrateable = JSON.parse(migrateable);

  // Overwrite migrateable in list
  migrateablesList[type] = migrateable;

  // Loop through queue
  for (let id of migrateable.queue) {
    if (!(id in migrateable.successful) && !(id in migrateable.failed)) {
      migrateablesList[type]['currentID'] = id;
      break;
    }
  }

  return migrateablesList[type]['currentID'];
}

/**
 * Handle migration response
 * 
 * @param   {Object}   response   The response object in the form of
 *          {success: true, status: 200, message: '', messages: {}, data: { {success: bool, message: string, data: mixed} }}
 * 
 * @returns void
 */
let responseHandler = function(response) {
  console.log(response);
}