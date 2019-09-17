<?php

namespace Drupal\cohesion_elements\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface CohesionElementSettingsInterface
 * Provides an interface for defining Cohesion element configuration entities.
 *
 * @package Drupal\cohesion_elements\Entity
 */
interface CohesionElementSettingsInterface extends ConfigEntityInterface {

  public function getCategory();

  public function getCategoryEntity();

  public function setCategory($category);

  public function getPreviewImage();

  public function setPreviewImage($preview_image);

  /**
   * Get the entity asset name (overridden for helpers).
   *
   * @return string
   */
  public function getAssetName();

}
