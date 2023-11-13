<?php

namespace Drupal\drupaldev_commerce\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentInformation as BasePaymentInformation;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the shipping information pane.
 *
 * Collects the shipping profile, then the information for each shipment.
 * Assumes that all shipments share the same shipping profile.
 *
 * @CommerceCheckoutPane(
 *   id = "payment_method_select",
 *   label = @Translation("Payment method"),
 *   wrapper_element = "div",
 * )
 */
class PaymentMethod extends BasePaymentInformation {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);

    if (isset($pane_form['billing_information']['#inline_form'])) {
      /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
      $inline_form = $pane_form['billing_information']['#inline_form'];

      // Hide billing info from here.
      $pane_form['billing_information']['#access'] = FALSE;

      if (!$form_state->has('payment_method_select')) {
        $form_state->set('payment_method_select', $inline_form->getEntity());
      }
    }

    // Show payment method form when only one option is available.
    if (!empty($pane_form['#payment_options']) && count($pane_form['#payment_options']) < 2) {
      $pane_form['payment_method']['#access'] = TRUE;
    }

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    parent::validatePaneForm($pane_form, $form_state, $complete_form);
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $pane_form['billing_information']['#inline_form'];

    if ($inline_form) {
      /** @var \Drupal\profile\Entity\ProfileInterface $profile */
      $form_state->set('billing_profile', $inline_form->getEntity());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    parent::submitPaneForm($pane_form, $form_state, $complete_form);
    $form_state->set('billing_profile', $this->order->getBillingProfile());
  }

}
