<?php

namespace Drupal\cohesion\Services;

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
    return \Drupal::config('system.theme')
        ->get('admin') == $this->themeManager->getActiveTheme()->getName();
  }

  /**
   * Whether the current theme had cohesion enabled.
   *
   *
   * @return bool - Returns TRUE if the current theme or one of its parent has
   * cohesion enabled (cohesion: true in info.yml)
   */
  public function currentThemeUseCohesion() {

    $current_theme_extension = $this->themeManager->getActiveTheme()
      ->getExtension();
    if ($this->isThemeCohesionEnabled($current_theme_extension)) {
      return TRUE;
    }
    elseif (property_exists($current_theme_extension, 'base_themes') && is_array($current_theme_extension->base_themes)) {
      foreach ($current_theme_extension->base_themes as $theme_id => $theme_name) {
        if (isset($this->themeHandler->listInfo()[$theme_id]) && $this->isThemeCohesionEnabled($this->themeHandler->listInfo()[$theme_id])) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * @param $theme_info
   *
   * @return bool
   */
  private function isThemeCohesionEnabled($theme_info) {
    return property_exists($theme_info, 'info') && is_array($theme_info->info) && isset($theme_info->info['cohesion']) && $theme_info->info['cohesion'] === TRUE;
  }

  /**
   * @return array
   */
  public function getCohesionRoutes() {
    $query = \Drupal::database();
    $routes_results = $query->select('router', 'r')
      ->fields('r', ['name',])
      ->condition('name', '%cohesion%', 'LIKE')
      ->execute()
      ->fetchCol();

    $routes = array_filter($routes_results, function ($route) {
      return (!in_array($route, [
        'cohesion.settings',
        'cohesion.configuration',
        'cohesion.configuration.account_settings',
        'cohesion.configuration.batch',
      ]));
    });
    return $routes ? \Drupal::service('router.route_provider')
      ->getRoutesByNames($routes) : [];
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

}
