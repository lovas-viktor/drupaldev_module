<?php

/**
 * @fileoverview
 * This file handles the redirect to a clean search url.
 */

namespace Drupal\drupaldev_search\EventSubscriber;

use Drupal\Core\Url;
use Drupal\drupaldev_search\Entity\DrupaldevSearchAlias;
use Drupal\facets\Entity\Facet;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Views;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class TermRedirectSubscriber implements EventSubscriberInterface {

  public function checkRedirection(RequestEvent $event) {

    if (\Drupal::routeMatch()
        ->getRouteName() === 'entity.taxonomy_term.canonical') {
      $term = \Drupal::routeMatch()->getParameter('taxonomy_term');

      $vid = $term->get('vid')->target_id;

      $facets = Facet::loadMultiple();

      foreach ($facets as $id => $facet) {
        if($id != 'catalog' && $id != 'tags'){
          continue;
        }
        $field_identifier = $facet->getFieldIdentifier();

        // Try to load field from commerce_product or commerce_product_variation.
        $field_config_commerce_product = FieldConfig::loadByName('commerce_product', 'default', $field_identifier);
        $field_config_commerce_product_variation = FieldConfig::loadByName('commerce_product_variation', 'default', $field_identifier);

        if (!$field_config_commerce_product) {
          $field_settings = $field_config_commerce_product_variation->getSettings();
        }
        else {
          if (!$field_config_commerce_product_variation) {
            $field_settings = $field_config_commerce_product->getSettings();
          }
          else {
            throw new \InvalidArgumentException(t('@field field not found', ['@field' => $field_identifier]));
          }
        }

        if (!empty($field_settings['handler']) && $field_settings['handler'] === 'default:taxonomy_term') {
          $bundle = reset($field_settings['handler_settings']['target_bundles']);

          if ($bundle == $vid) {
            $url = Url::fromUserInput('/'.t('products_prefix').'?f[0]=' . $facet->getUrlAlias() . ':' . $term->id())
                     ->toString();
            $event->setResponse(new RedirectResponse($url, 302));
          }
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    //The number 30 is the priority. This is set at 30 so that it runs before page caching (currently priority 27)
    $events[KernelEvents::REQUEST][] = ['checkRedirection', 30];
    return $events;
  }

}
