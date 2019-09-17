<?php

namespace Drupal\cohesion_layout_builder\Controller;

use Drupal\layout_builder\Controller\MoveBlockController;

/**
 * Class CohesionMoveBlockController
 *
 * @package Drupal\cohesion_layout_builder
 */
class CohesionMoveBlockController extends MoveBlockController {

  use CohesionLayoutRebuildTrait;

}