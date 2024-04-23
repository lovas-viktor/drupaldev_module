<?php

namespace Drupal\Tests\commerce_fee\Kernel;

use Drupal\commerce_fee\Entity\Fee;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the fee order processor.
 *
 * @group commerce
 */
class FeeOrderProcessorTest extends OrderKernelTestBase {

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

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
      'commerce_fee',
    ]);

    $this->user = $this->createUser();

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [],
    ]);
  }

  /**
   * Tests the order amount condition.
   */
  public function testOrderTotal() {
    // Use addOrderItem so the total is calculated.
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => [
        'number' => '20.00',
        'currency_code' => 'USD',
      ],
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();

    // Starts now, enabled. No end time.
    $fee = Fee::create([
      'name' => 'Fee 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'plugin' => [
        'target_plugin_id' => 'order_percentage',
        'target_plugin_configuration' => [
          'percentage' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'order_total_price',
          'target_plugin_configuration' => [
            'amount' => [
              'number' => '20.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
    ]);
    $fee->save();

    $this->assertTrue($fee->applies($this->order));
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order->recalculateTotalPrice();

    $this->assertEquals(1, count($this->order->collectAdjustments()));
    $this->assertEquals(new Price('44.00', 'USD'), $this->order->getTotalPrice());
  }

}
