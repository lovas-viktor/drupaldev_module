<?php

namespace Drupal\menu_link_config;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Menu link config entity.
 */
interface MenuLinkConfigInterface extends ConfigEntityInterface {

  /**
   *
   */
  public function getPluginDefinition();

  /**
   * Returns whether the menu link is enabled.
   *
   * @return bool
   *   TRUE if the link is enabled.
   */
  public function isEnabled();

}
