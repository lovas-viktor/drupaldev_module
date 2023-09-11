<?php

/**
 * @file
 * Contains \Drupal\wif\Plugin\Field\FieldFormatter\WebshopImageFormatter.
 */

namespace Drupal\wif\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'wif' formatter.
 *
 * @FieldFormatter(
 *   id = "wif_media",
 *   label = @Translation("Webshop Media Formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class WebshopMediaFormatter extends EntityReferenceFormatterBase {

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

    $selected_wif_preset = $this->getSetting('selected_wif_preset');
    $items = $this->getEntitiesToView($items, $langcode);

    foreach ($items as $delta => $item) {
      //TODO: add to the settings

      $file = $item->field_media_image->entity;

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
                'loadingIcon' => $base_url . drupal_get_path('module', 'wif') . '/images/ajax-loader.gif',
              ),
            ),
          ),
        );
      }
      $img_alt = $item->field_media_image->getValue()[0]['alt'];
      $img_alt = $item->field_media_image->getEntity()->getName();

      $fieldParent = $item->field_media_image->getEntity();
      if( isset($fieldParent->_referringItem) && $fieldParent->_referringItem->getEntity()) {
        // this line is only needed if using media entities (= entity references)
        // must disable entity view caching to work correctly
        $fieldParent = $fieldParent->_referringItem->getEntity();

        // Make sure it's a node.
        if ($fieldParent instanceof \Drupal\node\NodeInterface) {
          if( !$fieldParent->getType() == 'gourmet_travel') {
            $img_alt = $fieldParent->getTitle();
          } else{
            if (strpos($file->getFileName(), $item->getName()) === TRUE ||$item->getName() == $file->getFileName() ) {
              //$img_alt = $item->getName();
              $img_alt = $fieldParent->getTitle();
            } else{
              //$img_alt = $fieldParent->getTitle();
              $img_alt = $item->getName();
            }
          }
        }

      }

      if($fieldParent instanceof Drupal\media\Entity\Media){
        $img_alt = $item->field_media_image->getEntity()->getName();
      }

      $elements[0]['#items'][$delta] = array(
        'item' => $file,
        'alt' => $img_alt,
        'title' => $img_alt
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
