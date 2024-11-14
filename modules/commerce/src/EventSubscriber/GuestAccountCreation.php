<?php

namespace Drupal\drupaldev_commerce\EventSubscriber;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class GuestAccountCreation.
 */
class GuestAccountCreation implements EventSubscriberInterface {

  /**
   * Constructs a new OrderCompleteRegistrationSubscriber object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['commerce_order.place.pre_transition'] = ['accountCreationHandler'];
    $events[KernelEvents::REQUEST][] = ['redirect', 1000];
    return $events;
  }

  /**
   * This method is called whenever the commerce_order.place.post_transition
   * event is dispatched.
   *
   * @param WorkflowTransitionEvent $event
   */
  public function accountCreationHandler(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    $authorObj = $order->getCustomer(); // Author informations.
    $uid = $authorObj->id(); // Author Id.

    // Create new user account and initiate the email.
    if (!$uid) {
      // Loading user from email id.
      $mail = $order->getEmail();
      $oldUser = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['mail' => $mail]);
      $oldUser = reset($oldUser);

      if (is_object($oldUser) && $oldUser->id()) {
        $event->getEntity()->setCustomer($oldUser);
        $user = $oldUser;
      }
      else {
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $user = User::create();

        // Generate the username.
        $name = $mail;

        // Mandatory.
        $user->setEmail($mail);
        $user->setUsername($name);
        $user->enforceIsNew();

        // Optional.
        $user->set('init', 'email');
        $user->set('langcode', $language);
        $user->set('preferred_langcode', $language);
        $user->set('preferred_admin_langcode', $language);
        $user->activate();

        // Save user account.
        $user->save();

        // Set customer to order.
        $event->getEntity()->setCustomer($user);
      }
    }
  }

  /**
   * Redirects paths starting with multiple slashes to a single slash.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The RequestEvent to process.
   */
  public function redirect(RequestEvent $event) {

    $session = \Drupal::request()->getSession();
    $redirect_url = $session->get('redirect_to_product_entity_after_checkout');
    $payment_gateway = $session->get('payment_gateway');
    $order_id = $session->get('order_id');

    if (!empty($redirect_url) && $payment_gateway == 'barion_payment') {
      $event->setResponse(new RedirectResponse($redirect_url));

      // Note: Below this is a duplicate code. Maybe fix this later.
      // @see \Drupal\jelenletiiv\EventSubscriber\OrderPaidSubscriber::class->orderPaidHandler
      if ($payment_gateway == 'manual' || $payment_gateway == 'barion_payment') {
        $order = Order::load($order_id);
        $order_items = $order->getItems();
        $customer = $order->getCustomer();
        $sheet_access = [];

        $existing_data = $customer->get('field_sheet_access')->getValue();
        if (!empty($existing_data)) {
          $sheet_access = array_map(function ($item) {
            return $item['value'];
          }, $existing_data);
        }

        foreach ($order_items as $order_item) {
          $sku = $order_item->getPurchasedEntity()->getSku();
          if (strlen($sku) === 4) {
            foreach (range(1, 12) as $number) {
              $sheet_access[] = $sku . sprintf("%02d", $number);
            }
          }
          else {
            $sheet_access[] = $sku;
          }
        }

        $sheet_access = array_unique($sheet_access);

        $customer->set('field_sheet_access', $sheet_access);
        $customer->save();

        // Delete cache on order paid.
        Cache::invalidateTags(['related_attenance_sheets_block']);
      }
    }

    if ($order_id) {
      $redirect_url = Url::fromRoute('commerce_checkout.form', [
        'commerce_order' => $order_id,
        'step' => 'complete',
      ])->toString();
      $event->setResponse(new RedirectResponse($redirect_url));
    }

    $session->remove('order_id');
    $session->remove('redirect_to_product_entity_after_checkout');
    $session->remove('payment_gateway');
  }

}
