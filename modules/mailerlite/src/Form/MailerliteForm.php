<?php
/**
 * @file
 * Contains \Drupal\student_registration\Form\RegistrationForm.
 */

namespace Drupal\drupaldev_mailerlite\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gdpr_compliance\Utility\FormWarning;

class MailerliteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailerlite_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name_wrapper'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['mailerlite-name-wrapper'],
      ],
    ];

    $form['name_wrapper']['children']['last_name'] = [
      '#type' => 'textfield',
      '#title' => t('Last name'),
      '#required' => TRUE,
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

    $form['birth_date'] = [
      '#type' => 'textfield',
      '#title' => t('Birthdate'),
      '#attributes' => ['id' => 'datepicker'],
      '#attached' => [
        'library' => [
          'drupaldev_mailerlite/mailerlite',
        ],
      ],
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
