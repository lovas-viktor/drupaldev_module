<?php

/**
 * @file
 * Contains \Drupal\wif\Plugin\Field\FieldFormatter\WebshopImageFormatter.
 */

namespace Drupal\wif\Plugin\Field\FieldFormatter;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
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
    return [
        'selected_wif_preset' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $url = Url::fromRoute('entity.wif_preset.collection');
    $link_to_preset_collection_page = \Drupal\Core\Link::fromTextAndUrl(t('Webshop Image Formatter Presets admin page'), $url);

    $element['selected_wif_preset'] = [
      '#title' => t('Preset'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('selected_wif_preset'),
      '#description' => $this->t("You can add, edit or delete presets on the @link.", ['@link' => $link_to_preset_collection_page]),
      '#options' => $this->getPresetOptions(),
      '#require' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $wif_preset = $this->getPresetOptions();

    if ($this->getSetting('selected_wif_preset')) {
      $summary[] = t('Selected preset: @preset', ['@preset' => $wif_preset[$this->getSetting('selected_wif_preset')]]);
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
    $product_image_files = [];
    if (count($items)) {
      $entity = $items[0]->getEntity();
      if ($entity instanceof ProductVariation) {
        $product = $entity->getProduct();
        if ($product instanceof Product && $product->hasField('field_gallery')) {
          $product_image_items = $product->get('field_gallery');
          $product_image_files = $this->getEntitiesToView($product_image_items, $langcode);
        }
      }
    }

    $base_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/";
    $images = [];
    $elements = [];
    // Returns the referenced entities for display.
    $files = $this->getEntitiesToView($items, $langcode);
    if (count($product_image_files)) {

      $route = \Drupal::routeMatch()->getRouteName();
      $variables['has_color_filter'] = FALSE;

      if($route == 'entity.commerce_product.canonical'){
        $params = \Drupal::request()->query->all();

        if ($params && $params['v']) {
          $files = array_merge($files, $product_image_files);
        } else{
          $files = array_merge($product_image_files, $files);
        }
      } else{
        $files = array_merge($files, $product_image_files);
      }
    }
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

      if ($delta == 0) {
        // If this is the first image, create one element and add the item to #item as a array element
        $elements[0] = [
          '#theme' => 'webshop_image_formatter',
          '#selected_wif_preset' => $selected_wif_preset,
          '#items' => [],
          '#attached' => [
            'library' => [
              'wif/drupal.wif',
              'wif/lightGallery.thumbnail',
            ],
            'drupalSettings' => [
              'wif' => [
                'loadingIcon' => $base_url . \Drupal::service('extension.list.module')
                    ->getPath('wif') . '/images/ajax-loader.gif',
              ],
            ],
          ],
        ];
      }

      if ($fieldParent = $item->getEntity() instanceof Product
        || $fieldParent = $item->getEntity() instanceof ProductVariation) {

        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        /* @var $fieldParent Product | ProductVariation */
        $fieldParent = $item->getEntity();
        if($fieldParent->hasTranslation($language)){
          $fieldParent = $fieldParent->getTranslation($language);
        }

        $img_alt = $fieldParent->title->value;

        /** @var $item \Drupal\image\Plugin\Field\FieldType\ImageItem */
        $item->set('title', $img_alt);
        $item->set('alt', $img_alt);

      }

      $elements[0]['#items'][$delta] = [
        'item' => $item,
        'item_attributes' => $item_attributes,
        'alt' => $item->alt,
        'title' => $item->title ? $item->title : $item->alt,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getPresetOptions() {
    $options = [];
    $wif_preset_entities = \Drupal::entityTypeManager()
      ->getStorage('wif_preset')
      ->loadMultiple();

    foreach ($wif_preset_entities as $wif_preset_id => $wif_preset) {
      $options[$wif_preset_id] = $wif_preset->label;
    }

    return $options;
  }

}
