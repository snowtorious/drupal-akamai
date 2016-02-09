<?php

/**
 * @file
 *   Akamai is a registered trademark of Akamai Technologies, Inc.
 *   Administrative pages for the Akamai module.
 */

use Akamai\Open\EdgeGrid\Client as EdgeGridClient;
use Drupal\akamai\CcuClientInterface;

/**
 * General Settings for Akamai
 */
function akamai_settings() {
  $form = array();

  $form['disable_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Disable akamai cache clearing'),
    '#description' => t('Set this field to temporarity purge request submissions. Purge requests will still be queued if queueing is enabled. This can be useful when performing imports, migrations, or other batch processes.'),
  );

  $form['disable_fieldset']['akamai_disabled'] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Disable cache clearing'),
    '#default_value' => variable_get('akamai_disabled', FALSE),
  );

  $form['queue'] = array(
    '#type' => 'fieldset',
    '#title' => t('Purge request queue'),
  );
  $form['queue']['akamai_queue_purge_requests'] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Enable queueing of purge requests'),
    '#default_value' => variable_get('akamai_queue_purge_requests', FALSE),
    '#description'   => t('If enabled, purge requests will be queued and submitted when cron runs.'),
  );
  $form['queue']['akamai_queue_on_failure'] = array(
    '#type'          => 'checkbox',
    '#title'         => t('Queue failed purge requests'),
    '#default_value' => variable_get('akamai_queue_on_failure', TRUE),
    '#description'   => t('If a purge request fails, the paths will be added to the queue and re-submitted when cron runs.'),
  );
  $form['queue']['akamai_cron_queue_time_limit'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Cron time limit'),
    '#description'   => t("Items in the queue will stop being processed if this time limit is reached."),
    '#size'          => 5,
    '#maxlength'     => 3,
    '#field_suffix'  => t('seconds'),
    '#default_value' => variable_get('akamai_cron_queue_time_limit', AKAMAI_CRON_QUEUE_TIME_LIMIT_DEFAULT),
    '#required'      => TRUE,
  );

  $form['credentials'] = array(
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#title' => t('API Credentials'),
    '#description' => t('Instructions for obtaining credentials are available at !url.', array('!url' => l('https://developer.akamai.com/introduction/Prov_Creds.html', 'https://developer.akamai.com/introduction/Prov_Creds.html'))),
  );
  $form['credentials']['akamai_credential_storage'] = array(
    '#type'          => 'radios',
    '#title'         => t('Credential storage method'),
    '#default_value' => variable_get('akamai_credential_storage', 'database'),
    '#options'       => array(
      'database' => t('Database'),
      'file'     => t('.edgerc file'),
    ),
    '#required'      => TRUE,
    '#description' => t('Credentials may be stored in the database or in a file. See the README file for more information.')
  );

  $database_field_states = array(
    'required' => array(
      ':input[name="akamai_credential_storage"]' => array('value' => 'database'),
    ),
    'visible' => array(
      ':input[name="akamai_credential_storage"]' => array('value' => 'database'),
    ),
    'optional' => array(
      ':input[name="akamai_credential_storage"]' => array('value' => 'file'),
    ),
    'invisible' => array(
      ':input[name="akamai_credential_storage"]' => array('value' => 'file'),
    ),
  );
  $file_field_states = array(
    'required' => array(
      ':input[name="akamai_credential_storage"]' => array('value' => 'file'),
    ),
    'visible' => array(
      ':input[name="akamai_credential_storage"]' => array('value' => 'file'),
    ),
    'optional' => array(
      ':input[name="akamai_credential_storage"]' => array('value' => 'database'),
    ),
    'invisible' => array(
      ':input[name="akamai_credential_storage"]' => array('value' => 'database'),
    ),
  );
  $form['credentials']['akamai_base_uri'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Base URL'),
    '#default_value' => variable_get('akamai_base_uri', ''),
    '#description'   => t('Should be in for format of %uri', array('%uri' => 'https://akaa-xxxxxxxxxxxxxxxx-xxxxxxxxxxxxxxxx.luna.akamaiapis.net/')),
    '#states' => $database_field_states,
    '#element_validate' => array(
      'akamai_settings_validate_credential_field',
      'akamai_settings_validate_url',
    ),
    '#akamai_credential_type' => 'database',
  );
  $form['credentials']['akamai_access_token'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Access token'),
    '#default_value' => variable_get('akamai_access_token', ''),
    '#description'   => t('Should be in for format of %token', array('%token' => 'akab-xxxxxxxxxxxxxxxx-xxxxxxxxxxxxxxxx')),
    '#states' => $database_field_states,
    '#element_validate' => array('akamai_settings_validate_credential_field'),
    '#akamai_credential_type' => 'database',
  );
  $form['credentials']['akamai_client_token'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Client token'),
    '#default_value' => variable_get('akamai_client_token', ''),
    '#description'   => t('Should be in for format of %token', array('%token' => 'akab-xxxxxxxxxxxxxxxx-xxxxxxxxxxxxxxxx')),
    '#states' => $database_field_states,
    '#element_validate' => array('akamai_settings_validate_credential_field'),
    '#akamai_credential_type' => 'database',
  );
  $form['credentials']['akamai_client_secret'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Client secret'),
    '#default_value' => variable_get('akamai_client_secret', ''),
    '#states' => $database_field_states,
    '#element_validate' => array('akamai_settings_validate_credential_field'),
    '#akamai_credential_type' => 'database',
  );
  $form['credentials']['akamai_edgerc_path'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Path to .edgerc file'),
    '#default_value' => variable_get('akamai_edgerc_path', ''),
    '#states' => $file_field_states,
    '#element_validate' => array(
      'akamai_settings_validate_credential_field',
      'akamai_settings_validate_file_exists',
      'akamai_settings_validate_file_is_readable',
    ),
    '#akamai_credential_type' => 'file',
  );
  $form['credentials']['akamai_edgerc_section'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Section of .edgerc file to use for the CCU API'),
    '#default_value' => variable_get('akamai_edgerc_section', 'default'),
    '#states' => $file_field_states,
    '#element_validate' => array('akamai_settings_validate_credential_field'),
    '#akamai_credential_type' => 'file',
  );

  $default_hostname = parse_url($GLOBALS['base_url'], PHP_URL_HOST);
  $form['akamai_hostname'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Hostname'),
    '#default_value' => variable_get('akamai_hostname', $default_hostname),
    '#description'   => t('The hostname that contains objects (paths) you want to purge, e.g. "www.example.com"'),
    '#required'      => TRUE,
  );
  if (drupal_multilingual()) {
    $form['akamai_use_language_domain'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Use language domain for hostname.'),
      '#default_value' => variable_get('akamai_use_language_domain', FALSE),
      '#description'   => t('For multilingual sites, you may need to purge from multiple hostnames. Checking this box will cause the language domains specified in the <a href="!url">language settings</a> to be used as the hostname when submitting purge requests.', array('!url' => url('admin/config/regional/language'))),
    );
  }

  $form['akamai_timeout'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Timeout length'),
    '#description'   => t("The timeout used by when sending the cache clear request to Akamai's servers. Most users will not need to change this value."),
    '#size'          => 5,
    '#maxlength'     => 3,
    '#default_value' => variable_get('akamai_timeout', EdgeGridClient::DEFAULT_REQUEST_TIMEOUT),
    '#required'      => TRUE,
  );

  $form['akamai_network'] = array(
    '#type'          => 'radios',
    '#title'         => t('Domain'),
    '#default_value' => variable_get('akamai_network', CcuClientInterface::NETWORK_PRODUCTION),
    '#options'       => array(
      CcuClientInterface::NETWORK_STAGING    => t('Staging'),
      CcuClientInterface::NETWORK_PRODUCTION => t('Production'),
    ),
    '#description'   => t('The Akamai network to use for cache clearing.'),
    '#required'      => TRUE,
  );

  $form['akamai_action'] = array(
    '#type'          => 'radios',
    '#title'         => t('Clearing action type default'),
    '#default_value' => variable_get('akamai_action', 'invalidate'),
    '#options'       => array(
      'remove'     => t('Remove'),
      'invalidate' => t('Invalidate'),
    ),
    '#description'   => t('The default clearing action.  The options are <em>remove</em> (which removes the item from the Akamai cache) and <em>invalidate</em> (which leaves the item in the cache, but invalidates it so that the origin will be hit on the next request)'),
    '#required'      => TRUE,
  );

  return system_settings_form($form);
}

/**
 * Sets an error if required credential fields are not filled in.
 */
function akamai_settings_validate_credential_field(&$element, &$form_state, $form) {
  $storage_type = $element['#akamai_credential_type'];
  $is_required = ($form_state['values']['akamai_credential_storage'] == $storage_type);
  $value = $form_state['values'][$element['#name']];
  $is_empty = (is_string($element['#value']) && drupal_strlen(trim($element['#value'])) == 0);
  if ($is_required && $is_empty) {
    form_error($element, t('!name field is required.', array('!name' => $element['#title'])));
  }
  elseif (!$is_required) {
    // This storage method is not being used, so set value back to the default.
    form_set_value($element, $element['#default_value'], $form_state);
  }
}

/**
 * Sets an error if the form element's value is not a valid absolute URL.
 */
function akamai_settings_validate_url($element, &$form_state, $form) {
  $url = $form_state['values'][$element['#name']];
  if (!empty($url) && !valid_url($element['#value'], TRUE)) {
    form_error($element, t('The !name field must be a valid, absolute URL.', array('!name' => $element['#title'])));
  }
}

/**
 * Sets an error if the form element's value is not a path to an existing file.
 */
function akamai_settings_validate_file_exists($element, &$form_state, $form) {
  $path = $form_state['values'][$element['#name']];
  if (!empty($path) && !file_exists($path)) {
    form_error($element, t('Could not find the .edgerc file at %path. Please verify that it exists.', array('%path' => $path)));
  }
}

/**
 * Sets an error if the form element's value is not a path to a readable file.
 */
function akamai_settings_validate_file_is_readable($element, &$form_state, $form) {
  $path = $form_state['values'][$element['#name']];
  if (!empty($path) && !is_readable($path)) {
    form_error($element, t('Could not read the file at %path. Please verify that it is readable.', array('%path' => $path)));
  }
}

/**
 * Validate the akamai_settings form.
 */
function akamai_settings_validate(&$form, &$form_state) {
  // Validate .edgerc file.
  if ($form_state['values']['akamai_credential_storage'] == 'file') {
    $section = $form_state['values']['akamai_edgerc_section'];
    $path = $form_state['values']['akamai_edgerc_path'];
    try {
      $edgegrid_client = EdgeGridClient::createFromEdgeRcFile($section, $path);
    }
    catch (Exception $e) {
      form_set_error('akamai_edgerc_section', t('Could not validate .edgerc file. Exception: %message', array('%message' => $e->getMessage())));
    }
  }

  // check if timeout value is an integer
  $filtered_akamai_timeout = filter_var(
    $form_state['values']['akamai_timeout'],
    FILTER_VALIDATE_INT,
    array('options' => array('min_range' => 1)));
  if (!$filtered_akamai_timeout) {
    form_set_error('akamai_timeout', 'The Timeout Length must be an integer greater than 0.');
  }
}

/**
 * General cache clearing page.
 */
function akamai_cache_control() {
  $form = array();

  $form['paths'] = array(
    '#type'        => 'textarea',
    '#title'       => t('Paths/URLs'),
    '#description' => t('Enter one URL per line. URL entries should be relative to the basepath. (e.g. node/1, content/pretty-title, sites/default/files/some/image.png'),
  );

  $form['domain_override'] = array(
    '#type'          => 'select',
    '#title'         => t('Domain'),
    '#default_value' => variable_get('akamai_network', CcuClientInterface::NETWORK_PRODUCTION),
    '#options'       => array(
      CcuClientInterface::NETWORK_STAGING    => t('Staging'),
      CcuClientInterface::NETWORK_PRODUCTION => t('Production'),
    ),
    '#description'   => t('The Akamai domain to use for cache clearing.  Defaults to the Domain setting from the settings page.')
  );

  $form['refresh'] = array(
    '#type'        => 'radios',
    '#title'       => t('Clearing Action Type'),
    '#default_value' => variable_get('akamai_action', ''),
    '#options'     => array(
      'remove'     => t('Remove'),
      'invalidate' => t('Invalidate'),
    ),
    '#description' => t('<b>Remove:</b> Purge the content from Akamai edge server caches. The next time the edge server receives a request for the content, it will retrieve the current version from the origin server. If it cannot retrieve a current version, it will follow instructions in your edge server configuration.<br/><br/><b>Invalidate:</b> Mark the cached content as invalid. The next time the Akamai edge server receives a request for the content, it will send an HTTP conditional get (If-Modified-Since) request to the origin. If the content has changed, the origin server will return a full fresh copy; otherwise, the origin normally will respond that the content has not changed, and Akamai can serve the already-cached content.<br/><br/><b>Note that <em>Remove</em> can increase the load on the origin more than <em>Invalidate</em>.</b> With <em>Invalidate</em>, objects are not removed from cache and full objects are not retrieved from the origin unless they are newer than the cached versions.'),
  );

  $form['submit'] = array(
    '#type'  => 'submit',
    '#value' => t('Start Refreshing Content'),
  );

  return $form;
}


/**
 * Submit handler for akamai_cache_control().
 *
 * Process the settings and submit to Akamai
 */
function akamai_cache_control_submit($form, &$form_state) {
  // filter_xss() does not need to be used here as it turns the & symbol into
  // an HTML entity and v3 rejects it. We don't need HTML entites here as this
  // data won't be used in actual HTML, just API requests.
  $paths = explode("\n", $form_state['values']['paths']);
  $action = $form_state['values']['refresh'];

  $overrides = array(
    'action' => $form_state['values']['refresh'],
    'domain' => $form_state['values']['domain_override']
  );

  if ($result = akamai_clear_url($paths, $overrides)) {
    $message = t("Akamai Cache Request has been made successfully.") . theme("item_list", $paths);
    $status = 'status';

    if (is_object($result) && !empty($result->data)) {
      if ($akamai_response_data = json_decode($result->data)) {
        if (isset($akamai_response_data->httpStatus) && $akamai_response_data->httpStatus > 300) {
          $message = t("There was a problem with your cache clearing request.  The error message returned was '@msg'",
            array('@msg' => $akamai_response_data->details));
          $status = 'error';
        }
        else {
          $message = t("Akamai Cache Request has been made successfully.  Akamai reports an estimated time to completion of @time",
            array('@time' => format_interval($akamai_response_data->estimatedSeconds))) . theme("item_list", $paths);
        }
      }
      else {
        $message = t('There was a problem with your cache clearing request. Please check the watchdog logs for details.');
        $status = 'error';

        watchdog('akamai', 'Unable to parse Akamai API response data: @json_data',
          array('@json_data' => '<pre>' . print_r($result->data, TRUE) . '</pre>'), WATCHDOG_ERROR);
      }
    }
  }
  else {
    $message = t('There was a problem with your cache clearing request. Please check the watchdog logs for details.');
    $status = 'error';
  }

  drupal_set_message($message, $status);
}

