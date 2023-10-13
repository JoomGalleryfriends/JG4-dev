// Selectors used by this script
let buttonDataSelector = 'btn-migration';
let typeSelector       = 'data-type';
let formIdTmpl         = 'migrationForm';

/**
 * Submit a migration task
 * @param task
 */
let submitTask = function(event, element) {
  event.preventDefault();

  let type   = element.getAttribute(typeSelector);
  let formId = formIdTmpl + '-' + type;  
  let res    = performTask(formId);
};

/**
 * Perform a migration task
 * @param  task
 * 
 * @return  json string
 *          {success: true, status: 200, message: '', messages: {}, data: {}}
 */
let performTask = async function(formId) {

  // Catch form and data
  let formData = new FormData(document.getElementById(formId));
  formData.append('format', 'json');

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
    return {success: false, status: response.status, message: response.message, messages: {}, data: {error: txt}};
  }

  if(txt.startsWith('{"success"')) {
    // Response is of type json --> everything fine
    res = JSON.parse(txt);
    res.status = response.status;
    res.data   = JSON.parse(res.data);
  } else if (txt.includes('Fatal error')) {
    // PHP fatal error occurred
    res = {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: txt}};
  } else {
    // Response is not of type json --> probably some php warnings/notices
    let split = txt.split('\n{"');
    let temp  = JSON.parse('{"'+split[1]);
    let data  = JSON.parse(temp.data);
    res = {success: true, status: response.status, message: split[0], messages: temp.messages, data: data};
  }

  return res;
}
