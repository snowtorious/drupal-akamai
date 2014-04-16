<?php

/**
 * @file
 * Contains \Drupal\akamai\Form\SettingsForm.
 */

namespace Drupal\akamai\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures Akamai settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'akamai_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $akamai_config = $this->configFactory->get('akamai.settings');

    $form['akamai_wsdl'] = array(
      '#type'          => 'textfield',
      '#title'         => t('SOAP WSDL'),
      '#default_value' => $akamai_config->get('akamai_wsdl'),
      '#description'   => t('The URL of the Akamai SOAP call WSDL, e.g. "https://soap.example.com/example.wsdl"')
    );

    $form['akamai_basepath'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Base Path'),
      '#default_value' => $akamai_config->get('akamai_basepath'),
      '#description'   => t('The URL of the base path (fully qualified domain name) of the site.  This will be used as a prefix for all cache clears (Akamai indexs on the full URI). e.g. "http://www.example.com"')
    );

    $form['akamai_username'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Cache clearing user'),
      '#default_value' => $akamai_config->get('akamai_username'),
      '#description'   => t('The user name of the account being used for cache clearing (most likely an email)')
    );

    $form['akamai_password'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Cache clearing password'),
      '#default_value' => $akamai_config->get('akamai_password'),
      '#description'   => t('The password of the cache clearing user')
    );

    $form['akamai_domain'] = array(
      '#type'          => 'select',
      '#title'         => t('Domain'),
      '#default_value' => $akamai_config->get('akamai_domain'),
      '#options'       => array(
        'staging'    => t('Staging'),
        'production' => t('Production'),
      ),
      '#description'   => t('The Akamai domain to use for cache clearing')
    );

    $form['akamai_action'] = array(
      '#type'          => 'select',
      '#title'         => t('Clearing Action Type Default'),
      '#default_value' => $akamai_config->get('akamai_action'),
      '#options'       => array(
        'remove'     => t('Remove'),
        'invalidate' => t('Invalidate'),
      ),
      '#description'   => t('The default clearing action.  The options are <em>remove</em> (which removes the item from the Akamai cache) and <em>invalidate</em> (which leaves the item in the cache, but invalidates it so that the origin will be hit on the next request)')
    );
    $form['akamai_email'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Email Notification Override'),
      '#default_value' => $akamai_config->get('akamai_email'),
      '#description'   => t('If this email address is specified all cache clearing requests will send notifications to this address.  If this address is not specified, the email address of the user executing the request will be used.')
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {


    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('akamai.settings')
      ->set('akamai_wsdl', $form_state['values']['akamai_wsdl'])
      ->set('akamai_basepath', $form_state['values']['akamai_basepath'])
      ->set('akamai_username', $form_state['values']['akamai_username'])
      ->set('akamai_password', $form_state['values']['akamai_password'])
      ->set('akamai_domain', $form_state['values']['akamai_domain'])
      ->set('akamai_action', $form_state['values']['akamai_action'])
      ->set('akamai_email', $form_state['values']['akamai_email'])
      ->save();
  }
}
