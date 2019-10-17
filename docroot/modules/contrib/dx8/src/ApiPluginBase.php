<?php

namespace Drupal\cohesion;

use Drupal\cohesion\Entity\EntityJsonValuesInterface;
use Drupal\cohesion\Services\CohesionUtils;
use Drupal\cohesion_elements\Entity\CohesionLayout;
use Drupal\cohesion_website_settings\Entity\SCSSVariable;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\cohesion_website_settings\Entity\WebsiteSettings;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\cohesion_website_settings\Entity\Color;
use Drupal\cohesion_website_settings\Entity\FontStack;
use Drupal\cohesion_website_settings\Entity\IconLibrary;
use Drupal\Core\Utility\Token;
use Drupal\cohesion\Services\LocalFilesManager;

/**
 * Class ApiPluginBase
 *
 * @package Drupal\cohesion
 */
abstract class ApiPluginBase extends PluginBase implements ApiPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /** @var \Drupal\Core\StreamWrapper\StreamWrapperManager */
  protected $streamWrapperManager;

  /** @var \Drupal\Core\Utility\Token */
  protected $tokenService;

  /** @var \Drupal\cohesion\Services\LocalFilesManager */
  protected $localFilesManager;

  /** @var \Drupal\Core\Entity\EntityInterface | NULL */
  protected $entity;

  /**
   * The config installer.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  protected $configInstaller;

  /**
   * The cohesion utils helper.
   *
   * @var CohesionUtils
   */
  protected $cohesionUtils;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The data to be sent to the API
   *
   * @var \stdClass $data
   */
  protected $data;
  protected $response = NULL;

  /**
   * Whether to save the data in database / files (templates, stylesheet)
   *
   * @var bool $saveData
   */
  protected $saveData = TRUE;

  /**
   * ApiPluginBase constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManager $stream_wrapper_manager
   * @param \Drupal\Core\Utility\Token $token_service
   * @param \Drupal\cohesion\Services\LocalFilesManager $local_files_manager
   * @param CohesionUtils $cohesion_utils
   * @param ModuleHandlerInterface $module_handler
   */

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, StreamWrapperManager $stream_wrapper_manager, Token $token_service, LocalFilesManager $local_files_manager, ConfigInstallerInterface $config_installer, CohesionUtils $cohesion_utils, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Save the injected services.
    $this->entityTypeManager = $entity_type_manager;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->tokenService = $token_service;
    $this->localFilesManager = $local_files_manager;
    $this->configInstaller = $config_installer;
    $this->cohesionUtils = $cohesion_utils;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('stream_wrapper_manager'),
      $container->get('token'),
      $container->get('cohesion.local_files_manager'),
      $container->get('config.installer'),
      $container->get('cohesion.utils'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * @param EntityJsonValuesInterface $entity
   */
  public function setEntity(EntityJsonValuesInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Get the data from the API call, if no data return an empty array
   *
   * @return array
   */
  public function getData() {
    if (isset($this->response['data'])) {
      return $this->response['data'];
    } else {
      return [];
    }
  }

  /**
   * @param $to_save
   */
  public function setSaveData($to_save) {
    $this->saveData = boolval($to_save);
  }

  /**
   * @return bool
   */
  public function getSaveData() {
    return $this->saveData;
  }

  /**
   * Prepare the data to be send to the API in order to generate the right
   * asset. Attach the JSON representation of the stylesheet to the data
   * object.
   *
   * @param bool $attach_css
   *
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  protected function prepareData($attach_css = TRUE) {
    // Set up the data object that will be sent to the style API endpoint.
    $this->data = new \stdClass();
    $this->data->settings = new \stdClass();
    // Entities to be processed
    $this->data->settings->forms = [];
    // Global website settings needed to build the entities
    $this->data->settings->website_settings = new \stdClass();

    // Attach the website settings data (this is needed for every request).
    $website_settings_types = [
      'base_unit_settings',
      'responsive_grid_settings',
      'default_font_settings',
    ];

    $website_settings_storage = $this->entityTypeManager->getStorage('cohesion_website_settings');

    foreach ($website_settings_types as $website_settings_type) {
      // If the entity being saved is a website settings use it rather then the one
      // from the database as it has the latest data.
      /** @var WebsiteSettings $website_settings */
      if (isset($this->entity) && $this->entity->id() == $website_settings_type) {
        $website_settings = $this->entity;
      }
      else {
        // Otherwise, load the entity in and use its json values.
        $website_settings = $website_settings_storage->load($website_settings_type);
      }

      if($website_settings){
        $resource_object = $website_settings->getResourceObject();
        $this->data->settings->website_settings->$website_settings_type = $resource_object;
      }
    }

    // Attach icon libraries (as fake website settings).
    $this->data->settings->website_settings->icon_libraries = $this->getIconGroup();
    // Attach the font stacks (as fake website settings).
    $this->data->settings->website_settings->font_libraries = $this->getFontGroup();
    $this->data->settings->website_settings->color_palette = $this->getColorGroup();
    $this->data->settings->website_settings->scss_variables = $this->getSCSSVariableGroup();


    // Set entity type/id and to api
    if (isset($this->entity)) {
      $this->data->entity_type_id = $this->entity->getEntityTypeId();
      $this->data->entity_id = $this->entity->id();
    }

    // Attach the JSON representation of the stylesheet to the data object.
    if ($attach_css == TRUE) {
      $original_css_path = $this->localFilesManager->getStyleSheetFilename('json');
      $this->data->css = file_exists($original_css_path) ? file_get_contents($original_css_path) : '';
    }

    // Set styles sort order
    $this->data->sort_order = isset($this->data->sort_order)? $this->data->sort_order : [];
    $this->data->style_group = isset($this->data->style_group)? $this->data->style_group : null;
  }

  /**
   * Get combined icon library for settings and forms.
   *
   * @return array|string
   */
  public function getIconGroup() {
    $return = [
      'title' => 'Icon libraries',
      'type' => 'website_settings',
      'bundle' => 'icon_libraries',
      'mapper' => [],
    ];

    $icon_library_values = [];

    try {
      /** @var IconLibrary $icon_library_entity */
      foreach ($this->entityTypeManager->getStorage('cohesion_icon_library')
                 ->loadMultiple() as $icon_library_entity) {
        $icon_library_values['iconLibraries'][] = ['library' => $this->patchUri($icon_library_entity->getDecodedJsonValues())];
      }
      $return['values'] = $icon_library_values;
    }
    catch (\Exception $e) {
      $return = [];
    }

    return $return;
  }

  /**
   * Get combined font stacks for settings and forms.
   *
   * @return array|string
   */
  public function getFontGroup() {
    $return = [
      'title' => 'Font stacks',
      'type' => 'website_settings',
      'bundle' => 'font_libraries',
      'mapper' => [],
    ];

    $font_stack_values = [];
    $font_library_values = [];

    try {
      /** @var FontStack $font_stack_entity */
      foreach ($this->entityTypeManager->getStorage('cohesion_font_stack')
                 ->loadMultiple() as $font_stack_entity) {
        $font_stack_values[] = ['stack' => $this->patchUri($font_stack_entity->getDecodedJsonValues())];
      }

      $return['values'] = ['fontStacks' => $font_stack_values];

      /** @var \Drupal\cohesion_website_settings\Entity\FontLibrary $font_library_entity */
      foreach ($this->entityTypeManager->getStorage('cohesion_font_library')
                 ->loadMultiple() as $font_library_entity) {
        $font_library_values[] = ['library' => $this->patchUri($font_library_entity->getDecodedJsonValues())];
      }

      $return['values']['uploadFonts'] = $font_library_values;
    }
    catch (\Exception $e) {
      $return = [];
    }

    return $return;
  }

  /**
   * Get combined font stacks for settings and forms.
   *
   * @return array|string
   */
  public function getColorGroup() {
    $return = [
      'title' => 'Color palette',
      'type' => 'website_settings',
      'bundle' => 'color_palette',
      'mapper' => [],
    ];

    $color_values = [];

    try {
      /** @var Color $color_entity */
      foreach ($this->entityTypeManager->getStorage('cohesion_color')
                 ->loadMultiple() as $color_entity) {
        $color_values[] = $color_entity->getDecodedJsonValues();
      }
      $return['values'] = ['colors' => $color_values];
    } catch (\Exception $e) {
        $return = [];
      }

    return $return;
  }

  /**
   * Get combined scss variables for settings and forms.
   *
   * @return array|string
   */
  public function getSCSSVariableGroup() {
      $return = [
          'title' => 'SCSS variable',
          'type' => 'website_settings',
          'bundle' => 'scss_variable',
          'mapper' => [],
      ];

      $scss_variable_values = [];

      try {
          /** @var SCSSVariable $scss_variable_entity */
          foreach ($this->entityTypeManager->getStorage('cohesion_scss_variable')
                       ->loadMultiple() as $scss_variable_entity) {
              $scss_variable_values[] = $scss_variable_entity->getDecodedJsonValues();
          }
          $return['values'] = ['variables' => $scss_variable_values];

      } catch (\Exception $e) {
          $return = [];
      }

      return $return;
  }

  /**
   * Replace URI to relative path for the API to process
   *
   * @param $object
   *
   * @return mixed
   */
  private function patchUri($object) {
    foreach ($object as $key => &$value) {
      if (is_array($value) || is_object($value)) {
        $value = $this->patchUri($value);
      }
      elseif(strpos($value, '://') !== FALSE) {
        if($local_stream_wrappers = $this->streamWrapperManager->getWrappers(StreamWrapperInterface::ALL)){
          foreach ($local_stream_wrappers as $scheme => $scheme_value){
            $uri = $scheme . '://';
            if(strpos($value, $uri) === 0){
              $stream_wrapper = $this->streamWrapperManager->getViaScheme($scheme);
              $stream_wrapper->setUri($value);
              $base_path = \Drupal::request()->getSchemeAndHttpHost();
              $value = str_replace($base_path, '', $stream_wrapper->getExternalUrl());
            }
          }
        }
      }
    }
    return $object;
  }

  /**
   * Extract the stylesheets from the $this->response and apply them (check
   * timestamps, etc).
   *
   * @param $requestCSSTimestamp
   *
   * @return bool
   */
  protected function processStyles($requestCSSTimestamp) {

    // Decode the response from the API.
    $data = $this->getData();
    $running_dx8_batch = &drupal_static('running_dx8_batch');

    // Check to see if there are actually some stylesheets to process.
    if (isset($data['base']) && isset($data['theme']) && isset($data['master'])) {

      // First check to see if the stylesheets have updated since your request was made.
      if ($this->getStylesheetTimestamp() != $requestCSSTimestamp) {
        drupal_set_message(t('The main stylesheet has been updated by another user since you saved. Please try again.'), 'error');
        return FALSE;
      }

      // Everything was fine, so attempt to apply the stylesheets.
      // Create directory if not exist
      if (!is_dir(COHESION_CSS_PATH) && !file_exists(COHESION_CSS_PATH)) {
        \Drupal::service('file_system')->mkdir(COHESION_CSS_PATH, 0777, FALSE);
      }

      // Save the main/master stylesheet json.
      if ($stylesheet_json_content = $data['master']) {
        $stylesheet_json_path = $this->localFilesManager->getStyleSheetFilename('json');
        file_unmanaged_save_data($stylesheet_json_content, $stylesheet_json_path, FILE_EXISTS_REPLACE);
      }

      $cohesion_module_libraries = \Drupal::keyValue('cohesion.library.module');
      // smacss categories used by DX8
      $style_types = [
        'base',
        'theme',
      ];

      foreach ($style_types as $section_name) {

        // Make sure the directory exists
        $base_path = COHESION_CSS_PATH . '/' . $section_name;
        if (!is_dir($base_path) && !file_exists($base_path)) {
          \Drupal::service('file_system')->mkdir($base_path, 0777, FALSE);
        }

        // The filename...
        $destination = $this->localFilesManager->getStyleSheetFilename($section_name);
        $content = str_replace([
          "\r\n",
          "\n\n",
        ], "\n", ltrim($data[$section_name]));

        // Save the file.
        if ($content && file_unmanaged_save_data($content, $destination, FILE_EXISTS_REPLACE) && !$running_dx8_batch) {
          \Drupal::logger('cohesion')->notice(t(':name stylesheet has been updated', array(':name' => $section_name)));

          // Get the success message from the class definition.
          drupal_set_message(t(get_class($this->entity)::STYLES_UPDATED_SAVE_MESSAGE));
        }

        // Set the filename in the library (must always be clean filenames).
        $css_files = [];
        $css_files[] = ['file' => $this->localFilesManager->getStyleSheetFilename($section_name, TRUE)];
        $cohesion_module_libraries->set($section_name, serialize($css_files));
      }

      if(!$running_dx8_batch){
        $this->localFilesManager->refreshCaches();
      }
    }
    // Generate cache busting token for wysiwyg cohesion styles
    $wysiwyg_cache_token = \Drupal::keyValue('cohesion.wysiwyg_cache_token');
    $wysiwyg_cache_token->set('cache_token', uniqid());
    return TRUE;
  }

  /**
   * Get the (sub second) timestamp of the stylesheet.
   *
   * @return bool|int
   */
  protected function getStylesheetTimestamp() {
    $originalCssPath = $this->localFilesManager->getStyleSheetFilename('json');

    clearstatcache($originalCssPath);
    if (file_exists($originalCssPath)) {
      return filemtime($originalCssPath);
    } else {
      return 0;
    }
  }

  /**
   * Method performing the call to the cohesion API.
   *
   * @param $type
   *
   * @return bool
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function send() {
    // If in update.php mode, don't send.
    $dx8_no_send_to_api = &drupal_static('dx8_no_send_to_api');
    // Process entity if DX8 is enable && if is an entity, it is enable and we don't want to save the data
    $cohesion_sync_lock = &drupal_static('cohesion_sync_lock');

    if ($dx8_no_send_to_api || !($this->cohesionUtils->usedx8Status()) ||
      ((isset($this->entity) && method_exists($this->entity, 'status') && !$this->entity->status()) && !$this->getSaveData()) ||
      ($cohesion_sync_lock) || $this->configInstaller->isSyncing()) {
      return TRUE;
    }

    $is_content = $this->entity instanceof CohesionLayout;

    $this->prepareData(!$is_content);  // Don't attach the css to inject for content requests.

    // Whether the generated template should have its content translatable in interface translation
    $this->data->translatable = !$is_content;

    $this->data->settings->forms = array_values($this->data->settings->forms);

    // Allow modules to manipulate the data before it's sent.
    $this->moduleHandler->alter('dx8_api_outbound_data', $this->data, $this->entity, $is_content);

    // Save the last time the main stylesheet was updated.
    $requestCSSTimestamp = $this->getStylesheetTimestamp();

    // Perform the send (this function exists on the child classes).
    $this->callApi();

    // Process the response.
    if ($this->response && floor($this->response['code'] / 200) == 1) {

      // If this a layout_field, just return the entire request (as it will be store inline in the field).
      if ($is_content || !$this->saveData) {
        return TRUE;
      }

      // Attempt to process the stylesheets received back from the API (merge into the exist stylesheet).
      if ($this->processStyles($requestCSSTimestamp)) {
        return TRUE;
      }
      // Timestamp to the CSS is now later than when the request was made.
      else {
        return FALSE;
      }
    } else {
      if (isset($this->entity)) {
        if(($this->entity instanceof CohesionLayout) && $this->entity->getParentEntity()){
          /* @var CohesionLayout $entity */
          $label = $this->entity->getParentEntity()->label();
          $entity_type = $this->entity->getParentEntity()->getEntityType()->getLabel();
        }else{
          /* @var \Drupal\Core\Entity\Entity $entity */
          $label = $this->entity->label();
          $entity_type = $this->entity->getEntityType()->getLabel();
        }
        \Drupal::logger('api-call-error')->error(
          t('API Error while trying to save @entity_type - @label', [
            '@entity_type' => $entity_type,
            '@label' => $label
          ])
        );
      }
    }

    return FALSE;
  }

  /**
   * Delete a style/template styles using the API and apply the modified
   * stylesheet.
   *
   * @return bool
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function delete() {
    // Prevent sending data to API if Use DX8 has error
    if (!($this->cohesionUtils->usedx8Status()) || $this->configInstaller->isSyncing()) {
      return FALSE;
    }

    $this->prepareData();

    if (strstr($this->data->entity_type_id, '_template')) {
      $this->data->delete_id = $this->data->entity_type_id . '_' . $this->data->entity_id;
    } else {
      $this->data->delete_id = $this->entity->id() . '_' . $this->entity->getConfigItemId();
    }

    // Save the last time the main stylesheet was updated.
    $requestCSSTimestamp = $this->getStylesheetTimestamp();

    // Call the API to delete the entry from the stylesheet.
    $this->response = CohesionApiClient::buildDeleteStyle($this->data);

    if ($this->response && floor($this->response['code'] / 200) == 1) {

      // Attempt to process the stylesheets received back from the API (merge into the exist stylesheet).
      if ($this->processStyles($requestCSSTimestamp)) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
