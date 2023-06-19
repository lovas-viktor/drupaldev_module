<?php
/**
 * @file
 * Contains \Drupal\drupaldev_search\Routing\DrupaldevRoutes.
 */

namespace Drupal\drupaldev_search\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class DrupaldevRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];
    $path = '';
    for ($i = 0; $i <= 20; $i++) {
      $path .= "/{f$i}";
      $routes['drupaldev.search_route_' . $i] = new Route(
        "/products$path",
        [
          '_controller' => '\Drupal\drupaldev_search\Controller\RouteController::render',
          '_title' => ''
        ],
        [
          '_permission' => 'access content',
        ]
      );

    }

    return $routes;
  }

}
