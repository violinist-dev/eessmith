<?php

namespace Drupal\cohesion_website_settings\Plugin\Api;

use Drupal\cohesion\StylesApi;
use Drupal\cohesion_website_settings\Entity\IconLibrary;
use Drupal\cohesion_website_settings\Entity\WebsiteSettings;
use Drupal\Component\Serialization\Json;

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

  /**
   * @inheritDoc
   */
  protected function processStyles($requestCSSTimestamp) {
    parent::processStyles($requestCSSTimestamp);

    $data = $this->getData();
    $running_dx8_batch = &drupal_static('running_dx8_batch');

    // Check to see if there are actually some stylesheets to process.
    if (isset($data['base']) && isset($data['theme']) && isset($data['master'])) {

      // Create admin icon library and website settings stylesheet for admin.
      $master = Json::decode($data['master']);

      if (isset($master['cohesion_website_settings']['icon_libraries']) && $this->entity instanceof IconLibrary) {
        $destination = $this->localFilesManager->getStyleSheetFilename('icons');
        if (file_unmanaged_save_data($master['cohesion_website_settings']['icon_libraries'], $destination, FILE_EXISTS_REPLACE) && !$running_dx8_batch) {
          \Drupal::logger('cohesion')->notice(t(':name stylesheet has been updated', [':name' => 'icon library']));
        }
      }

      if (isset($master['cohesion_website_settings']['responsive_grid_settings']) && $this->entity instanceof WebsiteSettings && $this->entity->id() == 'responsive_grid_settings') {
        $destination = $this->localFilesManager->getStyleSheetFilename('grid');
        if (file_unmanaged_save_data($master['cohesion_website_settings']['responsive_grid_settings'], $destination, FILE_EXISTS_REPLACE) && !$running_dx8_batch) {
          \Drupal::logger('cohesion')->notice(t(':name stylesheet has been updated', array(':name' => 'Responsive grid')));
        }
      }
    }
  }
}
