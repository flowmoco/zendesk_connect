<?php

namespace Drupal\zendesk_connect\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class SsoController extends ControllerBase {

  /**
   * @var string
   */
  private $subdomain;

  /**
   * @var string
   */
  private $sharedSecret;

  public function __construct(ConfigFactoryInterface $configFactory) {
    $config = $configFactory->get('zendesk_connect.settings');
    $this->subdomain = $config->get('subdomain');
    $this->sharedSecret = $config->get('sso.shared_secret');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  public function ssoLogin(Request $request) {
    $user = $this->currentUser();

    if ($user->isAnonymous()) {
      // Redirect to login/register, then redirect back here
      return $this->redirect('user.login', [], [
        'query' => [
          'destination' => $request->getRequestUri(),
        ],
      ])
        ->setMaxAge(0)
        ->setSharedMaxAge(0)
        ->setPrivate();
    }

    $now = time();
    $random = Crypt::randomBytesBase64(8);
    $hash = Crypt::hashBase64("{$user->id()}:{$now}:{$random}");

    $token = (new Builder())
      ->setAudience("https://{$this->subdomain}.zendesk.com")
      ->setId($hash)
      ->setIssuedAt($now)
      ->setNotBefore($now)
      ->setSubject($user->getEmail())
      // Expires in 3 minutes.
      ->setExpiration($now + 180)
      // Zendesk specific claims.
      ->set('email', $user->getEmail())
      ->set('name', $user->getDisplayName())
      // HS256 algorithm
      ->sign(new Sha256(), $this->sharedSecret)
      ->getToken();

    // @todo Zendesk sends this & uses it to redirect - do we need to verify it,
    // or assume Zendesk does that on their end?
    $returnTo = $request->query->get('return_to') ?: '';

    return TrustedRedirectResponse::create($this->getEndpoint($token, $returnTo))
      ->setMaxAge(0)
      ->setSharedMaxAge(0)
      ->setPrivate();
  }

  private function getEndpoint(string $token, string $returnTo = ''): string {
    $parameters = ['jwt' => $token];

    if ($returnTo) {
      $parameters['return_to'] = $returnTo;
    }

    $query = http_build_query($parameters);

    return "https://{$this->subdomain}.zendesk.com/access/jwt?{$query}";
  }

}
