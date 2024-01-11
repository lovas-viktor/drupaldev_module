<?php

namespace Drupal\commerce_exchanger\Form;

use Drupal\commerce_exchanger\ExchangerManagerInterface;
use Drupal\commerce_exchanger\ExchangerProviderManager;
use Drupal\commerce_price\Entity\CurrencyInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExchangeRateForm.
 */
class ExchangeRatesForm extends EntityForm {

  /**
   * The exchange rates plugin manager.
   *
   * @var \Drupal\commerce_exchanger\ExchangerProviderManager
   */
  protected $pluginManager;

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The exchanger manager.
   *
   * @var \Drupal\commerce_exchanger\ExchangerManagerInterface
   */
  protected $exchangerManager;

  /**
   * Constructs a new ExchangeRatesForm object.
   *
   * @param \Drupal\commerce_exchanger\ExchangerProviderManager $plugin_manager
   *   The exchange rates plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Currency storage.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Configuration managment.
   * @param \Drupal\commerce_exchanger\ExchangerManagerInterface $exchanger_manager
   *   The exchange manager.
   */
  public function __construct(ExchangerProviderManager $plugin_manager, EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config_factory, ExchangerManagerInterface $exchanger_manager) {
    $this->pluginManager = $plugin_manager;
    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
    $this->configFactory = $config_factory;
    $this->exchangerManager = $exchanger_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_exchanger_provider'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('commerce_exchanger.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($this->pluginManager->getDefinitions())) {
      $form['warning'] = [
        '#markup' => $this->t('No  exchange rates plugins found. Please install a module which provides one.'),
      ];
      return $form;
    }

    // Load currencies.
    $currencies = $this->currencyStorage->loadMultiple();

    // If there is now two currencies enabled, do not allow saving.
    if (count($currencies) < 2) {
      $form['warning'] = [
        '#markup' => $this->t('Minimum of two currencies needs to be enabled, to be able to add exchange rates'),
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $exchanger = $this->entity;

    $plugin_definition = $this->pluginManager->getDefinitions();

    $plugins = array_column($plugin_definition, 'label', 'id');
    asort($plugins);

    // Use the first available plugin as the default value.
    if (!$exchanger->getPluginId()) {
      $plugin_ids = array_keys($plugins);
      $plugin = reset($plugin_ids);
      $exchanger->setPluginId($plugin);
    }

    // The form state will have a plugin value if #ajax was used.
    $plugin = $form_state->getValue('plugin', $exchanger->getPluginId());
    // Pass the plugin configuration only if the plugin
    // hasn't been changed via #ajax.
    $plugin_configuration = $exchanger->getPluginId() === $plugin ? $exchanger->getPluginConfiguration() : [];

    $wrapper_id = Html::getUniqueId('commerce-exchange-rate-form');
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';
    $form['#tree'] = TRUE;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $exchanger->label(),
      '#description' => $this->t('Label for the Exchange rates.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $exchanger->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_exchanger\Entity\ExchangeRates::load',
      ],
      '#disabled' => !$exchanger->isNew(),
    ];

    $form['plugin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      '#default_value' => $plugin,
      '#required' => TRUE,
      '#disabled' => !$exchanger->isNew(),
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['configuration'] = [
      '#type' => 'commerce_plugin_configuration',
      '#plugin_type' => 'commerce_exchanger_provider',
      '#plugin_id' => $plugin,
      '#default_value' => $plugin_configuration,
    ];

    $data = $this->exchangerManager->getLatest($exchanger->id());

    // Load currencies.
    $currencies = $this->currencyStorage->loadMultiple();

    $form['exchange_rates'] = [
      '#type' => 'details',
      '#title' => $this->t('Currency exchange rates'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    // See if plugin is manual. We dont need then flag for manual syncing.
    $manual_plugin = !empty($plugin_definition[$exchanger->getPluginId()]['manual']);

    $demo_amount = $plugin_configuration['demo_amount'] ?? 100;

    foreach ($currencies as $key => $currency) {
      assert($currency instanceof CurrencyInterface);

      $form['exchange_rates'][$key] = [
        '#type' => 'details',
        '#title' => $currency->label(),
        '#open' => FALSE,
      ];

      foreach ($currencies as $subkey => $subcurrency) {
        if ($key !== $subkey) {

          $default_rate = $data[$key][$subkey]['value'] ?? '0';
          $default_sync = $data[$key][$subkey]['manual'] ?? 0;

          // Specific change for manual plugin.
          if ($manual_plugin) {
            $default_sync = 1;
          }

          $form['exchange_rates'][$key][$subkey]['value'] = [
            '#type' => 'textfield',
            '#title' => $subkey,
            '#size' => 20,
            '#default_value' => $default_rate,
            '#disabled' => !$default_sync,
            '#field_suffix' => $this->t(
              '* @demo_amount @currency_symbol = @amount @conversion_currency_symbol',
              [
                '@demo_amount' => $demo_amount,
                '@currency_symbol' => $key,
                '@conversion_currency_symbol' => $subkey,
                '@amount' => ($demo_amount * $default_rate),
              ]
            ),
          ];

          // Based on cross sync value render form elements.
          if (isset($plugin_configuration['use_cross_sync']) && $plugin_configuration['use_cross_sync'] == 1) {
            $form['exchange_rates'][$key][$subkey]['value']['#description'] = $this->t('Exchange rate derived from @initial using cross sync.', [
              '@initial' => $plugin_configuration['base_currency'],
            ]);
          }
          else {
            $form['exchange_rates'][$key][$subkey]['value']['#description'] = $this->t('Exchange rate from @initial to @currency.', [
              '@initial' => $currency->getCurrencyCode(),
              '@currency' => $subcurrency->getCurrencyCode(),
            ]);
          }

          $form['exchange_rates'][$key][$subkey]['manual'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Manually enter an exchange rate'),
            '#default_value' => $default_sync,
            '#access' => !$manual_plugin,
          ];

        }
      }

    }

    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#options' => [
        0 => $this->t('Disabled'),
        1  => $this->t('Enabled'),
      ],
      '#default_value' => (int) $exchanger->status(),
    ];

    return $form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\commerce_exchanger\Entity\ExchangeRates $exchanger */
    $exchanger = $this->entity;
    $configuration = $form_state->getValue(['configuration']);
    $exchange_rates = $form_state->getValue(['exchange_rates']);

    // Set provider plugin configuration.
    $exchanger->setPluginConfiguration($configuration);
    // Set exchange rates and settings.
    $this->exchangerManager->setLatest($exchanger->id(), $exchange_rates);

    if (!empty($configuration['historical_rates'])) {
      $this->exchangerManager->setHistorical($exchanger->id(), $exchange_rates);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_exchanger\Entity\ExchangeRates $exchange_rates */
    $this->entity->save();

    $this->messenger()->addMessage($this->t('Saved the %label exchange rates.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_exchange_rates.collection');
  }

}
