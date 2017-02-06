<?php

namespace Drupal\zendesk_connect\Http;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ZendeskClient {

  use StringTranslationTrait;

  /**
   * @var \GuzzleHttp\Client
   */
  private $client;

  /**
   * @var string
   */
  private $domain;

  /**
   * @var string
   */
  private $token;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $account;

  public function __construct(
    Client $client,
    ConfigFactoryInterface $configFactory,
    AccountProxyInterface $account
  ) {
    $this->client = $client;
    // @todo Pass in config values as own parameters
    $config = $configFactory->get('zendesk_connect.settings');
    $this->domain = $config->get('zendesk_domain');
    $this->token = $config->get('zendesk_api_token');
    $this->account = $account;
  }

  public function performRequest($endpoint) {
    $url = $this->domain . $endpoint;
    $email = $this->account->getEmail();
    try {
      $result = $this->client->get(
        $url,
        ['http_errors' => FALSE, 'auth' => [$email . '/token', $this->token]]
      );
      return (json_decode($result->getBody()));
    } catch (RequestException $e) {
      return $this->t('Error');
    }
  }

}
