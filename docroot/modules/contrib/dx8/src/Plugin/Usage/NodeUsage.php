<?php

namespace Drupal\cohesion\Plugin\Usage;

use Drupal\cohesion\Plugin\Usage\FieldableContentEntityUsageBase;

/**
 * Class NodeUsage
 *
 * @package Drupal\cohesion\Plugin\Usage
 *
 * @Usage(
 *   id = "node_usage",
 *   name = @Translation("Node usage"),
 *   entity_type = "node",
 *   scannable = TRUE,
 *   scan_same_type = TRUE,
 *   group_key = FALSE,
 *   group_key_entity_type = FALSE,
 *   exclude_from_package_requirements = TRUE,
 *   exportable = FALSE
 * )
 */
class NodeUsage extends FieldableContentEntityUsageBase {

}