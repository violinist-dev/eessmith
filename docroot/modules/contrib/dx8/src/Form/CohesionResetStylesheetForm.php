<?php

namespace Drupal\cohesion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements the form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class CohesionResetStylesheetForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $rebuild_list_items = [
      t('Re-save DX8 Website settings and rebuild the CSS styles for them'),
      t('Re-save DX8 Base styles and rebuild the CSS styles for them'),
      t('Re-save DX8 Custom styles and rebuild the CSS styles for them'),
      t('Re-save DX8 Templates and rebuild the Twig files and inline CSS styles for them'),
      t('Re-save content entities using the DX8 layout canvas field and rebuild the Twig files and inline CSS styles for them'),
    ];

    $form['reset_stylesheet'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => t('Click rebuild below to:'),
    ];

    $form['reset_stylesheet']['list'] = [
      '#prefix' => '<ul><li>',
      '#markup' => implode('</li><li>', $rebuild_list_items),
      '#suffix' => '</li></ul>',
    ];

    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form. This is not required, but is convention.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rebuild'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cohesion_rebuild_stylesheet_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Rebuild cohesion configs
    $url = Url::fromRoute('cohesion_website_settings.batch_reload');
    if ($url->isRouted()) {
      $form_state->setRedirectUrl($url);
    }
  }
}