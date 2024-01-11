<?php

namespace Drupal\base_field_override_ui\Plugin\Derivative;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 */
class BaseFieldOverrideUiLocalTask extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an BaseFieldOverrideUiLocalTask object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route')) {
        $this->derivatives["overview_$entity_type_id"] = [
          'route_name' => "entity.$entity_type_id.field_ui_fields",
          'weight' => 1,
          'title' => $this->t('Fields'),
          'parent_id' => "field_ui.fields:overview_$entity_type_id",
        ];

        $this->derivatives["base_field_override_overview_$entity_type_id"] = [
          'route_name' => "entity.base_field_override.{$entity_type_id}.base_field_override_ui_fields",
          'weight' => 2,
          'title' => $this->t('Base fields Override'),
          'parent_id' => "field_ui.fields:overview_$entity_type_id",
        ];

        $this->derivatives["base_field_override_edit_$entity_type_id"] = [
          'route_name' => "entity.base_field_override.{$entity_type_id}_base_field_override_edit_form",
          'title' => $this->t('Edit'),
          'base_route' => "entity.base_field_override.{$entity_type_id}_base_field_override_edit_form",
        ];
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
