<?php

namespace Drupal\drupaldev_search;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the drupaldev - search alias entity type.
 */
class DrupaldevSearchAliasListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = parent::render();

    $total = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $build['summary']['#markup'] = $this->t('Total search alias: @total', ['@total' => $total]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['langcode'] = $this->t('Langcode');
    $header['alias'] = $this->t('Alias');
    $header['filter_values'] = $this->t('Filter values');
    $header['old_aliases'] = $this->t('Old aliases');
    $header['title'] = $this->t('Title');
    $header['meta_description'] = $this->t('Meta description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\drupaldev_search\DrupaldevSearchAliasInterface $entity */
    $row['id'] = $entity->id();
    $row['langcode'] = $entity->getLangcode();
    $row['alias'] = $entity->getAlias();
    $row['filter_values'] = $entity->getFilterValues();
    $row['old_aliases'] = $entity->getAliases();
    $row['title'] = $entity->getTitle();
    $row['meta_description'] = $entity->getMetaDescription();
    return $row + parent::buildRow($entity);
  }

}
