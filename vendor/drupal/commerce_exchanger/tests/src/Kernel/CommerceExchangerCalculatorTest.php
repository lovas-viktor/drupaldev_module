<?php

namespace Drupal\Tests\commerce_exchanger\Kernel;

use Drupal\commerce_exchanger\Entity\ExchangeRates;
use Drupal\commerce_exchanger\Exception\ExchangeRatesDataMismatchException;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the exchanger calculator.
 *
 * @coversDefaultClass \Drupal\commerce_exchanger\DefaultExchangerCalculator
 * @group commerce_exchanger
 */
class CommerceExchangerCalculatorTest extends CommerceKernelTestBase {

  /**
   * Price in EUR currency.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $priceEur;

  /**
   * Price in USD currency.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $priceUsd;

  /**
   * ExchangerRates entity.
   *
   * @var \Drupal\commerce_exchanger\Entity\ExchangeRatesInterface
   */
  protected $exchanger;

  /**
   * The exchanger manager.
   *
   * @var \Drupal\commerce_exchanger\ExchangerManagerInterface
   */
  protected $exchangerManager;

  /**
   * Configuration file name.
   *
   * @var string
   */
  protected $exchangerId;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_exchanger',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {
    parent::setUp();

    $this->installSchema('commerce_exchanger', ['commerce_exchanger_latest_rates']);

    // The parent has already imported USD.
    $currency_importer = $this->container->get('commerce_price.currency_importer');
    $currency_importer->import('EUR');

    $this->priceEur = new Price('100', 'EUR');
    $this->priceUsd = new Price('100', 'USD');

    $exchanger = ExchangeRates::create([
      'label' => 'ECB Rate',
      'id' => 'ecb_rates',
      'plugin' => 'ecb',
      'status' => TRUE,
    ]);
    $exchanger->save();

    $this->exchanger = $exchanger;
    $this->exchangerId = $exchanger->id();

    $this->exchangerManager = $this->container->get('commerce_exchanger.manager');

    $this->exchangerManager->setLatest($this->exchangerId, [
      'EUR' => [
        'USD' => [
          'value' => 1.19,
          'manual' => 0,
        ],
      ],
      'USD' => [
        'EUR' => [
          'value' => 0.84,
          'manual' => 0,
        ],
      ],
    ]);
  }

  /**
   * @covers ::getExchangerId
   */
  public function testExchangerId() {
    $this->assertEquals($this->exchangerId, $this->container->get('commerce_exchanger.calculate')->getExchangerId());
  }

  /**
   * @covers ::getExchangerId
   */
  public function testExchangerIdEmpty() {
    $this->exchanger->setStatus(FALSE);
    $this->exchanger->save();
    $this->assertEmpty($this->container->get('commerce_exchanger.calculate')->getExchangerId());

    $this->expectException(ExchangeRatesDataMismatchException::class);
    $this->assertEmpty($this->container->get('commerce_exchanger.calculate')->getExchangeRates());

    $this->expectException(ExchangeRatesDataMismatchException::class);
    $this->container->get('commerce_exchanger.calculate')->priceConversion($this->priceUsd, 'EUR');

    $this->expectException(ExchangeRatesDataMismatchException::class);
    $this->container->get('commerce_exchanger.calculate')->priceConversion($this->priceEur, 'USD');
  }

  /**
   * @covers ::getExchangeRates
   */
  public function testExchangeRates() {
    $this->assertArrayHasKey('EUR', $this->container->get('commerce_exchanger.calculate')->getExchangeRates());
    $this->assertArrayHasKey('USD', $this->container->get('commerce_exchanger.calculate')->getExchangeRates());
  }

  /**
   * @covers ::getExchangeRates
   */
  public function testExchangeRatesEmpty() {
    $this->exchanger->setStatus(FALSE);
    $this->exchanger->save();
    $this->expectException(ExchangeRatesDataMismatchException::class);
    $this->container->get('commerce_exchanger.calculate')->getExchangeRates();
  }

  /**
   * @covers ::priceConversion
   */
  public function testPriceConversion() {
    $priceEur = $this->container->get('commerce_exchanger.calculate')->priceConversion($this->priceEur, 'USD');
    $this->assertEquals(100 * 1.19, $priceEur->getNumber());

    $priceUsd = $this->container->get('commerce_exchanger.calculate')->priceConversion($this->priceUsd, 'EUR');
    $this->assertEquals(100 * 0.84, $priceUsd->getNumber());

    $price_equal = $this->container->get('commerce_exchanger.calculate')->priceConversion($this->priceUsd, 'USD');
    $this->assertEquals(100.00, $price_equal->getNumber());

    $this->exchangerManager->setLatest($this->exchangerId, [
      'EUR' => [
        'USD' => [
          'value' => 0,
          'manual' => 0,
        ],
      ],
      'USD' => [
        'EUR' => [
          'value' => '0',
          'manual' => 0,
        ],
      ],
    ]);

    // To avoid static cache on price calculator service.
    $this->container = $this->container->get('kernel')->rebuildContainer();

    $this->expectException(ExchangeRatesDataMismatchException::class);
    $this->container->get('commerce_exchanger.calculate')->priceConversion($this->priceUsd, 'EUR');

    $this->expectException(ExchangeRatesDataMismatchException::class);
    $this->container->get('commerce_exchanger.calculate')->priceConversion($this->priceEur, 'USD');

  }

  /**
   * @covers ::priceConversion
   */
  public function testPriceConversionEmpty() {
    $this->exchanger->setStatus(FALSE);
    $this->exchanger->save();
    $this->expectException(ExchangeRatesDataMismatchException::class);
    $this->container->get('commerce_exchanger.calculate')->priceConversion($this->priceEur, 'USD');
  }

}
