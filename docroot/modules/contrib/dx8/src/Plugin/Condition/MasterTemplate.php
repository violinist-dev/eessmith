<?php

namespace Drupal\cohesion\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Master template' condition.
 *
 * @Condition(
 *   id = "cohesion_master_template",
 *   label = @Translation("Master template")
 * )
 */
class MasterTemplate extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Creates a new Cohesion MasterTemplate instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['using_master_template'] = [
      '#title' => $this->t('Using master template'),
      '#type' => 'checkbox',
      '#default_value' => is_integer($this->configuration['using_master_template']) ? $this->configuration['using_master_template'] : 0,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['using_master_template'] = $form_state->getValue('using_master_template');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('The page is using DX8 Master Template');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (!$this->configuration['using_master_template'] && !$this->isNegated()) {
      return TRUE;
    }

    return _cohesion_templates_get_master_template();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['using_master_template' => []] + parent::defaultConfiguration();
  }

}
