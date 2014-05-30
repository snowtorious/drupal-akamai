<?php

/**
 * @file
 * Contains \Drupal\akamai\Plugin\Block\AkamaiCacheControl.
 */

namespace Drupal\akamai\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block for clearing Akamai cache.
 *
 * @Block(
 *   id = "akamai_cache_control",
 *   admin_label = @Translation("Akamai Cache Control")
 * )
 */
class AkamaiCacheControl extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('purge akamai cache');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\akamai\Form\CacheControlBlockForm');
  }

}
