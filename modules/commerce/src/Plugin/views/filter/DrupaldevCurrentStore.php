<?php

namespace Drupal\drupaldev_commerce\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter by current store.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("drupaldev_current_store")
 */
class DrupaldevCurrentStore extends FilterPluginBase {

  public function adminSummary() {}

  protected function operatorForm(&$form, FormStateInterface $form_state) {}

  public function canExpose() {
    return FALSE;
  }

  public function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";
    $store = \Drupal::service('commerce_store.current_store')->getStore();
    $snippet = "$field = " . $store->id();

    $this->query->addWhereExpression($this->options['group'], $snippet);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'store';
    $contexts[] = 'user';

    return $contexts;
  }

}
