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

class RedirectSubscriber implements EventSubscriberInterface {

  public function checkRedirection(ResponseEvent $event) {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $request = $event->getRequest();
    $params = $request->query->all();

    // Check view path.
    $url = \Drupal::service('path.validator')
      ->getUrlIfValid(\Drupal::routeMatch()->getRouteObject()->getPath());

    if (!$url) {
      return;
    }

    if (\Drupal::service('router.admin_context')->isAdminRoute()) {
      return;
    }

    $parameters = $url->getRouteParameters();

    // If it's not a view page, do nothing.
    if (empty($parameters['view_id'])) {
      return;
    }

    $view = Views::getView($parameters['view_id']);

    if (!$view || \Drupal::routeMatch()
        ->getRouteObject()
        ->getPath() != '/' . $view->getPath()) {
      return;
    }

    // Setup filter and alias array.
    $new_array = [];

    foreach ($params as $key => $param) {
      if ((!is_array($key) && $key == 'page') || $key == 'search_api_fulltext') {
        continue;
      }
      foreach ($param as $index => $filter_value) {
        $exploded_value = explode(':', $filter_value);

        // Get facet filter key.
        $facet_alias = $exploded_value[0];

        // Get facet by filter key.
        $facet = $this->getFacetByUrlIdentifier($facet_alias);

        // Get field name.
        $field_identifier = $facet->getFieldIdentifier();

        // Try to load field from commerce_product or commerce_product_variation.
        $field_config_commerce_product = FieldConfig::loadByName('commerce_product', 'default', $field_identifier);
        $field_config_commerce_product_variation = FieldConfig::loadByName('commerce_product_variation', 'default', $field_identifier);

        if ($field_config_commerce_product) {
          $field_settings = $field_config_commerce_product->getSettings();
        } else if ($field_config_commerce_product_variation) {
          $field_settings = $field_config_commerce_product_variation->getSettings();
        } else {
          //throw new \InvalidArgumentException(t('@field field not found', ['@field' => $field_identifier]));
        }

        $alias = $filter_value;

        $title_array = explode(':', $alias);

        if (!empty($field_settings['handler']) && $field_settings['handler'] == 'default:taxonomy_term') {
          $curr_langcode = \Drupal::languageManager()->getCurrentLanguage(\Drupal\Core\Language\LanguageInterface::TYPE_CONTENT)->getId();
          $term = Term::load($exploded_value[1]);
          if($term instanceof Term) {
            $taxonomy_term_trans = \Drupal::service('entity.repository')
              ->getTranslationFromContext($term, $curr_langcode);

            if ($taxonomy_term_trans instanceof Term) {
              $filter_value = $facet_alias . ':' . $taxonomy_term_trans->id();
              $alias = $facet_alias . ':' . $taxonomy_term_trans->getName();
              $title_array = [$title_array[1]];
              $title_array[] = $taxonomy_term_trans->getName();
            }
          }
        }

        $new_array['path'][] = $key . '[' . $index . ']=' . $filter_value;
        $new_array['query'][] = $filter_value;
        $new_array['alias'][] = $this->createAlias($alias);
        $new_array['filter_values'][] = $title_array[1];
      }
    }

    if (empty($new_array)) {
      return;
    }

    $alias = implode('-', $new_array['alias']);

    $query = \Drupal::entityQuery('drupaldev_search_alias');
    $filter_count = count($new_array['query']);

    foreach ($new_array['query'] as $q) {
      $conditionGroup = $query->andConditionGroup();
      $conditionGroup->condition('path', '%' . $q . '%', 'LIKE');
      $query->condition($conditionGroup);
    }

    $query->condition('langcode', \Drupal::languageManager()->getCurrentLanguage()->getId());

    $results = $query->execute();
    $exists = FALSE;

    if (!empty($results)) {
      $search_aliases =  DrupaldevSearchAlias::loadMultiple($results);
      foreach ($search_aliases as $search_alias) {
        if (count($search_alias->get('path')->getValue()) == $filter_count) {
          $exists = TRUE;
        }
      }
    }

    if (!$exists) {
      $search_alias = DrupaldevSearchAlias::create([
        'path' => $new_array['path'],
        'alias' => $alias,
        'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
        'filter_values' => implode(' ', $new_array['filter_values']),
      ]);

      $search_alias->save();
    }

    $url = Url::fromUserInput('/' . $view->getPath() . '/' . $alias)
      ->toString();

    $event->setResponse(new RedirectResponse($url, 302));
  }

  /**
   * Create alias based on filter value.
   *
   * @param string $filter_value
   *
   * @return string
   */
  public function createAlias($filter_value) {
    $cleaner = \Drupal::service('pathauto.alias_cleaner');
    $filter_value_exploded = explode(':', $filter_value);
    return $cleaner->cleanString($filter_value_exploded[1]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['checkRedirection'];
    return $events;
  }

  /**
   * Return facet by url identifier.
   *
   * @param string $identifier
   *
   * @return Facet
   */
  public function getFacetByUrlIdentifier($identifier) {
    $facets = Facet::loadMultiple();

    foreach ($facets as $facet) {
      if ($facet->getUrlAlias() == $identifier) {
        return $facet;
      }
    }
  }

}
