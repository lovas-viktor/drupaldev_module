<?php

namespace Drupal\commerce_barion_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Barion off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "barion_payment",
 *   label = @Translation("Barion"),
 *   display_label = @Translation("Barion"),
 *   forms = {
 *     "offsite-payment" =
 *   "Drupal\commerce_barion_payment\PluginForm\BarionRedirectForm",
 *   }
 * )
 */
class BarionPaymentGateway extends OffsitePaymentGatewayBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'email' => '',
      'private_key' => '',
      'api_version' => '2',
      'payment_window' => '00:05:00',
      'locale' => \UILocale::EN,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Barion email address'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#required' => TRUE,
      '#default_value' => $this->configuration['email'],
    ];

    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key (POSKey)'),
      '#description' => $this->t('Secret key provided by the Barion service.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#required' => TRUE,
      '#default_value' => $this->configuration['private_key'],
    ];

    $form['api_version'] = [
      '#type' => 'number',
      '#title' => $this->t('API version number'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#required' => TRUE,
      '#default_value' => $this->configuration['api_version'],
    ];

    $payment_window_parts = explode(':', $this->configuration['payment_window']);
    $form['payment_window_label_item'] = [
      '#type' => 'item',
      '#title' => $this->t('Payment Window(HMS)'),
      'payment_window' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'container-inline',
          ],
        ],
        0 => [
          '#type' => 'textfield',
          '#default_value' => $payment_window_parts[0],
          '#size' => 2,
        ],
        1 => [
          '#type' => 'textfield',
          '#default_value' => $payment_window_parts[1],
          '#size' => 2,
        ],
        2 => [
          '#type' => 'textfield',
          '#default_value' => $payment_window_parts[2],
          '#size' => 2,
        ],
      ],
    ];

    $ui_locale = new \ReflectionClass(\UILocale::class);

    $form['locale'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Barion locale(language also)'),
      '#options' => array_flip($ui_locale->getConstants()),
      '#required' => TRUE,
      '#default_value' => $this->configuration['locale'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedModes() {
    $barion_environment = new \ReflectionClass(\BarionEnvironment::class);
    return array_flip($barion_environment->getConstants());
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $values = $form_state->getValue(array_merge($form['#parents'], [
      'payment_window_label_item',
      'payment_window',
    ]));
    foreach ($values as $key => $value) {
      if (!is_numeric($value)) {
        $form_state->setErrorByName(
          implode('][', array_merge($form['#parents'], [
            'payment_window_label_item',
            'payment_window',
            $key,
          ])),
          $this->t('Value must be number')
        );
      }
      if (strlen($value) !== 2) {
        $form_state->setErrorByName(
          implode('][', array_merge($form['#parents'], [
            'payment_window_label_item',
            'payment_window',
            $key,
          ])),
          $this->t('Value must have exactly 2 characters, add zero in front if number is lower than 10')
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['email'] = $values['email'];
    $this->configuration['private_key'] = $values['private_key'];
    $this->configuration['api_version'] = $values['api_version'];
    $this->configuration['payment_window'] = implode(':', $values['payment_window_label_item']['payment_window']);
    $this->configuration['locale'] = $values['locale'];
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $payment_id = $order->getData('barion_payment_id');
    $payment_state = $this->getPaymentState($payment_id);
    // TODO handle statuses.
    switch ($payment_state->Status) {
      case \PaymentStatus::Prepared:
        throw new PaymentGatewayException('Barion returned status prepared');

      case \PaymentStatus::Started:
        throw new PaymentGatewayException('Barion payment not yet finished');

      case \PaymentStatus::InProgress:
        throw new PaymentGatewayException('Barion returned status of in progress.');

      case \PaymentStatus::Succeeded:
      case \PaymentStatus::Reserved:
        $this->updatePaymentState($payment_id, $payment_state);
        break;

      case \PaymentStatus::Canceled:
        throw new PaymentGatewayException('Barion returned status of canceled.');

      case \PaymentStatus::Failed:
        throw new PaymentGatewayException('Barion returned status of failed.');
    }
    parent::onReturn($order, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    $payment_id = $request->query->get('paymentId', FALSE);
    $payment_state = $this->getPaymentState($payment_id);
    if ($payment_state) {
      $this->updatePaymentState($payment_id, $payment_state);
    }
    parent::onNotify($request);
  }

  /**
   * Validates the payment state on Barion.
   *
   * @param mixed $payment_id
   *   Payment ID.
   *
   * @return \PaymentStateResponseModel
   *   Payment state response.
   */
  public function getPaymentState($payment_id) {
    $barion_client = new \BarionClient($this->configuration['private_key'], $this->configuration['api_version'], $this->getMode());
    return $barion_client->GetPaymentState($payment_id);
  }

  /**
   * Prepare the payment.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order.
   * @param string $redirect_url
   *   Redirect URL.
   *
   * @return \PreparePaymentResponseModel
   *   Prepare payment response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Invalid plugin definition.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Plugin not found.
   */
  public function preparePayment(OrderInterface $order, $redirect_url) {
    $barion_client = new \BarionClient($this->configuration['private_key'],
      $this->configuration['api_version'],
      $this->getMode());

    $language = \Drupal::languageManager()->getCurrentLanguage();
    $local = \UILocale::EN;
    switch ($language->getId()) {
      case 'hu':
        $local = \UILocale::HU;
        $decimals = 0;
        break;
      case 'en':
        $local = \UILocale::EN;
        $decimals = 2;
        break;
    }

    $items = $order->getItems();

    // Prepare payment transaction model.
    $transaction = new \PaymentTransactionModel();
    $transaction->POSTransactionId = $order->id();
    $transaction->Payee = $this->configuration['email'];
    $total_paid_price = $order->getTotalPaid();
    $total_price = $order->getTotalPrice();
    if ($total_paid_price->greaterThan($total_price)) {
      throw new PaymentGatewayException('You have already completed payment, please contact administrator.');
    }

    $transaction->Total = number_format($total_price->subtract($total_paid_price)
      ->getNumber(), $decimals, '.', '');

    $variation_storage = $this->entityTypeManager
      ->getStorage('commerce_product_variation');

    // Add item models to transaction.
    foreach ($items as $order_item) {

      $item = new \ItemModel();
      $item->Name = $order_item->getTitle();
      $item->Description = $order_item->getTitle();
      $item->Quantity = $order_item->getQuantity();
      $item->Unit = "piece";

      /** @var \Drupal\commerce_product\Entity\ProductVariation $variation */
      $variation = $variation_storage->load($order_item->getPurchasedEntityId());
      if ($variation) {
        $item->SKU = $variation->getSku();
      }
      else {
        $item->SKU = "none";
      }

      $item->UnitPrice = $order_item->getUnitPrice()->getNumber();
      $item->ItemTotal = $order_item->getTotalPrice()->getNumber();
      $transaction->AddItem($item);
    }

    // Prepare payment model.
    $prepare_payment = new \PreparePaymentRequestModel();
    $prepare_payment->GuestCheckout = TRUE;
    $prepare_payment->PaymentType = \PaymentType::Immediate;
    $prepare_payment->FundingSources = [\FundingSourceType::All];
    $prepare_payment->PaymentRequestId = $order->id();
    $prepare_payment->PayerHint = $order->getEmail();

    $currency_code = $total_price->getCurrencyCode();
    $prepare_payment->Currency = $this->assertCurrency($currency_code);

    $prepare_payment->Locale = $local;

    $address = $order->get('billing_profile')->entity->get('address')
      ->getValue()[0]['address_line1'];

    $prepare_payment->OrderNumber = $order->getOrderNumber();
    $prepare_payment->ShippingAddress = $address;

    $prepare_payment->AddTransaction($transaction);
    $prepare_payment->PaymentWindow = $this->configuration['payment_window'];

    $prepare_payment->RedirectUrl = $redirect_url;
    $prepare_payment->CallbackUrl = $this->getNotifyUrl()->toString();

    $prepared_payment = $barion_client->PreparePayment($prepare_payment);

    return $prepared_payment;
  }

  /**
   * Asserts currency code.
   */
  public function assertCurrency($currency_code) {
    if (\Currency::isValid($currency_code)) {
      return $currency_code;
    }
    throw new PaymentGatewayException('Barion does not support currency of order');
  }

  /**
   * {@inheritdoc}
   */
  public function updatePaymentState($payment_id, $remote_state) {
    $payment_storage = $this->entityTypeManager
      ->getStorage('commerce_payment');
    $payment = $payment_storage->loadByProperties([
      'remote_id' => $payment_id,
      'payment_gateway' => $this->entityId,
    ]);
    if (count($payment) === 1) {
      /** @var \Drupal\commerce_payment\Entity\Payment $payment */
      $payment = reset($payment);
      $payment->setState($this->mapState($remote_state->Status));
      $payment->setRemoteState($remote_state->Status);
      $order = $payment->getOrder();
      if ($order->getData('barion_payment_id') === $payment_id) {
        $payment->save();
      }
      else {
        throw new PaymentGatewayException('Not allowed to update payment in case payment is not active payment on order.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment($order, $payment_state) {
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => $this->mapState($payment_state->Status),
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'remote_id' => $payment_state->PaymentId,
      'remote_state' => $payment_state->Status,
    ]);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function mapState($remote_state) {
    $state_map = [
      \PaymentStatus::Prepared => 'new',
      \PaymentStatus::Started => 'new',
      \PaymentStatus::InProgress => 'authorization',
      \PaymentStatus::Reserved => 'completed',
      \PaymentStatus::Canceled => 'authorization_expired',
      \PaymentStatus::Succeeded => 'completed',
      \PaymentStatus::Failed => 'authorization_voided',
    ];
    return $state_map[$remote_state];
  }

}
