<?php

namespace Drupal\cohesion\Helper;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ThemeManager;

/**
 * Class CohesionUtils
 *
 * @package Drupal\cohesion
 */
class CohesionUtils {

  /**
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * @var \Drupal\Core\Theme\ThemeManager
   */
  protected $themeManager;

  /**
   * CohesionUtils constructor.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ThemeManager $theme_manager) {
    $this->themeHandler = $theme_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * @return bool
   */
  public function isAdminTheme() {
    return \Drupal::config('system.theme')->get('admin') == $this->getCurrentTheme()->getName();
  }

  /**
   * @return bool
   */
  public function isDX8EnabledTheme() {
    // Should DX8 apply to this theme?
    if (!$dx8_enabled_theme = \Drupal::config('cohesion.settings')->get('dx8_enabled_theme')) {
      $dx8_enabled_theme = '';
    }
    return $dx8_enabled_theme == $this->getCurrentTheme()->getName() || $dx8_enabled_theme == '';
  }

  /**
   * @return \Drupal\Core\Theme\ActiveTheme
   */
  private function getCurrentTheme() {
    return $this->themeManager->getActiveTheme();
  }

  /**
   * @return array
   */
  public function getCohesionRoutes() {
    $query = \Drupal::database();
    $routes_results = $query->select('router', 'r')->fields('r', ['name',])->condition('name', '%cohesion%', 'LIKE')->execute()->fetchCol();

    $routes = array_filter($routes_results, function ($route) {
      return (!in_array($route, [
          'cohesion.settings',
          'cohesion.configuration',
          'cohesion.configuration.account_settings',
          'cohesion.configuration.batch',
        ]));
    });
    return $routes ? \Drupal::service('router.route_provider')->getRoutesByNames($routes) : [];
  }

  /**
   * @return bool
   * @todo - store as a static.
   */
  public function usedx8Status() {
    $dx8_config = \Drupal::config('cohesion.settings');
    if (!$dx8_config || $dx8_config->get('use_dx8') === 'disable' || !$dx8_config->get('api_key') || $dx8_config->get('api_key') == '') {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @return bool
   */
  public function logdx8ErrorStatus() {
    if (\Drupal::config('cohesion.settings')->get('log_dx8_error') === 'disable') {
      return FALSE;
    }
    return TRUE;
  }

}
