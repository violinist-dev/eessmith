<?php

/**
 * Modify the menu links to wrap the link name in a <span> for easier theming
 * 
 * @param array $variables
 * @return type 
 */
function acquia_d7_menu_link(array $variables) {
  $element = $variables['element'];
  $sub_menu = '';

  if ($element['#below']) {
    $sub_menu = drupal_render($element['#below']);
  }
  $options = array_merge($element['#localized_options'],array('html'=>TRUE));
  $output = l('<span>'.$element['#title'].'</span>', $element['#href'], $options);
  return '<li' . drupal_attributes($element['#attributes']) . '>' . $output . $sub_menu . "</li>\n";
}

/**
 * Theme alteration to theme_table. Wraps a div around tables for help in 
 * theming
 * 
 * @param array $table_array
 * @return string 
 */
function acquia_d7_table($table_array) {
  return '<div class="table-wrapper">' . theme_table($table_array) . '</div>';
}