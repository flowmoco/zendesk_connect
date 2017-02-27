<?php

namespace Drupal\zendesk_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\zendesk_connect\Form\RequestCommentForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Zendesk\API\Exceptions\ApiResponseException;
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
    try {
      $requests = $this->client->requests()->findAll(['sort_by' => 'updated_at', 'sort_order' => 'desc']);
    } catch (ApiResponseException $e) {
      $statusCode = $e->getCode();
      if ($statusCode == 401) {
        drupal_set_message(t('User is not registered in the request system, contact support via email'), 'error');
        return $this->redirect('user.page');
      } else if ($statusCode == 403) {
        throw new AccessDeniedHttpException("You to not have permission to view this content");
      } else if ($statusCode == 404) {
        throw new NotFoundHttpException("Content not found");
      }
    }

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
    try {
      $request = $this->client->requests($id)->find();
    } catch (ApiResponseException $e) {
      $statusCode = $e->getCode();
      if ($statusCode == 403) {
        throw new AccessDeniedHttpException("You to not have permission to view this request");
      } else if ($statusCode == 404) {
        throw new NotFoundHttpException("No Request found matching that ID");
      }
    }

    try {
      $commentsResponse = $this->client->requests($id)->comments()->findAll();
    } catch (ApiResponseException $e) {
      $statusCode = $e->getCode();
      if ($statusCode == 403) {
        throw new AccessDeniedHttpException("You to not have permission to view this requests comments");
      } else if ($statusCode == 404) {
        throw new NotFoundHttpException("No Request found matching that ID");
      }

    }

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
    $client = $container->get('zendesk_connect.client_factory.current_user')->get();

    return new static($client);
  }

}
