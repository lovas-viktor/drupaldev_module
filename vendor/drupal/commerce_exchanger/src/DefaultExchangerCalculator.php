<?php

namespace Drupal\commerce_exchanger;

/**
 * {@inheritdoc}
 */
class DefaultExchangerCalculator extends AbstractExchangerCalculator {

  /**
   * {@inheritdoc}
   */
  public function getExchangerId() {
    // Return the first one.
    foreach ($this->providers as $provider) {
      if ($provider->status()) {
        return $provider->id();
      }
    }

    return NULL;
  }

}
