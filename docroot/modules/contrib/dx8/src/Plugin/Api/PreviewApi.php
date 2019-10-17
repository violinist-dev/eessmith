<?php

namespace Drupal\cohesion\Plugin\Api;

use Drupal\cohesion\StylesApi;
use Drupal\cohesion_website_settings\Entity\WebsiteSettings;
use Drupal\Component\Serialization\Json;
use Drupal\cohesion\CohesionApiClient;

/**
 * Class PreviewApi
 *
 * @package Drupal\cohesion
 *
 * @Api(
 *   id = "preview_api",
 *   name = @Translation("Preview send to API"),
 * )
 */
class PreviewApi extends StylesApi {

  protected $entity_type_id;

  protected $style_model;

  /**
   * @param $entity_type_id
   * @param $style_model
   */
  public function setupPreview($entity_type_id, $style_model) {
    // Process the style model.
    $this->processStyleTokensRecursive($style_model['styles']);

    // And save the values.
    $this->entity_type_id = $entity_type_id;
    $this->style_model = $style_model;
  }

  /**
   * {@inheritdoc}
   */
  public function send() {

    if (!(\Drupal::service('cohesion.utils')->usedx8Status()) || $this->configInstaller->isSyncing()) {
      return FALSE;
    }

    // Prepare the data and DO NOT attach the stylesheet.json to the payload
    $this->prepareData(FALSE);

    // Add additional payload config required for custom styles.
    if ($this->entity_type_id != 'cohesion_base_styles') {
      $this->data->entity_type_id = $this->entity_type_id;
      $this->data->entity_id = 'preview_1';
      $this->data->settings->forms[0]['parent'] = Json::encode([
        'title' => '',
        'type' => 'custom_styles',
        'class_name' => '.coh-preview',
        'bundle' => 'preview_customstyle',
        'schema' => [],
        'values' => Json::encode($this->style_model),
        'mapper' => [],
      ]);
      $this->data->css = [];
    }
    // Add additional payload config required for base styles.
    else {
      $this->data->entity_type_id = $this->entity_type_id;
      $this->data->entity_id = 'preview_1';
      $this->data->settings->forms[0]['parent'] = Json::encode([
        'title' => '',
        'type' => 'base_styles',
        'bundle' => isset($this->style_model['styles']) ? $this->style_model['styles']['settings']['element'] : NULL,
        'schema' => [],
        'values' => Json::encode($this->style_model),
      ]);
      $this->data->css = [];
    }

    // Perform the send.
    $this->callApi();

    if ($this->response && floor($this->response['code'] / 200) == 1) {
      return TRUE;
    }
    else {
      return FALSE;
    }

  }

}
