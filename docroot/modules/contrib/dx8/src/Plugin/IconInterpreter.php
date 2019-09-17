<?php

namespace Drupal\cohesion\Plugin;

/**
 * Defines the IconInterpreter plugin
 *
 * The IconInterpreter plugin actions calls that needs interpreting uploaded icon libraries
 *
 * @todo - ^^^ this is incorrect.
 *
 */
class IconInterpreter {

  /**
   * 
   * @param type $json
   * @return \Drupal\cohesion\CohesionApiClient response array
   */
  public function sendToApi($json = '') {
    $results = new \stdClass();
    $results->body = \Drupal\Component\Serialization\Json::decode($json);
    return \Drupal\cohesion\CohesionApiClient::resourceIcon($results);
  }

}
