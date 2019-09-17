<?php

namespace Drupal\cohesion_sync;

use Drupal;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\cohesion_sync\Entity\Package;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Serialization\Yaml;
use Drupal\cohesion\UsageUpdateManager;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

define('ENTRY_NEW_IMPORTED', 1);
define('ENTRY_EXISTING_ASK', 2);
define('ENTRY_EXISTING_OVERWRITTEN', 3);
define('ENTRY_EXISTING_IGNORED', 4);
define('ENTRY_EXISTING_LOCKED', 5);
define('ENTRY_EXISTING_NO_CHANGES', 6);

/**
 * Class PackagerManager
 *
 * @package Drupal\cohesion_sync
 */
class PackagerManager {

  /**
   * Holds the entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * Holds the entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\cohesion_sync\SyncPluginManager
   */
  protected $syncPluginManager;

  /**
   * @var \Drupal\cohesion\UsageUpdateManager
   */
  protected $usageUpdateManager;

  /**
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The entries extracted from the last import.
   *
   * @var array
   */
  protected $entries = [];

  /**
   * PackagerManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityRepository $entityRepository
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\cohesion_sync\SyncPluginManager $sync_plugin_manager
   * @param \Drupal\cohesion\UsageUpdateManager $usage_update_manager
   * @param \Drupal\Core\File\FileSystem $file_system
   */
  public function __construct(EntityRepository $entityRepository, EntityTypeManagerInterface $entityTypeManager, SyncPluginManager $sync_plugin_manager, UsageUpdateManager $usage_update_manager, FileSystem $file_system) {
    $this->entityRepository = $entityRepository;
    $this->entityTypeManager = $entityTypeManager;
    $this->syncPluginManager = $sync_plugin_manager;
    $this->usageUpdateManager = $usage_update_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * Find a matching SyncPlugin plugin for this entity.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type_definition
   *
   * @return null
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function getPluginInstanceFromType(EntityTypeInterface $entity_type_definition) {
    // Loop through all the plugin definitions.
    foreach ($this->syncPluginManager->getDefinitions() as $id => $definition) {
      // Create a reflection class to see if this entity implements in the interface in the annotation.
      if (in_array($definition['interface'], class_implements($entity_type_definition->getClass()))) {
        // Found a match.
        return $this->syncPluginManager->createInstance($id)->setType($entity_type_definition);
      }
    }

    return NULL;
  }

  /**
   * Loads and decodes a Yaml file and runs the callback on each root entry.
   *
   * @param $uri
   * @param $callback
   *
   * @return bool
   */
  private function parseYaml($uri, $callback) {
    if ($handle = @ fopen($uri, 'r')) {
      $yaml = '';

      while (!feof($handle)) {
        $line = fgets($handle);

        // Hit the end of an array entry.
        if ($yaml != '' && $line == "-\n") {
          $entry = Yaml::decode($yaml)[0];
          $callback($entry);
          $yaml = $line;
        }
        // Building up an array entry.
        else {
          $yaml .= $line;
        }
      }

      $entry = Yaml::decode($yaml)[0];
      $callback($entry);

      fclose($handle);
    }
  }

  /**
   * Validate a package list via the plugin and return the actions.
   *
   * @param $entry
   * @param $action_list
   *
   * @throws \Exception
   */
  private function validatePackageEntry($entry, &$action_list) {
    // Get the Sync plugin for this entity type.
    try {
      $type_definition = $this->entityTypeManager->getDefinition($entry['type']);
      /** @var SyncPluginInterface $plugin */
      $plugin = $this->getPluginInstanceFromType($type_definition);
    } catch (\Exception $e) {
      throw new \Exception(t('Entity type @type not found.', ['@type' => $entry['type']]));
    }

    // Check to see if the entry can be applied without asking what to do.
    $action_state = $plugin->validatePackageEntryShouldApply($entry['export']);

    $action_data = $plugin->getActionData($entry['export'], $action_state);

    // Add the action object to the list if it will apply or requires user input.
    $action_list[$entry['export']['uuid']] = $action_data;
  }

  /**
   * Given a URI, validate the stream.
   *
   * @param $uri
   *
   * @return array
   * @throws \Exception
   */
  public function validateYamlPackageStream($uri) {
    // Make sure the $uri is accessible.
    if (@ !fopen($uri, 'r')) {
      throw new \Exception(t('Cannot access @path', ['@path' => $uri]));
    }

    // Process it.
    $action_list = [];

    $this->parseYaml($uri, function ($entry) use (&$action_list) {
      $this->validatePackageEntry($entry, $action_list);
    });

    return $action_list;
  }

  /**
   * Apply a package entry to the site.
   *
   * @param $entry
   *
   * @throws \Exception
   */
  public function applyPackageEntry($entry) {
    // Get the Sync plugin for this entity type.
    try {
      $type_definition = $this->entityTypeManager->getDefinition($entry['type']);
      /** @var SyncPluginInterface $plugin */
      $plugin = $this->getPluginInstanceFromType($type_definition);
    } catch (\Exception $e) {
      throw new \Exception(t('Entity type @type not found.', ['@type' => $entry['type']]));
    }

    // Check to see if the entry can be applied without asking what to do.
    $plugin->applyPackageEntry($entry['export']);
  }

  /**
   * @param $entry
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function postApplyPackageEntry($entry) {
    $cohesion_sync_lock = &drupal_static('cohesion_sync_lock');
    $cohesion_sync_lock = FALSE;

    // Get the imported entity to work on.
    if ($entity = $this->entityRepository->loadEntityByUuid($entry['type'], $entry['export']['uuid'])) {
      // Send to API and re-calculate in-use table.
      try {
        if (method_exists($entity, 'process')) {
          $entity->process();
        }

        $this->usageUpdateManager->buildRequires($entity);
      } catch (\Exception $e) {
      }
    }
  }

  /**
   * Scan a Yaml package stream and only apply the entries in $action_data
   *
   * @param $uri
   * @param $action_data
   *
   * @return int
   */
  public function applyYamlPackageStream($uri, $action_data) {
    $count = 0;

    // Import the packages.
    $this->parseYaml($uri, function ($entry) use ($action_data, &$count) {
      if (isset($action_data[$entry['export']['uuid']]) && in_array($action_data[$entry['export']['uuid']]['entry_action_state'], [ENTRY_NEW_IMPORTED, ENTRY_EXISTING_OVERWRITTEN])) {
        $this->applyPackageEntry($entry);
        $count += 1;
      }
    });

    // Post apply (all entities exist within the system at this point). 
    $this->parseYaml($uri, function ($entry) use ($action_data) {
      if (isset($action_data[$entry['export']['uuid']]) && in_array($action_data[$entry['export']['uuid']]['entry_action_state'], [ENTRY_NEW_IMPORTED, ENTRY_EXISTING_OVERWRITTEN])) {
        $this->postApplyPackageEntry($entry);
      }
    });

    return $count;
  }

  /**
   * Scan a Yaml package stream and create a batch array for the entries in
   * $action_data
   *
   * @param $uri
   * @param $action_data
   *
   * @return array
   * @throws \Exception
   */
  public function applyBatchYamlPackageStream($uri, $action_data) {

    $batch = [
      'title' => t('Importing configuration.'),
      'operations' => [],
      'finished' => '\Drupal\cohesion_sync\Controller\BatchImportController::batchFinishedCallback',
    ];

    $batch['operations'][] = [
      '\Drupal\cohesion_sync\Controller\BatchImportController::batchStartAction',
      [$action_data]
    ];

    // Apply the entitites to the site.
    $this->parseYaml($uri, function ($entry) use ($action_data, &$batch) {
      if (isset($action_data[$entry['export']['uuid']]) && in_array($action_data[$entry['export']['uuid']]['entry_action_state'], [ENTRY_NEW_IMPORTED, ENTRY_EXISTING_OVERWRITTEN])) {
        // Add  item to the batch.
        $batch['operations'][] = [
          '\Drupal\cohesion_sync\Controller\BatchImportController::batchAction',
          [$entry],
        ];
      }
    });

    // Post apply the entitites to the site.
    $this->parseYaml($uri, function ($entry) use ($action_data, &$batch) {
      if (isset($action_data[$entry['export']['uuid']])  && in_array($action_data[$entry['export']['uuid']]['entry_action_state'], [ENTRY_NEW_IMPORTED, ENTRY_EXISTING_OVERWRITTEN])) {
        // Add  item to the batch.
        $batch['operations'][] = [
          '\Drupal\cohesion_sync\Controller\BatchImportController::batchPostAction',
          [$entry],
        ];
      }
    });

    $batch['operations'][] = [
      '\Drupal\cohesion_sync\Controller\BatchImportController::batchCompleteAction',
      [$action_data],
    ];

    return $batch;
  }

  /**
   * Generator for streaming package contents.
   *
   * @param $entities
   * @param $yaml
   * @param $exclude_entity_type_ids
   *
   * @return \Generator
   * @throws \Exception
   */
  public function buildPackageStream($entities, $yaml = FALSE, $exclude_entity_type_ids = []) {
    // Loop over the dependencies including the source entity.
    foreach ($this->buildPackageEntityList($entities, $exclude_entity_type_ids) as $entry) {
      if ($entry) {
        // Get the plugin for this entity type.
        /** @var SyncPluginInterface $plugin */
        if ($plugin = $this->getPluginInstanceFromType($this->entityTypeManager->getDefinition($entry['type']))) {
          // Yield the yaml encoded export.
          $item = [
            'type' => $entry['type'],
            'export' => $plugin->buildExport($entry['entity']),
          ];

          if ($yaml) {
            yield Yaml::encode([$item]);
          }
          else {
            yield $item;
          }
        }
      }
    }
  }

  /**
   * Get the redirect destination. Handles no input.
   *
   * @return string
   */
  private function getDestination() {
    $current_path = \Drupal::service('path.current')->getPath();
    $destination = \Drupal::destination()->get();

    return $current_path !== $destination ? $destination : '/admin/cohesion';
  }

  /**
   * User downloads a Yaml package from their browser.
   * This first saves the file to temporary:// so we can check for any errors.
   *
   * @param $filename
   * @param $entities
   * @param array $exclude_entity_type_ids
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function sendYamlDownload($filename, $entities, $exclude_entity_type_ids = []) {
    // Stream the package to a temporary file.
    $temp_file_path = $this->fileSystem->tempnam('temporary://', 'package');
    $temp_file = fopen($temp_file_path, 'wb');

    if ($temp_file) {
      try {
        // Use the Yaml generator to stream the output to the file.
        foreach ($this->buildPackageStream($entities, TRUE, $exclude_entity_type_ids) as $yaml) {
          // Write to the temporary file.
          if (fwrite($temp_file, $yaml) === FALSE) {
            drupal_set_message(t('Unable to write to temporary file "%path"', ['%path' => $temp_file_path]), 'error');
            return new RedirectResponse(Url::fromUserInput($this->getDestination())->toString());
          }
        }

      }
      catch (\Throwable $e) {
        fclose($temp_file);
        drupal_set_message(t('Package %path failed to build. There was a problem exporting the package. %e', ['%path' => $filename, '%e' => $e->getMessage()]), 'error');
        return new RedirectResponse(Url::fromUserInput($this->getDestination())->toString());
      }
    }
    else {
      // Don't try to close $temp_file since it's FALSE at this point.
      drupal_set_message(t('Temporary file "%path" could not be opened for file upload', ['%path' => $temp_file_path]), 'error');
      return new RedirectResponse(Url::fromUserInput($this->getDestination())->toString());
    }

    fclose($temp_file);

    // Stream the temporary file to the users browser.
    return new BinaryFileResponse($temp_file_path, 200, [
      'Content-disposition' => 'attachment; filename=' . $filename,
      'Content-type' => 'application/x-yaml'
    ]);
  }

  /**
   * Generator that yields each dependency of a config entity by scanning
   * recursively until it runs out of entities. Ignores duplicates.
   *
   * @param $entities
   * @param array $excluded_entity_type_ids
   * @param array $list
   * @param bool $recurse
   *
   * @return \Generator
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function buildPackageEntityList($entities, $excluded_entity_type_ids = [], &$list = [], $recurse = TRUE) {
    foreach ($entities as $entity) {
      // Re-calculate the usage for this entity.
      $this->usageUpdateManager->buildRequires($entity);

      // Get the Sync plugin for this entity.
      if ($plugin = $this->getPluginInstanceFromType($entity->getEntityType())) {
        // Don't yield if already sent or in the excluded list.
        $dependency_name = $entity->getConfigDependencyName();
        if (!isset($list[$dependency_name]) && !in_array($entity->getEntityTypeId(), $excluded_entity_type_ids)) {
          // Add this entity to the list so we don't yield something already sent.
          $list[$entity->getConfigDependencyName()] = TRUE;

          // And yield it.
          yield [
            'dependency_name' => $entity->getConfigDependencyName(),
            'type' => $entity->getEntityTypeId(),
            'entity' => $entity,
          ];

          // Loop through it's dependencies.
          /** @var SyncPluginInterface $plugin */
          foreach ($plugin->getDependencies($entity) as $key => $items) {
            foreach ($items as $item) {
              if (is_array($item)) {
                $id = NULL;
                $uuid = NULL;
                $type = NULL;

                if ($key == 'config') {
                  $type = $item['type'];
                  $id = $item['id'];
                }
                else {
                  if ($key == 'content') {
                    $type = $item['type'];
                    $uuid = $item['uuid'];
                  }
                }

                // Try and load the entity.
                try {
                  // Config entity by id.
                  $tentity = NULL;
                  if ($id) {
                    $tentity = $this->entityTypeManager->getStorage($type)->load($id);
                  }

                  if (!$tentity) {
                    // Content entity id uuid.
                    if (!$tentity = $this->entityRepository->loadEntityByUuid($type, $uuid)) {
                      continue;
                    }
                  }
                } catch (\Exception $e) {
                  continue;
                }

                if ($recurse) {
                  // Don't recurse te next entry if exporting a package entity.
                  yield from $this->buildPackageEntityList([$tentity], $excluded_entity_type_ids, $list, $entity instanceof Package ? FALSE : $recurse);
                }
              }
            }
          }
        }
      }
    }
  }

}