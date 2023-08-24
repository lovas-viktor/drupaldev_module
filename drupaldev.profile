<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements hook_preprocess().
 */
function drupaldev_preprocess(&$variables, $hook) {}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function drupaldev_form_user_login_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['#submit'][] = 'drupaldev_user_login_form_submit';
}

/**
 * Custom submit handler for the login form.
 */
function drupaldev_user_login_form_submit($form, FormStateInterface $form_state) {
  $url = Url::fromRoute('<front>');
  $form_state->setRedirectUrl($url);
}
