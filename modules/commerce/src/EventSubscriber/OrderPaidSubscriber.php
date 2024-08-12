<?php

namespace Drupal\drupaldev_commerce\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderPaidSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OrderEvents::ORDER_PAID => 'onPaid',
    ];
  }

  /**
   * Increments an order flag each time the paid event gets dispatched.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The event.
   */
  public function onPaid(OrderEvent $event) {
    $order = $event->getOrder();
    if ($order->isPaid()) {
      $order_state = $order->getState();

      $payment_gateway = $order->payment_gateway->entity;

      if ($payment_gateway instanceof PaymentGateway && $payment_gateway->id() == 'wiretransfer') {
        if ($order_state->getOriginalId() == 'awaiting_payment') {
          $order_transition = 'awaiting_payment_to_processing';
          // Check if transition is allowed.
          if ($order_state->isTransitionAllowed($order_transition)) {
            $order_state->applyTransitionById($order_transition);
          }
        }
      }
    }
  }
}
