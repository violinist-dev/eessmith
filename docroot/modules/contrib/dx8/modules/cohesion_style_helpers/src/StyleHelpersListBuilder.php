<?php

namespace Drupal\cohesion_style_helpers;

use Drupal\Core\Entity\EntityInterface;
use Drupal\cohesion\CohesionListBuilder;

/**
 * Class StyleHelpersListBuilder
 *
 * Provides a listing of style helper entities.
 *
 * @package Drupal\cohesion_style_helpers
 */
class StyleHelpersListBuilder extends CohesionListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);

    try {
      $type_entity = \Drupal::entityTypeManager()->getStorage('custom_style_type')->load($entity->getCustomStyleType());
    } catch (\Drupal\Component\Plugin\Exception\PluginNotFoundException $ex) {
      watchdog_exception('cohesion', $ex);
      $type_entity = null;
    }    
    $row['type'] = $type_entity ? $type_entity->label() : null;

    return $row;
  }
}
