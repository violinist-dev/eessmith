<?php

namespace Drupal\cohesion_custom_styles;

use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;

/**
 * Class CustomStyleBatch
 *
 * Re-saves all custom styles so they appear in their weight order in the
 * output .css.
 *
 * @package Drupal\cohesion_custom_styles
 */
class CustomStyleBatch {

  /**
   * Entry point into the batch run.
   */
  public static function run() {
    $batch = [
      'title' => t('Reordering custom styles.'),
      'operations' => [],
      'finished' => '\Drupal\cohesion_custom_styles\CustomStyleBatch::finishedCallback',
    ];

    $batch['operations'][] = [
      '\Drupal\cohesion_custom_styles\CustomStyleBatch::startCallback',
      []
    ];

    $entity_list = \Drupal::entityTypeManager()
      ->getStorage('cohesion_custom_style')
      ->loadMultiple();

    foreach ($entity_list as $entity) {
      // Only rebuild entities that have been activated.
      $batch['operations'][] = [
        '_resave_entity',
        ['entity' => $entity, 'realsave' => FALSE],
      ];
    }

    batch_set($batch);
    return batch_process(Url::fromRoute('entity.cohesion_custom_style.collection'));
  }

  /**
   * Start the batch process.
   *
   * @param $context
   */
  public static function startCallback(&$context) {
    $running_dx8_batch = &drupal_static('running_dx8_batch');
    $running_dx8_batch = TRUE;  // Initial state.

    // Copy the live stylesheet.json to temporary:// so styles don't get wiped when  re-importing.
    \Drupal::service('cohesion.local_files_manager')->liveToTemp();
  }

  /**
   * The batch run has finished. Clean up and show a status message.
   *
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function finishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $running_dx8_batch = &drupal_static('running_dx8_batch');
      $running_dx8_batch = TRUE;

      \Drupal::service('cohesion.local_files_manager')->tempToLive();
      Cache::invalidateTags(['dx8-form-data-tag']);
      $message = t('Custom styles have been reordered.');
    }
    else {
      $message = t('Custom styles reordering failed to complete.');
    }
    \Drupal::messenger()->addMessage($message);
  }

}