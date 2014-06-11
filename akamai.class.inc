<?php
/**
 * @file
 * akamai.class.inc
 *
 * Akamai is a registered trademark of Akamai Technologies, Inc.
 * This class is an abstraction around the Akamai Cache Control API.
 */

interface AkamaiCacheControl {
  /**
   * Clears the provided URLs from the Akamai Content Cache.
   *
   * @param array $paths
   *   A path (or array of paths) to clear from Akamai
   *
   * @return array
   *   An array with 2 keys:
   *     success: TRUE or FALSE indicating cache clearing success
   *     message: Blank if successful, the error message if not successful.
   */
  function clear_url($paths);
}

/**
 * Default implementation of the AkamaiCacheControl interface
 */
class AkamaiCacheControlClient implements AkamaiCacheControl {
  // akamai_get_defaults();
  private $defaults;

  public $parameters;

  /**
   * Constructs an instance of the Akamai Cache Control facade.
   *
   * Valid parameters are specified in the options array as key/value pairs with
   * the parameter name being the key and the parameter setting being the value
   *
   * @param array $options
   *   An array of parameter options for the Akamae Cache Control Web Service.
   *   These will override the defaults.
   */
  function __construct($options = array()) {
    $this->defaults = array(
      'basepath'  => variable_get("akamai_basepath", ""),
      'name'      => variable_get("akamai_username", ""),
      'pwd'       => variable_get("akamai_password", ""),
      'action'    => variable_get("akamai_action", ""),
      'type'      => "arl",
      'domain'    => variable_get("akamai_domain", ""),
      'restapi'   => variable_get("akamai_restapi", ""),
      'email'     => variable_get("akamai_email", ""),
    );

    $this->parameters = array_merge($this->defaults, $options);
  }

  /**
   * Clears the provided URLs from the Akamai Content Cache.
   *
   * @param array $paths
   *   A path (or array of paths) to clear from Akamai
   *
   * @return array
   *   An array with 2 keys:
   *     success: TRUE or FALSE indicating cache clearing success
   *     message: Blank if successful, the error message if not successful.
   */
  function clear_url($paths) {

    // Grab paramaters.
    extract($this->parameters);

    // Make sure $paths is an array.
    if (!is_array($paths)) {
      $url = array($paths);
    }

    // Prepend base path to paths to make URIs.
    $uris = array();
    foreach ($paths as $path) {
      $path = rtrim(preg_match("/^\//", $path) ? $path : "/$path");
      array_push($uris, $basepath . $path);
    }

    $opt = array(
      "action=$action",
      "domain=$domain",
      "type=$type",
    );

    if (!empty($email) && $email != AKAMAI_EMAIL_DISABLE) {
      $opt[] = "email-notification=$email";
    }

    $data = array(
      "type" => "arl",
      "action" => "$action",
      "domain" => "$domain",
      "objects" => $uris,
    );
    $data_string = json_encode($data);
    $data_string = str_replace("\\/", '/', $data_string);

    // URL needs to be in the format of scheme://user:pass@url.
    $url_parsed = parse_url($restapi);
    $url = $url_parsed['scheme'] . '://' . $name . ':' . $pwd . '@' . $url_parsed['host'] . $url_parsed['path'];

    $headers = array(
      'Content-Type' => 'application/json',
      'Content-Length' => strlen($data_string),
    );

    $response = drupal_http_request($url, $headers, 'POST', $data_string, 3, 5);

    $is_success = ($response->code < 300);
    if (!$is_success) {
      throw new \Exception($response->data);
    }
    $response_data = json_decode($response->data);

    watchdog('Akamai', "Akamai RestAPI %action for %uris: Response: %response", array(
      '%uris' => implode(' ', $uris),
      '%action' => $action,
      '%response' => $response_data->detail),
    WATCHDOG_NOTICE);

    return $response;
  }
}

/**
 * Implementation of the AkamaiCacheControl interface that records what was sent
 * to it.
 *
 * Hint: This is only useful for testing.
 */
class RecordingCacheControlClient implements AkamaiCacheControl {

  public $paths;
  public $options;

  /**
   * Constructs an instance of the Akamai Cache Control facade.
   */
  function __construct($options = array()) {
    $this->options = $options;
  }

  /**
   * Records the paths passed in.
   */
  function clear_url($paths) {
    if (!is_array($paths)) {
      $paths = array($paths);
    }
    $this->paths = $paths;

    return array(
      'success' => TRUE,
      'code' => '',
      'message' => '',
    );
  }
}

/**
 * Implementation of the AkamaiCacheControl interface that throws Exceptions.
 * Hint: This is only useful for testing.
 */
class ErrorCacheControlClient implements AkamaiCacheControl {

  /**
   * Constructs an instance of the Akamai Cache Control facade.
   */
  function __construct($options = array()) {
  }

  /**
   * Records the paths passed in.
   */
  function clear_url($paths) {
    throw new \Exception(t('Akamai Failure Condition Test'));
  }
}
