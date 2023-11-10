<?php

/**
 * @file
 * Contains \Drupal\drupaldev_search\Plugin\Block\SearchDescriptionBlock.
 */

namespace Drupal\drupaldev_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\drupaldev_search\Entity\DrupaldevSearchAlias;
use Drupal\taxonomy\Entity\Term;

/**
 *
 * @Block(
 *   id = "drupaldev_search_description",
 *   admin_label = @Translation("Drupaldev Search: Description Block")
 * )
 */
class SearchDescription extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $parameters = \Drupal::routeMatch()->getRawParameters();

    $alias = implode('-', $parameters->all());

    $search_alias_query = \Drupal::entityQuery('drupaldev_search_alias');
    $search_alias_query->condition('alias', $alias);
    $search_alias_query->condition('langcode', \Drupal::languageManager()->getCurrentLanguage()->getId());
    $results = $search_alias_query->execute();

    if (empty($results)) {
      return [];
    }

    $result = reset($results);
    $search_alias = DrupaldevSearchAlias::load($result);
    $long_used = FALSE;
    $description = '';

    if (!empty($search_alias)) {

      // If filter value is empty return empty block.
      if (empty($search_alias->getFilterValues())) {
        return [];
      }

      $description = $search_alias->getMetaDescriptionWithFilters();

      if (!empty($search_alias->getLongDescription())) {
        $long_used = TRUE;
        $description = $search_alias->getLongDescriptionWithFilters();
      }
    }

    return array(
      '#theme' => 'search_description_block',
      '#content' => $description,
      '#long_used' => $long_used,
      '#cache' => array(
        'contexts' => array('url'),
      ),
    );
  }
}
