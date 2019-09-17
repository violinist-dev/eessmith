<?php

namespace Drupal\cohesion\Drush;

use Drupal\cohesion_website_settings\Controller\WebsiteSettingsController;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\Config;
use Drupal\cohesion\Controller\AdministrationController;
use Drupal\Component\Serialization\Json as Json;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class DX8CommandHelpers
 *
 * @package Drupal\cohesion\Drush
 */
final class DX8CommandHelpers {

  /**
   * Import s3forms and rebuild element styles.
   */
  public static function import() {
    $config = \Drupal::configFactory()->getEditable('cohesion.settings');

    if ($config->get('api_key') !== '') {

      // Get a list of the batch items.
      $batch = AdministrationController::batchAction(TRUE);

      if (isset($batch['error'])) {
        return $batch;
      }

      foreach ($batch['operations'] as $operation) {
        $context = ['results' => []];
        $function = $operation[0];
        $args = $operation[1];

        if (function_exists($function)) {
          call_user_func_array($function, array_merge($args, [&$context]));
        }
      }

      // Give access to all routes.
      cohesion_website_settings_batch_import_finished(TRUE, $context['results'], '');   // Enable the routes.

      if (isset($context['results']['error'])) {
        return ['error' => $context['results']['error']];
      }
    }
    else {
      return ['error' => t('Your API KEY has not been set.') . $config->get('site_id')];
    }

    return FALSE;

  }

  /**
   * Resave all Cohesion config entities.
   */
  public static function rebuild() {
    \Drupal::state()->set('system.maintenance_mode', TRUE);

    // Reset temporary template list
    $batch = WebsiteSettingsController::batch(TRUE);

    foreach ($batch['operations'] as $operation) {
      $context = ['results' => []];
      $function = $operation[0];
      $args = $operation[1];

      if (function_exists($function)) {
        call_user_func_array($function, array_merge($args, [&$context]));
      }
    }

    if (!isset($context['results']['error'])) {
      $running_dx8_batch = &drupal_static('running_dx8_batch');
      $running_dx8_batch = TRUE;

      \Drupal::service('cohesion.local_files_manager')->tempToLive();
      \Drupal::service('cohesion.local_files_manager')->moveTemporaryTemplateToLive();
      \Drupal::state()->set('system.maintenance_mode', FALSE);
    }
    else {
      return ['error' => $context['results']['error']];
    }

    return FALSE;
  }

}
