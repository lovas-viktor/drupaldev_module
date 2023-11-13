<?php

namespace Drupal\commerce_barion_payment\PluginForm;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BarionRedirectForm.
 *
 * @package Drupal\commerce_barion_payment\PluginForm
 */
class BarionRedirectForm extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();
    /** @var \Drupal\commerce_barion_payment\Plugin\Commerce\PaymentGateway\BarionPaymentGateway $payment_gateway */
    $payment_gateway = $payment->getPaymentGateway()->getPlugin();
    $prepared_payment = $payment_gateway->preparePayment($order, $form['#return_url']);
    if ($prepared_payment->Status !== \PaymentStatus::Prepared) {
      throw new PaymentGatewayException('Payment could not be prepared.');
    }
    // @todo handle if status is not prepared.
    $order->setData('barion_payment_id', $prepared_payment->PaymentId);
    $order->save();
    $payment_gateway->createPayment($order, $prepared_payment);
    return $this->buildRedirectForm($form, $form_state, $prepared_payment->PaymentRedirectUrl, [], self::REDIRECT_GET);
  }

}
