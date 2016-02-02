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
   * @var \Akamai\Open\EdgeGrid\Client
   */
  protected $client;

  /**
   * The network to use when issuing purge requests.
   *
   * @var string
   */
  protected $network = 'production';

  /**
   * The version of the CCU API.
   */
  protected $version = 'v2';

  /**
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
    if ($network != 'production' && $network != 'staging') {
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
  public function postPurgeRequest($hostname, $paths, $operation = 'invalidate') {
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
    ///ccu/v2/queues/{queuename}
    $uri = "/ccu/{$this->version}/queues/{$this->queuename}";
    $response = $this->client->post($uri, [
      'body' => json_encode($purge_body),
      'headers' => ['Content-Type' => 'application/json']
    ]);
    return json_decode($response->getBody());
  }

  /**
   * Implements CcuClientInterface::invalidateUrls().
   */
  public function invalidateUrls($hostname, $paths) {
    return $this->postPurgeRequest($hostname, $paths, 'invalidate');
  }

  /**
   * Implements CcuClientInterface::deleteUrls().
   */
  public function deleteUrls($hostname, $paths) {
    return $this->postPurgeRequest($hostname, $paths, 'delete');
  }

}
