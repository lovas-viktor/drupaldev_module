<?php

namespace Drupal\base_field_override_ui\ConfigTranslation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\config_translation\Controller\ConfigTranslationFieldListBuilder;
use Drupal\base_field_override_ui\BaseFieldOverrideUI;

/**
 * Defines the config translation list builder for base field override entities.
 */
class ConfigTranslationBaseFieldOverrideListBuilder extends ConfigTranslationFieldListBuilder {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $ids = \Drupal::entityQuery('base_field_override')
      ->condition('id', $this->baseEntityType . '.', 'STARTS_WITH')
      ->execute();
    return $this->storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Base Field');
    if ($this->displayBundle()) {
      $header['bundle'] = $this->baseEntityInfo->getBundleLabel() ?: $this->t('Bundle');
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = [];
    $operations['translate'] = [
      'title' => $this->t('Translate'),
      'weight' => 1,
      'url' => BaseFieldOverrideUI::getTranslateRouteInfo($entity),
    ];

    return $operations;
  }

}
