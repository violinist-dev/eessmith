<?php

namespace Drupal\cohesion_templates\Plugin\Api;

use Drupal\cohesion\Entity\EntityJsonValuesInterface;
use Drupal\cohesion\Plugin\LayoutCanvas\LayoutCanvas;
use Drupal\Component\Serialization\Json;
use Drupal\cohesion\CohesionApiClient;
use Drupal\cohesion\ApiPluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\cohesion_elements\Entity\Component;

/**
 * Class TemplatesApi
 *
 * @package Drupal\cohesion_templates
 *
 * @Api(
 *   id = "templates_api",
 *   name = @Translation("Templates send to API"),
 * )
 */
class TemplatesApi extends ApiPluginBase {

  /** @var EntityJsonValuesInterface $entity */
  protected $entity;

  public $json_values;

  public $filename;

  private $content_hashes;

  private $is_preview = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityJsonValuesInterface $entity) {
    parent::setEntity($entity);
    $this->setJsonValues($this->entity->getJsonValues());
  }

  /**
   * Setter.
   *
   * @param $json_values
   */
  public function setJsonValues($json_values) {
    $this->json_values = $json_values;
  }

  /**
   * @param null $is_preview
   *
   * @return bool
   */
  public function isPreview($is_preview = NULL) {
    if ($is_preview !== NULL) {
      $this->is_preview = (bool) $is_preview;
    }

    return $this->is_preview;

  }

  /**
   * Replace the previously hashed content that comes back from the API.
   *
   * @param $template
   *
   * @return mixed
   */
  private function replaceDX8ContentTokens($template) {
    $template_values = Json::decode($template);
    if (is_array($this->content_hashes)) {
      foreach ($this->content_hashes as $hash => $string) {

        // Make sure all single quotes are escaped in single quoted values (unescape all then escape all)
        if (strpos($template_values['twig'], "'" . $hash . "'") !== FALSE) {
          $string = str_replace("\'", "'", $string);
          $string = str_replace("'", "\'", $string);
        }
        // Perform the replacement.
        $template_values['twig'] = str_replace($hash, $string, $template_values['twig']);
      }
    }

    return json_encode($template_values, JSON_UNESCAPED_UNICODE);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareData($attach_css = TRUE) {
    parent::prepareData($attach_css);

    // Layout builder field.
    if (isset($this->json_values)) {
      $layoutCanvas = new LayoutCanvas($this->json_values);
    }
    // Content/master/view/etc. template.
    else {
      $layoutCanvas = $this->entity->getLayoutCanvasInstance();
    }

    // If it's a content template, tell the API. This is used to wrap
    // content in an <article> tag for QuickEdit to work correctly.
    if ($this->entity instanceof \Drupal\cohesion_templates\Entity\ContentTemplates) {
      $this->data->settings->isContentTemplate = TRUE;
    }

    // If it's a component template, tell the API.
    if ($this->entity instanceof Component) {
      $this->data->settings->isComponentTemplate = TRUE;

      if ($this->entity->get('has_quick_edit') === NULL || $this->entity->get('has_quick_edit') === TRUE) {
        $this->data->settings->isComponentContextual = TRUE;
      }
    }

    if ($this->entity instanceof ContentEntityInterface || $this->is_preview) {
      $this->data->settings->isLayoutEntity = TRUE;

      // Rendering a component preview.
      $this->data->settings->isPreview = $this->is_preview;
    }

    // Search through the JSON model and turn tokens into: [token.*|context|context]
    // String replace any raw content so the API doesn't see any sensitive data.
    $layoutCanvas->prepareDataForAPI($this->isPreview());
    $this->content_hashes = $layoutCanvas->getContentHashed();
    $this->data->templates = $layoutCanvas;
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    $sendApi = parent::send();

    // If this is a layout builder on an entity, return the twig string.
    if ($this->entity instanceof ContentEntityInterface || $this->is_preview) {
      return $sendApi;
    }

    // Content/Master/Menu/etc template
    // Decode json
    $response = $this->getData();

    if (isset($response['template'])) {

      // Check for errors in template markup.
      $decoded_template = Json::decode($response['template']);

      if (isset($decoded_template['error'])) {
        drupal_set_message(t('Template compilation error (template has not been saved): ') . $decoded_template['error'], 'error');
        return FALSE;
      }

      // If this is a normal template, then save the twig file to disk.
      $this->filename = $this->entity->get('twig_template');

      // Save template to filesystem
      if ($this->getSaveData()) {

        try {
          \Drupal::keyValue('coh_template_metadata')->set($this->filename, $decoded_template['metadata']);
        } // There was no filename.
        catch (\Exception $e) {
          \Drupal::logger('cohesion_templates')->notice("Template metadata did not contain a filename: @template_file", ['@template_file' => $this->filename]);
        }

        $twig_filename = $this->filename . '.html.twig';

        $running_dx8_batch = &drupal_static('running_dx8_batch');
        if (!$running_dx8_batch) {
          $this->saveTemplate($decoded_template['twig'], $twig_filename);
        }
        else {
          $this->saveTemporaryTemplate($decoded_template['twig'], $twig_filename);
        }
      }
    }

    return $sendApi;
  }

  /**
   * Save a .twig template that has been compiled by the API.
   *
   * @param $content
   * @param $filename
   *
   * @return bool
   * @throws \Exception
   */
  private function saveTemplate($content, $filename) {
    // Create the template twig directory if needed.
    if (!file_exists(COHESION_TEMPLATE_PATH)) {
      \Drupal::service('file_system')->mkdir(COHESION_TEMPLATE_PATH, 0777, FALSE);
    }

    // Save the compiled twig file.
    $template_file = COHESION_TEMPLATE_PATH . '/' . $filename;
    if (($template_saved = file_unmanaged_save_data($content, $template_file, FILE_EXISTS_REPLACE))) {
      \Drupal::logger('cohesion_templates')->notice("Template created: @template_file", ['@template_file' => $template_file]);
    }
    else {
      \Drupal::logger('cohesion_templates')->notice("Unable to create template: @template_file", ['@template_file' => $template_file]);
      throw new \Exception(t('Unable to create template: @template_file', ['@template_file' => $template_file]));
    }

    return $template_saved;
  }

  /**
   * When rebuilding, .twig templates are stored temporarily, so rebuilds that
   * fail do not result in a broken looking site.
   *
   * @param null $data
   * @param null $filename
   *
   * @return array|null
   * @throws \Exception
   */
  public function saveTemporaryTemplate($data = NULL, $filename = NULL) {
    $temp_files = [];
    if (!$filename) {
      return NULL;
    }

    // Build the path to the temporary file.
    $temporary_directory = \Drupal::service('cohesion.local_files_manager')->scratchDirectory();
    $temp_file = $temporary_directory . '/' . $filename;

    if (file_put_contents($temp_file, $data) !== FALSE) {
      // Register temporary template files
      $templates = \Drupal::keyValue('cohesion.temporary_template')->get('temporary_templates', []);
      $templates[] = $temp_file;
      \Drupal::keyValue('cohesion.temporary_template')->set('temporary_templates', $templates);
    }
    else {
      throw new \Exception(t('Unable to create template: @template_file', ['@template_file' => $temp_file]));
    }

    return $temp_files;
  }

  /**
   * {@inheritdoc}
   */
  public function callApi() {
    $this->response = CohesionApiClient::buildTemplate($this->data);

    if (is_array($this->response) && isset($this->response['data']['template'])) {
      $this->response['data']['template'] = $this->replaceDX8ContentTokens($this->response['data']['template']);
    }
  }

}
