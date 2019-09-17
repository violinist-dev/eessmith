<?php

namespace Drupal\cohesion;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface EntityUpdateInterface.
 * All entities that can be updated via EntityUpdateManager must implement this.
 *
 * @package Drupal\cohesion\Update
 */
interface EntityUpdateInterface {

  /**
   * Return the name of the last entity update callback that was applied to this entity.
   *
   * by \Drupal\cohesion\EntityUpdateManager.
   *
   * @return mixed
   */
  public function getLastAppliedUpdate();

  /**
   * Set the name of the last entity update callback that was applied to this entity.
   *
   * @param $callback
   *
   * @return mixed
   */
  public function setLastAppliedUpdate($callback);

}
