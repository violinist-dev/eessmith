<?php

namespace Drupal\cohesion_custom_styles\Plugin\Api;

use Drupal\cohesion\StylesApi;
use Drupal\cohesion_custom_styles\Entity\CustomStyle;

/**
 * Class CustomStylesApi
 *
 * @package Drupal\cohesion_custom_styles
 *
 * @Api(
 *   id = "custom_styles_api",
 *   name = @Translation("Custom styles send to API"),
 * )
 */
class CustomStylesApi extends StylesApi {

  /** @var CustomStyle $entity */
  protected $entity;
  /** @var CustomStyle $parent */
  protected $parent;

  /**
   * {@inheritdoc}
   */
  protected function prepareData($attach_css = true) {
    parent::prepareData($attach_css);

    // Hash the child entities.
    $child_entities = $this->parent->getChildEntities();
    $child_resources = null;

    if (count($child_entities)) {
      /** @var CustomStyle $child */
      foreach ($child_entities as $child) {
        //Only process enable children
        if ($child->getStatus()) {
          $resource = $child->getResourceObject();
          $this->processStyleTokensRecursive($resource->values);

          $child_resources[] = $resource;
        }
      }
    }

    // Send the parent and children to the API.
    // processStyleTokensRecursive
    $resource = $this->parent->getResourceObject();
    $this->processStyleTokensRecursive($resource->values);

    $this->data->settings->forms[] = $this->getFormElement($resource, $child_resources);

    // Reorder custom style styles
    $custom_styles = CustomStyle::loadParentChildrenOrdered();
    $style_order = [];
    if ($custom_styles) {
      foreach ($custom_styles as $custom_style) {
        $key = $custom_style->id() . '_' . $custom_style->getConfigItemId();
        $style_order[] = $key;
      }
    }

    $this->data->sort_order = $style_order;
    $this->data->style_group = 'cohesion_custom_style';
  }

  /**
   * {@inheritdoc}
   */
  public function send() {

    // Assume this entity is the parent.
    $this->parent = $this->entity;

    // If this is a child element, set the parent element.
    if ($parent_id = $this->entity->getParentId()) {
      $this->parent = \Drupal::entityTypeManager()->getStorage('cohesion_custom_style')->load($parent_id);
    }

    // Send to API only if the parent of this entity is enabled.
    if ($this->parent && $this->parent->status() || $this->getSaveData()) {
      return parent::send();
    }

    return TRUE;
  }

  /**
   * Remove the entity from stylesheet.json
   *
   * @return bool|void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function delete() {

    // Assume this entity is the parent.
    $this->parent = $this->entity;

    // If this is a child element, set the parent element.
    if ($parent_id = $this->entity->getParentId()) {
      $this->parent = \Drupal::entityTypeManager()->getStorage('cohesion_custom_style')->load($parent_id);
    }

    parent::delete();
  }

}
