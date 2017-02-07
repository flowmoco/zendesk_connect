<?php

namespace Drupal\zendesk_connect\Http;

use Drupal\Core\Session\AccountProxyInterface;
use Zendesk\API\HttpClient;

class CurrentUserClientFactory {

  /**
   * @var \Drupal\zendesk_connect\Http\ClientFactory
   */
  private $clientFactory;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $account;

  public function __construct(
    ClientFactory $clientFactory,
    AccountProxyInterface $account
  ) {
    $this->clientFactory = $clientFactory;
    $this->account = $account;
  }

  public function get(): HttpClient {
    return $this->clientFactory->get($this->account->getEmail());
  }

}
