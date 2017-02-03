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

    $config = \Drupal::service('config.factory')->get('zendesk_connect.settings');

    $form['zendesk_api_token'] = array(
      '#type' => 'textfield',
      '#title' => t('Zendesk API Token'),
      '#default_value' => $config->get('zendesk_api_token', ''),
      '#description' => t('API Token, copy from the zendesk dashboard under api settings.'),
      '#required' => TRUE,
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('zendesk_api_token'))) {
      $form_state->setErrorByName('zendesk_api_token', $this->t('Please enter your Zendesk API token'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::service('config.factory')->getEditable('zendesk_connect.settings');
    $config->set('zendesk_api_token', $form_state->getValue('zendesk_api_token'))
            ->save();
  }

}
