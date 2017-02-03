<?php

namespace Drupal\zendesk_connect\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\zendesk_connect\Http\ZendeskConnectHttp;

class ZendeskConnectController extends ControllerBase {

	// all requests
	public function requests() {
		return [
      '#theme' => 'requests',
      '#title' => 'Requests page',
			'#requests' => $this->getAllReq(),
			'#attached' => [
        'library' => [
          'zendesk_connect/requests-styles',
        ]
      ]
    ];
	}

	// single request
	public function request($id) {
		return [
      '#theme' => 'request',
			'#title' => $this->getReq($id)->request->subject,
      '#request_id' => $id,
			'#request' => $this->getReq($id),
			'#comments' => $this->getReqCom($id),
    ];
	}

	// new request
	public function newRequest() {
		return [
			'#theme' => 'new_request',
			'#title' => 'New Request',
		];
	}

	public function getAllReq() {
		$url = 'https://flowmoco.zendesk.com/api/v2/requests.json';
	  $check = new ZendeskConnectHttp();
	  $response = $check->performRequest($url);
		return $response;
	}

	public function getReq($id) {
		$url = 'https://flowmoco.zendesk.com/api/v2/requests/' . $id . '.json';
	  $check = new ZendeskConnectHttp();
	  $response = $check->performRequest($url);
		return $response;
	}

	public function getReqCom($id) {
		$url = 'https://flowmoco.zendesk.com/api/v2/requests/' . $id . '/comments.json';
	  $check = new ZendeskConnectHttp();
	  $response = $check->performRequest($url);
		return $response;
	}

	public function userData() {
		if ($_SESSION['auth0__user_info']) {
			return json_encode($_SESSION['auth0__user_info']);
		} else {
			return NULL;
		}
	}

	public function accessToken() {
		if ($_SESSION['auth0__access_token']) {
			return $_SESSION['auth0__access_token'];
		} else {
			return NULL;
		}
	}

	public function idToken() {
		if ($_SESSION['auth0__id_token']) {
			return $_SESSION['auth0__id_token'];
		} else {
			return NULL;
		}
	}
}
