<?php

namespace Drupal\drupaldev_commerce;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\drupaldev_search\Entity\DrupaldevSearchAlias;
use Drupal\node\NodeInterface;
use Drupal\Tests\field\Kernel\FieldAttachStorageTest;

/**
 * Class Breadcrumbs.
 *
 * @package Drupal\custom_breadcrumb
 */
class CustomBreadcrumbs implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'entity.commerce_product.canonical' || str_contains($route_match->getRouteName(), 'drupaldev.search_route_');
  }

  /**
   * {@inheritDoc}
   */
  public function build(RouteMatchInterface $route_match) {
    // Route paramters.
    $facet_param = $route_match->getParameter('f0');

    $breadcrumb = new Breadcrumb();

    // Add "Home" breadcrumb link.
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    // Add "Products" breadcrumb link.
    $products_link = Link::createFromRoute(t('Products'), 'view.products.page_1');
    $breadcrumb->addLink($products_link);

    // By setting a "cache context" to the "url", each requested URL gets it's
    // own cache. This way a single breadcrumb isn't cached for all pages on the
    // site.
    $breadcrumb->addCacheContexts(["url"]);
    $commerce_product = $route_match->getParameter('commerce_product');

    if ($commerce_product) {
      // By adding "cache tags" for this specific node, the cache is invalidated
      // when the node is edited.
      $breadcrumb->addCacheTags(["commerce_product:{$commerce_product->id()}"]);

      $catalog_terms = $commerce_product->get('field_catalog')
        ->referencedEntities();

      if (!empty($catalog_terms)) {

        // Fix if no catalog term.
        if (empty($catalog_terms[0])) {
          $catalog_terms[0] = reset($catalog_terms);
        }

        $parent_terms = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadAllParents($catalog_terms[0]->id());

        $parent_terms = array_reverse($parent_terms, TRUE);
        if (!empty($parent_terms)) {
          foreach ($parent_terms as $key => $term) {
            $curr_langcode = \Drupal::languageManager()
              ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
              ->getId();
            $taxonomy_term_trans = \Drupal::service('entity.repository')
              ->getTranslationFromContext($term, $curr_langcode);
            $breadcrumb->addLink($taxonomy_term_trans->toLink());
          }
        }
      }
    }

    if (!empty($facet_param)) {
      $query = \Drupal::entityQuery('drupaldev_search_alias');
      $conditionGroup = $query->andConditionGroup();
      $conditionGroup->condition('alias', $facet_param);
      $query->condition($conditionGroup);
      $query->condition('langcode', \Drupal::languageManager()
        ->getCurrentLanguage()
        ->getId());
      $query->range(0, 1);
      $query->accessCheck(FALSE);
      $results = $query->execute();

      if (empty($results)) {

        return $breadcrumb;
      }

      $search_alias = DrupaldevSearchAlias::load(reset($results));
      $path_array = explode('&', $search_alias->getPath());
      $last_term_id = FALSE;

      foreach ($path_array as $filter) {
        $last_path_filter = explode(':', $filter);
        if (strpos($last_path_filter[0], 'catalog') !== FALSE) {
          $last_term_id = $last_path_filter[1];
        }
      }

      if ($last_term_id) {
        $parent_terms = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadAllParents($last_term_id);
        $parent_terms = array_reverse($parent_terms, TRUE);

        if (!empty($parent_terms)) {
          foreach ($parent_terms as $key => $term) {
            if ($key != array_key_last($parent_terms)) {
              $curr_langcode = \Drupal::languageManager()
                ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
                ->getId();
              $taxonomy_term_trans = \Drupal::service('entity.repository')
                ->getTranslationFromContext($term, $curr_langcode);
              $breadcrumb->addLink($taxonomy_term_trans->toLink());
            }
          }
        }
      }
    }

    return $breadcrumb;
  }

}
