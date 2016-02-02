<?php

/**
 * @file
 * Constains the Drupal\akamai\Ccu3Client class.
 *
 * This class is used for interacting with v3 of Akamai's CCU API.
 */

namespace Drupal\akamai;

use Akamai\Open\EdgeGrid\Client as EdgeGridClient;
use InvalidArgumentException;

class Ccu3Client implements CcuClientInterface {

  /**
   * The CCU API version.
   */
  const API_VERSION = 3;

  /**
   * The string used when invalidating objects.
   */
  const OPERATION_INVALIDATE = 'invalidate';

  /**
   * The string used when removing objects.
   */
  const OPERATION_DELETE = 'remove';

  /**
   * An instance of an OPEN EdgeGrid Client.
   *
   * @var \Akamai\Open\EdgeGrid\Client
   */
  protected $client;

  /**
   * The network to use when issuing purge requests.
   *
   * @var string
   */
  protected $network = self::NETWORK_PRODUCTION;

  /**
   * The version of the CCU API.
   *
   * @var string
   */
  protected $version = 'v' . self::API_VERSION;


  /**
   * Implements CcuClientInterface::__construct().
   */
  public function __construct(EdgeGridClient $client) {
    $this->client = $client;
  }

  /**
   * Implements CcuClientInterface::setNetwork().
   */
  public function setNetwork($network) {
    if ($network != self::NETWORK_PRODUCTION && $network != self::NETWORK_STAGING) {
      throw new InvalidArgumentException('Invalid queue name supplied.');
    }
    $this->network = $network;
  }

  /**
   * Implements CcuClientInterface::checkProgress().
   */
  public function checkProgress($progress_uri) {
    $response = $this->client->get($progress_uri);
    return json_decode($response->getBody());
  }

  /**
   * Implements CcuClientInterface::postPurgeRequest().
   */
  public function postPurgeRequest($hostname, $paths, $operation = self::OPERATION_INVALIDATE) {
    $uri = "/ccu/{$this->version}/{$operation}/url/{$this->network}";
    $response = $this->client->post($uri, [
      'body' => $this->getPurgeBody($hostname, $paths),
      'headers' => ['Content-Type' => 'application/json']
    ]);
    return json_decode($response->getBody());
  }

  /**
   * Implements CcuClientInterface::invalidateUrls().
   */
  public function invalidateUrls($hostname, $paths) {
    return $this->postPurgeRequest($hostname, $paths, self::OPERATION_INVALIDATE);
  }

  /**
   * Implements CcuClientInterface::deleteUrls().
   */
  public function deleteUrls($hostname, $paths) {
    return $this->postPurgeRequest($hostname, $paths, self::OPERATION_DELETE);
  }

  /**
   * Verifies that the body of a purge request will be under 50,000 bytes.
   *
   * @param string $hostname
   *   The name of the URL that contains the objects you want to purge.
   * @param array $paths
   *   An array of paths to be purged.
   * @return bool
   *   TRUE if the body size is below the limit, otherwise FALSE.
   */
  public function bodyIsBelowLimit($hostname, $paths) {
    $body = $this->getPurgeBody($hostname, $paths);
    $bytes = mb_strlen($body, '8bit');
    return $bytes < self::MAX_BODY_SIZE;
  }

  /**
   * Generates a JSON-encoded body for a purge request.
   */
  protected function getPurgeBody($hostname, $paths) {
    $purge_body = array(
      'hostname' => $hostname,
      'objects' => $paths,
    );
    return json_encode($purge_body);
  }

}
