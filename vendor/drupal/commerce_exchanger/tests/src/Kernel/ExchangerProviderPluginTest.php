<?php

namespace Drupal\Tests\commerce_exchanger\Kernel;

use Drupal\commerce_exchanger\Entity\ExchangeRates;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_exchanger\Plugin\Commerce\ExchangerProvider\ExchangerProviderRemoteBase
 * @group commerce_exchanger
 */
class ExchangerProviderPluginTest extends CommerceKernelTestBase {

  /**
   * Exchange rate plugin.
   *
   * @var \Drupal\commerce_exchanger_test\Plugin\Commerce\ExchangerProvider\TestExchanger
   */
  protected $test;

  /**
   * Exchange rate plugin.
   *
   * @var \Drupal\commerce_exchanger_test\Plugin\Commerce\ExchangerProvider\TestEnterpriseExchanger
   */
  protected $enterprise;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_exchanger',
    'commerce_exchanger_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {
    parent::setUp();

    $this->installSchema('commerce_exchanger', ['commerce_exchanger_latest_rates']);

    // The parent has already imported USD.
    $currency_importer = $this->container->get('commerce_price.currency_importer');
    $currency_importer->import('AUD');
    $currency_importer->import('EUR');

    $exchange_entity = ExchangeRates::create([
      'id' => 'test',
      'label' => 'Test',
      'plugin' => 'test',
    ]);
    $exchange_entity->save();

    $this->test = $exchange_entity->getPlugin();

    $fixer_entity = ExchangeRates::create([
      'id' => 'enterprise',
      'label' => 'Enterprise',
      'plugin' => 'enterprise',
      'configuration' => [
        'enterprise' => TRUE,
      ],
    ]);
    $fixer_entity->save();

    $this->enterprise = $fixer_entity->getPlugin();
  }

  /**
   * Test generic settings for plugins.
   *
   * ::@covers ::useCrossSync
   * ::@covers ::isEnterprise.
   */
  public function testPluginSettings() {
    $this->assertTrue($this->test->useCrossSync());
    $this->assertFalse($this->test->isEnterprise());
    $this->assertEquals('EUR', $this->test->getBaseCurrency());
    $this->assertFalse($this->enterprise->useCrossSync());
    $this->assertTrue($this->enterprise->isEnterprise());
    $this->assertEmpty($this->enterprise->getBaseCurrency());
  }

  /**
   * Test cross sync import.
   *
   * ::@covers ::import
   * ::@covers ::buildExchangeRates
   * ::@covers ::importCrossSync
   * ::@covers ::processRemoteData
   * ::@covers ::crossSyncCalculate
   * ::@covers ::mapExchangeRates.
   */
  public function testImportCrossSyncTransformRates() {
    $this->test->import();
    $rates = $this->container->get('commerce_exchanger.calculate')->getExchangeRates();
    $this->assertEquals(round(1 / 0.602409, 2), round($rates['EUR']['AUD']['value'], 2));
    $this->assertEquals(round(1 / 0.840336, 2), round($rates['EUR']['USD']['value'], 2));
    $this->assertEquals(0.602409, $rates['AUD']['EUR']['value']);
    $this->assertEquals(0.840336, $rates['USD']['EUR']['value']);
  }

  /**
   * Test enterprise import.
   *
   * ::@covers ::import
   * ::@covers ::buildExchangeRates
   * ::@covers ::mapExchangeRates
   * ::@covers ::processRemoteData
   * ::@covers ::importEnterprise.
   */
  public function testImportEnterprise() {
    // We need to disable test which is loaded first in default calculator.
    $test = ExchangeRates::load('test');
    $test->set('status', 0)->save();

    // Import rates.
    $this->enterprise->import();
    $rates = $this->container->get('commerce_exchanger.calculate')->getExchangeRates();

    $this->assertEquals(1.19, $rates['EUR']['USD']['value']);
    $this->assertEquals(0.840336, $rates['USD']['EUR']['value']);
  }

  /**
   * Test transform mode with cross sync options vs. direct enterprise values.
   *
   * ::@covers ::import
   * ::@covers ::buildExchangeRates
   * ::@covers ::importCrossSync
   * ::@covers ::mapExchangeRates
   * ::@covers ::processRemoteData
   * ::@covers ::importEnterprise
   * ::@covers \Drupal\commerce_exchanger\ExchangerProviderRates.
   */
  public function testCompareTransform() {
    $this->test->import();
    $transform_cross_sync_rates = $this->container->get('commerce_exchanger.manager')->getLatest('test');

    // Import rates.
    $this->enterprise->import();
    $enterprise_rates = $this->container->get('commerce_exchanger.manager')->getLatest('enterprise');

    // Confirm that all cross sync values with transform_mode are calculated
    // properly from plugin "test" with values from enterprise plugin.
    foreach ($transform_cross_sync_rates as $base_currency => $values) {
      foreach ($values as $target_currency => $value) {
        // Round values to two digit for test purposes.
        // Cross sync at 6 digit is going to have
        // minor difference from directly inserted values in enterprise mode.
        $this->assertEquals(round($value['value'], 2), round($enterprise_rates[$base_currency][$target_currency]['value'], 2));
      }
    }
  }

}
