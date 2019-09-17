<?php

namespace Drupal\cohesion;

/**
 * Trait for EntityHasResourceObjectTrait.
 */
trait EntityHasResourceObjectTrait {

  /**
   * Return the object formatted for the API
   *
   * @return \stdClass
   */
  public function getResourceObject() {
    $entity_values = new \stdClass();

    $entity_values->title = $this->label();
    $entity_values->type = $this->getAssetGroupId();
    $entity_values->bundle = $this->id();
    $entity_values->values = $this->getDecodedJsonValues();
    $entity_values->mapper = $this->getJsonMapper();
    $entity_values->itemID = $this->getConfigItemId();
    $entity_values->modified = $this->isModified();

    return $entity_values;
  }
}
