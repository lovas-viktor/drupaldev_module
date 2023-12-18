<?php
/**
 * @file
 * Contains \Drupal\student_registration\Form\RegistrationForm.
 */

namespace Drupal\drupaldev_mailerlite\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MailerliteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailerlite_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name_wrapper'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['mailerlite-name-wrapper']
      ],
      'children' => [
        'last_name' => [
          '#type' => 'textfield',
          '#title' => t('Last name'),
          '#placeholder' => t('Last name'),
          '#title_display' => FALSE,
          '#required' => TRUE,
        ],
        'first_name' => [
          '#type' => 'textfield',
          '#title' => t('First name'),
          '#placeholder' => t('First name'),
          '#title_display' => FALSE,
          '#required' => TRUE,
        ],
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => t('Email'),
      '#placeholder' => t('Email'),
      '#title_display' => FALSE,
      '#required' => TRUE,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('email'))) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $mailerlite = \Drupal::service('mailerlite.api');
    $mailerlite->createSubscriber($values['email']);
  }

}