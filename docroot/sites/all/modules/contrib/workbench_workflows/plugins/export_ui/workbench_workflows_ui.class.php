<?php

module_load_include('php', 'workbench_workflows', 'plugins/export_ui/workbench_base_ui.class');

class workbench_workflows_ui extends workbench_base_ui {
  function init($plugin) {
    parent::init($plugin);
    ctools_include('context');
  }
/*
  function list_form(&$form, &$form_state) {

    parent::list_form($form, $form_state);

    foreach ($this->items as $item) {
      $categories[$item->category] = $item->category ? $item->category : t('workbench workflows');
    }

    $form['top row']['category'] = array(
      '#type' => 'select',
      '#title' => t('Category'),
      '#options' => $categories,
      '#default_value' => 'all',
      '#weight' => -10,
    );

  }

  function list_filter($form_state, $item) {
    if ($form_state['values']['category'] != 'all' && $form_state['values']['category'] != $item->category) {
      return TRUE;
    }


    return parent::list_filter($form_state, $item);
  }
*/
  function list_sort_options() {
    return array(
      'disabled' => t('Enabled, title'),
      'title' => t('Title'),
      'name' => t('Name'),
      'category' => t('Category'),
      'storage' => t('Storage'),
      'weight' => t('Weight'),
    );
  }

  function list_build_row($item, &$form_state, $operations) {
    // Set up sorting
    switch ($form_state['values']['order']) {
      case 'disabled':
        $this->sorts[$item->name] = empty($item->disabled) . $item->admin_title;
        break;
      case 'title':
        $this->sorts[$item->name] = $item->admin_title;
        break;
      case 'name':
        $this->sorts[$item->name] = $item->name;
        break;
      case 'category':
        $this->sorts[$item->name] = ($item->category ? $item->category : t('workbench workflows')) . $item->admin_title;
        break;
      case 'weight':
        $this->sorts[$item->name] = $item->weight;
        break;
      case 'storage':
        $this->sorts[$item->name] = $item->type . $item->admin_title;
        break;
    }

    $category = $item->category ? check_plain($item->category) : t('workbench workflows');

    $this->rows[$item->name] = array(
      'data' => array(
        array('data' => check_plain($item->admin_title), 'class' => array('ctools-export-ui-title')),
        array('data' => check_plain($item->name), 'class' => array('ctools-export-ui-name')),
        array('data' => $category, 'class' => array('ctools-export-ui-category')),
        array('data' => $item->type, 'class' => array('ctools-export-ui-storage')),
        array('data' => $item->weight, 'class' => array('ctools-export-ui-weight')),
        array('data' => theme('links', array('links' => $operations)), 'class' => array('ctools-export-ui-operations')),
      ),
      'title' => !empty($item->admin_description) ? check_plain($item->admin_description) : '',
      'class' => array(!empty($item->disabled) ? 'ctools-export-ui-disabled' : 'ctools-export-ui-enabled'),
    );
  }

  function list_table_header() {
    return array(
      array('data' => t('Title'), 'class' => array('ctools-export-ui-title')),
      array('data' => t('Name'), 'class' => array('ctools-export-ui-name')),
      array('data' => t('Category'), 'class' => array('ctools-export-ui-category')),
      array('data' => t('Storage'), 'class' => array('ctools-export-ui-storage')),
      array('data' => t('Weight'), 'class' => array('ctools-export-ui-weight')),
      array('data' => t('Operations'), 'class' => array('ctools-export-ui-operations')),
    );
  }

  function edit_form(&$form, &$form_state) {
    // Get the basic edit form
    parent::edit_form($form, $form_state);

    $form['title']['#title'] = t('Title');
    $form['title']['#description'] = t('The title for this workbench workflow.');

    $form['states'] = array(
      '#type' => 'checkboxes',
      '#options' => workbench_workflows_options('states'),
      '#default_value' => $form_state['item']->states,
      '#title' => t('States'),
      '#description' => t("States available in this workflow."),
    );

    $form['weight'] = array(
      '#type' => 'textfield',
      '#default_value' => $form_state['item']->weight,
      '#title' => t('Weight'),
      '#element_validate' => array('element_validate_integer_positive'),
    );
  }

  /**
   * Validate submission of the workbench workflow edit form.
   *
   * @todo evaluate need to validate states here.
   */
  function edit_form_basic_validate($form, &$form_state) {
    parent::edit_form_validate($form, $form_state);
  //  if (preg_match("/[^A-Za-z0-9 ]/", $form_state['values']['category'])) {
//      form_error($form['category'], t('Categories may contain only alphanumerics or spaces.'));
   // }
  }

  function edit_form_events(&$form, &$form_state) {

    $available_states = array();
    foreach ($form_state['item']->states as $key => $value) {
      if (!empty($value)) {
        $available_states[$key] =$value;
      }
    }

    ctools_include('export');
    $workbench_events = workbench_workflows_load_all('events');
    $event_options = array();
    $unavailable_events = array();
    $unavailable_text_string = '';
    $unavailable_events_replacements = array();

    foreach ($workbench_events as $workbench_event) {

      // @todo
      // Exclude events when there is not an origin state in the workflow.
      if (in_array($workbench_event->target_state, $available_states)) {
        $event_options[$workbench_event->name] = $workbench_event->admin_title;
      } else {
        $unavailable_text_string .= '%' . $workbench_event->name . ', ';
        $unavailable_events[$workbench_event->name] = $workbench_event->admin_title;
        $unavailable_events_replacements['%' . $workbench_event->name] = $workbench_event->admin_title;
      }
    }

    $form['events'] = array(
      '#type' => 'checkboxes',
      '#options' => $event_options,
      '#default_value' => $form_state['item']->events,
      '#title' => t('Events'),
    );

    // @@TODO
    // Handle pluralization of event/events
    if (!empty($unavailable_text_string)) {
      $form['events']['#description'] = t("Unavailable events include: " . $unavailable_text_string, $unavailable_events_replacements);
    }
  }
}
