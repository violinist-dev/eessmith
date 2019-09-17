<?php

namespace Drupal\cohesion_layout_builder\Controller;

use Drupal\layout_builder\Controller\AddSectionController;

/**
 * Class CohesionAddSectionController
 *
 * @package Drupal\cohesion_layout_builder\Controller
 */
class CohesionAddSectionController extends AddSectionController {

  use CohesionLayoutRebuildTrait;

}