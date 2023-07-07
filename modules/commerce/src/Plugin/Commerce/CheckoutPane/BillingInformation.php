<?php

namespace Drupal\drupaldev_commerce\Plugin\Commerce\CheckoutPane;

use \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\BillingInformation as BaseBillingInformation;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the shipping information pane.
 *
 * Collects the shipping profile, then the information for each shipment.
 * Assumes that all shipments share the same shipping profile.
 *
 * @CommerceCheckoutPane(
 *   id = "billing_profile",
 *   label = @Translation("Billing details"),
 *   wrapper_element = "fieldset",
 * )
 */
class BillingInformation extends BaseBillingInformation {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);

    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $pane_form['profile']['#inline_form'];

    if (!$form_state->has('billing_profile')) {
      $form_state->set('billing_profile', $inline_form->getEntity());
    }

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    parent::validatePaneForm($pane_form, $form_state, $complete_form);
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $pane_form['profile']['#inline_form'];
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $form_state->set('billing_profile', $inline_form->getEntity());
  }

}
