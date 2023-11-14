<?php

namespace Drupal\drupaldev_commerce;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

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
    return $route_match->getRouteName() == 'entity.commerce_product.canonical';
  }

  /**
   * {@inheritDoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $commerce_product = $route_match->getParameter('commerce_product');
    $breadcrumb = new Breadcrumb();

    // By setting a "cache context" to the "url", each requested URL gets it's
    // own cache. This way a single breadcrumb isn't cached for all pages on the
    // site.
    $breadcrumb->addCacheContexts(["url"]);

    // By adding "cache tags" for this specific node, the cache is invalidated
    // when the node is edited.
    $breadcrumb->addCacheTags(["commerce_product:{$commerce_product->id()}"]);

    // Add "Home" breadcrumb link.
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    $catalog_terms = $commerce_product->get('field_catalog')->referencedEntities();
    if (!empty($catalog_terms)) {
      foreach ($catalog_terms as $key => $term) {
        if ($key !== array_key_last($catalog_terms)) {
          $breadcrumb->addLink($term->toLink());
        }
      }
    }
    return $breadcrumb;
  }

}