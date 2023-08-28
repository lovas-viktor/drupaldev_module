<?php

namespace Drupal\drupaldev_mailerlite\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class MailerliteSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'mailerlite.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailerlite_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['api_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Api key'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint url'),
      '#default_value' => $config->get('endpoint'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::SETTINGS)
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}