<?php

  module_load_include('inc', 'workbench_workflows', 'workbench_workflows.admin');
  $plugin = workbench_workflows_export_ui_base_plugin('workflow');

  // Override the form info because workflows have an extra screen.
  $plugin['form info']['order'] = array(
    'basic' => t('Settings'),
    'events' => t('Events'),
    'context' => t('Context'),
    'access' => t('Access'),
  );
