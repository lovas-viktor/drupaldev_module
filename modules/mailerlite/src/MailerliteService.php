<?php

namespace Drupal\drupaldev_mailerlite;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MailerLite\MailerLite;
use GuzzleHttp\ClientInterface;

/**
 * Class MailerliteService.
 *
 * Implements functions to connect to the Mailerlite api.
 *
 * @package Drupal\drupaldev_mailerlite\Services
 */
class MailerliteService {

  use StringTranslationTrait;

  /**
   * The billingo.settings configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Mailerlite base url.
   *
   * @var string
   */
  protected $endpointBase;

  /**
   * Mailerlite class.
   *
   * @var string
   */
  protected $mailerlite;

  /**
   * Constructs a new service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->config = $config_factory->get('mailerlite.settings');
    $this->httpClient = $http_client;
    $this->endpointBase = $this->config->get('endpoint');
    $this->mailerlite = new MailerLite(['api_key' => $this->config->get('api_key')]);
  }

  /**
   * Create subscriber.
   *
   * @param string $email
   */
  public function createSubscriber($email) {
    if (empty($this->config->get('api_key'))) {
      \Drupal::messenger()->addError(t('No mailerlite API key set.'));
      return;
    }

    $data = [
      'email' => $email,
    ];

    $response = $this->mailerLite->subscribers->create($data);
  }

}
