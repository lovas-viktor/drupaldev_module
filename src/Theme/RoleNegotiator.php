<?php

namespace Drupal\drupaldev\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Sets the active theme on admin pages.
 */
class RoleNegotiator implements ThemeNegotiatorInterface {

  /**
   * Protected configFactory variable.
   *
   * @var configFactory
   */
  protected $configFactory;

  /**
   * Protected adminRoute variable.
   *
   * @var adminRoute
   */
  protected $adminRoute;

  /**
   * Protected route_match variable.
   *
   * @var route_match
   */
  protected $routeMatch;

  /**
   * Protected account variable.
   *
   * @var account
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AdminContext $adminRoute, RouteMatchInterface $routeMatch, AccountProxy $account) {
    $this->configFactory = $config_factory;
    $this->adminRoute = $adminRoute;
    $this->routeMatch = $routeMatch;
    $this->account = $account;
  }

  /**
   * Whether this theme negotiator should be used to set the theme.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return bool
   *   TRUE if this negotiator should be used or FALSE to let other negotiators
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match) {
    // Use this theme on a certain route.
    $change_theme = TRUE;
    $route = $this->routeMatch->getRouteObject();
    $is_admin_route = $this->adminRoute->isAdminRoute($route);

    if (!$is_admin_route === TRUE) {
      $change_theme = FALSE;
    }

    // Get current roles a user has.
    $roles = $this->account->getRoles();

    if(!in_array('store_admin', $roles)) {
      $change_theme = FALSE;
    }

    return $change_theme;
  }

  /**
   * Determine the active theme for the request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return string|null
   *   The name of the theme, or NULL if other negotiators, like the configured
   *   default one, should be used instead.
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return 'gin';
  }

}
