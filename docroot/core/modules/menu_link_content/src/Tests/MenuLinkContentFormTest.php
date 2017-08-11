<?php

namespace Drupal\menu_link_content\Tests;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the menu link content UI.
 *
 * @group Menu
 */
class MenuLinkContentFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $web_user = $this->drupalCreateUser(['administer menu']);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the MenuLinkContentForm class.
   */
  public function testMenuLinkContentForm() {
    $this->drupalGet('admin/structure/menu/manage/admin/add');
    $element = $this->xpath('//select[@id = :id]/option[@selected]', [':id' => 'edit-menu-parent']);
    $this->assertTrue($element, 'A default menu parent was found.');
    $this->assertEqual('admin:', $element[0]['value'], '<Administration> menu is the parent.');

    $this->drupalPostForm(
      NULL,
      [
        'title[0][value]' => t('Front page'),
        'link[0][uri]' => '<front>',
      ],
      t('Save')
    );
    $this->assertText(t('The menu link has been saved.'));
  }

  /**
   * Tests validation for the MenuLinkContentForm class.
   */
  public function testMenuLinkContentFormValidation() {
    $this->drupalGet('admin/structure/menu/manage/admin/add');
    $this->drupalPostForm(
      NULL,
      [
        'title[0][value]' => t('Test page'),
        'link[0][uri]' => '<test>',
      ],
      t('Save')
    );
    $this->assertText(t('Manually entered paths should start with /, ? or #.'));
  }

}
