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
  protected $network = 'production';

  /**
   * The version of the CCU API.
   */
  protected $version = 'v3';

  /**
   * Constructor.
   *
   * @param \Akamai\Open\EdgeGrid\Client $client
   *   An instance of the EdgeGrid HTTP client class.
   */
  public function __construct(EdgeGridClient $client) {
    $this->client = $client;
  }

  /**
   * Sets the network on which purge requests will be executed.
   *
   * @param string $network
   *   Must be either 'production' or 'staging'.
   */
  public function setNetwork($network) {
    if ($network != 'production' && $network != 'staging') {
      throw new InvalidArgumentException('Invalid queue name supplied.');
    }
    $this->network = $network;
  }

  /**
   * Checks the progress of a purge request.
   *
   * @param string $progress_uri
   *  A URI as provided in response to a purge request.
   */
  public function checkProgress($progress_uri) {
    $response = $this->client->get($progress_uri);
    return json_decode($response->getBody());
  }

  /**
   * Submits a purge request for one or more URLs.
   *
   * @param string $hostname
   *   The name of the URL that contains the objects you want to purge.
   * @param array $paths
   *   An array of paths to be purged.
   * @param string $operation
   *   Should be either 'invalidate' or 'delete'.
   */
  public function postPurgeRequest($hostname, $paths, $operation = 'invalidate') {
    $purge_body = array(
      'hostname' => $hostname,
      'objects' => $paths,
    );

    $uri = "/ccu/{$this->version}/{$operation}/url/{$this->network}";
    $response = $this->client->post($uri, [
      'body' => json_encode($purge_body),
      'headers' => ['Content-Type' => 'application/json']
    ]);
    return json_decode($response->getBody());
  }

  /**
   * Submits a purge request to invalidate a set of URLs.
   *
   * @param string $hostname
   *   The name of the URL that contains the objects you want to purge.
   * @param array $paths
   *   An array of paths to be invalidated.
   */
  public function invalidateUrls($hostname, $paths) {
    return $this->postPurgeRequest($hostname, $paths, 'invalidate');
  }

  /**
   * Submits a purge request to remove/delete a set of URLs.
   *
   * @param string $hostname
   *   The name of the URL that contains the objects you want to purge.
   * @param array $paths
   *   An array of paths to be deleted.
   */
  public function deleteUrls($hostname, $paths) {
    return $this->postPurgeRequest($hostname, $paths, 'delete');
  }

}
