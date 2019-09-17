<?php

namespace Drupal\cohesion_website_settings\Plugin\Api;

use Drupal\cohesion\StylesApi;

/**
 * Class WebsiteSettingsApi
 *
 * @package Drupal\cohesion_website_settings
 *
 * @Api(
 *   id = "website_settings_api",
 *   name = @Translation("Website settings send to API"),
 * )
 */
class WebsiteSettingsApi extends StylesApi {

  /** @var \Drupal\cohesion_website_settings\Entity\WebsiteSettings $entity */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function prepareData($attach_css = TRUE) {
    parent::prepareData();

    $this->data->entity_type_id = 'cohesion_website_settings';
    // Add the form data.
    $this->data->settings->forms[] = $this->getFormElement($this->entity->getResourceObject());
  }
}
