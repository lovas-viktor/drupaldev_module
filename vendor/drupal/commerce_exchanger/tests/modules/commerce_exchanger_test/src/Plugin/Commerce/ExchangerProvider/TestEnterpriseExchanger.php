<?php

namespace Drupal\commerce_exchanger_test\Plugin\Commerce\ExchangerProvider;

use Drupal\commerce_exchanger\Plugin\Commerce\ExchangerProvider\ExchangerProviderRemoteBase;

/**
 * Provides the enterprise exchange rates.
 *
 * @CommerceExchangerProvider(
 *   id = "enterprise",
 *   label = "Enterprise",
 *   display_label = "Enterprise",
 *   enterprise = TRUE,
 *   api_key= TRUE,
 * )
 */
class TestEnterpriseExchanger extends ExchangerProviderRemoteBase {

  /**
   * {@inheritdoc}
   */
  public function apiUrl() {
    return 'http://example.enterprise';
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteData($base_currency = NULL) {
    $data = [
      'EUR' => [
        'AUD' => 1.659999,
        'USD' => 1.190000,
      ],
      'USD' => [
        'AUD' => 1.394957,
        'EUR' => 0.840336,
      ],
      'AUD' => [
        'EUR' => 0.602409,
        'USD' => 0.716867,
      ],
    ];

    return $data[$base_currency];
  }

}
