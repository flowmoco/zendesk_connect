<?php
namespace Drupal\zendesk_connect\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
/**
 * Get a response code from any URL using Guzzle in Drupal 8!
 *
 * Usage:
 * In the head of your document:
 *
 * use Drupal\custom_guzzle_request\Http\CustomGuzzleHttp;
 *
 * In the area you want to return the result, using any URL for $url:
 *
 * $check = new CustomGuzzleHttp();
 * $response = $check->performRequest($url);
 *
 **/
class ZendeskConnectHttp {
  use StringTranslationTrait;

  public function performRequest($endpoint) {
    $config = \Drupal::service('config.factory')->get('zendesk_connect.settings');
    $domain = $config->get('zendesk_domain');
    $url = $domain.$endpoint;
    $token = $config->get('zendesk_api_token');
    $userEmail = $_SESSION['auth0__user_info'][result][email].'/token';
    $client = new \GuzzleHttp\Client();
    try {
      $res = $client->get($url, ['http_errors' => false, 'auth' => [$userEmail, $token]]);
      return(json_decode($res->getBody()));
    } catch (RequestException $e) {
      return($this->t('Error'));
    }
  }
}
