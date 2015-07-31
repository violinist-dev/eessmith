<?php

/**
 * @file
 * Contains \Drupal\beta2beta\Tests\Update\Beta11Beta12UpdatePath.
 */

namespace Drupal\beta2beta\Tests\Update;

use Drupal\beta2beta\Tests\Update\TestTraits\FrontPage;
use Drupal\beta2beta\Tests\Update\TestTraits\NewNode;

/**
 * Tests the beta 11 to beta 12 update path.
 *
 * @group beta2beta
 */
class Beta11Beta12UpdatePath extends Beta2BetaUpdateTestBase {

  use FrontPage;
  use NewNode;

  /**
   * {@inheritdoc}
   */
  protected static $startingBeta = 11;

}
