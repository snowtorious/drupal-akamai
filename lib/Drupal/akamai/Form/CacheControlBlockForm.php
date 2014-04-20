<?php

/**
 * @file
 * Contains \Drupal\akamai\Form\CacheControlBlockForm.
 */

namespace Drupal\akamai\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures Akamai settings.
 */
class CacheControlBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'akamai_cache_control_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $akamai_config = \Drupal::config('akamai.settings');

    $path = check_plain(current_path());

    $nid = arg(1);
  
    if (arg(0) == 'node' && is_numeric($nid) && arg(2) == NULL) {
      $path = arg(0) . '/' . $nid;
      $form['#node'] = node_load($nid);
    }
    else {
      $form['#node'] = NULL;
    }
  
    $path_label = $path;
    if ($path == \Drupal::config('system.site')->get('page.front')) {
      $path_label = t("[frontpage]");
    }
  
    $form['path'] = array(
      '#type'  => 'hidden',
      '#value' => $path
    );
    $form['message'] = array(
      '#type'  => 'item',
      '#title' => t('Refresh URL'),
      '#markup' => $path_label,
    );
    $form['submit'] = array(
      '#type'  => 'submit',
      '#value' => t('Refresh Akamai Cache'),
    );
  
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $values = $form_state['values'];
    $path = $values['path'];
    $did_clear = akamai_clear_url($path, array(), $form['#node']);
    if ($did_clear) {
      $message = t("Akamai Cache Request has been made successfully, please allow 10 minutes for changes to take effect.");
      drupal_set_message($message);
    }
    else {
      drupal_set_message(t("There was a problem clearing the cache for this page.  Check your log messages for more information."), 'error');
    }
  }
}
