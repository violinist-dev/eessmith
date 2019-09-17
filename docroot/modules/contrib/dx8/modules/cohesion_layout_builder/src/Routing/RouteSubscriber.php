<?php

namespace Drupal\cohesion_layout_builder\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Class RouteSubscriber
 *
 * This subscriber mostly monkey-patches the LayoutRebuildTrait to use the DX8
 * template wrapper. There is no render service provided by layout_buider to
 * elegantly override this - just a long list of traits.
 * The layout builder /layout page also does not render the content view mode
 * template, whcih means the layout builder is not strictly WYSIWYG.
 *
 * @package Drupal\cohesion_layout_builder\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Loop over all the routes looking for the layout builder /layout controller.
    $it = $collection->getIterator();

    while ($it->valid()) {
      $route = $it->current();
      $defaults = $route->getDefaults();

      if (isset($defaults['_controller'])) {
        switch ($defaults['_controller']) {
          case '\Drupal\layout_builder\Controller\LayoutBuilderController::layout': // == '\Drupal\layout_builder\Controller\LayoutBuilderController::layout') {
            $defaults['_controller'] = '\Drupal\cohesion_layout_builder\Controller\CohesionLayoutBuilderController::layout';
            $route->setDefaults($defaults);
            break;

          case '\Drupal\layout_builder\Controller\MoveBlockController::build':
            $defaults['_controller'] = '\Drupal\cohesion_layout_builder\Controller\CohesionMoveBlockController::build';
            $route->setDefaults($defaults);
            break;

          case '\Drupal\layout_builder\Controller\AddSectionController::build':
            $defaults['_controller'] = '\Drupal\cohesion_layout_builder\Controller\CohesionAddSectionController::build';
            $route->setDefaults($defaults);
            break;

          case '\Drupal\layout_builder\Controller\ChooseSectionController::build':
            $defaults['_controller'] = '\Drupal\cohesion_layout_builder\Controller\CohesionChooseSectionController::build';
            $route->setDefaults($defaults);
            break;
        }
      }

      if (isset($defaults['_form'])) {
        switch ($defaults['_form']) {
          case '\Drupal\layout_builder\Form\AddBlockForm':
            $defaults['_form'] = '\Drupal\cohesion_layout_builder\Form\CohesionAddBlockForm';
            $route->setDefaults($defaults);
            break;

          case '\Drupal\layout_builder\Form\UpdateBlockForm':
            $defaults['_form'] = '\Drupal\cohesion_layout_builder\Form\CohesionUpdateBlockForm';
            $route->setDefaults($defaults);
            break;

          case '\Drupal\layout_builder\Form\RemoveBlockForm':
            $defaults['_form'] = '\Drupal\cohesion_layout_builder\Form\CohesionRemoveBlockForm';
            $route->setDefaults($defaults);
            break;

          case '\Drupal\layout_builder\Form\ConfigureSectionForm':
            $defaults['_form'] = '\Drupal\cohesion_layout_builder\Form\CohesionConfigureSectionForm';
            $route->setDefaults($defaults);
            break;

          case '\Drupal\layout_builder\Form\RemoveSectionForm':
            $defaults['_form'] = '\Drupal\cohesion_layout_builder\Form\CohesionRemoveSectionForm';
            $route->setDefaults($defaults);
            break;
        }
      }

      $it->next();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -999];
    return $events;
  }

}