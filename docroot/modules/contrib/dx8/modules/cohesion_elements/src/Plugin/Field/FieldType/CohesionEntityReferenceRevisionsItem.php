<?php

namespace Drupal\cohesion_elements\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;
use Drupal\cohesion_elements\Entity\CohesionLayout;

/**
 * Defines the 'cohesion_entity_reference_revisions' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 * - target_bundle: (optional): If set, restricts the entity bundles which may
 *   may be referenced. May be set to an single bundle, or to an array of
 *   allowed bundles.
 *
 * @FieldType(
 *   id = "cohesion_entity_reference_revisions",
 *   label = @Translation("DX8 Entity reference revisions"),
 *   description = @Translation("An entity field containing a CohesionLayout entity reference to a specific revision."),
 *   category = @Translation("DX8"),
 *   no_ui = FALSE,
 *   class = "\Drupal\cohesion_elements\Plugin\Field\FieldType\CohesionEntityReferenceRevisionsItem",
 *   list_class = "\Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList",
 *   default_formatter = "cohesion_entity_reference_revisions_entity_view",
 *   default_widget = "cohesion_layout_builder_widget",
 *   cardinality = 1
 * )
 */
class CohesionEntityReferenceRevisionsItem extends EntityReferenceRevisionsItem {

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    $element['target_type'] = [
      '#type' => 'textfield',
      '#value' => $this->getSetting('target_type'),
      '#required' => TRUE,
      '#disabled' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if ($this->entity && $this->entity instanceof CohesionLayout) {
      /* @var CohesionLayout $this ->entity */

      $this->entity->setHost($this->getEntity());
      $this->entity->isDefaultRevision($this->entity->getHost()->isDefaultRevision());

      // Save if during a dx8 batch (rebuild/in use)
      $running_dx8_batch = &drupal_static('running_dx8_batch');
      if ($running_dx8_batch) {
        $this->entity->setNeedsSave(TRUE);
      }
    }

    parent::preSave();
  }

}
