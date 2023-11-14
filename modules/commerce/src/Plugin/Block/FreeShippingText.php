<?php

/**
 * @file
 * Contains \Drupal\drupaldev_commerce\Plugin\Block\FreeShippingText.
 */

namespace Drupal\drupaldev_commerce\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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
        'max-age' => 0,
      ],
	  );
  }
}
