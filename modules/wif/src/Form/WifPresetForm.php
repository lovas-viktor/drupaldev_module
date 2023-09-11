<?php


/**
 * @file
 * Contains \Drupal\wif\Form\WifPresetForm.
 */

namespace Drupal\wif\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

class WifPresetForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $wif_preset = $this->entity;

    // Change page title for the edit operation
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit preset: @name', array('@name' => $wif_preset->label));
    }

    // Label
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $wif_preset->label(),
      '#description' => $this->t("Label for the Preset."),
      '#required' => TRUE,
    );

    // Machine name (id)
    $form['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#default_value' => $wif_preset->id(),
      '#machine_name' => array(
        'source' => array('label'),
        'exists' => array($this, 'exist'),
      ),
      '#disabled' => !$wif_preset->isNew(),
    );

    $image_styles = image_style_options(FALSE);

    // Responsive image styles.
    $responsive_image_styles = ResponsiveImageStyle::loadMultiple();
    if (!empty($responsive_image_styles)) {
      $responsive_image_style_array = [];
      foreach ($responsive_image_styles as $style_name => $responsive_image_style) {
        $responsive_image_style_array[$style_name . '-responsive'] = $responsive_image_style->label() . ' - Responsive image style';
      }
      $image_styles = array_merge($image_styles, $responsive_image_style_array);
    }

    // Thumbnail image style
    $form['image_style_thumb'] = array(
      '#title' => t('Thumbnail image style'),
      '#type' => 'select',
      '#default_value' => $wif_preset->image_style_thumb,
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#maxlength' => 255,
    );

    // Normal image style
    $form['image_style_normal'] = array(
      '#title' => t('Normal image style'),
      '#type' => 'select',
      '#default_value' => $wif_preset->image_style_normal,
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#maxlength' => 255,
    );

    // Zoomed image style
    $form['image_style_zoomed'] = array(
      '#title' => t('Zoomed image style'),
      '#type' => 'select',
      '#default_value' => $wif_preset->image_style_zoomed,
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#maxlength' => 255,
    );

    // You will need additional form elements for your custom properties.

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $wif_preset = $this->entity;
    $status = $wif_preset->save();

    if ($status) {
      \Drupal::messenger()->addMessage($this->t('Saved the %label Preset.', array(
        '%label' => $wif_preset->label(),
      )));
    }
    else {
      \Drupal::messenger()->addMessage($this->t('The %label Preset was not saved.', array(
        '%label' => $wif_preset->label(),
      )));
    }

    $form_state->setRedirect('entity.wif_preset.collection');
  }

  public function exist($id) {
    $entity = $this->entityTypeManager->getListBuilder('wif_preset')
      ->getStorage()
      ->loadByProperties([
        'status' => 1,
        'id' => $id,
      ]);
    return (bool) $entity;
  }
}
