<?php

namespace Drupal\drupaldev_google_tagmanager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Drupaldev google tagmanager settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupaldev_google_tagmanager_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['drupaldev_google_tagmanager.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['gtm_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GTM Id'),
      '#default_value' => $this->config('drupaldev_google_tagmanager.settings')->get('gtm_id'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('drupaldev_google_tagmanager.settings')
      ->set('gtm_id', $form_state->getValue('gtm_id'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
