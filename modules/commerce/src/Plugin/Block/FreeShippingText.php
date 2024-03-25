<?php

/**
 * @file
 * Contains \Drupal\drupaldev_commerce\Plugin\Block\FreeShippingText.
 */

namespace Drupal\drupaldev_commerce\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 *
 * @Block(
 *   id = "cart_informations_free_shipping_text",
 *   admin_label = @Translation("Cart Informations: Free Shipping Text")
 * )
 */
class FreeShippingText extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $free_shipping_datas = drupaldev_commerce_get_free_shipping_datas();

    if (!$free_shipping_datas['free_shipping_offer_available']) {
      return [];
    }

    //Get the created time of the current node
    return array(
      '#theme' => 'free_shipping_text',
      '#free_shipping_limit' => $free_shipping_datas['free_shipping_limit'],
      '#free_shipping_rest' => $free_shipping_datas['free_shipping_rest'],
      '#free_shipping_rest_value' => $free_shipping_datas['free_shipping_rest_value'],
      '#cache' => [
        'contexts' => ['cart'],
      ],
	  );
  }

    /**
     * {@inheritdoc}
     */
    public function getCacheContexts() {
        return Cache::mergeContexts(parent::getCacheContexts(), ['cart']);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags() {
        $cache_tags = parent::getCacheTags();
        $cart_cache_tags = [];

        /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
        $carts = \Drupal::service('commerce_cart.cart_provider')->getCarts();
        foreach ($carts as $cart) {
            // Add tags for all carts regardless items or cart flag.
            $cart_cache_tags = Cache::mergeTags($cart_cache_tags, $cart->getCacheTags());
        }
        return Cache::mergeTags($cache_tags, $cart_cache_tags);
    }
}
