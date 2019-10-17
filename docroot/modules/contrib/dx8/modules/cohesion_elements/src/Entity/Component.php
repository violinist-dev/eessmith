<?php

namespace Drupal\cohesion_elements\Entity;

use Drupal\cohesion\Entity\CohesionSettingsInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\cohesion_templates\Plugin\Api\TemplatesApi;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Cohesion component entity.
 *
 * @ConfigEntityType(
 *   id = "cohesion_component",
 *   label = @Translation("Component"),
 *   label_singular = @Translation("Component"),
 *   label_plural = @Translation("Components"),
 *   label_collection = @Translation("Components"),
 *   label_count = @PluralTranslation(
 *     singular = "@count component",
 *     plural = "@count components",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\cohesion_elements\ElementsListBuilder",
 *     "form" = {
 *       "default" = "Drupal\cohesion_elements\Form\ComponentForm",
 *       "add" = "Drupal\cohesion_elements\Form\ComponentForm",
 *       "edit" = "Drupal\cohesion_elements\Form\ComponentForm",
 *       "duplicate" = "Drupal\cohesion_elements\Form\ComponentForm",
 *       "delete" = "Drupal\cohesion_elements\Form\ComponentDeleteForm",
 *       "enable-selection" = "Drupal\cohesion_elements\Form\ComponentEnableSelectionForm",
 *       "disable-selection" = "Drupal\cohesion_elements\Form\ComponentDisableSelectionForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\cohesion\CohesionHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "cohesion_component",
 *   admin_permission = "administer components",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "selectable" = "selectable",
 *   },
 *   links = {
 *     "edit-form" = "/admin/cohesion/components/components/{cohesion_component}/edit",
 *     "add-form" = "/admin/cohesion/components/components/add",
 *     "delete-form" = "/admin/cohesion/components/components/{cohesion_component}/delete",
 *     "collection" = "/admin/cohesion/components/components",
 *     "duplicate-form" = "/admin/cohesion/components/components/{cohesion_component}/duplicate",
 *     "in-use" = "/admin/cohesion/components/components/{cohesion_component}/in_use",
 *     "enable-selection" = "/admin/cohesion/components/components/{cohesion_component}/enable-selection",
 *     "disable-selection" = "/admin/cohesion/components/components/{cohesion_component}/disable-selection",
 *   }
 * )
 */
class Component extends CohesionElementEntityBase implements CohesionSettingsInterface, CohesionElementSettingsInterface {

  const ASSET_GROUP_ID = 'component';

  const CATEGORY_ENTITY_TYPE_ID = 'cohesion_component_category';

  // When styles are saved for this entity, this is the message.
  const STYLES_UPDATED_SAVE_MESSAGE = 'Your component styles have been updated.';

  const entity_machine_name_prefix = 'cpt_';

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Get the twig filename.
    $filename_prefix = 'component--cohesion-';
    $filename = $filename_prefix . str_replace('_', '-', str_replace('cohesion-helper-', '', $this->get('id')));
    $this->set('twig_template', $filename);
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    parent::process();
    /** @var TemplatesApi $send_to_api */
    $send_to_api = \Drupal::service('plugin.manager.api.processor')->createInstance('templates_api');
    $send_to_api->setEntity($this);
    $send_to_api->send();
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Invalidate the template cache.
    self::clearCache($this);

    $this->process();
  }

  /**
   * Delete the template twig cache (if available) and invalidate the render
   * cache tags.
   */
  protected static function clearCache($entity) {
    // The twig filename for this template.
    $filename = $entity->get('twig_template');

    _cohesion_templates_delete_twig_cache_file($filename);

    // Content template
    $entity_cache_tags = ['component.cohesion.' . $entity->id()];

    // Invalidate render cache tag for this template.
    \Drupal::service('cache_tags.invalidator')->invalidateTags($entity_cache_tags);

    // And clear the theme cache.
    parent::clearCache($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getInUseMessage() {
    return [
      'message' => [
        '#markup' => t('This Component has been tracked as in use in the places listed below. You should not delete it until you have removed its use.'),
      ],
    ];
  }

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity
   */
  public static function reload(EntityTypeInterface $entity) {
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Remove preview images - update usage and delete file if necessary.
    foreach ($entities as $entity) {
      // Clear the cache for this component.
      self::clearCache($entity);
    }
  }

  /**
   * Return the URI of the twig template for this component.
   *
   * @return bool|string
   */
  protected function getTwigPath() {
    return $this->get('twig_template') ? COHESION_TEMPLATE_PATH . '/' . $this->get('twig_template') . '.html.twig' : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function clearData() {
    if ($template_file = $this->getTwigPath()) {
      if (file_exists($template_file)) {
        file_unmanaged_delete($template_file);
      }
    }
  }

}
