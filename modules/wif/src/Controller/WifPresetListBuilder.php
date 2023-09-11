<?php

/**
 * @file
 * Contains \Drupal\wif\Controller\WifPresetListBuilder.
 */

namespace Drupal\wif\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Wif Preset.
 */
class WifPresetListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Preset');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();

    // You probably want a few more properties here...

    return $row + parent::buildRow($entity);
  }

}
