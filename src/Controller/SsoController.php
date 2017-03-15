<?php

namespace Drupal\zendesk_connect\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class SsoController extends ControllerBase {

  /**
   * @var string
   */
  private $subDomain;

  /**
   * @var string
   */
  private $sharedSecret;

  public function __construct(ImmutableConfig $config) {
    $this->subDomain = $config->get('zendesk_domain');
    $this->sharedSecret = $config->get('sso.shared_secret');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('zendesk_connect.settings')
    );
  }

  public function zendeskSso(Request $request) {
    $user = $this->currentUser();

    if ($user->isAnonymous()) {
      // Redirect to login/register, then redirect back here
      return $this->redirect('user.login', [], [
        'query' => [
          'destination' => Url::fromRoute('zendesk_connect.sso')
        ],
      ]);
    }

    $now = time();
    $random = Crypt::randomBytesBase64(8);
    $hash = Crypt::hashBase64("{$user->id()}:{$now}:{$random}");

    $token = (new Builder())
      ->setAudience("https://{$this->subDomain}.zendesk.com")
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

    return TrustedRedirectResponse::create($this->getEndpoint($token, $request->query->get('return_to')));
  }

  private function getEndpoint(string $token, string $returnTo = ''): string {
    $parameters = ['jwt' => $token];

    if ($returnTo) {
      $parameters['return_to'] = $returnTo;
    }

    $query = http_build_query($parameters);

    return "https://{$this->subDomain}.zendesk.com/access/jwt{$query}";
  }

}
