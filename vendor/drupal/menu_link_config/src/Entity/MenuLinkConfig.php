<?php

namespace Drupal\menu_link_config\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\menu_link_config\MenuLinkConfigInterface;

/**
 * Defines the menu link config entity.
 *
 * @ConfigEntityType(
 *   id = "menu_link_config",
 *   label = @Translation("Menu link config"),
 *   handlers = {
 *     "access" = "\Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "default" = "\Drupal\menu_link_config\Plugin\Menu\Form\MenuLinkConfigForm",
 *       "delete" = "\Drupal\Core\Entity\EntityDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer menu link config",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "status" = "status"
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/menu/link/{menu_link_config}/delete",
 *     "edit-form" = "/admin/structure/menu/link/{menu_link_plugin}/edit",
 *     "add-form" = "/admin/structure/menu/manage/{menu}/add_config_link"
 *   },
 *   config_export = {
 *     "id",
 *     "title",
 *     "route_name",
 *     "route_parameters",
 *     "options",
 *     "expanded",
 *     "menu_name",
 *     "enabled",
 *     "parent",
 *     "weight",
 *     "description"
 *   },
 * )
 */
class MenuLinkConfig extends ConfigEntityBase implements MenuLinkConfigInterface {

  public $id;
  public $title;
  public $url;
  public $route_name;
  public $route_parameters;
  public $options;
  public $expanded;
  public $menu_name;
  public $parent;
  public $weight;
  public $description;
  public $enabled;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return $this->route_name ?: '<front>';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    return $this->route_parameters ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlObject() {
    return new Url($this->getRouteName(), $this->getRouteParameters(), $this->getOptions());
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $plugin_definition = [];
    $plugin_definition['title'] = $this->getTitle();
    $plugin_definition['description'] = $this->getDescription();
    $plugin_definition['route_name'] = $this->getRouteName();
    $plugin_definition['route_parameters'] = $this->getRouteParameters();
    $plugin_definition['options'] = $this->getOptions();
    $plugin_definition['menu_name'] = $this->getMenuName();
    $plugin_definition['parent'] = $this->getParent();
    $plugin_definition['enabled'] = $this->isEnabled() ? 1 : 0;
    $plugin_definition['expanded'] = $this->isExpanded() ? 1 : 0;
    $plugin_definition['weight'] = $this->getWeight();
    $plugin_definition['metadata']['entity_id'] = $this->id();
    $plugin_definition['class'] = 'Drupal\menu_link_config\Plugin\Menu\MenuLinkConfig';
    $plugin_definition['form_class'] = 'Drupal\menu_link_config\Plugin\Menu\Form\MenuLinkConfigForm';

    return $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function isExpanded() {
    return $this->expanded;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuName() {
    return $this->menu_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'menu_link_config:' . $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

    // The menu link can just be updated if there is already a menu link entry
    // on both entity and menu link plugin level.
    $definition = $this->getPluginDefinition();
    // Even when $update is FALSE, for top level links it is possible the link
    // already is in the storage because of the getPluginDefinition() call
    // above, see https://www.drupal.org/node/2605684#comment-10515450 for the
    // call chain. Because of this the $update flag is ignored and only the
    // existence of the definition (equals to being in the tree storage) is
    // checked.
    if ($menu_link_manager->getDefinition($this->getPluginId(), FALSE)) {
      $menu_link_manager->updateDefinition($this->getPluginId(), $definition, FALSE);
    }
    else {
      $menu_link_manager->addDefinition($this->getPluginId(), $definition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

    /** @var \Drupal\menu_link_config\Entity\MenuLinkConfig $menu_link */
    foreach ($entities as $menu_link) {
      /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link */
      $menu_link_manager->removeDefinition($menu_link->getPluginId(), FALSE);

      // Children get re-attached to the menu link's parent.
      $parent_plugin_id = $menu_link->getParent();
      $children = $storage->loadByProperties(['parent' => $menu_link->getPluginId()]);
      foreach ($children as $child) {
        /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $child */
        $child->set('parent', $parent_plugin_id)->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    return [
      'menu_link_plugin' => 'menu_link_config:' . $this->id(),
      'menu_link_config' => $this->id(),
    ];
  }

  /**
   * Processes entity route parameters for a given menu link.
   *
   * @param \Drupal\menu_link_config\MenuLinkConfigInterface|\Drupal\Core\Menu\MenuLinkInterface $menu_link
   *   The menu link to process. This is being passed in to support
   *   static::postLoad()
   * @param callable $processor
   *   An entity route parameter processor that gets the entity type ID and the
   *   current route parameter value as arguments and can return the processed
   *   route parameter value or NULL if it does not want to alter the value.
   */
  public static function processEntityRouteParameters($menu_link, $processor) {
    /** @var \Symfony\Component\Routing\Route $route */
    $route = \Drupal::service('router.route_provider')->getRouteByName($menu_link->getRouteName());
    $route_parameters = $menu_link->getRouteParameters();
    $changed = FALSE;
    foreach ($route_parameters as $name => $value) {
      $parameter_info = $route->getOption('parameters');
      // Ignore route parameters that are not entity IDs.
      if (isset($parameter_info[$name]['type']) && (strpos($parameter_info[$name]['type'], 'entity:') === 0)) {
        $entity_type_id = substr($parameter_info[$name]['type'], 7);
        $new_value = $processor($entity_type_id, $value);
        if (isset($new_value)) {
          $route_parameters[$name] = $new_value;
          $changed = TRUE;
        }
      }
    }

    if ($changed) {
      $menu_link->set('route_parameters', $route_parameters);
    }
  }

}
