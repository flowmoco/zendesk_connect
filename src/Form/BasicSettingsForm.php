<?php

namespace Drupal\zendesk_connect\Form;

/**
 * @file
 * Contains \Drupal\zendesk_connect\Form\BasicSettingsForm.
 */

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * This forms handles the basic module configurations.
 */
class BasicSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zendesk_connect_basic_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\ImmutableConfig $config */
    $config = \Drupal::service('config.factory')->get('zendesk_connect.settings');

    $form['zendesk_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zendesk sub-domain'),
      '#default_value' => $config->get('zendesk_domain'),
      '#description' => $this->t('Your Zendesk sub-domain'),
      '#required' => TRUE,
    ];

    $form['zendesk_api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zendesk API Token'),
      '#default_value' => $config->get('zendesk_api_token'),
      '#description' => $this->t('API Token, copy from the zendesk dashboard under api settings.'),
      '#required' => TRUE,
    ];

    $form['zendesk_admin_email'] = [
      '#type' => 'textfield',
      '#title' => t('Zendesk Admin Email'),
      '#default_value' => $config->get('zendesk_admin_email', ''),
      '#description' => t('An admin email to create zendesk users on registration'),
      '#required' => TRUE,
    ];

    $form['oauth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OAuth'),
    ];

    $form['oauth']['zendesk_connect_oauth_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client id'),
      '#default_value' => $config->get('zendesk_connect.oauth.client_id'),
      '#description' => $this->t('OAuth client id - copy from the zendesk dashboard.'),
      '#required' => TRUE,
    ];

    $form['oauth']['zendesk_connect_oauth_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#default_value' => $config->get('zendesk_connect.oauth.client_secret'),
      '#description' => $this->t('OAuth client secret - copy from the zendesk dashboard.'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('zendesk_domain'))) {
      $form_state->setErrorByName('zendesk_domain', $this->t('Please enter your Zendesk domain'));
    }

    if ($this->startsWith($form_state->getValue('zendesk_domain'), 'https://')) {
      $form_state->setErrorByName('zendesk_domain', $this->t('Please only use the sub-domain; remove the "https://" from the start.'));
    }

    if ($this->endsWith($form_state->getValue('zendesk_domain'), '.zendesk.com')) {
      $form_state->setErrorByName('zendesk_domain', $this->t('Please only use the sub-domain; remove ".zendesk.com" from the end.'));
    }

    if (empty($form_state->getValue('zendesk_api_token'))) {
      $form_state->setErrorByName('zendesk_api_token', $this->t('Please enter your Zendesk API token'));
    }
  }

  private function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
  }

  private function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) {
      return TRUE;
    }
    return (substr($haystack, -$length) === $needle);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = \Drupal::service('config.factory')->getEditable('zendesk_connect.settings');
    $config
      ->set('zendesk_domain', $form_state->getValue('zendesk_domain'))
      ->set('zendesk_api_token', $form_state->getValue('zendesk_api_token'))
      ->set('zendesk_admin_email', $form_state->getValue('zendesk_admin_email'))
      ->set('zendesk_connect.oauth.client_id', $form_state->getValue('zendesk_connect_oauth_client_id'))
      ->set('zendesk_connect.oauth.client_secret', $form_state->getValue('zendesk_connect_oauth_client_secret'))
      ->save();
  }

}
