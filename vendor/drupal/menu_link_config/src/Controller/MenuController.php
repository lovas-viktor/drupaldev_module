<?php

namespace Drupal\menu_link_config\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\system\MenuInterface;

/**
 * Controller for menu link config entity form.
 */
class MenuController extends ControllerBase {

  /**
   * Provides the config menu link creation form.
   *
   * @param \Drupal\system\MenuInterface $menu
   *   An entity representing a custom menu.
   *
   * @return array
   *   Returns the menu link creation form.
   */
  public function addLink(MenuInterface $menu) {
    $menu_link = $this->entityTypeManager()->getStorage('menu_link_config')->create([
      'id' => '',
      'parent' => '',
      'menu_name' => $menu->id(),
      'bundle' => 'menu_link_config',
    ]);
    return $this->entityFormBuilder()->getForm($menu_link);
  }

  /**
   * Check if a menu link config entity exists.
   *
   * @param $id
   *   The menu link ID.
   *
   * @return bool
   *   TRUE if a menu link config entity with this ID exists.
   */
  public static function menuLinkExists($id) {
    return (bool) \Drupal::entityTypeManager()->getStorage('menu_link_config')->load($id);
  }

}
