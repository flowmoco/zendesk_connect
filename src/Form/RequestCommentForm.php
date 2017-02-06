<?php
namespace Drupal\zendesk_connect\Form;

/**
 * @file
 * Contains \Drupal\zendesk_connect\Form\BasicSettingsForm.
 */

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\zendesk_connect\Http\ZendeskClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This forms handles the basic module configurations.
 */
class RequestCommentForm extends FormBase {

  /**
   * @var \Drupal\zendesk_connect\Http\ZendeskClient
   */
  private $zendeskClient;


  public function __construct(ZendeskClient $zendeskClient) {
    $this->zendeskClient = $zendeskClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
      $zendeskClient = $container->get('zendesk_connect.client');

      return new static($zendeskClient);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zendesk_connect_request_comment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

    $form_state->set('zendesk_connect_request_id', $id);

    $form['request_comment_body'] = array(
      '#type' => 'textarea',
      '#title' => t('Comment'),
      '#default_value' => '',
      '#description' => t('Enter a comment'),
      '#required' => TRUE,
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Comment'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('request_comment_body'))) {
      $form_state->setErrorByName('request_comment_body', $this->t('Please enter a comment'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->get('zendesk_connect_request_id');
    $postData = array(
      'request' => [
        'comment' => [
          'body' => $form_state->getValue('request_comment_body')
        ]
      ]
    );
    $response = $this->zendeskClient->performPutRequest('/api/v2/requests/' . $id . '.json', $postData);
    echo json_encode($response);
  }

}
