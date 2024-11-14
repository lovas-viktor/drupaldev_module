<?php

/**
 * @fileoverview
 *
 * This file handles the request parse of the redirected clean url for search.
 */

namespace Drupal\drupaldev_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupaldev_search\Entity\DrupaldevSearchAlias;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Cache\CacheableJsonResponse;

/**
 * Render views for taxonomy term pages.
 */
class RouteController extends ControllerBase {

  /**
   * Render function.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function render(Request $request) {
    $parameters = \Drupal::routeMatch()->getParameters();
    $build = NULL;

    // View path should remain products.
    $view = Views::getView('products');

    if ($view instanceof ViewExecutable) {
      // Ensure view exists and is enabled.

      if ($view && $view->storage->status()) {
        $this->setArgumentsFromAlias($request, $parameters);
        $build = $view->executeDisplay('page_1');

      }
    }

    return $build;
  }

  /**
   * Set query arguments from alias.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Symfony\Component\HttpFoundation\ParameterBag $parameters
   *
   * @return void
   */
  public function setArgumentsFromAlias(Request $request, $parameters) {

    $alias = implode('/', $parameters->all());

    $results = drupaldev_search_get_alias($alias);

    if (empty($results)) {
      return;
    }

    $alias = reset($results);
    $search_alias = DrupaldevSearchAlias::load($alias);

    $filter_values = $search_alias->getFilterQueryValues();

    $arguments = [];

    foreach ($filter_values as $key => $value) {
      foreach ($value as $val) {
        $arguments[] = $val;
      }

      $request->query->set($key, $arguments);
    }

  }

  /**
   * Get view by path.
   *
   * @param string $path
   *
   * @return \Drupal\views\ViewExecutable|void|null
   */
  public function getViewByPath($path) {
    $url = \Drupal::service('path.validator')->getUrlIfValid($path);
    $parameters = $url->getRouteParameters();

    if (!empty($parameters['view_id'])) {
      return Views::getView($parameters['view_id']);
    }
  }

}
