<?php

/**
 * @file
 * Constains the Drupal\akamai\CcuClientInterface interface.
 *
 * This class is used for interacting with v3 of Akamai's CCU API.
 */

namespace Drupal\akamai;

use Akamai\Open\EdgeGrid\Client as EdgeGridClient;
use InvalidArgumentException;

interface CcuClientInterface {

  /**
   * Constructor.
   *
   * @param \Akamai\Open\EdgeGrid\Client $client
   *   An instance of the EdgeGrid HTTP client class.
   */
  public function __construct(EdgeGridClient $client);

  /**
   * Sets the network on which purge requests will be executed.
   *
   * @param string $network
   *   Must be either 'production' or 'staging'.
   */
  public function setNetwork($network);

  /**
   * Checks the progress of a purge request.
   *
   * @param string $progress_uri
   *  A URI as provided in response to a purge request.
   */
  public function checkProgress($progress_uri);

  /**
   * Submits a purge request for one or more URLs.
   *
   * @param string $hostname
   *   The name of the URL that contains the objects you want to purge.
   * @param array $urls
   *   An array of fully qualified URLs to be purged.
   * @param string $operation
   *   Should be either 'invalidate' or 'delete'.
   */
  public function postPurgeRequest($hostname, $paths, $operation = 'invalidate');

  public function invalidateUrls($hostname, $paths);

  public function deleteUrls($hostname, $paths);

}