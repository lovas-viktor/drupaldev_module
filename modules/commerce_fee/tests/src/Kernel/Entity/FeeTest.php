<?php

namespace Drupal\Tests\commerce_fee\Kernel\Entity;

use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_fee\Entity\Fee;
use Drupal\commerce_fee\Plugin\Commerce\Fee\OrderItemPercentage;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the Fee entity.
 *
 * @coversDefaultClass \Drupal\commerce_fee\Entity\Fee
 *
 * @group commerce
 */
class FeeTest extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'commerce_fee',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_fee');
    $this->installConfig([
      'profile',
      'commerce_fee',
    ]);
  }

  /**
   * @covers ::getName
   * @covers ::setName
   * @covers ::getDisplayName
   * @covers ::setDisplayName
   * @covers ::getDescription
   * @covers ::setDescription
   * @covers ::getOrderTypes
   * @covers ::setOrderTypes
   * @covers ::getOrderTypeIds
   * @covers ::setOrderTypeIds
   * @covers ::getStores
   * @covers ::setStores
   * @covers ::setStoreIds
   * @covers ::getStoreIds
   * @covers ::getPlugin
   * @covers ::setPlugin
   * @covers ::getConditionOperator
   * @covers ::setConditionOperator
   * @covers ::getStartDate
   * @covers ::setStartDate
   * @covers ::getEndDate
   * @covers ::setEndDate
   * @covers ::isEnabled
   * @covers ::setEnabled
   */
  public function testFee() {
    $order_type = OrderType::load('default');
    $fee = Fee::create([
      'status' => FALSE,
    ]);

    $fee->setName('My Fee');
    $this->assertEquals('My Fee', $fee->getName());

    $fee->setDisplayName('50%');
    $this->assertEquals('50%', $fee->getDisplayName());

    $fee->setDescription('My Fee Description');
    $this->assertEquals('My Fee Description', $fee->getDescription());

    $fee->setOrderTypes([$order_type]);
    $order_types = $fee->getOrderTypes();
    $this->assertEquals($order_type->id(), $order_types[0]->id());

    $fee->setOrderTypeIds([$order_type->id()]);
    $this->assertEquals([$order_type->id()], $fee->getOrderTypeIds());

    $fee->setStores([$this->store]);
    $this->assertEquals([$this->store], $fee->getStores());

    $fee->setStoreIds([$this->store->id()]);
    $this->assertEquals([$this->store->id()], $fee->getStoreIds());

    $rounder = $this->prophesize(RounderInterface::class)->reveal();
    $plugin = new OrderItemPercentage(['percentage' => '0.5'], 'order_percentage', [], $rounder);
    $fee->setPlugin($plugin);
    $this->assertEquals($plugin->getPluginId(), $fee->getPlugin()->getPluginId());
    $this->assertEquals($plugin->getConfiguration(), $fee->getPlugin()->getConfiguration());

    $this->assertEquals('AND', $fee->getConditionOperator());
    $fee->setConditionOperator('OR');
    $this->assertEquals('OR', $fee->getConditionOperator());

    $fee->save();
    $fee = $this->reloadEntity($fee);
    $this->assertEquals($fee->id(), 1);

    $fee->setStartDate(new DrupalDateTime('2017-01-01'));
    $this->assertEquals('2017-01-01', $fee->getStartDate()->format('Y-m-d'));

    $fee->setEndDate(new DrupalDateTime('2017-01-31'));
    $this->assertEquals('2017-01-31', $fee->getEndDate()->format('Y-m-d'));

    $fee->setEnabled(TRUE);
    $this->assertEquals(TRUE, $fee->isEnabled());
  }

}
