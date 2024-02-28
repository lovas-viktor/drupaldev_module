<?php

/**
 * @file
 * Contains \Drupal\drupaldev_search\Plugin\Block\SearchDescriptionBlock.
 */

namespace Drupal\drupaldev_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\LanguageInterface;
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
    $search_alias_query->condition('langcode', \Drupal::languageManager()
      ->getCurrentLanguage()
      ->getId());
    $search_alias_query->accessCheck(FALSE);
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

      // If only one filter set try to get description from term.
      $filter_query = $search_alias->getFilterQueryValues();
      $curr_langcode = \Drupal::languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
      if (!empty($filter_query['f']) && count($filter_query['f']) == 1) {

        foreach ($filter_query['f'] as $filter) {

          $filter_value = explode(':', $filter);
          // Only for catalog for now.
          if ($filter_value[0] == 'catalog') {
            $term = Term::load($filter_value[1]);

            if ($term instanceof Term) {
              $taxonomy_term_trans = \Drupal::service('entity.repository')
                ->getTranslationFromContext($term, $curr_langcode);

              $description = $taxonomy_term_trans->get('field_seo_description')
                ->getString();
            }
          }

        }
      }
    }

    return [
      '#theme' => 'search_description_block',
      '#content' => $description,
      '#long_used' => $long_used,
      '#cache' => [
        'contexts' => ['url'],
      ],
    ];
  }

}
