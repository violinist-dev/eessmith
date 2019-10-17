<?php

namespace Drupal\cohesion\Commands;

use Drupal\cohesion\Drush\DX8CommandHelpers;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Question\Question;

/**
 * Class Dx8Commands
 *
 * @package Drupal\cohesion\Commands
 */
class Dx8Commands extends DrushCommands {

  /**
   * Import assets and rebuild element styles (replacement for the CRON).
   *
   * @command dx8:import
   * @usage drush dx8:import
   */
  public function import() {
    $this->say(t('Importing assets.'));
    $errors = DX8CommandHelpers::import();
    if ($errors) {
      $this->say('[error] ' . $errors['error']);
    }
    $this->yell(t('Congratulations. Cohesion is installed and up to date. You can now build your website.'));
  }

  /**
   * Resave all Cohesion config entities.
   *
   * @command dx8:rebuild
   * @usage drush dx8:rebuild
   */
  public function rebuild() {
    $this->say(t('Rebuilding all entities.'));
    DX8CommandHelpers::rebuild();
    $this->yell(t('Finished rebuilding.'));
  }
}
