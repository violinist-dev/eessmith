<?php

namespace Drupal\cohesion_base_styles\Plugin\Api;

use Drupal\cohesion\StylesApi;

/**
 * Class BaseStylesApi
 *
 * @package Drupal\cohesion_base_styles\Plugin\Usage
 *
 * @Api(
 *   id = "base_styles_api",
 *   name = @Translation("Base styles send to API"),
 * )
 */
class BaseStylesApi extends StylesApi {

  protected function prepareData($attach_css = TRUE) {
    parent::prepareData();

    $resource = $this->entity->getResourceObject();
    $this->processStyleTokensRecursive($resource->values);

    $this->data->settings->forms[] = $this->getFormElement($resource);
  }
}
