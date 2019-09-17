<?php

namespace Drupal\cohesion\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CohesionBaseForm.
 *
 * @package Drupal\cohesion\Form
 */
class CohesionBaseForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $operation = $this->getOperation();

    switch ($operation) {

      case 'edit':
        $form['#title'] = $this->t('Edit %label', [
          '%label' => strtolower($this->entity->label()),
        ]);
        break;

      case 'duplicate':
        // Clone the entity
        $this->entity = $this->entity->createDuplicate(); // create a duplicate with a new UUID

        $form['#title'] = $this->t('Duplicate of %label', [
          '%label' => strtolower($this->entity->label()),
        ]);

        break;
    }

    /** @var \Drupal\cohesion\Entity\CohesionConfigEntityBase $entity */
    $entity = $this->entity;
    $form_class = str_replace('_', '-', $entity->getEntityTypeId()) . '-' . str_replace('_', '-', $entity->id()) . '-form';
    $form_class_entity = str_replace('_', '-', $entity->getEntityTypeId()) . '-edit-form';

    $jsonValue = $entity->getJsonValues() ? $entity->getJsonValues() : "{}";
    $jsonMapper = $entity->getJsonMapper() ? $entity->getJsonMapper() : "{}";

    // Retain field values if validation error
    $response = $this->getRequest()->request->all();
    if ($response) {
      $jsonValue = ($response && isset($response['json_values'])) ? $response['json_values'] : $jsonValue;
      $jsonMapper = ($response && isset($response['json_mapper'])) ? $response['json_mapper'] : $jsonMapper;
    }

    // Regenerate UUID for duplicate component entity
    // @todo - this logic should be in the child form.
    if ($this->getOperation() == 'duplicate' &&
      $entity instanceof \Drupal\cohesion_elements\Entity\Component) {
      $jsonValue = \Drupal::service('cohesion.api.utils')->uniqueJsonKeyUuids($jsonValue);
    }

    $form['cohesion'] = [
      // Drupal\cohesion\Element\CohesionField.
      '#type' => 'cohesionfield',
      '#json_values' => $jsonValue,
      '#json_mapper' => $jsonMapper,
      '#classes' => [$form_class_entity, $form_class],
      '#entity' => $entity,
      '#ng-init' => [
        'group' => $entity->getAssetGroupId(),
        'id' => $entity->id(), 
      ],
    ];

    if($entity->isLayoutCanvas()){
      $form['cohesion']['#canvas_name'] = 'config_layout_canvas';
    }

    $form['details'] = [
      '#type' => 'cohesion_accordion',
      '#title' => t('Details'),
      '#weight' => -99,
      'label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#maxlength' => 255,
        '#default_value' => $entity->label(),
        '#required' => TRUE,
        '#access' => TRUE,
        '#weight' => 0,
      ],
      '#open' => 'panel-open',
    ];

    if ($operation == 'duplicate') {
      $form['details']['label']['#default_value'] = t('Duplicate of ') . $form['details']['label']['#default_value'];
    }

    if ($this->entity->getEntityType()->hasKey('status')) {
      $form['status'] = [
        '#title' => $this->t('Enable'),
        '#type' => 'checkbox',
        '#default_value' => $entity->isModified() ? $entity->status() : TRUE,
        '#weight' => 10,
      ];
    }

    if ($this->entity->getEntityType()->hasKey('selectable')) {
      $form['selectable'] = [
        '#title' => $this->t('Enable selection'),
        '#type' => 'checkbox',
        '#default_value' => $entity->isModified() ? $entity->isSelectable() : TRUE,
        '#weight' => 10,
      ];
    }

    // Add the shared attachments.
    _cohesion_shared_page_attachments($form);

    /* You will need additional form elements for your custom properties. */
    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state, $redirect = NULL) {
    // Set modified on save
    // Only on form and not on entity as modified should be set upon user action not code
    $this->entity->setModified();

    $status = parent::save($form, $form_state);

    // Show status message.
    $message = $this->t('@verb the @type %label.', [
      '@verb' => ($status == SAVED_NEW) ? 'Created' : 'Saved',
      '@type' => $this->entity->getEntityType()->getSingularLabel(),
      '%label' => $this->entity->label(),
    ]);
    drupal_set_message($message);
    
    \Drupal::request()->query->remove('destination');

    $element = $form_state->getTriggeringElement();
    if (isset($element['#continue']) && $element['#continue']) {
      $form_state->setRedirectUrl($this->entity->toUrl());
    } elseif ($redirect) {
      $form_state->setRedirectUrl($redirect);
    } else {
      $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    // Add a "Continue" button.
    $actions['continue'] = $actions['submit'];
    // If the "Continue" button is clicked, redirect back to same page.
    $actions['continue']['#continue'] = TRUE;
    $actions['continue']['#dropbutton'] = 'save';
    $actions['continue']['#value'] = t('Save and continue');
    $actions['continue']['#weight'] = 0;


    // Add a "Save" button.
    $actions['enable'] = $actions['submit'];
    $actions['enable']['#continue'] = FALSE;
    $actions['enable']['#dropbutton'] = 'save';
    $actions['enable']['#value'] = t('Save');
    $actions['enable']['#weight'] = 1;

    // Remove the "Save" button.
    $actions['submit']['#access'] = FALSE;

    return $actions;
  }

  /**
   * Required by machine name field validation.
   * @param $value
   * @return bool
   */
  public function exists($value) {
    return FALSE;
  }

  /**
   * Set the entity ID based on the machine_name field in the form or generate a random id if no machine_name field.
   *
   * @param $entity
   * @param $form_state
   */
  public function setEntityIdFromForm($entity, $form_state) {
    // If the form has a machine name field, use it as the id for the entity..
    if ($machine_name = $form_state->getValue('machine_name')) {
      $entity->set('id', $this->entity->getEntityMachineNamePrefix() . $machine_name);
    }
    // If form doesn't have a machine name field, generate a random id for the entity.
    else {
      $entity->set('id', implode('_', [
        hash('crc32b', $entity->uuid()),
      ]));
    }
  }

  /**
   * Check to see if the machine name is unique.
   *
   * @param $value
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkUniqueMachineName($value) {

    $query = $this->entityTypeManager->getStorage($this->entity->getEntityTypeId())->getQuery();
    $parameter = $this->entity->getEntityMachineNamePrefix() . $value;
    $query->condition('id', $parameter);
    $entity_ids = $query->execute();

    return count($entity_ids) > 0;
  }
}
