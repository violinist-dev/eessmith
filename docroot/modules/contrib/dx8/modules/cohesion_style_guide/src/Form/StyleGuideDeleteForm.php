<?php

namespace Drupal\cohesion_style_guide\Form;

use Drupal\cohesion\Form\CohesionDeleteForm;
use Drupal\cohesion\Services\RebuildInuseBatch;
use Drupal\cohesion\UsageUpdateManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete Cohesion custom styles entities.
 */
class StyleGuideDeleteForm extends CohesionDeleteForm {

  /**
   * The instance of the rebuild in use batch service
   *
   * @var RebuildInuseBatch
   */
  protected $rebuildInUseBatch;

  /**
   * The instance of the update usage manager batch service
   *
   * @var UsageUpdateManager
   */
  protected $usageUpdateManager;

  /**
   *
   * @param RebuildInuseBatch $rebuild_inuse_batch
   * @param UsageUpdateManager $usage_update_manager
   */
  public function __construct(RebuildInuseBatch $rebuild_inuse_batch, UsageUpdateManager $usage_update_manager) {
    $this->rebuildInUseBatch = $rebuild_inuse_batch;
    $this->usageUpdateManager = $usage_update_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cohesion.rebuild_inuse_batch'),
      $container->get('cohesion_usage.update_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a <em>Style guide</em> will remove it from the <em>Style guide manager</em> within theme setting. 
      Token values created by the Style guide will no longer work. The configuration of your Style guide will be deleted. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $in_use_list = $this->usageUpdateManager->getInUseEntitiesList($this->entity);
    if (!empty($in_use_list)) {
      $this->rebuildInUseBatch->run($in_use_list);
    }
  }

}
