<?php

/**
 * @file
 * Contains \Drupal\wif\Entity\WifPreset.
 */

namespace Drupal\wif\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\wif\WifPresetInterface;

/**
 * Defines the Wif Preset entity.
 *
 * @ConfigEntityType(
 *   id = "wif_preset",
 *   label = @Translation("Preset"),
 *   handlers = {
 *     "list_builder" = "Drupal\wif\Controller\WifPresetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\wif\Form\WifPresetForm",
 *       "edit" = "Drupal\wif\Form\WifPresetForm",
 *       "delete" = "Drupal\wif\Form\WifPresetDeleteForm",
 *     }
 *   },
 *   config_prefix = "wif_preset",
 *   admin_permission = "access wif admin pages",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/media/wif/preset/{wif_preset}",
 *     "delete-form" = "/admin/config/media/wif/preset/{wif_preset}/delete",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "image_style_zoomed",
 *     "image_style_normal",
 *     "image_style_thumb"
 *   }
 * )
 */
class WifPreset extends ConfigEntityBase implements WifPresetInterface {

  /**
   * The Wif Preset ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Wif Preset label.
   *
   * @var string
   */
  public $label;

  /**
   * The zoomed image style.
   *
   * @var string
   */
  public $image_style_zoomed;

  /**
   * The main image style.
   *
   * @var string
   */
  public $image_style_normal;

  /**
   * The thumbnail image style.
   *
   * @var string
   */
  public $image_style_thumb;

  // Your specific configuration property get/set methods go here,
  // implementing the interface.
}
