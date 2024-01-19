<?php

namespace Drupal\drupaldev_search\Plugin\search_api\processor;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\drupaldev_search\Plugin\search_api\processor\Property\RelatedRenderedItemProperty;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the related products to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "related_products",
 *   label = @Translation("Related products"),
 *   description = @Translation("Adds the related products to the indexed
 *   data."),
 *   stages = {
 *     "add_properties" = 0,
 *   }
 * )
 */
class RelatedProducts extends ProcessorPluginBase {

  use LoggerTrait;

  /**
   * The current_user service used by this plugin.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface|null
   */
  protected $accountSwitcher;

  /**
   * The renderer to use.
   *
   * @var \Drupal\Core\Render\RendererInterface|null
   */
  protected $renderer;

  /**
   * Theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Theme initialization service.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * Theme settings config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setAccountSwitcher($container->get('account_switcher'));
    $plugin->setRenderer($container->get('renderer'));
    $plugin->setLogger($container->get('logger.channel.search_api'));
    $plugin->setThemeManager($container->get('theme.manager'));
    $plugin->setThemeInitializer($container->get('theme.initialization'));
    $plugin->setConfigFactory($container->get('config.factory'));

    return $plugin;
  }

  /**
   * Retrieves the account switcher service.
   *
   * @return \Drupal\Core\Session\AccountSwitcherInterface
   *   The account switcher service.
   */
  public function getAccountSwitcher() {
    return $this->accountSwitcher ?: \Drupal::service('account_switcher');
  }

  /**
   * Sets the account switcher service.
   *
   * @param \Drupal\Core\Session\AccountSwitcherInterface $current_user
   *   The account switcher service.
   *
   * @return $this
   */
  public function setAccountSwitcher(AccountSwitcherInterface $current_user) {
    $this->accountSwitcher = $current_user;
    return $this;
  }

  /**
   * Retrieves the renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  public function getRenderer() {
    return $this->renderer ?: \Drupal::service('renderer');
  }

  /**
   * Sets the renderer.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The new renderer.
   *
   * @return $this
   */
  public function setRenderer(RendererInterface $renderer) {
    $this->renderer = $renderer;
    return $this;
  }

  /**
   * Retrieves the theme manager.
   *
   * @return \Drupal\Core\Theme\ThemeManagerInterface
   *   The theme manager.
   */
  protected function getThemeManager() {
    return $this->themeManager ?: \Drupal::theme();
  }

  /**
   * Sets the theme manager.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   *
   * @return $this
   */
  protected function setThemeManager(ThemeManagerInterface $theme_manager) {
    $this->themeManager = $theme_manager;
    return $this;
  }

  /**
   * Retrieves the theme initialization service.
   *
   * @return \Drupal\Core\Theme\ThemeInitializationInterface
   *   The theme initialization service.
   */
  protected function getThemeInitializer() {
    return $this->themeInitialization ?: \Drupal::service('theme.initialization');
  }

  /**
   * Sets the theme initialization service.
   *
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization service.
   *
   * @return $this
   */
  protected function setThemeInitializer(ThemeInitializationInterface $theme_initialization) {
    $this->themeInitialization = $theme_initialization;
    return $this;
  }

  /**
   * Retrieves the config factory service.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  protected function getConfigFactory() {
    return $this->configFactory ?: \Drupal::configFactory();
  }

  /**
   * Sets the config factory service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @return $this
   */
  protected function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource || !$datasource->getEntityTypeId()) {
      $definition = [
        'label' => $this->t('Related products'),
        'description' => $this->t('Related products'),
        'type' => 'search_api_html',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];
      $properties['related_products'] = new RelatedRenderedItemProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Switch to the default theme in case the admin theme (or any other theme)
    // is enabled.
    $active_theme = $this->getThemeManager()->getActiveTheme();
    $default_theme = $this->getConfigFactory()
      ->get('system.theme')
      ->get('default');
    $default_theme = $this->getThemeInitializer()
      ->getActiveThemeByName($default_theme);
    $active_theme_switched = FALSE;
    if ($default_theme->getName() !== $active_theme->getName()) {
      $this->getThemeManager()->setActiveTheme($default_theme);
      // Ensure that static cached default variables are set correctly,
      // especially the directory variable.
      drupal_static_reset('template_preprocess');
      $active_theme_switched = TRUE;
    }

    $product_variation = $item->getOriginalObject()->getEntity();
    $product = $product_variation->getProduct();

    if ($product_variation instanceof ProductVariation) {
      $entity_langcode = $product_variation->language()->getId();

      // Get catalog term.
      $catalog_values = $product->get('field_catalog');

      // Exit if field_catalog is empty.
      if (!empty($catalog_values->getValue())) {
        $term = $catalog_values->first()->get('target_id')->getValue();

        // Get existing related products.
        $existing_related_products = $product->get('field_related_products')
          ->getValue();

        $existing_related_product_variation_ids = [];

        // Create array which holds the target ids.
        $existing_related_product_ids = array_map(function ($item) {
          return $item['target_id'];
        }, $existing_related_products);

        // Add more items from the vocabulary if not enough added.
        if (count($existing_related_products) < 4) {
          $products = \Drupal::entityTypeManager()
            ->getStorage('commerce_product')
            ->loadByProperties(['field_catalog' => $term]);

          foreach ($products as $product) {
            if ($product->language()->getId() == $entity_langcode) {
              $product_variation = \Drupal::entityTypeManager()
                ->getStorage('commerce_product_variation')
                ->load((int) $product->getVariationIds()[0]);
              $existing_related_product_variation_ids[] = $product_variation->id();
            }
          }
        }
      } else {
        $existing_related_product_variation_ids = [];
      }

      // Filter out duplicates.
      $uniqe_product_variation_ids = array_unique($existing_related_product_variation_ids);

      if (!empty($uniqe_product_variation_ids)) {
        $fields = $item->getFields(FALSE);
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($fields, NULL, 'related_products');
        foreach ($fields as $field) {
          $configuration = $field->getConfiguration();
          // Limit to 4 items.
          $uniqe_product_variation_ids = array_slice($uniqe_product_variation_ids, 0, $configuration['item_number']);

          // If a (non-anonymous) role is selected, then also add the authenticated
          // user role.
          $roles = $configuration['roles'];
          $authenticated = RoleInterface::AUTHENTICATED_ID;
          if (array_diff($roles, [
            $authenticated,
            RoleInterface::ANONYMOUS_ID,
          ])) {
            $roles[$authenticated] = $authenticated;
          }

          // Change the current user to our dummy implementation to ensure we are
          // using the configured roles.
          $this->getAccountSwitcher()
            ->switchTo(new UserSession(['roles' => array_values($roles)]));

          foreach ($uniqe_product_variation_ids as $product_variation_id) {
            $storage = \Drupal::entityTypeManager()
              ->getStorage('commerce_product_variation');
            $product_variation = $storage->load($product_variation_id);
            $view_builder = \Drupal::entityTypeManager()
              ->getViewBuilder('commerce_product_variation');
            if ($product_variation instanceof ProductVariation) {
              $output = $view_builder->view($product_variation, $configuration['view_mode']['entity:commerce_product_variation']['default']);

              $full_output = \Drupal::service('renderer')->renderPlain($output);
              $field->addValue($full_output);
            }
          }
        }
      }
    }
  }
}
