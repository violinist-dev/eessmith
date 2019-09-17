<?php

namespace Drupal\cohesion;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

interface ApiPluginInterface extends PluginInspectionInterface {

  /**
   * Return the name of the reusable form plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Call the API, get the response and replace the tokenized content.
   *
   * @return mixed
   */
  public function callApi();

}
