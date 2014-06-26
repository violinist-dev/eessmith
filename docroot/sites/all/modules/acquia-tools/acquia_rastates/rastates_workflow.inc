<?php

/**
 * @file
 * Energy.gov implementation of State Flow, an extension of the State Machine class
 */

state_machine_load_class_file();

class RAWorkflow extends StateFlow {

  public function init() {
    // Initialize states
    $this->create_state('ticket created');
    $this->create_state('locally updating');
    $this->create_state('branch deployed');
    $this->create_state('client testing branch');
    $this->create_state('branch approved');
    $this->create_state('merging branch');
    $this->create_state('tag deployed');
    $this->create_state('tag approved for production');
    $this->create_state('tag deployed production');
    $this->create_state('ra troubleshooting');
    $this->create_state('published', array(
      'on_enter' => array($this, 'on_enter_published'),
      'on_exit' => array($this, 'on_exit_published'),
    ));

    // Initialize events
    $this->create_event('locally updating', array(
      'origin' => 'ticket created',
      'target' => 'locally updating',
    ));
    $this->create_event('branch deployed', array(
      'origin' => 'locally updating',
      'target' => 'branch deployed',
    ));
    $this->create_event('unpublish', array(
      'origin' => 'published',
      'target' => 'unpublished',
    ));
    $this->create_event('ra troubleshooting', array(
      'origin' => array('client testing branch', 'client testing tag', 'branch deployed'),
      'target' => 'ra troubleshooting',
    ));
  }

  public function on_event_fail($event) {
    $key = array_search($event, $this->events);
    drupal_set_message(t('Could not transition node using %event event.', array('%event' => $key)), 'error');
  }
}
