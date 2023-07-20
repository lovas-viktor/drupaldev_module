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
 *   id = "billing_profile",
 *   label = @Translation("Shipping information"),
 *   wrapper_element = "fieldset",
 * )
 */
class PaymentInformation extends BasePaymentInformation {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);

    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $pane_form['billing_information']['#inline_form'];

    // Hide payment method from here.
    $pane_form['payment_method']['#access'] = FALSE;

    if (!$form_state->has('billing_profile')) {
      $form_state->set('billing_profile', $inline_form->getEntity());
    }

    $pane_form['#title'] = $this->t('Shipping information');

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    parent::validatePaneForm($pane_form, $form_state, $complete_form);
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $pane_form['billing_information']['#inline_form'];
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $form_state->set('billing_profile', $inline_form->getEntity());
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    parent::submitPaneForm($pane_form, $form_state, $complete_form);
    $form_state->set('billing_profile', $this->order->getBillingProfile());
  }

}
