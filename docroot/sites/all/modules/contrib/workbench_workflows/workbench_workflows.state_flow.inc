<?php

/**
 * @file workbench_workflows.module
 */


/**
 * Implements hook_state_flow_plugins().
 */
function _workbench_workflows_state_flow_plugins() {
  $info = array();

  // Load all workflows, events and states.
  $workflows = workbench_workflows_load_all('workflows');
  $event_exportables = workbench_workflows_load_all('events');
  $state_exportables = workbench_workflows_load_all('states');

  // We're just building on top of the state_flow plugins.
  $class_path = drupal_get_path('module', 'state_flow') . '/plugins';

  foreach ($workflows as $workflow) {

    // This array will be built up and included with the state_flow plugin.
    $workflow_options = array();

    // Build up states in a way compatible with state_flow_plugins.
    foreach ($workflow->states as $state_name => $state) {
      $state_array = array(
        'label' => $state_exportables[$state_name]->admin_title,
      );

      // @todo, should there be a UI way of adding more of these?
      // Perhaps just a ui flag for "treat this as a published state."
      if ($state_name === 'published') {
        $state_array['on_enter'] = 'on_enter_published';
        $state_array['on_exit'] = 'on_exit_published';
      }

      $workflow_options['states'][$state_name] = $state_array;
    }

    // Build up events in a way compatible with state_flow_plugins.
    foreach ($workflow->events as $event_name => $event) {

      $origins = array();

      foreach($event_exportables[$event_name]->origin_states as $key => $state_value) {

        // $state_value might be 0.
        // This transformation is a little annoying. It might be better to fix
        // This in the validation of saving workbench_events.
        if (!empty($state_value)) {
          $origins[$key] = $state_value;
        }
      }

      $event_array = array(
        'origin' => $origins,
        'target' => $event_exportables[$event_name]->target_state,
        'guard' => 'workbench_workflows_guard',
        'label' => $event_exportables[$event_name]->admin_title,
      );

      $workflow_options['events'][$event_name] = $event_array;
    }

    $info['workbench_workflows__' . $workflow->name] = array(
      'handler' => array(
        // @todo, Once StateFlow can handle non-nodes, these properties will have
        // to respond to something in the $workflow variable to change the class
        // name and the file, parent etc.
        'class' => 'StateFlowNode',
        'file' => 'state_flow_node.inc',
        'path' => $class_path,
        'parent' => 'state_flow_entity',
        'workflow_options' => $workflow_options,
        'entity_type' => 'node',

        // @todo, make this configurable in workflow exportables.
        // @todo, should this key be inside "handler" or at the top level of the plugin?
        'event_form_options' => array(
          'event_element_label' => t('Change State'),
          'require_log_message' => FALSE,
        ),

      ),
    );
  }

  return $info;
}

/**
 * Return the appropriate workflow for a node.
 */
function workbench_workflows_select_workflow($entity) {

  $workflows = workbench_workflows_load_all('workflows');
  foreach($workflows as $workflow) {
    if (workbench_workflows_exportable_access($entity, $workflow)) {
      // Return the first workflow for which access returns TRUE.
      return $workflow;
    }
  }
  return FALSE;
}
