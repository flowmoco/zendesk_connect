<?php

namespace Drupal\zendesk_connect\Http;

use Drupal\Core\Config\ImmutableConfig;
use Zendesk\API\HttpClient as ZendeskAPI;

class ClientFactory {

  /**
   * @var string
   */
  private $subdomain;

  /**
   * @var string
   */
  private $apiToken;

  public function __construct(ImmutableConfig $config) {
    $this->subdomain = $config->get('subdomain');
    $this->apiToken = $config->get('basic.api_token');
  }

  public function get(string $email): ZendeskAPI {
    $client = new ZendeskAPI($this->subdomain);
    $client->setAuth('basic', [
      'username' => $email,
      'token' => $this->apiToken,
    ]);

    return $client;
  }

}
