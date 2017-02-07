<?php

namespace Drupal\zendesk_connect\Http;

use Drupal\Core\Config\ImmutableConfig;
use Zendesk\API\HttpClient as ZendeskAPI;

class ClientFactory {

  /**
   * @var string
   */
  private $domain;

  /**
   * @var string
   */
  private $apiToken;

  public function __construct(ImmutableConfig $config) {
    $this->domain = $config->get('zendesk_domain');
    $this->apiToken = $config->get('zendesk_api_token');
  }

  public function get(string $email): ZendeskAPI {
    $client = new ZendeskAPI($this->domain);
    $client->setAuth('basic', [
      'username' => $email,
      'token' => $this->apiToken,
    ]);

    return $client;
  }

}
