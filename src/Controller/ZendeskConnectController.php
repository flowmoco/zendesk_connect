<?php

namespace Drupal\zendesk_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\zendesk_connect\Http\ZendeskClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ZendeskConnectController extends ControllerBase {

  /**
   * @var \Drupal\zendesk_connect\Http\ZendeskClient
   */
  private $zendeskClient;

  public function __construct(ZendeskClient $zendeskClient) {
    $this->zendeskClient = $zendeskClient;
  }

  public function requests() {
    return [
      '#theme' => 'requests',
      '#title' => 'Requests page',
      '#requests' => $this->getAllReq(),
      '#attached' => [
        'library' => [
          'zendesk_connect/requests-styles',
        ],
      ],
    ];
  }

  public function request($id) {
    return [
      '#theme' => 'request',
      '#title' => $this->getReq($id)->request->subject,
      '#request_id' => $id,
      '#request' => $this->getReq($id),
      '#comments' => $this->getReqCom($id),
      '#attached' => [
        'library' => [
          'zendesk_connect/requests-styles',
        ],
      ],
    ];
  }

  public function getAllReq() {
    return $this->zendeskClient->performRequest('/api/v2/requests.json');
  }

  public function getReq($id) {
    return $this->zendeskClient->performRequest("/api/v2/requests/{$id}.json");
  }

  public function getReqCom($id) {
    return $this->zendeskClient->performRequest("/api/v2/requests/{$id}/comments.json");
  }

  public function userData() {
    if ($_SESSION['auth0__user_info']) {
      return json_encode($_SESSION['auth0__user_info']);
    }
    else {
      return NULL;
    }
  }

  public function accessToken() {
    if ($_SESSION['auth0__access_token']) {
      return $_SESSION['auth0__access_token'];
    }
    else {
      return NULL;
    }
  }

  public function idToken() {
    if ($_SESSION['auth0__id_token']) {
      return $_SESSION['auth0__id_token'];
    }
    else {
      return NULL;
    }
  }

  public static function create(ContainerInterface $container) {
    $zendeskClient = $container->get('zendesk_connect.client');

    return new static($zendeskClient);
  }

}
