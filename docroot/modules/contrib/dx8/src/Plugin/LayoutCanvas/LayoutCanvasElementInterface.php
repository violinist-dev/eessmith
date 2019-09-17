<?php

namespace Drupal\cohesion\Plugin\LayoutCanvas;

/**
 * Interface LayoutCanvasElementInterface
 *
 * @package Drupal\cohesion\Plugin\LayoutCanvas
 */
interface LayoutCanvasElementInterface {

  /**
   * @param $is_preview
   *
   * @return mixed
   */
  public function prepareDataForAPI($is_preview);


}
