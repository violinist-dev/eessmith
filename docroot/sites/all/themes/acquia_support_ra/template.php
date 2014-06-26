<?php

function acquia_support_ra_menu_link(array $variables) {
  $element = $variables['element'];
  $sub_menu = '';

  if ($element['#below']) {
    $sub_menu = drupal_render($element['#below']);
  }
  $options = array_merge($element['#localized_options'],array('html'=>TRUE));
  $output = l('<span>'.$element['#title'].'</span>', $element['#href'], $options);
  return '<li' . drupal_attributes($element['#attributes']) . '>' . $output . $sub_menu . "</li>\n";
}

function acquia_support_ra_heartbeat_attachments($variables) {

  $output = '<div class="heartbeat-attachments">';
  foreach ($variables['attachments'] as $plugin_name => $attachments) {
    $output .= implode(' ', $attachments);
  }
  $output .= '</div>';
  $output = '';

  return $output;
}

function acquia_support_ra_preprocess_page(&$variables, $hook) {
  if (isset($variables['node'])) {
    $variables['theme_hook_suggestions'][] = 'page__type__'. $variables['node']->type;
  }
}
