<?php

namespace Drupal\commerce_exchanger\Plugin\Field\FieldFormatter;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_exchanger\ExchangerCalculatorInterface;
use Drupal\commerce_exchanger\ExchangerManagerInterface;
use Drupal\commerce_price\Plugin\Field\FieldFormatter\PriceDefaultFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_price_exchanger' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_price_exchanger",
 *   label = @Translation("Currency converter price"),
 *   field_types = {
 *     "commerce_price"
 *   }
 * )
 */
class PriceExchangerFormatter extends PriceDefaultFormatter {

  /**
   * The price exchanger.
   *
   * @var \Drupal\commerce_exchanger\ExchangerCalculatorInterface
   */
  protected $priceExchanger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PriceConvertedFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   * @param \Drupal\commerce_exchanger\ExchangerCalculatorInterface $price_exchanger
   *   The price exchanger.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CurrencyFormatterInterface $currency_formatter, ExchangerCalculatorInterface $price_exchanger, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $currency_formatter);
    $this->priceExchanger = $price_exchanger;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('commerce_price.currency_formatter'),
      $container->get('commerce_exchanger.calculate'),
      $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['target_currency' => 'USD'] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $currency_storage = $this->entityTypeManager->getStorage('commerce_currency');
    // Get all active currencies.
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface[] $active_currencies */
    $active_currencies = $currency_storage->loadByProperties(['status' => 1]);
    $options = [];
    foreach ($active_currencies as $active_currency) {
      $options[$active_currency->getCurrencyCode()] = $active_currency->getName();
    }

    $elements['target_currency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Target currency'),
      '#options' => $options,
      '#default_value' => $this->getSetting('target_currency'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $target_currency = $this->getSetting('target_currency');
    $summary[] = $this->t('Target currency: @target_currency.', ['@target_currency' => $target_currency]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $options = $this->getFormattingOptions();
    $elements = [];
    foreach ($items as $delta => $item) {
      $price = $item->toPrice();
      $converted_price = $this->priceExchanger->priceConversion($price, $this->getSetting('target_currency'));
      $elements[$delta] = [
        '#markup' => $this->currencyFormatter->format($converted_price->getNumber(), $converted_price->getCurrencyCode(), $options),
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
            'country',
          ],
          'tags' => [
            ExchangerManagerInterface::EXCHANGER_RATES_CACHE_TAG,
          ],
        ],
      ];
    }
    return $elements;
  }

}
