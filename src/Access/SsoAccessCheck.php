<?php

namespace Drupal\zendesk_connect\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

class SsoAccessCheck implements AccessInterface {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  public function __construct(ImmutableConfig $config) {
    $this->config = $config;
  }

  public function access(AccountInterface $account) {
      return AccessResult::allowedIf($account->hasPermission('access zendesk sso') && $this->config->get('sso.enabled'))
        ->addCacheableDependency($account)
        ->addCacheableDependency($this->config);
  }

}
