<?php

class workbench_base_ui extends ctools_export_ui {

  function edit_form_context(&$form, &$form_state) {

    // Force setting of the node required context.
    // @todo, add a way of setting which entity type this state, event, workflow works with.
    $form_state['item']->requiredcontexts = array(
      0 => array(
          'identifier' => 'Node',
          'keyword' => 'node',
          'name' => 'entity:node',
          'id' => 1
        )
     );

    ctools_include('context-admin');
    ctools_context_admin_includes();
    ctools_add_css('ruleset');

    // Set this up and we can use CTools' Export UI's built in wizard caching,
    // which already has callbacks for the context cache under this name.
    $module = 'export_ui::' . $this->plugin['name'];
    $name = $this->edit_cache_get_key($form_state['item'], $form_state['form type']);

    ctools_context_add_relationship_form($module, $form, $form_state, $form['relationships_table'], $form_state['item'], $name);

    // While these modules are in an experimental state, throw this message in
    // for some clarity.
    drupal_set_message("This is a screen where the CTools export UI needs customization to fit the needs of States/Events/Workflows.
    <br><br>
    The idea here is that a site builder could start from an article node and bring in related contexts. For instance the relevant Organic
    Group could be brought it with one of these relationship plugins. One the next screen the site builder can then use the relevant Organic Group
    as the basis for access control. So the end user's access to this state/event/workflow could be restricted on an arbitrary condition of the Organic
    Group, like its published status or taxonomy values.
    <br><br>
    If you have an opinion on how to improve this UI please go to
    <a href='http://drupal.org/node/1376258'>http://drupal.org/node/1376258</a>", 'warning');
  }

  function edit_form_access(&$form, &$form_state) {
    // The 'access' UI passes everything via $form_state, unlike the 'context' UI.
    // The main difference is that one is about 3 years newer than the other.
    ctools_include('context');
    ctools_include('context-access-admin');

    $form_state['access'] = $form_state['item']->access;
    $form_state['contexts'] = ctools_context_load_contexts($form_state['item']);

    $form_state['module'] = 'ctools_export_ui';
    $form_state['callback argument'] = $form_state['object']->plugin['name'] . ':' . $form_state['object']->edit_cache_get_key($form_state['item'], $form_state['form type']);
    $form_state['no buttons'] = TRUE;

    $form = ctools_access_admin_form($form, $form_state);

    // While these modules are in an experimental state, throw this message in
    // for some clarity.
    drupal_set_message("Here the site builder can set arbitary restrictions on when this state/event/workflow
      is available. For instance the site builder could make this available under the condition that the currently
      logged in user is either the node author (if this is a node state/event/workflow) or has the role 'site contributor'.
      <br><br>
      If you have an opinion on how to improve this UI please go to
      <a href='http://drupal.org/node/1376258'>http://drupal.org/node/1376258</a>", 'warning');
  }

  function edit_form_rules_submit(&$form, &$form_state) {
    $form_state['item']->access['logic'] = $form_state['values']['logic'];
  }
}
