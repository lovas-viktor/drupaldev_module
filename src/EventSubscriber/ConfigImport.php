<?php

namespace Drupal\drupaldev\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigImport implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::STORAGE_TRANSFORM_IMPORT][] = ['onImportTransform'];
    $events[ConfigEvents::STORAGE_TRANSFORM_EXPORT][] = ['onExportTransform'];
    return $events;
  }
  /**
   * The storage is transformed for importing.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onImportTransform(StorageTransformEvent $event) {
    /** @var \Drupal\Core\Config\StorageInterface $storage */
    $storage = $event->getStorage();
    $configs = $storage->listAll();
    foreach($configs as $config_name) {
      $config = $storage->read($config_name);

      // Only change something if the sync storage has data.
      if (!empty($config) && isset($config['uuid'])) {
        $site_config = \Drupal::config($config_name)->getRawData();
        if(isset($site_config['uuid'])){
          $config['uuid'] = $site_config['uuid'];
        }
        // Write to the storage from the event to alter it.
        $storage->write($config_name, $config);
      }
    }
  }

  /**
   * The storage is transformed for exporting.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onExportTransform(StorageTransformEvent $event) {
    $uuid = \Drupal::config('system.site')->getRawData()['uuid'];

    $storage = $event->getStorage();
    $configs = $storage->listAll();
    foreach($configs as $config_name) {
      $config = $storage->read($config_name);
      // Only change something if the sync storage has data.
      if (!empty($config) && isset($config['uuid'])) {
        $config['uuid'] = $uuid;
        // Write to the storage from the event to alter it.
        $storage->write($config_name, $config);
      }
    }
  }
}
