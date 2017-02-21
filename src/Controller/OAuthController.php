<?php

namespace Drupal\zendesk_connect\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\SessionManagerInterface;
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
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  private $tempStore;

  public function __construct(
    OAuthClient $oauthClient,
    SessionManagerInterface $sessionManager,
    PrivateTempStore $tempStore
  ) {
    $this->oauthClient = $oauthClient;
    $this->sessionManager = $sessionManager;
    $this->tempStore = $tempStore;
  }

  public function beginAuthorization(Request $request) {
    // Begin session for anonymous users, so private temp store can be used.
    if ($this->currentUser()->isAnonymous() && !isset($_SESSION['session_started'])) {
      $_SESSION['session_started'] = true;
      $this->sessionManager->start();
    }

    // Store state to prevent CSRF attacks later.
    $state = Crypt::randomBytesBase64(16);
    $this->tempStore->set('oauth.state', $state);

    // Redirect user to authorization endpoint.
    return $this->oauthClient->authorize([
      'scope' => [
        'read',
        'write',
      ],
      'state' => $state,
    ], [$this, 'getAuthorizationRedirect']);
  }

  /**
   * Redirect callback function for the Zendesk OAuth client library.
   *
   * We use this function to return a redirect response instead of using the
   * library's built in redirect function so Drupal has the opportunity to
   * clean up gracefully.
   *
   * The main problem with not using a Drupal redirect is the session is not
   * saved automatically, and so the 'state' parameter is lost & CSRF checks
   * always fail.
   *
   * @param string $url
   * @param \Stevenmaguire\OAuth2\Client\Provider\Zendesk $client
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   */
  public function getAuthorizationRedirect(string $url, OAuthClient $client) {
    return new TrustedRedirectResponse($url);
  }

  public function redirectEndpoint(Request $request) {
    if (!$request->query->has('code')) {
      throw new BadRequestHttpException("No authorization code found in 'code' query parameter from Zendesk");
    }

    // Check state to prevent CSRF attacks.
    if (!($request->query->has('state') && ($request->query->get('state') === $this->tempStore->get('oauth.state')))) {
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
      // Redirect to journey start with error message?
    }
  }

  public static function create(ContainerInterface $container) {
    /** @var \Stevenmaguire\OAuth2\Client\Provider\Zendesk $oauthClient */
    $oauthClient = $container->get('zendesk_connect.oauth_client');
    /** @var \Drupal\Core\Session\SessionManagerInterface $sessionManager */
    $sessionManager = $container->get('session_manager');
    /** @var \Drupal\user\PrivateTempStore $tempStore */
    $tempStore = $container->get('user.private_tempstore')->get('zendesk_connect');

    return new static($oauthClient, $sessionManager, $tempStore);
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
