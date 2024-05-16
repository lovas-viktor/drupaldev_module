<?php

declare(strict_types=1);

namespace Drupal\drupaldev_commerce\EventSubscriber;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Event\ProductDefaultVariationEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @todo Add description for this subscriber.
 */
final class ProductDefaultVariation implements EventSubscriberInterface {

  public function getDefaultVariation(ProductDefaultVariationEvent $event) {
    if ($event->getProduct()->bundle() == 'default') {
      $variations = $event->getProduct()->getVariations();
      if (!empty($variations)) {
        $variation_price_map = [];

        foreach ($variations as $variation) {
          $variation_price_map[$variation->id()] = $variation->getPrice()
            ->getNumber();
        }

        /** @var ProductVariation $cheapest_variation */
        $cheapest_variation = ProductVariation::load(min(array_keys($variation_price_map)));
        $event->setDefaultVariation($cheapest_variation);
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ProductEvents::PRODUCT_DEFAULT_VARIATION => [
        'getDefaultVariation',
        -100,
      ],
    ];
  }

}
