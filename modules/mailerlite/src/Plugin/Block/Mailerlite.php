<?php

/**
 * @file
 * Contains \Drupal\drupaldev_mailerlite\Plugin\Block\Mailerlite.
 */

namespace Drupal\drupaldev_mailerlite\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 * @Block(
 *   id = "drupaldev_mailerlite",
 *   admin_label = @Translation("Mailerlite block")
 * )
 */
class Mailerlite extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ml_user_id' => $this->t(''),
      'ml_form_id' => $this->t(''),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['ml_user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailerlite user ID'),
      '#description' => $this->t('Mailerlite user ID'),
      '#default_value' => $config['ml_user_id'],
    ];

    $form['ml_form_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailerlite form ID'),
      '#description' => $this->t('Mailerlite form ID'),
      '#default_value' => $config['ml_form_id'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['ml_user_id'] = $values['ml_user_id'];
    $this->configuration['ml_form_id'] = $values['ml_form_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if ($form_state->getValue('ml_user_id') === '') {
      $form_state->setErrorByName('ml_user_id', $this->t('Set mailerlite user id.'));
    }
    if ($form_state->getValue('ml_form_id') === '') {
      $form_state->setErrorByName('ml_form_id', $this->t('Set mailerlite form id.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'mailerlite',
      '#ml_user_id' => $this->configuration['ml_user_id'],
      '#ml_form_id' => $this->configuration['ml_form_id'],
      '#cache' => [
        'contexts' => ['languages'],
      ],
    ];
  }

}
