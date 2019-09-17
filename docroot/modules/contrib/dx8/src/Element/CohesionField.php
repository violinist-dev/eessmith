<?php

/**
 * @file
 */

namespace Drupal\cohesion\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\cohesion_elements\Entity\CohesionLayout;
use Drupal\cohesion\Entity\CohesionSettingsInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides a DX8 layout form element. This is used in the BaseForm and
 * the CohesionLayout field formatter.
 *
 * @FormElement("cohesionfield")
 */
class CohesionField extends FormElement {

  /**
   * @return array
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processCohesion'],
      ],
      '#pre_render' => [
        [$class, 'processCohesionError'],
      ],
      '#element_validate' => [
        [$class, 'validateElement'],
      ],
    ];
  }

  /**
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function validateElement(&$element, FormStateInterface $form_state) {
    $entity = $element['#entity'];
    if ($entity instanceof CohesionSettingsInterface || $entity instanceof CohesionLayout) {
      $entity->setJsonValue($element['#json_values']);
      $errors = $entity->jsonValuesErrors();

      if ($errors !== FALSE) {
        // If errors has uuid it is a layout canvas error
        if (isset($errors['uuid'])) {
          // Set the uuid so it can be added to drupalSettings later
          $uuids = &drupal_static('cohesion_layout_canvas_error');
          if (is_null($uuids)) {
            $uuids = [];
          }
          $uuids[] = $errors['uuid'];
        }

        $form_state->setError($element, $errors['error']);
      }
    }
  }

  /**
   * @param $element
   *
   * @return mixed
   */
  public static function processCohesionError($element) {

    if (isset($element['#errors'])) {
      $uuids = &drupal_static('cohesion_layout_canvas_error');
      $element['#attached']['drupalSettings']['cohesion']['layout_canvas_errors'] = $uuids;
    }
    return $element;
  }

  /**
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   *
   * @return mixed
   */
  public static function processCohesion(&$element, FormStateInterface $form_state, &$complete_form) {

    if (isset($element['#title'])) {
      $element['title'] = [
        '#type' => 'label',
        '#title' => $element['#title'],
        '#required' => isset($element['#required']) ? $element['#required'] : FALSE,
        '#weight' => 0,
      ];
    }

    // Prevent progress spinning wheel from loading if form is field config form
    $is_loading = ($form_state->getFormObject()->getFormId() == 'field_config_edit_form') ? '' : 'is-loading';

    // Add the entity style.
    $matches = [
      'cohesion-custom-style' => 'cohesion_custom_style',
      'cohesion-style-helper' => 'cohesion_custom_style',
      'cohesion-base-styles' => 'cohesion_base_styles',
      'cohesion-content-templates' => 'cohesion_content_templates',
      'cohesion-master-templates' => 'cohesion_master_templates',
      'cohesion-view-templates' => 'cohesion_view_templates',
      'cohesion-menu-templates' => 'cohesion_menu_templates',
      'cohesion-helper' => 'cohesion_helper',
      'cohesion-component' => 'cohesion_component',
    ];

    foreach ($matches as $search => $entityTypeId) {
      if (strstr($complete_form['#attributes']['data-drupal-selector'], $search)) {
        $element['#attached']['drupalSettings']['cohesion']['entityTypeId'] = $entityTypeId;
        break;
      }
    }

    // Add the json values.
    if (isset($element['#canvas_name'])) {
      $drupal_settings_json_values = [$element['#canvas_name'] => json_decode($element['#json_values'])];
    }
    else {
      $drupal_settings_json_values = json_decode($element['#json_values']);
    }

    // Add the data.
    $element['#attached']['drupalSettings']['cohesion']['entityForm']['json_values'] = $drupal_settings_json_values;
    if (isset($element['#json_mapper'])) {
      $element['#attached']['drupalSettings']['cohesion']['entityForm']['json_mapper'] = json_decode($element['#json_mapper']);
    }

    // Add the max file size.
    $element['#attached']['drupalSettings']['cohesion']['upload_max_filesize'] = file_upload_max_size();

    // Image browser page attachments.
    \Drupal::service('cohesion_image_browser.update_manager')->sharedPageAttachments($element['#attached'], $element['#entity'] instanceof ContentEntityInterface ? 'content' : 'config');

    // Attach the editor.module text format settings
    $pluginManager = \Drupal::service('plugin.manager.editor');

    // Get the filter formats the current user has permissions to access.
    $formats = [];
    foreach (\Drupal::currentUser()->getRoles() as $role) {
      $formats = array_merge(filter_get_formats_by_role($role), $formats);
    }

    $format_ids = array_keys($formats);
    $element['#attached'] = BubbleableMetadata::mergeAttachments($element['#attached'], $pluginManager->getAttachments($format_ids));

    // Patch the text format labels ("Full HTML") into the Drupal settings.
    if (isset($element['#attached']['drupalSettings']['editor']['formats'])) {
      foreach ($element['#attached']['drupalSettings']['editor']['formats'] as $key => $settings) {
        if (isset($formats[$key])) {
          $element['#attached']['drupalSettings']['editor']['formats'][$key]['label'] = $formats[$key]->get('name');
        }
      }
    }

    $element['#attached']['drupalSettings']['editor']['default'] = NULL;
    if (isset($element['#attached']['drupalSettings']['editor']['formats']['cohesion'])) {
      $element['#attached']['drupalSettings']['editor']['default'] = 'cohesion';
    }
    elseif(is_array($element['#attached']['drupalSettings']['editor']['formats'])){
      $last_format = end($element['#attached']['drupalSettings']['editor']['formats']);
      if ($last_format && isset($last_format['format'])) {
        $element['#attached']['drupalSettings']['editor']['default'] = $last_format['format'];
      }
    }

    // Attach the Angular app.
    $element['#attached']['library'][] = 'cohesion/cohesion-admin';

    // Load icon library for admin pages if it has been generated.
    $icon_lib_path = COHESION_CSS_PATH . '/cohesion-icon-libraries.css';
    if (file_exists($icon_lib_path)) {
      $element['#attached']['library'][] = 'cohesion/admin-icon-libraries';
    }

    // Load responsive grid settings for admin pages if it has been generated.
    $grid_lib_path = COHESION_CSS_PATH . '/cohesion-responsive-grid-settings.css';
    if (file_exists($grid_lib_path)) {
      $element['#attached']['library'][] = 'cohesion/admin-responsive-grid-settings';
    }

    // Set Global form attributes
    $complete_form['toast'] = [
      '#type' => 'item',
      '#markup' => '<toast></toast>',
      '#allowed_tags' => ['toast',],
      '#parents' => [],
    ];

    $classes = [$is_loading, 'coh-preloader-large', 'coh-form'];
    if (isset($element['#classes']) && is_array($element['#classes'])) {
      $classes = array_merge($classes, $element['#classes']);
    }

    $complete_form['#attributes']['class'] = array_merge($complete_form['#attributes']['class'], $classes);

    $complete_form['#attributes']['ng-class'] = "formLoaded() ? 'is-loaded' : '{$is_loading}'";
    if (!isset($complete_form['#attributes']['ng-init'])) {
      $complete_form['#attributes']['ng-init'] = 'onInit(formRenderer, \'' . $element['#ng-init']['group'] . '\', \'' . $element['#ng-init']['id'] . '\')';
      $complete_form['#attached']['drupalSettings']['cohOnInitForm'] = \Drupal::service('settings.endpoint.utils')->getCohFormOnInit($element['#ng-init']['group'], $element['#ng-init']['id']);
    }
    $complete_form['#attributes']['name'] = 'forms.formRenderer';
    $complete_form['#attributes']['ng-submit'] = 'onSubmit($event, forms.formRenderer)';
    $complete_form['#attributes']['novalidate'] = '1';

    $complete_form['#attributes']['ng-controller'] = 'CohFormRendererCtrl';

    if (isset($element['#canvas_name'])) {
      $ng_init = 'ng-init="registerLayoutCanvas(\'' . $element['#canvas_name'] . '\')"';
      $sf_form = 'sf-form="form.form.' . $element['#canvas_name'] . '"';
      $class_name_canvas = $element['#canvas_name'];
    }
    else {
      $ng_init = '';
      $sf_form = 'sf-form="form.form"';
      $class_name_canvas = '';
    }

    // Add the schemaform.
    $element['ng_init_schemaform'] = [
      '#markup' => '<div ' . $ng_init . ' sf-schema="form.schema" ' . $sf_form . ' sf-model="form.model" sf-options="form.options"></div>',
      '#weight' => 1,
    ];

    // Add the token browser.
    if (isset($element['#token_browser'])) {
      // Build the token tree (token.module).
      $token_tree = [
        '#theme' => 'token_tree_link',
        '#token_types' => ($element['#token_browser'] == 'all') ? 'all' : [$element['#token_browser']],
        // Token types (usually 'node').
      ];

      // Render it using the service.
      $rendered_token_tree = \Drupal::service('renderer')->render($token_tree);

      // Attach the bootstrap fix to the form element.
      $element['#attached']['library'][] = 'cohesion/cohesion_token';
    }

    $element['json_values'] = [
      '#type' => 'hidden',
      '#title' => t('Values data'),
      '#default_value' => '{}',
      '#description' => t('Values data for the DX8 website settings.'),
      '#required' => FALSE,
      '#attributes' => [
        'class' => [$class_name_canvas . '_modelAsJson'],
      ],
      '#weight' => 3,
    ];

    if (isset($element['#json_mapper'])) {
      $element['json_mapper'] = [
        '#type' => 'hidden',
        '#title' => t('Mapper'),
        '#default_value' => '{}',
        '#description' => t("mapper for the Cohesion website settings."),
        '#required' => FALSE,
        '#weight' => 5,
        '#attributes' => [
          'class' => [$class_name_canvas . '_mapperAsJson'],
        ],
      ];
    }

    // Show the JSON field.
    $show_json_fields = FALSE;

    $config = \Drupal::configFactory()->getEditable('cohesion_devel.settings');

    if ($config && $config->get("show_json_fields")) {  // Check config
      $show_json_fields = TRUE;
    }
    else {  // Check global $settings[]
      if (Settings::get('dx8_json_fields', FALSE)) {
        $show_json_fields = TRUE;
      }
    }

    if ($show_json_fields) {
      $element['#attached']['drupalSettings']['cohesion']['showJsonFields'] = TRUE;

      $element['json_values_view'] = [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#attributes' => [
          'class' => [$class_name_canvas . '_modelAsJsonView'],
          'id' => $class_name_canvas . '_modelAsJsonView',
          'style' => 'max-height: 250px; border: 1px solid #ccc; padding: 1em;',
          'title' => 'Model',
        ],
        '#weight' => 4,
      ];

      if (isset($element['#json_mapper'])) {
        $element['json_mapper_view'] = [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#attributes' => [
            'class' => [$class_name_canvas . '_mapperAsJsonView'],
            'style' => 'max-height: 250px; border: 1px solid #ccc; padding: 1em;',
            'title' => 'Mapper',
          ],
          '#weight' => 6,
        ];
      }
    }

    // Return the form definitions.
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Return element configuration if set.
    if ($input && is_array($input) && array_key_exists('json_values', $input)) {
      $element['#json_values'] = $input['json_values'];

      if (isset($element['#json_mapper'])) {
        $element['#json_mapper'] = $input['json_mapper'];
      }
      return $input['json_values'];
    }

    // Return NULL otherwise.
    return NULL;
  }

}
