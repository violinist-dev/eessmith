<?php

namespace Drupal\cohesion_layout_builder\Form;

use Drupal\layout_builder\Form\RevertOverridesForm;
use Drupal\cohesion_layout_builder\Controller\CohesionLayoutRebuildTrait;

/**
 * Provides a form to add a block.
 *
 * @internal
 */
class CohesionRevertOverridesForm extends RevertOverridesForm {

  use CohesionLayoutRebuildTrait;

}
