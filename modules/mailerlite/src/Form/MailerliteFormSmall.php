<?php
/**
 * @file
 * Contains \Drupal\student_registration\Form\RegistrationForm.
 */

namespace Drupal\drupaldev_mailerlite\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gdpr_compliance\Utility\FormWarning;

class MailerliteFormSmall extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailerlite_form_small';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $params = NULL) {
    $form['name_wrapper'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['mailerlite-name-wrapper'],
      ],
    ];

    $form['name_wrapper']['children']['name'] = [
      '#type' => 'textfield',
      '#title' => t('First name'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => t('Email'),
      '#required' => TRUE,
    ];

    // Add GDPR checkbox.
    FormWarning::addWarning($form);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('email'))) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    /** @var $mailerlite \Drupal\drupaldev_mailerlite\MailerliteService */
    $mailerlite = \Drupal::service('mailerlite.api');
    $res = $mailerlite->createSubscriber($values);

    if ($res['status_code'] == 201) {
      \Drupal::messenger()->addStatus(t('Thank you for Subscribing!'));
    }

  }

}
