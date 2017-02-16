<?php

namespace Drupal\zendesk_connect\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\user\PrivateTempStore;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Stevenmaguire\OAuth2\Client\Provider\Zendesk as OAuthClient;
use Stevenmaguire\OAuth2\Client\Provider\ZendeskResourceOwner;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OAuthController extends ControllerBase {

  /**
   * @var \Stevenmaguire\OAuth2\Client\Provider\Zendesk
   */
  private $oauthClient;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  private $tempStore;

  public function __construct(
    OAuthClient $oauthClient,
    PrivateTempStore $tempStore
  ) {
    $this->oauthClient = $oauthClient;
    $this->tempStore = $tempStore;
  }

  public function beginAuthorization() {
    // Store state to prevent CSRF attacks later.
    $this->tempStore->set('oauth.state', $this->oauthClient->getState());
    // Redirect user to authorization endpoint.
    $this->oauthClient->authorize([
      'scope' => [
        'read',
        'write',
      ],
    ]);
  }

  public function redirectEndpoint(Request $request) {
    if (!$request->query->has('code')) {
      throw new BadRequestHttpException("No authorization code found in 'code' query parameter from Zendesk");
    }

    // Check state to prevent CSRF attacks.
    if (!$request->query->has('state') || ($request->query->get('state') !== $this->tempStore->get('oauth.state'))) {
      $this->tempStore->delete('oauth.state');
      throw new AccessDeniedHttpException("Invalid state passed for OAuth authorization");
    }

    try {
      $accessToken = $this->oauthClient->getAccessToken('authorization_code', [
        'code' => $request->query->get('code'),
      ]);

      $this->tempStore->set('oauth.token', $accessToken);

      /** @var \Stevenmaguire\OAuth2\Client\Provider\ZendeskResourceOwner $resourceOwner */
      $resourceOwner = $this->oauthClient->getResourceOwner($accessToken);
      $user = user_load_by_mail($resourceOwner->getEmail());

      if (!$user) {
        $user = $this->registerFromResourceOwner($resourceOwner);
      }

      $this->login($user);
    } catch (IdentityProviderException $e) {
      // @todo Handle failure retrieving OAuth token/resource owner.
    }
  }

  public static function create(ContainerInterface $container) {
    /** @var \Stevenmaguire\OAuth2\Client\Provider\Zendesk $oauthClient */
    $oauthClient = $container->get('zendesk_connect.oauth_client');
    /** @var \Drupal\user\PrivateTempStore $tempStore */
    $tempStore = $container->get('user.private_tempstore')->get('zendesk_connect');
    return new static($oauthClient, $tempStore);
  }

  private function registerFromResourceOwner(ZendeskResourceOwner $resourceOwner): User {
    // @todo Are there other fields we want to add here?
    $user = User::create([
      'name' => $resourceOwner->getEmail(),
      'pass' => Crypt::randomBytesBase64(16),
    ]);

    $user->save();

    return $user;
  }

  private function login(User $user) {
    user_login_finalize($user);
    $this->redirect('zendesk_connect.requests');
  }

}
