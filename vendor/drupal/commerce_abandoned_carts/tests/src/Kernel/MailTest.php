<?php

namespace Drupal\Tests\commerce_abandoned_carts\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests that customers that abandoned their carts receive a mail.
 *
 * @group commerce_abandoned_carts
 */
class MailTest extends CommerceKernelTestBase {

  use AssertMailTrait;

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_number_pattern',
    'commerce_order',
    'commerce_abandoned_carts',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system', 'language']);
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_number_pattern');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig([
      'commerce_number_pattern',
      'commerce_order',
      'commerce_abandoned_carts',
    ]);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    // Set site name and mail address.
    $this->config('system.site')
      ->set('name', 'Drupal')
      ->set('mail', 'sitetest@example.com')
      ->save();

    // Create billing profile.
    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'uid' => $user->id(),
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);

    // Create order.
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'billing_profile' => $profile,
      'store_id' => $this->store->id(),
      'order_items' => [],
    ]);
    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * Tests if the from setting is respected.
   *
   * @param string $expected_from
   *   The expected "From" header.
   * @param array $settings
   *   Commerce abandoned cart settings.
   *
   * @dataProvider providerFrom
   */
  public function testFrom($expected_from, array $settings) {
    $config = $this->config('commerce_abandoned_carts.settings');
    foreach ($settings as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    $this->markTestIncomplete('Needs to use mail handler');

    $params = [
      'order' => $this->order,
    ];
    $this->container->get('plugin.manager.mail')->mail('commerce_abandoned_carts', 'abandoned_cart', 'test@example.com', 'en', $params, NULL, TRUE);

    $emails = $this->getMails();
    $email = reset($emails);
    $this->assertEquals($expected_from, $email['headers']['From']);
  }

  /**
   * Data provider for ::testFrom().
   */
  public function providerFrom() {
    return [
      'default' => [
        'Default store <admin@example.com>',
        [],
      ],
      'from_email' => [
        'Default store <commerce_abandoned_carts@example.com>',
        ['from_email' => 'commerce_abandoned_carts@example.com'],
      ],
      'from_name' => [
        'Bar <admin@example.com>',
        ['from_name' => 'Bar'],
      ],
      'from_both' => [
        'Bar <commerce_abandoned_carts@example.com>',
        [
          'from_email' => 'commerce_abandoned_carts@example.com',
          'from_name' => 'Bar',
        ],
      ],
    ];
  }

}
