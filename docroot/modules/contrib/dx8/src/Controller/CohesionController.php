<?php

namespace Drupal\cohesion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\cohesion\CohesionJsonResponse;
use Drupal\Component\Serialization\Json;

/**
 * Class CohesionController
 *
 * Controller routines for Cohesion admin index page.
 *
 * @package Drupal\cohesion\Controller
 */
class CohesionController extends ControllerBase {

  /**
   * System Manager Service.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  /**
   * Constructs a new SystemController.
   *
   * @param \Drupal\system\SystemManager $systemManager
   */
  protected $file_name;

  public function __construct(SystemManager $systemManager) {
    $this->systemManager = $systemManager;
    $file_name = \Drupal::request()->query->get('file_name');
    $this->file_name = $file_name;
  }

  /**
   * The admin landing page (admin/cohesion).
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Controller's container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('system.manager'));
  }

  /**
   * Constructs a page with placeholder content.
   *
   * @return array
   */
  public function index() {
    return $this->systemManager->getBlockContents();
  }

  /**
   * Downloads a tarball of the site configuration.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function cohesionConfigDownload(Request $request) {
    $uri = COHESION_FILESYSTEM_URI . 'content_export/' . $this->file_name;
    if (!file_exists($uri)) {
      $url = Url::fromUri('internal:/' . Url::fromRoute('dx8_deployment.export_content')->getInternalPath());
      drupal_set_message(t('Your Cohesion content is not ready please click "Export Cohesion content" first'), 'warning');
      return new RedirectResponse($url->toString());
    }
    else {
      $url = \Drupal::service('stream_wrapper_manager')->getViaUri($uri)->getExternalUrl();
      return new RedirectResponse($url);
    }
  }

  /**
   * Get an array of the available cohesion entity types
   *
   * @return array
   */
  public static function getCohesionEnityTypes() {
    $results = [];
    foreach (\Drupal::service('entity.manager')->getDefinitions() as $key => $value) {
      if ($value->entityClassImplements('\Drupal\cohesion\Entity\CohesionSettingsInterface')) {
        $results[$value->get('id')] = $value->getLabel()->render();
      }
    }
    return $results;
  }

  /**
   * Log JS errors to Drupal DB logs
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\cohesion\CohesionJsonResponse
   */
  public static function errorLogger(Request $request) {
    if (($error_data = Json::decode($request->getContent())) && isset($error_data['message'])) {
      \Drupal::service('settings.endpoint.utils')->logError($error_data['message']);
    }
    return new CohesionJsonResponse([]);
  }

}
