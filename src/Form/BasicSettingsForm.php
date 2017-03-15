<?php

namespace Drupal\zendesk_connect\Form;

/**
 * @file
 * Contains \Drupal\zendesk_connect\Form\BasicSettingsForm.
 */

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Zendesk\API\Utilities\Auth;

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

    $form['authentication_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Authentication type'),
      '#options' => [
        Auth::BASIC => 'Basic (Email & API token)',
        Auth::OAUTH => 'OAuth',
      ],
      '#default_value' => $config->get('authentication_type'),
      '#description' => $this->t('What authentication method to use to connect with Zendesk'),
      '#required' => TRUE,
    ];

    $form['basic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic'),
      '#states' => [
        'visible' => [
          ':input[name="authentication_type"]' => ['value' => Auth::BASIC],
        ],
      ],
    ];

    $form['basic']['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#default_value' => $config->get('zendesk_api_token'),
      '#description' => $this->t('API Token, copy from the zendesk dashboard under api settings.'),
      '#required' => FALSE,
      '#states' => [
        'required' => [
          ':input[name="authentication_type"]' => ['value' => Auth::BASIC],
        ],
      ],
    ];

    $form['basic']['admin_email'] = [
      '#type' => 'textfield',
      '#title' => t('Admin email'),
      '#default_value' => $config->get('zendesk_admin_email'),
      '#description' => t('An admin email to create zendesk users on registration'),
      '#required' => FALSE,
      '#states' => [
        'required' => [
          ':input[name="authentication_type"]' => ['value' => Auth::BASIC],
        ],
      ],
    ];

    $form['oauth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OAuth'),
      '#states' => [
        'visible' => [
          ':input[name="authentication_type"]' => ['value' => Auth::OAUTH],
        ],
      ],
    ];

    $form['oauth']['oauth_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client id'),
      '#default_value' => $config->get('oauth.client_id'),
      '#description' => $this->t('OAuth client id - copy from the zendesk dashboard.'),
      '#required' => FALSE,
      '#states' => [
        'required' => [
          ':input[name="authentication_type"]' => ['value' => Auth::OAUTH],
        ],
      ],
    ];

    $form['oauth']['oauth_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#default_value' => $config->get('oauth.client_secret'),
      '#description' => $this->t('OAuth client secret - copy from the zendesk dashboard.'),
      '#required' => FALSE,
      '#states' => [
        'required' => [
          ':input[name="authentication_type"]' => ['value' => Auth::OAUTH],
        ],
      ],
    ];

    $form['sso_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable single sign on'),
      '#default_value' => (bool) $config->get('sso.enabled'),
      '#description' => $this->t('Exposes an endpoint to provide Zendesk with a JWT SSO endpoint to use with this site. Users trying to login to Zendesk are redirected to this site & authenticated here.'),
      '#required' => FALSE,
    ];

    $form['sso'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Single Sign On'),
      '#collapsible' => TRUE,
      '#collapsed' => (bool) $config->get('sso.enabled'),
      '#states' => [
        'visible' => [
          ':input[name="sso_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['sso']['shared_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shared secret'),
      '#default_value' => $config->get('sso.shared_secret'),
      '#description' => $this->t('Shared secret - generated in Zendesk when configuring JWT SSO.'),
      '#required' => FALSE,
      '#states' => [
        'required' => [
          ':input[name="sso_enabled"]' => ['checked' => TRUE],
        ],
      ],
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

    if (empty($form_state->getValue('api_token'))) {
      $form_state->setErrorByName('api_token', $this->t('Please enter your Zendesk API token'));
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
      ->set('authentication_type', $form_state->getValue('authentication_type'))
      ->set('zendesk_api_token', $form_state->getValue('api_token'))
      ->set('zendesk_admin_email', $form_state->getValue('admin_email'))
      ->set('oauth.client_id', $form_state->getValue('oauth_client_id'))
      ->set('oauth.client_secret', $form_state->getValue('oauth_client_secret'))
      ->set('sso.enabled', $form_state->getValue('sso_enabled'))
      ->set('sso.shared_secret', $form_state->getValue('shared_secret'))
      ->save();
  }

}
