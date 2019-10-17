<?php

namespace Drupal\cohesion_website_settings\Entity;

use Drupal\cohesion\Entity\CohesionConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\cohesion\Entity\CohesionSettingsInterface;
use Drupal\cohesion_website_settings\Plugin\Api\WebsiteSettingsApi;

/**
 * Defines the Cohesion website settings entity.
 *
 * @ConfigEntityType(
 *   id = "cohesion_scss_variable",
 *   label = @Translation("SCSS Variable"),
 *   label_singular = @Translation("SCSS Variable"),
 *   label_plural = @Translation("SCSS Variables"),
 *   label_collection = @Translation("SCSS Variables"),
 *   label_count = @PluralTranslation(
 *     singular = "@count SCSS variables",
 *     plural = "@count SCSS variables",
 *   ),
 *   fieldable = TRUE,
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "Drupal\cohesion\CohesionHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "cohesion_scss_variable",
 *   admin_permission = "administer website settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "value" = "value",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "in-use" = "/admin/cohesion/cohesion_scss_variable/{cohesion_scss_variable}/in_use",
 *     "collection" = "/admin/cohesion/cohesion_website_settings"
 *   }
 * )
 */
class SCSSVariable extends WebsiteSettingsEntityBase implements CohesionSettingsInterface {

  const ASSET_GROUP_ID = 'website_settings';

  /**
   * @var int
   */
  protected $weight;

  /**
   * @var
   */
  protected $label_collection;

  /**
   * Getter.
   *
   * @return int
   */
  public function getWeight() {
    return $this->weight ? $this->weight : 0;
  }

  /**
   * Setter.
   *
   * @param $weight
   */
  public function setWeight($weight) {
    $this->weight = $weight;
  }

  /**
   * Return all the icons combined for the form[]
   *
   * @return array|\stdClass|string
   */
  public function getResourceObject() {
    /** @var WebsiteSettingsApi $send_to_api */
    $send_to_api = \Drupal::service('plugin.manager.api.processor')->createInstance('website_settings_api');

    return $send_to_api->getSCSSVariableGroup();
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    /** @var WebsiteSettingsApi $send_to_api */
    $send_to_api = \Drupal::service('plugin.manager.api.processor')->createInstance('website_settings_api');
    $send_to_api->setEntity($this);
    $send_to_api->send();
    return $send_to_api;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonValuesErrors() {
    /** @var WebsiteSettingsApi $send_to_api */
    $send_to_api = \Drupal::service('plugin.manager.api.processor')->createInstance('website_settings_api');
    $send_to_api->setEntity($this);
    $send_to_api->setSaveData(FALSE);
    $success = $send_to_api->send();
    $responseData = $send_to_api->getData();
    if ($success === TRUE) {
      return FALSE;
    }
    else {
      return $responseData;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $this->process();

    // Invalidate settings endpoint shared cache entries.
    // Cache::invalidateTags($tags);
  }

  /**
   * Return a description.
   */
  public function label() {
    return '$' . $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionLabel() {
    if (empty($this->label_collection)) {
      $label = $this->label();
      $this->label_collection = new TranslatableMarkup('@label', ['@label' => $label], [], $this->getStringTranslation());
    }
    return $this->label_collection;
  }

  /**
   * {@inheritdoc}
   */
  public function getInUseMessage() {
    return [
      'message' => [
        '#markup' => t('This SCSS variable has been tracked as in use in the places listed below. You should not delete it until you have removed its use.'),
      ],
    ];
  }

  public function clearData() {
  }

  /**
   * @inheritdoc
   */
  public function isLayoutCanvas() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValues() {
    parent::setDefaultValues();

    $this->modified = TRUE;
    $this->status = TRUE;
  }
}
