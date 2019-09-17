<?php

namespace Drupal\cohesion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\cohesion\CohesionJsonResponse;
use Drupal\cohesion\CohesionApiClient;

/**
 * Class ParseJsonEndpoint
 *
 * Makes a request to the API to parse data.
 *
 * @package Drupal\cohesion\Controller
 */
class ParseJsonEndpoint extends ControllerBase {

  public function index(Request $request) {
    try {
      $body = $request->getContent();
      $results = json_decode($body);

      $response = CohesionApiClient::parseJson($request->attributes->get('command'), $results);

      if ($response && $response['code'] == 200) {
        $status = $response['code'];
        $result = $response['data'];
      }
      else {
        $status = 500;
        $result = [
          'error' => t('Unknown error.'),
        ];
      }

    } catch (\GuzzleHttp\Exception\ClientException $e) {
      $status = 500;
      $result = [
        'error' => t('Connection error.'),
      ];
    }

    return new CohesionJsonResponse($result, $status);
  }

}
