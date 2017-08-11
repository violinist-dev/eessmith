<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Vocabulary entity display migration.
 *
 * @group migrate_drupal_6
 */
class MigrateVocabularyEntityDisplayTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'taxonomy', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Execute Dependency Migrations.
    $this->migrateContentTypes();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigrations([
      'd6_node_type',
      'd6_taxonomy_vocabulary',
      'd6_vocabulary_field',
      'd6_vocabulary_field_instance',
    ]);
  }

  /**
   * Tests the Drupal 6 vocabulary-node type association to Drupal 8 migration.
   */
  public function testVocabularyEntityDisplay() {
    $this->executeMigration('d6_vocabulary_entity_display');

    // Test that the field exists.
    $component = EntityViewDisplay::load('node.page.default')->getComponent('tags');
    $this->assertIdentical('entity_reference_label', $component['type']);
    $this->assertIdentical(20, $component['weight']);
    // Test the Id map.
    $this->assertIdentical(['node', 'article', 'default', 'tags'], $this->getMigration('d6_vocabulary_entity_display')->getIdMap()->lookupDestinationID([4, 'article']));
  }

}
