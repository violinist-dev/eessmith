<?php

namespace Drupal\cohesion_elements\Form;

use Drupal\cohesion\Form\CohesionDeleteForm;

/**
 * Class ComponentDeleteForm
 *
 * Builds the form to delete Cohesion custom styles entities.
 *
 * @package Drupal\cohesion_elements\Form
 */
class ComponentDeleteForm extends CohesionDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a <em>Component</em> will delete all instances where its been used, 
    all content that’s been added to it and the configuration of your <em>Component</em>. This action cannot be undone.');
  }


}
