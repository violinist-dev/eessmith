<?php

/**
 * @file
 * Contains \Drupal\beta2beta\Tests\Update\Beta7UpdatePath.
 */

namespace Drupal\beta2beta\Tests\Update;

use Drupal\beta2beta\Tests\Update\TestTraits\FrontPage;
use Drupal\beta2beta\Tests\Update\TestTraits\NewNode;

/**
 * Tests the beta 10 update path.
 *
 * @group beta2beta
 */
class Beta7UpdatePath extends Beta2BetaUpdateTestBase {

  use FrontPage;
  use NewNode;

  /**
   * Turn off strict config schema checking.
   *
   * This has to be turned off since there are multiple update hooks that update
   * views. Since only the final view save will be compliant with the current
   * schema, an exception would be thrown on the first view to be saved if this
   * were left on.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $startingBeta = 7;

}
