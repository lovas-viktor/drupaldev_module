<?php

/**
 * @fileoverview
 * This file handles the redirect to a clean search url.
 */

namespace Drupal\drupaldev_search\EventSubscriber;

use Drupal\Core\Url;
use Drupal\drupaldev_search\Entity\DrupaldevSearchAlias;
use Drupal\facets\Entity\Facet;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class OldAliasRedirectSubscriber implements EventSubscriberInterface {

  public function checkRedirection(ResponseEvent $event) {
    if (\Drupal::service('router.admin_context')->isAdminRoute() || \Drupal::routeMatch()->getRouteName() == 'system.404') {
      return;
    }
    \Drupal::service('page_cache_kill_switch')->trigger();
    $request = $event->getRequest();
    $parameters = $request->attributes->get('_raw_variables')->all();

    if (empty($parameters['f0'])) {
      return $event;
    }

    $query = \Drupal::entityQuery('drupaldev_search_alias');
    $orGroup = $query->orConditionGroup()
      ->condition('path', $parameters['f0'], 'IN')
      ->condition('langcode', \Drupal::languageManager()->getCurrentLanguage()->getId())
      ->condition('old_aliases', $parameters['f0'], 'IN');

    // Add the group to the query.
    $query->condition($orGroup);

    $results = $query->execute();

    // If alias not found in the aliases, try to search in old aliases.
    if (!empty($results)) {
      $alias = reset($results);
      $search_alias = DrupaldevSearchAlias::load($alias);
      $url = Url::fromUserInput('/products/' . $search_alias->getAlias())
        ->toString();
      $current = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getRequestUri();
      if ($current !== $url) {
        $event->setResponse(new RedirectResponse($url, 302));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['checkRedirection'];
    return $events;
  }

}
