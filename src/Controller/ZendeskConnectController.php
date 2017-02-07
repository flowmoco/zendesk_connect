<?php

namespace Drupal\zendesk_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\zendesk_connect\Form\RequestCommentForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zendesk\API\HttpClient;

class ZendeskConnectController extends ControllerBase {

  /**
   * @var \Zendesk\API\HttpClient
   */
  private $client;

  public function __construct(HttpClient $client) {
    $this->client = $client;
  }

  public function requests() {
    $requests = $this->client->requests()->findAll();

    return [
      '#theme' => 'requests',
      '#title' => 'Requests page',
      '#requests' => $requests,
      '#attached' => [
        'library' => [
          'zendesk_connect/requests-styles',
        ],
      ],
    ];
  }

  public function request(int $id) {
    $form = \Drupal::formBuilder()->getForm(RequestCommentForm::class, $id);
    $request = $this->client->requests()->find($id);
    $commentsResponse = $this->client->requests()->comments()->sideload(['users'])->find($id);

    return [
      '#theme' => 'request',
      '#title' => $request->request->subject,
      '#request_id' => $id,
      '#request' => $request,
      '#comments' => $commentsResponse->comments,
      '#commentAuthors' => $commentsResponse->users,
      '#form' => $form,
      '#attached' => [
        'library' => [
          'zendesk_connect/requests-styles',
        ],
      ],
    ];
  }

  public static function create(ContainerInterface $container) {
    $client = $container->get('zendesk_connect.client.current_user');

    return new static($client);
  }

}
