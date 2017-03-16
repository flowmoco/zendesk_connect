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

  public function getFormId() {
    return 'zendesk_connect_basic_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('zendesk_connect.settings');

    $form['subdomain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zendesk sub-domain'),
      '#default_value' => $config->get('subdomain'),
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
      '#default_value' => $config->get('basic.api_token'),
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
      '#default_value' => $config->get('basic.email'),
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

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('subdomain'))) {
      $form_state->setErrorByName('subdomain', $this->t('Please enter your Zendesk domain'));
    }

    if ($this->startsWith($form_state->getValue('subdomain'), 'https://')) {
      $form_state->setErrorByName('subdomain', $this->t('Please only use the sub-domain; remove the "https://" from the start.'));
    }

    if ($this->endsWith($form_state->getValue('subdomain'), '.zendesk.com')) {
      $form_state->setErrorByName('subdomain', $this->t('Please only use the sub-domain; remove ".zendesk.com" from the end.'));
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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('zendesk_connect.settings')
      ->set('subdomain', $form_state->getValue('subdomain'))
      ->set('authentication_type', $form_state->getValue('authentication_type'))
      ->set('basic.api_token', $form_state->getValue('api_token'))
      ->set('basic.email', $form_state->getValue('admin_email'))
      ->set('oauth.client_id', $form_state->getValue('oauth_client_id'))
      ->set('oauth.client_secret', $form_state->getValue('oauth_client_secret'))
      ->set('sso.enabled', $form_state->getValue('sso_enabled'))
      ->set('sso.shared_secret', $form_state->getValue('shared_secret'))
      ->save();
  }

}
