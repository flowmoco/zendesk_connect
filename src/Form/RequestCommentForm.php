<?php
namespace Drupal\zendesk_connect\Form;

/**
 * @file
 * Contains \Drupal\zendesk_connect\Form\BasicSettingsForm.
 */

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zendesk\API\HttpClient;

/**
 * This forms handles the basic module configurations.
 */
class RequestCommentForm extends FormBase {

  /**
   * @var \Zendesk\API\HttpClient
   */
  private $zendeskClient;

  public function __construct(HttpClient $zendeskClient) {
    $this->zendeskClient = $zendeskClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $zendeskClient = $container->get('zendesk_connect.client_factory.current_user')->get();

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
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    $id = NULL
  ) {

    $form_state->set('zendesk_connect_request_id', $id);

    $form['request_comment_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Type your message here'),
      '#default_value' => '',
      '#description' => $this->t('Type your message here'),
      '#description_display' => 'invisible',
      '#required' => TRUE,
    ];

//    $form['request_comment_file'] = array(
//      '#type' => 'managed_file',
//      '#description' => t('Upload a file, allowed extensions: jpg, jpeg, png, gif'),
//      '#multiple' => TRUE,
//      '#upload_location' => 'temporary://zendesk_uploads/',
//    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('request_comment_body'))) {
      $form_state->setErrorByName(
        'request_comment_body',
        $this->t('Please enter a comment')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->get('zendesk_connect_request_id');
    $files = $form_state->getValue('request_comment_file');

    if ($files) {
      $token = NULL;
      foreach ($files as $file) {
        if (!$token) {
          $fileResponse = $this->zendeskClient->attachments()->upload($files);
          $token = $fileResponse->upload->token;
        } else {
          $files->token = $token;
          $fileResponse = $this->zendeskClient->attachments()->upload($files);
        }
      }
      $postData = [
        'comment' => [
          'body' => $form_state->getValue('request_comment_body'),
          "uploads" => $files,
        ],
      ];
      $response = $this->zendeskClient->requests()->update($id, $postData);
      if ($response) {
        drupal_set_message(t('Your comment has been submitted.'), 'status');
      } else {
        drupal_set_message(t('There was a problem submitting your message, please try again.'), 'error');
      }
    } else {
      $postData = [
        'comment' => [
          'body' => $form_state->getValue('request_comment_body'),
        ],
      ];
      $response = $this->zendeskClient->requests()->update($id, $postData);
      if ($response) {
        drupal_set_message(t('Your comment has been submitted.'), 'status');
      } else {
        drupal_set_message(t('There was a problem submitting your message, please try again.'), 'error');
      }
    }

  }

}
