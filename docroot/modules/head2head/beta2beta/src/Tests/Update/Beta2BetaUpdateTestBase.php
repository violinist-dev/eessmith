<?php

/**
 * @file
 * Contains \Drupal\beta2beta\Tests\Update\Beta2BetaUpdateTestBase.
 */

namespace Drupal\beta2beta\Tests\Update;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Base class for testing beta to beta update paths.
 */
abstract class Beta2BetaUpdateTestBase extends UpdatePathTestBase {

  /**
   * Starting beta version to use.
   *
   * @var int
   */
  protected static $startingBeta;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['beta2beta'];

  /**
   * {@inheritdoc}
   *
   */
  public function setUp() {
    $this->initBetaDb();
    parent::setUp();
    $this->initBetaVersion();

    // Set the schema version for beta2beta to the starting beta version so that
    // update hooks are properly run. Since the naming convention for update
    // hooks is the target (eg, updates from 9 to 10 are in the
    // beta2beta_update_810xx series), the starting beta is incremented by 1.
    $version = ((int) '8' . (static::$startingBeta + 1) . '00') - 1;
    drupal_set_installed_schema_version('beta2beta', $version);
  }

  /**
   * Sets a fake version for testing purposes.
   *
   * @todo This relies on the State API on the un-updated database, so is not
   *   ideal, and may not work with sufficiently old beta versions.
   *
   * @see beta2beta_update_helper()
   * @see beta2beta_determine_beta_version().
   */
  protected function initBetaVersion() {
    // Use a sufficiently high beta. This simply allows all update hooks to run.
    \Drupal::state()->set('beta2beta_testing_version', 16);
  }

  /**
   * Sets the proper starting database using self::$startingBeta.
   */
  protected function initBetaDb() {
    if (!static::$startingBeta) {
      throw new \RuntimeException('No starting beta version is set!');
    }
    $file = __DIR__ . '/../../../tests/fixtures/drupal-8.bare.standard.beta' . static::$startingBeta . '.php.gz';
    if (!file_exists($file)) {
      throw new \RuntimeException(SafeMarkup::format('Database dump file @file not found', ['@file' => $file]));
    }
    // This database should be the very first to be loaded.
    array_unshift($this->databaseDumpFiles, $file);
    $this->databaseDumpFiles = array_unique($this->databaseDumpFiles);
  }

  /**
   * {@inheritdoc}
   *
   * Take site out of maintenance mode after running updates.
   */
  protected function runUpdates() {
    parent::runUpdates();

    // @todo should the core method do this instead?
    \Drupal::state()->set('system.maintenance_mode', FALSE);
  }

}
