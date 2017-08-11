<?php

namespace Drupal\migrate\Plugin\migrate\process;

@trigger_error('The ' . __NAMESPACE__ . '\Migration is deprecated in
Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use ' . __NAMESPACE__ . '\MigrationLookup', E_USER_DEPRECATED);

/**
 * Calculates the value of a property based on a previous migration.
 *
 * @link https://www.drupal.org/node/2149801 Online handbook documentation for migration process plugin @endlink
 *
 * @MigrateProcessPlugin(
 *   id = "migration"
 * )
 *
 * @deprecated in Drupal 8.3.x and will be removed in Drupal 9.0.x.
 *  Use \Drupal\migrate\Plugin\migrate\process\MigrationLookup instead.
 */
class Migration extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $processPluginManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The migration to be executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManagerInterface $process_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->migration = $migration;
    $this->processPluginManager = $process_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $migration_ids = $this->configuration['migration'];
    if (!is_array($migration_ids)) {
      $migration_ids = [$migration_ids];
    }
    if (!is_array($value)) {
      $value = [$value];
    }
    $this->skipOnEmpty($value);
    $self = FALSE;
    /** @var \Drupal\migrate\Plugin\MigrationInterface[] $migrations */
    $destination_ids = NULL;
    $source_id_values = [];
    $migrations = $this->migrationPluginManager->createInstances($migration_ids);
    foreach ($migrations as $migration_id => $migration) {
      if ($migration_id == $this->migration->id()) {
        $self = TRUE;
      }
      if (isset($this->configuration['source_ids'][$migration_id])) {
        $configuration = ['source' => $this->configuration['source_ids'][$migration_id]];
        $source_id_values[$migration_id] = $this->processPluginManager
          ->createInstance('get', $configuration, $this->migration)
          ->transform(NULL, $migrate_executable, $row, $destination_property);
      }
      else {
        $source_id_values[$migration_id] = $value;
      }
      // Break out of the loop as soon as a destination ID is found.
      if ($destination_ids = $migration->getIdMap()->lookupDestinationId($source_id_values[$migration_id])) {
        break;
      }
    }

    if (!$destination_ids && !empty($this->configuration['no_stub'])) {
      return NULL;
    }

    if (!$destination_ids && ($self || isset($this->configuration['stub_id']) || count($migrations) == 1)) {
      // If the lookup didn't succeed, figure out which migration will do the
      // stubbing.
      if ($self) {
        $migration = $this->migration;
      }
      elseif (isset($this->configuration['stub_id'])) {
        $migration = $migrations[$this->configuration['stub_id']];
      }
      else {
        $migration = reset($migrations);
      }
      $destination_plugin = $migration->getDestinationPlugin(TRUE);
      // Only keep the process necessary to produce the destination ID.
      $process = $migration->getProcess();

      // We already have the source ID values but need to key them for the Row
      // constructor.
      $source_ids = $migration->getSourcePlugin()->getIds();
      $values = [];
      foreach (array_keys($source_ids) as $index => $source_id) {
        $values[$source_id] = $source_id_values[$migration->id()][$index];
      }

      $stub_row = new Row($values + $migration->getSourceConfiguration(), $source_ids, TRUE);

      // Do a normal migration with the stub row.
      $migrate_executable->processRow($stub_row, $process);
      $destination_ids = [];
      try {
        $destination_ids = $destination_plugin->import($stub_row);
      }
      catch (\Exception $e) {
        $migration->getIdMap()->saveMessage($stub_row->getSourceIdValues(), $e->getMessage());
      }

      if ($destination_ids) {
        $migration->getIdMap()->saveIdMapping($stub_row, $destination_ids, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
      }
    }
    if ($destination_ids) {
      if (count($destination_ids) == 1) {
        return reset($destination_ids);
      }
      else {
        return $destination_ids;
      }
    }
  }

  /**
   * Skips the migration process entirely if the value is FALSE.
   *
   * @param mixed $value
   *   The incoming value to transform.
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   */
  protected function skipOnEmpty(array $value) {
    if (!array_filter($value)) {
      throw new MigrateSkipProcessException();
    }
  }

}
