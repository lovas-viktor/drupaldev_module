<?php

namespace Drupal\commerce_exchanger;

use Drupal\commerce_exchanger\Exception\ExchangeRatesDataMismatchException;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Base class for exchanger calculator.
 *
 * @package Drupal\commerce_exchanger
 */
abstract class AbstractExchangerCalculator implements ExchangerCalculatorInterface {

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * List of providers.
   *
   * @var \Drupal\commerce_exchanger\Entity\ExchangeRatesInterface[]
   */
  protected $providers;

  /**
   * The exchanger manager.
   *
   * @var \Drupal\commerce_exchanger\ExchangerManagerInterface
   */
  protected $exchangerManager;

  /**
   * Drupal commerce price rounder service.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * The static cache to avoid querying each time.
   *
   * @var array
   */
  protected array $rates = [];

  /**
   * DefaultExchangerCalculator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\commerce_exchanger\ExchangerManagerInterface $exchanger_manager
   *   Drupal exchanger manager.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   Drupal commerce price rounder service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ExchangerManagerInterface $exchanger_manager, RounderInterface $rounder) {
    $this->exchangerManager = $exchanger_manager;
    $this->providers = $entity_type_manager->getStorage('commerce_exchange_rates')->loadMultiple();
    $this->rounder = $rounder;
  }

  /**
   * {@inheritdoc}
   */
  public function priceConversion(Price $price, string $target_currency) {
    // Price currency.
    $price_currency = $price->getCurrencyCode();

    // If someone is trying to convert same currency.
    if ($price_currency == $target_currency) {
      return $price;
    }

    $exchange_rates = $this->getExchangeRates();

    // Determine rate.
    $rate = $exchange_rates[$price_currency][$target_currency]['value'] ?? 0;

    // Don't allow to multiply with zero or one.
    if (empty($rate)) {
      throw new ExchangeRatesDataMismatchException('There are no exchange rates set for ' . $price_currency . ' and ' . $target_currency);
    }

    // Convert amount to target currency.
    $price = $price->convert($target_currency, (string) $rate);
    return $this->rounder->round($price);
  }

  /**
   * {@inheritdoc}
   */
  public function getExchangeRates() {
    $exchanger_id = $this->getExchangerId();
    if (empty($exchanger_id)) {
      throw new ExchangeRatesDataMismatchException('Not any active Exchange rates present');
    }
    if (empty($this->rates[$exchanger_id])) {
      $this->rates[$exchanger_id] = $this->exchangerManager->getLatest($exchanger_id);
    }
    return $this->rates[$exchanger_id];
  }

}
