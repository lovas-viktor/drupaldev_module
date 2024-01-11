<?php

namespace Drupal\commerce_exchanger;

/**
 * Provides the Commerce exchange rates provider plugin manager.
 */
interface ExchangerManagerInterface {

  public const EXCHANGER_LATEST_RATES = 'commerce_exchanger_latest_rates';

  public const EXCHANGER_HISTORICAL_RATES = 'commerce_exchanger_historical_rates';

  public const EXCHANGER_RATES_CACHE_TAG = 'commerce_exchanger_latest';

  /**
   * Fetch latest rates per exchanger id.
   *
   * @param string $exchanger_id
   *   The exchanger entity id.
   *
   * @return array
   *   Formatted response.
   */
  public function getLatest(string $exchanger_id): array;

  /**
   * Set latest rates per exchanger id.
   *
   * @param string $exchanger_id
   *   The exchanger entity id.
   * @param array $rates
   *   The rates array.
   */
  public function setLatest(string $exchanger_id, array $rates): void;

  /**
   * Fetch historical rates per exchanger id and date.
   *
   * @param string $exchanger_id
   *   The exchanger entity id.
   * @param string|null $date
   *   The date.
   *
   * @return array
   *   Formatted response.
   */
  public function getHistorical(string $exchanger_id, string $date = NULL): array;

  /**
   * Set historical rates per exchanger id.
   *
   * @param string $exchanger_id
   *   The exchanger entity id.
   * @param array $rates
   *   The rates array.
   * @param string|null $date
   *   The date.
   */
  public function setHistorical(string $exchanger_id, array $rates, string $date = NULL): void;

}
