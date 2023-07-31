<?php

namespace Drupal\drupaldev_commerce\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;

/**
 * @CommerceCheckoutFlow(
 *  id = "custom_checkout_flow",
 *  label = @Translation("Custom checkout flow"),
 * )
 */
class CustomCheckoutFlow extends CheckoutFlowWithPanesBase {

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    return [
        'login' => [
         'label' => $this->t('Login'),
         'previous_label' => $this->t('Go back'),
         'has_sidebar' => FALSE,
       ],
        'payment_information' => [
          'label' => $this->t('Billing details'),
          'has_sidebar' => TRUE,
          'previous_label' => $this->t('Go back'),
        ],
        'shipping_information' => [
          'label' => $this->t('Shipping information'),
          'has_sidebar' => TRUE,
          'previous_label' => $this->t('Go back'),
          'next_label' => $this->t('Continue to select shipping'),
        ],
        'payment_method' => [
          'label' => $this->t('Payment method'),
          'has_sidebar' => TRUE,
          'previous_label' => $this->t('Go back'),
          'next_label' => $this->t('Continue to payment method'),
        ],
        'review' => [
          'label' => $this->t('Review'),
          'next_label' => $this->t('Continue to review'),
          'previous_label' => $this->t('Go back'),
          'has_sidebar' => TRUE,
        ],
      ] + parent::getSteps();
  }

}
