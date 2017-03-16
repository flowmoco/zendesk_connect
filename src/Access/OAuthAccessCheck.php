<?php

namespace Drupal\zendesk_connect\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Routing\Access\AccessInterface;
use Zendesk\API\Utilities\Auth;

class OAuthAccessCheck implements AccessInterface {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  public function __construct(ImmutableConfig $config) {
    $this->config = $config;
  }

  public function access(): AccessResultInterface {
    return AccessResult::allowedIf($this->config->get('authentication_type') === Auth::OAUTH)
      ->addCacheableDependency($this->config);
  }

}
