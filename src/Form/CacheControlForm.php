<?php

/**
 * @file
 * Contains \Drupal\akamai\Form\CacheControlForm.
 */

namespace Drupal\akamai\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures Akamai settings.
 */
class CacheControlForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'akamai_cache_control_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $akamai_config = \Drupal::config('akamai.settings');

    $form['paths'] = array(
      '#type'        => 'textarea',
      '#title'       => t('Paths/URLs'),
      '#description' => t('Enter one URL per line. URL entries should be relative to the basepath. (e.g. node/1, content/pretty-title, sites/default/files/some/image.png'),
    );

    $form['domain_override'] = array(
      '#type'          => 'select',
      '#title'         => t('Domain'),
      '#default_value' => $akamai_config->get('domain'),
      '#options'       => array(
        'staging'    => t('Staging'),
        'production' => t('Production'),
      ),
      '#description'   => t('The Akamai domain to use for cache clearing.  Defaults to the Domain setting from the settings page.')
    );

    $form['refresh'] = array(
      '#type'        => 'radios',
      '#title'       => t('Clearing Action Type'),
      '#default_value' => $akamai_config->get('action'),
      '#options'     => array(
        'remove'     => t('Remove'),
        'invalidate' => t('Invalidate'),
      ),
      '#description' => t('<b>Remove:</b> Purge the content from Akamai edge server caches. The next time the edge server receives a request for the content, it will retrieve the current version from the origin server. If it cannot retrieve a current version, it will follow instructions in your edge server configuration.<br/><br/><b>Invalidate:</b> Mark the cached content as invalid. The next time the Akamai edge server receives a request for the content, it will send an HTTP conditional get (If-Modified-Since) request to the origin. If the content has changed, the origin server will return a full fresh copy; otherwise, the origin normally will respond that the content has not changed, and Akamai can serve the already-cached content.<br/><br/><b>Note that <em>Remove</em> can increase the load on the origin more than <em>Invalidate</em>.</b> With <em>Invalidate</em>, objects are not removed from cache and full objects are not retrieved from the origin unless they are newer than the cached versions.'),
    );

    $default_email = akamai_get_notification_email();
    $form['email'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Email Notification'),
      '#default_value' => $default_email,
      '#description'   => t('Email address to be used for cache clear notifications. Note that it can take up to 10 minutes or more to receive a notification.')
    );
    $form['submit'] = array(
      '#type'  => 'submit',
      '#value' => t('Start Refreshing Content'),
    );

  return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: probably should have some validation here
   */
  public function validateForm(array &$form, array &$form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $paths = explode("\n", filter_xss($form_state['values']['paths']));
    $action = $form_state['values']['refresh'];

    $overrides = array(
      'action' => $form_state['values']['refresh'],
      'domain' => $form_state['values']['domain_override'],
      'email'  => $form_state['values']['email'],
    );

    $did_clear = akamai_clear_url($paths, $overrides);
    if ($did_clear) {
      $message = t("Akamai Cache Request has been made successfully, please allow 10 minutes for changes to take effect.") . theme("item_list", $paths);
      drupal_set_message($message);
    }
    else {
      drupal_set_message(t("There was a problem with your cache control request.  Check your log messages for more information."), 'error');
    }
  }
}
