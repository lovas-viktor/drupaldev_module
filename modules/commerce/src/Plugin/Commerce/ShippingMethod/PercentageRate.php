<?php

namespace Drupal\drupaldev_commerce\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\Core\Form\FormStateInterface;
use Drupal\state_machine\WorkflowManagerInterface;

/**
 * Provides the PercentageRate shipping method.
 *
 * @CommerceShippingMethod(
 *   id = "percentage_rate",
 *   label = @Translation("Percentage rate"),
 * )
 */
class PercentageRate extends ShippingMethodBase {

  /**
   * Constructs a new FlatRate object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $package_type_manager
   *   The package type manager.
   * @param \Drupal\state_machine\WorkflowManagerInterface $workflow_manager
   *   The workflow manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $package_type_manager, WorkflowManagerInterface $workflow_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $package_type_manager, $workflow_manager);

    $this->services['default'] = new ShippingService('default', $this->configuration['rate_label']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'rate_label' => '',
        'rate_description' => '',
        'base_amount' => NULL,
        'rate_amount' => NULL,
        'services' => ['default'],
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $amount = $this->configuration['rate_amount'];
    $base_amount = $this->configuration['base_amount'];

    $form['rate_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rate label'),
      '#description' => $this->t('Shown to customers when selecting the rate.'),
      '#default_value' => $this->configuration['rate_label'],
      '#required' => TRUE,
    ];
    $form['rate_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rate description'),
      '#description' => $this->t('Provides additional details about the rate to the customer.'),
      '#default_value' => $this->configuration['rate_description'],
    ];
    $form['base_amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Base amount'),
      '#default_value' => $base_amount,
      '#required' => TRUE
    ];
    $form['rate_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate amount'),
      '#default_value' => $amount,
      '#required' => TRUE,
      '#field_suffix' => '%',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['rate_label'] = $values['rate_label'];
      $this->configuration['rate_description'] = $values['rate_description'];
      $this->configuration['rate_amount'] = $values['rate_amount'];
      $this->configuration['base_amount'] = $values['base_amount'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    $rates = [];
    $percentage = $this->configuration['rate_amount'] / 100;

    $order_total = [
      'currency' => '',
      'number' => 0,
    ];

    foreach ($shipment->getItems() as $shipmentItem) {
      $declared_value = $shipmentItem->getDeclaredValue();
      $order_total['currency_code'] = $declared_value->getCurrencyCode();
      $order_total['number'] += (float) $declared_value->getNumber();
    }

    $order_total['number'] = $this->configuration['base_amount']['number'] + $order_total['number'] + ($order_total['number'] * $percentage);

    $rates[] = new ShippingRate([
      'shipping_method_id' => $this->parentEntity->id(),
      'service' => $this->services['default'],
      'amount' => Price::fromArray($order_total),
      'description' => $this->configuration['rate_description'],
    ]);

    return $rates;
  }

}
