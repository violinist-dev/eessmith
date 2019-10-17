<?php

namespace Drupal\cohesion_website_settings;

use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class RebuildInuseBatch
 *
 * Saves website settings and all entities used by those entities.
 *
 * @package Drupal\cohesion_website_settings
 */
class RebuildInuseBatch {

  /**
   * Entry point into the batch run.
   *
   * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public static function run() {

    /** @var PrivateTempStore $tempstore */
    $tempstore = \Drupal::service('user.private_tempstore')->get('website_settings');

    // Setup the batch.
    $batch = [
      'title' => t('Rebuilding website settings.'),
      'operations' => [],
      'finished' => '\Drupal\cohesion_website_settings\RebuildInuseBatch::finishedCallback',
    ];

    $batch['operations'][] = [
      '\Drupal\cohesion_website_settings\RebuildInuseBatch::startCallback',
      [],
    ];

    // Save the entities that have changed.
    foreach ($tempstore->get('changed_entities') as $entity) {
      // Only rebuild entities that have been activated.
      $batch['operations'][] = [
        '_resave_entity',
        ['entity' => $entity, 'realsave' => TRUE],
      ];
    }

    // Save entities that use these entities.
    foreach ($tempstore->get('in_use_list') as $uuid => $type) {

      try {
        $query = \Drupal::entityQuery($type);
        $query->condition('uuid', $uuid);
        $entity_ids = $query->execute();

        $entity = \Drupal::entityTypeManager()->getStorage($type)->load(array_shift($entity_ids));

        if ($entity instanceof ContentEntityInterface) {
          // Batch process the content entities.
          $batch['operations'][] = [
            '_resave_content_entity',
            ['entity' => $entity],
          ];
        }
        else {
          $batch['operations'][] = [
            '_resave_entity',
            ['entity' => $entity, 'realsave' => TRUE],
          ];
        }

      } catch (\Exception $e) {
      }
    }

    $operations[] = ['cohesion_templates_secure_directory', []];

    // Clear the render cache (see the PHPDoc for why this is done for website stetings).
    $batch['operations'][] = [
      '\Drupal\cohesion_website_settings\RebuildInuseBatch::clearRenderCache',
      [],
    ];


    // Clean up the temporary store.
    $tempstore->delete('in_use_list');

    // Setup and run the batch.
    batch_set($batch);
    return batch_process(Url::fromRoute('entity.cohesion_website_settings.collection'));
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
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public static function finishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      // Clean the tempstore lists out.
      /** @var PrivateTempStore $tempstore */
      $tempstore = \Drupal::service('user.private_tempstore')->get('website_settings');
      $tempstore->delete('changed_entities');
      $tempstore->delete('in_use_list');

      // Stop the batch.
      $running_dx8_batch = &drupal_static('running_dx8_batch');
      $running_dx8_batch = TRUE;

      // Copy the stylesheets back.
      \Drupal::service('cohesion.local_files_manager')->tempToLive();
      \Drupal::service('cohesion.local_files_manager')->moveTemporaryTemplateToLive();
      Cache::invalidateTags(['dx8-form-data-tag']);
      $message = t('Website settings have been rebuilt.');
    }
    else {
      $message = t('Website settings rebuild failed to complete.');
    }
    drupal_set_message($message);
  }

  /**
   * The entire render cache needs clearing when rebuilding website settings
   * because the rebuild is not recursive (it only rebuilds entities that
   * directly reclare their use of a website settings). For example, a website
   * settings could be used in a style and that style can be used on an entity,
   * there is a chance that an entity will not show an updated website settings.
   *
   * @param $context
   */
  public static function clearRenderCache(&$context) {
    $context['message'] = t('Flushing render cache.');

    if (!isset($context['results']['error'])) {
      $renderCache = \Drupal::service('cache.render');
      $renderCache->invalidateAll();
    }
  }

}