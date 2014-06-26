<?php
/**
 * @file
 * workbench_workflows_sample_feature.workbench_workflows.inc
 */

/**
 * Implements hook_default_workbench_workflows_events().
 */
function workbench_workflows_sample_feature_default_workbench_workflows_events() {
  $export = array();

  $event = new stdClass;
  $event->disabled = FALSE; /* Edit this to true to make a default event disabled initially */
  $event->api_version = 1;
  $event->name = 'draft';
  $event->editor_title = '';
  $event->admin_title = 'Going to Draft (event)';
  $event->admin_description = '';
  $event->requiredcontexts = array(
    0 => array(
      'identifier' => 'Node',
      'keyword' => 'node',
      'name' => 'entity:node',
      'id' => 1,
    ),
  );
  $event->contexts = array();
  $event->relationships = array();
  $event->access = array();
  $event->target_state = 'draft';
  $event->origin_states = array(
    'needs_review' => 'needs_review',
    'published' => 'published',
    'draft' => 0,
  );
  $export['draft'] = $event;

  $event = new stdClass;
  $event->disabled = FALSE; /* Edit this to true to make a default event disabled initially */
  $event->api_version = 1;
  $event->name = 'needs_review';
  $event->editor_title = '';
  $event->admin_title = 'Going to Needs Review (event)';
  $event->admin_description = '';
  $event->requiredcontexts = array(
    0 => array(
      'identifier' => 'Node',
      'keyword' => 'node',
      'name' => 'entity:node',
      'id' => 1,
    ),
  );
  $event->contexts = array();
  $event->relationships = array();
  $event->access = array();
  $event->target_state = 'needs_review';
  $event->origin_states = array(
    'draft' => 'draft',
    'published' => 'published',
    'needs_review' => 0,
  );
  $export['needs_review'] = $event;

  $event = new stdClass;
  $event->disabled = FALSE; /* Edit this to true to make a default event disabled initially */
  $event->api_version = 1;
  $event->name = 'published';
  $event->editor_title = '';
  $event->admin_title = 'Going to Published (event)';
  $event->admin_description = '';
  $event->requiredcontexts = array(
    0 => array(
      'identifier' => 'Node',
      'keyword' => 'node',
      'name' => 'entity:node',
      'id' => 1,
    ),
  );
  $event->contexts = array();
  $event->relationships = array();
  $event->access = array();
  $event->target_state = 'published';
  $event->origin_states = array(
    'draft' => 'draft',
    'needs_review' => 'needs_review',
    'published' => 0,
  );
  $export['published'] = $event;

  return $export;
}

/**
 * Implements hook_default_workbench_workflows_states().
 */
function workbench_workflows_sample_feature_default_workbench_workflows_states() {
  $export = array();

  $state = new stdClass;
  $state->disabled = FALSE; /* Edit this to true to make a default state disabled initially */
  $state->api_version = 1;
  $state->name = 'draft';
  $state->editor_title = '';
  $state->admin_title = 'Draft (state)';
  $state->admin_description = '';
  $state->requiredcontexts = array(
    0 => array(
      'identifier' => 'Node',
      'keyword' => 'node',
      'name' => 'entity:node',
      'id' => 1,
    ),
  );
  $state->contexts = array();
  $state->relationships = array();
  $state->access = array();
  $export['draft'] = $state;

  $state = new stdClass;
  $state->disabled = FALSE; /* Edit this to true to make a default state disabled initially */
  $state->api_version = 1;
  $state->name = 'needs_review';
  $state->editor_title = '';
  $state->admin_title = 'Needs Review (state)';
  $state->admin_description = '';
  $state->requiredcontexts = array(
    0 => array(
      'identifier' => 'Node',
      'keyword' => 'node',
      'name' => 'entity:node',
      'id' => 1,
    ),
  );
  $state->contexts = array();
  $state->relationships = array();
  $state->access = array();
  $export['needs_review'] = $state;

  $state = new stdClass;
  $state->disabled = FALSE; /* Edit this to true to make a default state disabled initially */
  $state->api_version = 1;
  $state->name = 'published';
  $state->editor_title = '';
  $state->admin_title = 'Published (state)';
  $state->admin_description = '';
  $state->requiredcontexts = array(
    0 => array(
      'identifier' => 'Node',
      'keyword' => 'node',
      'name' => 'entity:node',
      'id' => 1,
    ),
  );
  $state->contexts = array();
  $state->relationships = array();
  $state->access = array();
  $export['published'] = $state;

  return $export;
}

/**
 * Implements hook_default_workbench_workflows_workflows().
 */
function workbench_workflows_sample_feature_default_workbench_workflows_workflows() {
  $export = array();

  $workflow = new stdClass;
  $workflow->disabled = FALSE; /* Edit this to true to make a default workflow disabled initially */
  $workflow->api_version = 1;
  $workflow->name = 'sample_workflow';
  $workflow->editor_title = '';
  $workflow->admin_title = 'Sample Workflow (workflow)';
  $workflow->admin_description = '';
  $workflow->requiredcontexts = array(
    0 => array(
      'identifier' => 'Node',
      'keyword' => 'node',
      'name' => 'entity:node',
      'id' => 1,
    ),
  );
  $workflow->contexts = array();
  $workflow->relationships = array();
  $workflow->access = array();
  $workflow->category = '';
  $workflow->states = array(
    'draft' => 'draft',
    'needs_review' => 'needs_review',
    'published' => 'published',
  );
  $workflow->events = array(
    'draft' => 'draft',
    'needs_review' => 'needs_review',
    'published' => 'published',
  );
  $export['sample_workflow'] = $workflow;

  return $export;
}
