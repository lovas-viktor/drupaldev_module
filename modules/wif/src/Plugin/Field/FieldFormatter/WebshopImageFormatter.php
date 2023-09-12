<?php

/**
 * @file
 * Contains \Drupal\wif\Plugin\Field\FieldFormatter\WebshopImageFormatter.
 */

namespace Drupal\wif\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'wif' formatter.
 *
 * @FieldFormatter(
 *   id = "wif",
 *   label = @Translation("Webshop Image Formatter"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class WebshopImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'selected_wif_preset' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $url = Url::fromRoute('entity.wif_preset.collection');
    $link_to_preset_collection_page = \Drupal\Core\Link::fromTextAndUrl(t('Webshop Image Formatter Presets admin page'), $url);

    $element['selected_wif_preset'] = array(
      '#title' => t('Preset'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('selected_wif_preset'),
      '#description' => $this->t("You can add, edit or delete presets on the @link.", array('@link' => $link_to_preset_collection_page)),
      '#options' => $this->getPresetOptions(),
      '#require' => TRUE,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $wif_preset = $this->getPresetOptions();

    if ($this->getSetting('selected_wif_preset')) {
      $summary[] = t('Selected preset: @preset', array('@preset' => $wif_preset[$this->getSetting('selected_wif_preset')]));
    }
    else {
      $summary[] = t('No preset have been selected yet! Please select a preset.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $base_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/";
    $images = array();
    $elements = array();
    // Returns the referenced entities for display.
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $selected_wif_preset = $this->getSetting('selected_wif_preset');

    foreach ($files as $delta => $file) {

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      if ($delta == 0){
        // If this is the first image, create one element and add the item to #item as a array element
        $elements[0] = array(
          '#theme' => 'webshop_image_formatter',
          '#selected_wif_preset' => $selected_wif_preset,
          '#items' => array(),
          '#attached' => array(
            'library' => array(
              'wif/drupal.wif',
              'wif/lightGallery.thumbnail',
            ),
            'drupalSettings' => array(
              'wif' => array(
                'loadingIcon' => $base_url . \Drupal\Core\Extension\ExtensionPathResolver::getPath('module', 'wif') . '/images/ajax-loader.gif',
              ),
            ),
          ),
        );
      }

      $elements[0]['#items'][$delta] = array(
        'item' => $item,
        'item_attributes' => $item_attributes,
        'alt' => $item->alt,
        'title' => $item->title? $item->title : $item->alt,
      );
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getPresetOptions() {
    $options = array();
    $wif_preset_entities = \Drupal::entityTypeManager()->getStorage('wif_preset')->loadMultiple();

    foreach($wif_preset_entities as $wif_preset_id => $wif_preset) {
      $options[$wif_preset_id] = $wif_preset->label;
    }

    return $options;
  }

}
