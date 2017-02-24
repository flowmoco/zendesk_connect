<?php

namespace Drupal\zendesk_connect\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\zendesk_connect\Http\CurrentUserClientFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * Provides a resource for zendesk requests.
 *
 * @RestResource(
 *   id = "zendeskconnect",
 *   label = @Translation("Zendesk Connect"),
 *   uri_paths = {
 *     "canonical" = "/zendesk/requests/{id}"
 *   }
 * )
 */
class ZendeskConnectResource extends ResourceBase {
  private $clientFactory;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, CurrentUserClientFactory $clientFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->clientFactory = $clientFactory;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('zendesk_connect.client_factory.current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a Zendesk request specified by ID.
   *
   * @param int $id
   *   The ID of the request.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the request.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the request was not found.
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when no request entry was provided.
   */
  public function get($id = NULL) {
    if ($id) {
      $request = $this->clientFactory->get()->requests($id)->find();
      if ($request) {
        $decodedResponse = json_decode(json_encode($request), true);
        return new ResourceResponse($decodedResponse);
      }

      throw new NotFoundHttpException(t('Request with ID @id was not found', array('@id' => $id)));
    }

    throw new BadRequestHttpException(t('No log entry ID was provided'));
  }

}
