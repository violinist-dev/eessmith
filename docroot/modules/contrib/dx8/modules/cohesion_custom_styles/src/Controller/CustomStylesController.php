<?php

namespace Drupal\cohesion_custom_styles\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Controller\EntityController;
use \Drupal\Core\Link;

/**
 * Class CustomStylesController
 *
 * Returns responses for custom style type routes.
 *
 * @package Drupal\cohesion_custom_styles\Controller
 */
class CustomStylesController extends EntityController implements ContainerInjectionInterface {

  /**
   * Get an array of the available custom style types
   * (@see cohesion_custom_styles.views.inc)
   */
  private function getCustomStyleTypes() {
    $results = [];

    foreach ($this->entityTypeManager->getStorage('custom_style_type')->loadMultiple() as $type) {
      $results[$type->id()] = $type->label();
    }
    // Sort custom style type list in ascending order
    array_multisort($results, SORT_ASC);
    return $results;
  }

  /**
   * Displays add content links for available custom style types.
   *
   * Redirects to custom_style_types/add/{type} if only one custom style type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   * A render array for a list of the custom style types that can be added however if there is
   * only one custom style type defined for the site, the function will return a RedirectResponse
   * to the custom style add page for that one custom style type
   *
   * /admin/cohesion/styles/cohesion_custom_styles/add
   */
  public function addPage($entity_type_id) {
    // Get complete list of custom style types
    $types = [];

    foreach ($this->getCustomStyleTypes() as $id => $label) {
      $types[$id] = [
        'label' => $label,
        'description' => '',
        'add_link' => Link::createFromRoute($id, 'entity.cohesion_custom_style.add_form', ['custom_style_type' => $id]),
      ];
    }

    // Send this to entity-add-list.html.twig via system.module
    $build = [
      '#theme' => 'entity_add_list',
      '#bundles' => $types,
      '#add_bundle_message' => t('There are no available custom style types. Go to the batch import page to import the list of custom style types.'),
      '#cache' => [
        'tags' => $this->entityTypeManager->getDefinition('custom_style_type')->getListCacheTags(),
      ],
    ];

    return $build;
  }

}
