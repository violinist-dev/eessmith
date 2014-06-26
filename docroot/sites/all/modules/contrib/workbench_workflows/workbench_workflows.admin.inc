<?php

// @todo, document this file.

function workbench_workflows_admin_page() {

$item = menu_get_item();
  if ($content = system_admin_menu_block($item)) {
    $output = theme('admin_block_content', array('content' => $content));
  }
  else {
    $output = t('You do not have any administrative items.');
  }
  return $output;
}

/**
 * @param string $type
 *   Allowed values are 'state', 'event', and 'workflow'
 * @return array
 */
function workbench_workflows_export_ui_base_plugin($type) {

  $plural = $type . 's';

  $plugin = array(
    "schema" => "workbench_workflows_$plural",
    "access" => "administer_workbench_workflows",

    "menu" => array(
      "menu prefix" => "admin/structure/workbench-workflows",
      "menu item" => $plural,
      "menu title" => "Workbench " . $plural,
      "menu description" => "Add, edit or delete Workbench " . ucfirst($plural),
    ),

    "title singular" => t("workbench state"),
    "title singular proper" => t("Workbench " . ucfirst($type)),
    "title plural" => t("workbench $type"),
    "title plural proper" => t("Workbench " . ucfirst($plural)),

    "handler" => "workbench_". $plural . "_ui",

    "use wizard" => TRUE,
    "form info" => array(
      "order" => array(
        "basic" => t("Basic information"),
        "context" => t("Contexts"),
        "access" => t("Access"),
      ),
    ),
  );

  return $plugin;
}
