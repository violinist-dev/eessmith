<?php

namespace Drupal\cohesion_sync\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Element\ManagedFile;

/**
 * Provides an form element for uploading managed files in chunks to bypass `upload_max_filesize`
 *
 * @FormElement("chunked_file")
 */
class ChunkedFile extends ManagedFile {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processManagedFile'],
      ],
      '#pre_render' => [
        [$class, 'preRenderManagedFile'],
      ],
      '#theme' => 'file_managed_file',
      '#theme_wrappers' => ['form_element'],
      '#progress_indicator' => 'throbber',
      '#progress_message' => NULL,
      '#upload_validators' => [],
      '#upload_location' => NULL,
      '#size' => 22,
      '#multiple' => FALSE,
      '#extended' => FALSE,
      '#attached' => [
        'library' => ['cohesion_sync/sync-chunked-file'],
      ],
      '#accept' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Find the current value of this field.
    $fids = !empty($input['fids']) ? explode(' ', $input['fids']) : [];
    foreach ($fids as $key => $fid) {
      $fids[$key] = (int) $fid;
    }

    // Process any input and save new uploads.
    if ($input !== FALSE) {
      if ($input = $form_state->getUserInput()) {
        if (isset($input['files'])) {
          $fids = array_merge($fids, $input['files']);
        }
      }
    }

    $return['fids'] = $fids;
    return $return;
  }

}
