<?php

namespace Drupal\commerce_exchanger_test\Plugin\Commerce\ExchangerProvider;

use Drupal\commerce_exchanger\Plugin\Commerce\ExchangerProvider\ExchangerProviderRemoteBase;

/**
 * Provides the test exchange rates.
 *
 * @CommerceExchangerProvider(
 *   id = "test",
 *   label = "Test",
 *   display_label = "Test",
 *   base_currency = "EUR",
 *   refresh_once = TRUE,
 *   transform_rates = TRUE,
 * )
 */
class TestExchanger extends ExchangerProviderRemoteBase {

  /**
   * {@inheritdoc}
   */
  public function apiUrl() {
    return 'http://example.test';
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteData($base_currency = NULL) {
    // Reverse values, used to test transform_rates.
    return [
      'USD' => 0.840336,
      'AUD' => 0.602409,
    ];
  }

}
