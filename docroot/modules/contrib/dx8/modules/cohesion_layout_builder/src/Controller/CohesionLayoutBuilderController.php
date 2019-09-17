<?php

namespace Drupal\cohesion_layout_builder\Controller;

use Drupal\layout_builder\Controller\LayoutBuilderController;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Class CohesionLayoutBuilderController
 *
 * @package Drupal\cohesion_layout_builder\Controller
 */
class CohesionLayoutBuilderController extends LayoutBuilderController {

  /**
   * {@inheritdoc}
   */
  public function layout(SectionStorageInterface $section_storage, $is_rebuilding = FALSE) {
    // @todo - this could call parent::layout() instead of duplicating code.
    $this->prepareLayout($section_storage, $is_rebuilding);

    $content = [];
    $count = 0;
    for ($i = 0; $i < $section_storage->count(); $i++) {
      $content[] = $this->buildAddSectionLink($section_storage, $count);
      $content[] = $this->buildAdministrativeSection($section_storage, $count);
      $count++;
    }
    $content[] = $this->buildAddSectionLink($section_storage, $count);
    $output['#attached']['library'][] = 'layout_builder/drupal.layout_builder';
    $output['#type'] = 'container';
    $output['#attributes']['id'] = 'layout-builder';
    // Mark this UI as uncacheable.
    $output['#cache']['max-age'] = 0;

    $output['#theme'] = 'cohesion_layout_builder';
    $output['#content'] = $content;

    return $output;
  }
}

