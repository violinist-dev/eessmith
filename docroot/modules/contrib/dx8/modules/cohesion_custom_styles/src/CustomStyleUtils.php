<?php

namespace Drupal\cohesion_custom_styles;

use Drupal\cohesion_custom_styles\Entity\CustomStyle;

/**
 * Class CustomStyleUtils
 *
 * @package Drupal\cohesion_custom_styles
 */
class CustomStyleUtils {

  /**
   * ????????
   *
   * @return array|int
   */
  public function parentCustomStyles() {
    try {
      $ids = \Drupal::entityQuery('cohesion_custom_style')
        ->notExists('parent')
        ->sort('label', 'ASC')
        ->sort('weight', 'ASC')
        ->execute();
    } catch (\Drupal\Component\Plugin\Exception\PluginNotFoundException $ex) {
      $ids = [];
    }
    return $ids;
  }

  /**
   * ????????
   *
   * @param null $parent_id
   *
   * @return array|int
   */
  public function childrenCustomStyles($parent_id = null) {
    $ids = [];

    if ($parent_entity = CustomStyle::load($parent_id)) {
      $parent_classname = $parent_entity->getClass();

      $ids = \Drupal::entityQuery('cohesion_custom_style')
        ->condition('parent', $parent_classname, '=')
        ->sort('label', 'ASC')
        ->sort('weight', 'ASC')
        ->execute();
    }

    return $ids;
  }

  /**
   * ??????
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function loadCustomStyles() {
    $result = [];
    if (($entities_ids = $this->parentCustomStyles())) {
      foreach ($entities_ids as $entityId) {
        $result[$entityId] = $entityId;
        if (($children = $this->childrenCustomStyles($entityId))) {
          $result += $children;
        }
      }
    }

    $ids = array_values($result);

    try {
      $custom_styles = \Drupal::entityTypeManager()
        ->getStorage('cohesion_custom_style')
        ->loadMultiple($ids);
    } catch (\Drupal\Component\Plugin\Exception\PluginNotFoundException $ex) {
      $custom_styles = [];
    }

    return $custom_styles;
  }

  /**
   * ??????
   *
   * @param $group_id
   *
   * @return array|int
   */
  public function countCustomStylesByGroupId($group_id) {
    try {
      return \Drupal::entityQuery('cohesion_custom_style')
          ->condition('custom_style_type', $group_id, '=')
          ->count()->execute();
    } catch (\Drupal\Component\Plugin\Exception\PluginNotFoundException $ex) {
      
    }

    return 0;
  }

  /**
   * ??????
   *
   * @param $config_key
   * @param int $weight
   *
   * @return bool
   */
  public function updateCustomStylesWeight($config_key, $weight = 0) {
    try {
      $config = \Drupal::configFactory()->getEditable($config_key);
      $config->set('weight', $weight);
      $config->save(true);
      return true;
    } catch (\Exception $ex) {
      
    }
    return false;
  }

  /**
   * ??????
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function loadCustomStylesOrder() {
    $result = [];
    if (($entities_ids = $this->parentCustomStyles())) {
      foreach ($entities_ids as $entityId) {
        $result[$entityId] = $entityId;
        if (($children = $this->childrenCustomStyles($entityId))) {
          $result += $children;
        }
      }
    }

    $ids = array_values($result);

    try {
      $custom_styles = \Drupal::entityTypeManager()
        ->getStorage('cohesion_custom_style')
        ->loadMultiple($ids);
    } catch (\Drupal\Component\Plugin\Exception\PluginNotFoundException $ex) {
      $custom_styles = [];
    }

    $order = [];

    if ($custom_styles) {
      foreach ($custom_styles as $custom_style) {
        $key = $custom_style->id() . '_' . $custom_style->getConfigItemId();
        $order[] = $key;
      }
    }

    return $order;
  }

}
