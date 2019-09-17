<?php

namespace Drupal\cohesion_layout_builder\Form;

use Drupal\layout_builder\Form\ConfigureSectionForm;
use Drupal\cohesion_layout_builder\Controller\CohesionLayoutRebuildTrait;

/**
 * Provides a form to add a block.
 *
 * @internal
 */
class CohesionConfigureSectionForm extends ConfigureSectionForm {

  use CohesionLayoutRebuildTrait;

}
