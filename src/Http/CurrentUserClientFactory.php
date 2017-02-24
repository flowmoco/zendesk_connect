<?php

namespace Drupal\zendesk_connect\Http;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\zendesk_connect\Exception\ZendeskConnectException;
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
    if ($this->account->getEmail() !== NULL) {
      return $this->clientFactory->get($this->account->getEmail());
    } else {
      throw new ZendeskConnectException(t('No email found'));
    }
  }

}

