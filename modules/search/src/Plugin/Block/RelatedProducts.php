<?php

namespace Drupal\drupaldev_search\Plugin\Block;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Block\BlockBase;

/**
 *
 * @Block(
 *   id = "drupaldev_related_products",
 *   admin_label = @Translation("Drupaldev Search: Related product block for
 *   testing")
 * )
 */
class RelatedProducts extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $product = $product_id = \Drupal::routeMatch()
      ->getParameter('commerce_product');
    if (!$product instanceof Product) {
      return;
    }

    $product_variation = $product->getDefaultVariation();
    $item_language = $product->language()->getId();

    if ($product_variation instanceof ProductVariation) {
      $entity_langcode = $product_variation->language()->getId();

      // Get catalog term.
      $catalog_values = $product->get('field_catalog');

      // Get existing related products.
      $existing_related_products = $product->get('field_related_products')
        ->getValue();

      $existing_related_product_variation_ids = [];
      // Create array which holds the target ids.
      $existing_related_product_ids = array_map(function ($item) {
        return $item['target_id'];
      }, $existing_related_products);

      foreach ($existing_related_product_ids as $product_id) {
        $productObject = Product::load($product_id);
        $existing_related_product_variation_ids[] = $productObject->getDefaultVariation()
          ->id();
      }

      // Exit if field_catalog is empty.
      if (!empty($catalog_values->getValue())) {
        $catalog_related_variation_ids = [];
        $term = $catalog_values->first()->get('target_id')->getValue();
        // Add more items from the vocabulary if not enough added.
        if (count($existing_related_products) < 4) {
          $query = \Drupal::entityQuery('commerce_product');
          $query->condition('status', 1);
          $query->condition('field_catalog', $term);
          $query->range(0, 10);
          $query->accessCheck(FALSE);
          $products = $query->execute();

          foreach ($products as $product) {
            $query = \Drupal::entityQuery('commerce_product_variation');
            $query->condition('status', 1);
            $query->condition('product_id', $product);
            $query->condition('langcode', $item_language);
            $query->range(0, 1);
            $query->accessCheck(FALSE);
            $product_variation_ids = $query->execute();
            $catalog_related_variation_ids += $product_variation_ids;
          }
        }
      }
      else {
        $catalog_related_variation_ids = [];
      }

      $related_products = array_merge($existing_related_product_variation_ids, $catalog_related_variation_ids);
      // Filter out duplicates.
      $uniqe_product_variation_ids = array_unique($related_products);

      if (!empty($uniqe_product_variation_ids)) {
        // Limit to 4 items.
        $uniqe_product_variation_ids = array_slice($uniqe_product_variation_ids, 0, 4);

        foreach ($uniqe_product_variation_ids as $product_variation_id) {
          $storage = \Drupal::entityTypeManager()
            ->getStorage('commerce_product_variation');
          $related_product_variation = $storage->load($product_variation_id);

          if ($related_product_variation instanceof ProductVariation) {
            $view_builder = \Drupal::entityTypeManager()
              ->getViewBuilder('commerce_product_variation');
            $output = $view_builder->view($related_product_variation, 'teaser', $item_language);
            $full_output .= \Drupal::service('renderer')->renderPlain($output);
          }
        }
        return ['#markup' => $full_output];
      }
    }
  }

}
