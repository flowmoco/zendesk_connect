<?php

namespace Drupal\zendesk_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\zendesk_connect\Form\RequestCommentForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
    $requests = $this->client->requests()->findAll(['sort_by' => 'updated_at', 'sort_order' => 'desc']);

    return [
      '#theme' => 'requests',
      '#title' => 'My Consultations',
      '#requests' => $requests,
      '#attached' => [
        'library' => [
          'zendesk_connect/requests-styles',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  public function request(int $id) {
    $form = $this->formBuilder()->getForm(RequestCommentForm::class, $id);
    $request = $this->client->requests($id)->find();
    $commentsResponse = $this->client->requests($id)->comments()->findAll();
    $commentAuthors = [];
    foreach ($commentsResponse->users as $author) {
      $commentAuthors[$author->id] = $author;
    }

    return [
      '#theme' => 'request',
      '#title' => $request->request->subject,
      '#request_id' => $id,
      '#request' => $request,
      '#comments' => $commentsResponse->comments,
      '#commentAuthors' => $commentAuthors,
      '#form' => $form,
      '#attached' => [
        'library' => [
          'zendesk_connect/requests-styles',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  public function requestComments(int $id) {
    $commentsResponse = $this->client->requests($id)->comments()->findAll();
    $commentAuthors = [];
    foreach ($commentsResponse->users as $author) {
      $commentAuthors[$author->id] = $author;
    }

    $response = new JsonResponse($commentsResponse);
    return $response;
  }

  public static function create(ContainerInterface $container) {
    $client = $container->get('zendesk_connect.client.current_user');

    return new static($client);
  }

}
