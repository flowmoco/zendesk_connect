<?php

namespace Drupal\zendesk_connect\Http;

use Drupal\Core\Config\ImmutableConfig;
use Stevenmaguire\OAuth2\Client\Provider\Zendesk;
use Symfony\Component\Routing\Router;

class OauthClientFactory {

  /**
   * @var string
   */
  private $subDomain;

  /**
   * @var string
   */
  private $clientId;

  /**
   * @var string
   */
  private $clientSecret;

  /**
   * @var string
   */
  private $redirectUri;

  public function __construct(ImmutableConfig $config, Router $router) {
    $this->subDomain = $config->get('zendesk_domain');
    $this->clientId = $config->get('zendesk_connect.oauth.client_id');
    $this->clientSecret = $config->get('zendesk_connect.oauth.client_secret');
    $this->redirectUri = $router->generate('zendesk_connect.oauth_redirect');
  }

  public function get(): Zendesk {
    return new Zendesk([
      'clientId' => $this->clientId,
      'clientSecret' => $this->clientSecret,
      'redirectUri' => $this->redirectUri,
      'subdomain' => $this->subDomain,
    ]);
  }

}
