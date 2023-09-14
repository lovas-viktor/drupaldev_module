<?php

/**
 * @file
 * Builds placeholder replacement tokens for order data.
 */

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;

/**
 * Implements hook_token_info().
 */
function drupaldev_commerce_token_info() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_type = $entity_type_manager->getDefinition('commerce_order');
  assert($entity_type !== NULL);
  $info = [];

  $info['tokens']['commerce_order']['shipping-description'] = [
    'name' => t('Shipping description'),
    'description' => t('The description provided by shipping'),
  ];

  return $info;
}


/**
 * Implements hook_tokens().
 */
function drupaldev_commerce_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'commerce_order' && !empty($data['commerce_order'])) {
    $order = $data['commerce_order'];
    assert($order instanceof OrderInterface);

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'shipping-description':
          $shipping_method = $order->shipments->entity->shipping_method->entity;
          $shipping_method_description = $shipping_method->get('shipping_description')->value;
          $replacements[$original] = !empty($shipping_method_description) ? Markup::create($shipping_method_description) : '';
          break;
      }
    }
  }

  return $replacements;
}
