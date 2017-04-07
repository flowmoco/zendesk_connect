<?php

namespace Drupal\zendesk_connect\Http;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\user\PrivateTempStore;
use Drupal\zendesk_connect\Exception\ZendeskConnectException;
use League\OAuth2\Client\Token\AccessToken;
use Zendesk\API\HttpClient as ZendeskAPI;
use Zendesk\API\Utilities\Auth;

class ClientFactory {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  private $tempStore;

  public function __construct(ImmutableConfig $config, PrivateTempStore $tempStore) {
    $this->config = $config;
    $this->tempStore = $tempStore;
  }

  public function get(string $email): ZendeskAPI {
    $client = new ZendeskAPI($this->config->get('subdomain'));

    switch ($this->config->get('authentication_type')) {
      case Auth::BASIC:
        $client->setAuth(Auth::BASIC, [
          'username' => $email,
          'token' => $this->config->get('basic.api_token'),
        ]);
        break;

      case Auth::OAUTH:
        $token = $this->tempStore->get('oauth.token');

        if (!$token instanceof AccessToken) {
          throw new ZendeskConnectException("No OAuth token found for Zendesk API, please authenticate.");
        }

        if ($token->getExpires() && $token->hasExpired()) {
          throw new ZendeskConnectException("Zendesk OAuth token has expired, please re-authenticate.");
        }

        $client->setAuth(Auth::OAUTH, [
          'token' => $token->getToken(),
        ]);
        break;

      default:
        throw new ZendeskConnectException("Unable to create Zendesk API client. Unknown authentication type '{$this->config->get('authentication_type')}' requested.");
    }


    return $client;
  }

}
