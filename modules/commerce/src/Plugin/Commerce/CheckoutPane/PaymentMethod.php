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
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('drupaldev_commerce')->getPath();

    if (isset($pane_form['billing_information']['#inline_form'])) {
      /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
      $inline_form = $pane_form['billing_information']['#inline_form'];

      // Hide billing info from here.
      $pane_form['billing_information']['#access'] = FALSE;

      if (!$form_state->has('payment_method_select')) {
        $form_state->set('payment_method_select', $inline_form->getEntity());
      }
    }

    $pane_form['#type'] = 'container';
    $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
    $payment_gateways = $payment_gateway_storage->loadMultipleForOrder($this->order);
    $selected_payment_method = FALSE;

    if ($form_state->hasValue('payment_method_select')) {
      $selected_payment_method = $form_state->getValue('payment_method_select')['payment_method'];
    }
    $i = 0;
    $option_labels = $pane_form['payment_method']['#options'];

    foreach ($payment_gateways as $id => $payment_gateway) {
      if (isset($payment_gateway->getPluginConfiguration()['instructions'])) {
        // Set first item as default.
        if ($i == 0 && !$selected_payment_method) {
          $pane_form['payment_method']['#default_value'] = $id;
        }
        else {
          if ($selected_payment_method && strpos('paypal_custom', $selected_payment_method) !== FALSE) {
            // When paypal selected the option key is "new--paypal_checkout--paypal_custom" but,
            // $selected_payment_method returns "paypal_custom", that's why this hack needs here.
            $pane_form['payment_method']['#default_value'] = $id;
          }
        }

        $desc = $payment_gateway->getPluginConfiguration()['instructions']['value'];

        if ($desc) {
          $option_labels[$id] .= '<br><small class="payment_description">' . nl2br($desc) . '</small>';
        }
      } else {
        if (str_contains($id, 'paypal')) {
          $desc = '<img src="/'.$module_path.'/images/paypal.png" width="200px;">';
          foreach($option_labels as $label_id => $label){
            if (str_contains($label_id, 'paypal')) {
              $option_labels[$label_id] .= '<br><small class="payment_description">' . nl2br($desc) . '</small>';
            }
          }
        } elseif (str_contains($id, 'barion')) {
          $desc = '<img src="/'.$module_path.'/images/barion.png" width="300px;">';
          foreach($option_labels as $label_id => $label){
            if (str_contains($label_id, 'barion')) {
              $option_labels[$label_id] .= '<br><small class="payment_description">' . nl2br($desc) . '</small>';
            }
          }
        } elseif (str_contains($id, 'stripe')) {
          $desc = '<img src="/'.$module_path.'/images/stripe.png" width="340px;">';
          foreach($option_labels as $label_id => $label){
            if (str_contains($label_id, 'stripe')) {
              $option_labels[$label_id] .= '<br><small class="payment_description">' . nl2br($desc) . '</small>';
            }
          }
        } elseif (str_contains($id, 'worldpay')) {
            $desc = '<img src="/'.$module_path.'/images/worldpay.png" width="300px;">';
            foreach($option_labels as $label_id => $label){
                if (str_contains($label_id, 'worldpay')) {
                    $option_labels[$label_id] .= '<br><small class="payment_description">' . nl2br($desc) . '</small>';
                }
            }
        }
      }

      $i++;
    }

    $pane_form['payment_method']['#options'] = $option_labels;

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
    if(isset($pane_form['billing_information'])){
      $inline_form = $pane_form['billing_information']['#inline_form'];

      if ($inline_form) {
        /** @var \Drupal\profile\Entity\ProfileInterface $profile */
        $form_state->set('billing_profile', $inline_form->getEntity());
      }
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
