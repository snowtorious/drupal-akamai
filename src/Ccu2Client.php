<?php

/**
 * @file
 * Constains the Drupal\akamai\Ccu2Client class.
 *
 * This class is used for interacting with v2 of Akamai's CCU API.
 */

namespace Drupal\akamai;

use Akamai\Open\EdgeGrid\Client as EdgeGridClient;
use InvalidArgumentException;

class Ccu2Client implements CcuClientInterface {

  /**
   * The CCU API version.
   */
  const API_VERSION = 2;

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
   * The queue to use when issuing a purge request.
   *
   * @var string
   */
  protected $queuename = 'default';

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
   * Sets the queue name.
   *
   * @param string $queuename
   *   Valid values are 'default' and 'emergency'.
   */
  public function setQueueName($queuename) {
    if ($queuename != 'default' && $queuename != 'emergency') {
      throw new InvalidArgumentException('Invalid queue name supplied.');
    }
    $this->queuename = $queuename;
  }

  /**
   * Gets the number of items in the queue.
   *
   * @return int
   *   The number of pending purge requests in the queue.
   */
  public function getQueueLength() {
    $uri = "/ccu/{$this->version}/queues/{$this->queuename}";
    $response = $this->client->get($uri);
    return json_decode($response->getBody())->queueLength;
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
    $uri = "/ccu/{$this->version}/queues/{$this->queuename}";
    $response = $this->client->post($uri, [
      'body' => $this->getPurgeBody($hostname, $paths, $operation),
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
  protected function getPurgeBody($hostname, $paths, $operation) {
    // Prepend hostname to paths.
    foreach ($paths as $key => $path) {
      $paths[$key] = 'http://' . $hostname . $path;
      $paths[] = 'https://' . $hostname . $path;
    }
    $purge_body = array(
      'action' => $operation,
      'objects' => $paths,
      'domain' => $this->network,
    );
    return json_encode($purge_body);
  }

}
