<?php

namespace Drupal\drupaldev_commerce\EventSubscriber;

use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AvailableCountriesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LimitCountriesEventSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    $events[AddressEvents::AVAILABLE_COUNTRIES][] = ['onAvailableCountries'];
    return $events;
  }

  public function onAvailableCountries(AvailableCountriesEvent $event) {
    if (\Drupal::languageManager()->getCurrentLanguage()->getId() == 'hu') {
      $countries = ['HU' => 'HU'];
      $event->setAvailableCountries($countries);
    }
    else {
      $event->setAvailableCountries($event->getAvailableCountries());
    }
  }

}
