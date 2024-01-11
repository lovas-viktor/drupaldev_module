<?php

namespace Drupal\commerce_exchanger;

/**
 * Main interface for importing rates.
 *
 * @package Drupal\commerce_exchanger
 */
interface ExchangerImporterInterface {

  /**
   * Triggers importing exchange rates.
   *
   * @param bool $force
   *   If import is forced regardless of cron setup.
   */
  public function run($force);

}
