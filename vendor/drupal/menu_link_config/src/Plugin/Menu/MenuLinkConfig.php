<?php

namespace Drupal\menu_link_config\Plugin\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a menu link plugin based upon storage in config.
 *
 * @todo Instead of extending MenuLinkContent, there should be a generic base
 *   class for the generic entity handling.
 */
class MenuLinkConfig extends MenuLinkContent {

  /**
   * The config menu link entity connected to this plugin instance.
   *
   * @var \Drupal\menu_link_config\MenuLinkConfigInterface
   */
  protected $entity;

  /**
   * The config translation mapper manager.
   *
   * Used to provide the translation route in case Config Translation module is
   * installed.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $mapperManager;

  /**
   * Constructs a MenuLinkConfig.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $mapper_manager
   *   The config translation mapper manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, LanguageManagerInterface $language_manager, EntityRepositoryInterface $entity_repository, ConfigMapperManagerInterface $mapper_manager = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $language_manager, $entity_repository);

    $this->mapperManager = $mapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('entity.repository'),
      // Provide integration with Config Translation module if it is enabled.
      $container->get('plugin.manager.config_translation.mapper', ContainerInterface::NULL_ON_INVALID_REFERENCE)
    );
  }

  /**
   * Loads the entity associated with this menu link.
   *
   * @return \Drupal\menu_link_config\MenuLinkConfigInterface
   *   The menu link content entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the entity ID and UUID are both invalid or missing.
   */
  public function getEntity() {
    if (empty($this->entity)) {
      $entity = NULL;
      $storage = $this->entityTypeManager->getStorage('menu_link_config');
      if (!empty($this->pluginDefinition['metadata']['entity_id'])) {
        $entity_id = $this->pluginDefinition['metadata']['entity_id'];
        // Make sure the current ID is in the list, since each plugin empties
        // the list after calling loadMultple(). Note that the list may include
        // multiple IDs added earlier in each plugin's constructor.
        static::$entityIdsToLoad[$entity_id] = $entity_id;
        $entities = $storage->loadMultiple(array_values(static::$entityIdsToLoad));
        $entity = $entities[$entity_id] ?? NULL;
        static::$entityIdsToLoad = [];
      }
      if (!$entity) {
        // Fallback to the loading by the ID.
        $entity = $storage->load($this->getDerivativeId());
      }
      if (!$entity) {
        throw new PluginException(sprintf('Entity not found through the menu link plugin definition and could not fallback on ID %s', $this->getDerivativeId()));
      }
      // Clone the entity object to avoid tampering with the static cache.
      $this->entity = clone $entity;
      $this->entity = $this->entityRepository->getTranslationFromContext($this->entity);
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute() {
    return $this->getEntity()->toUrl('delete-form');
  }

  /**
   * {@inheritdoc}
   *
   * @todo This could be moved upstream, as it is generic.
   */
  public function getEditRoute() {
    return $this->getEntity()->toUrl('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslateRoute() {
    // @todo There should be some way for Config Translation module to alter
    //   this information in on its own.
    if ($this->mapperManager) {
      $entity_type = 'menu_link_config';
      /** @var \Drupal\menu_link_config\MenuLinkConfigMapper $mapper */
      $mapper = $this->mapperManager->createInstance($entity_type);
      $mapper->setEntity($this->getEntity());
      return [
        'route_name' => $mapper->getOverviewRouteName(),
        'route_parameters' => $mapper->getOverviewRouteParameters(),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getUuid() {
    return $this->getEntity()->uuid();
  }

  /**
   * {@inheritdoc}
   *
   * @todo Simply storing the entity type ID in a variable would alleviate the
   *   need to override this entire method.
   */
  public function updateLink(array $new_definition_values, $persist) {
    // Filter the list of updates to only those that are allowed.
    $overrides = array_intersect_key($new_definition_values, $this->overrideAllowed);
    // Update the definition.
    $this->pluginDefinition = $overrides + $this->getPluginDefinition();
    if ($persist) {
      $entity = $this->getEntity();
      foreach ($overrides as $key => $value) {
        $entity->{$key} = $value;
      }
      $this->entityTypeManager->getStorage('menu_link_config')->save($entity);
    }

    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    // @todo Injecting the module handler for a proper moduleExists() check
    //   might be a bit cleaner.
    return (bool) $this->mapperManager;
  }

}
