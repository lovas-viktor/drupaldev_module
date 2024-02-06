<?php

namespace Drupal\drupaldev_mailerlite;

use Drupal\Core\Config\ConfigFactoryInterface;
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
   * The mailerlite.settings configuration.
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
   * @var MailerLite
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
    $this->config = $config_factory->get('drupaldev_mailerlite.settings');
    $this->httpClient = $http_client;
    $this->endpointBase = $this->config->get('endpoint');

    $this->mailerlite = new MailerLite(['api_key' => $this->config->get('api_key')]);
  }

  /**
   * Create subscriber.
   *
   * @param string $email
   */
  public function createSubscriber($data) {
    if (empty($this->config->get('api_key'))) {
      \Drupal::messenger()->addError(t('No mailerlite API key set.'));
      return;
    }

    // Unset non neccessary data.
    $data_to_unset = [
      'gdpr-warning',
      'submit',
      'form_build_id',
      'form_token',
      'form_id',
      'op'
    ];
    foreach ($data as $key => $value) {
        if (in_array($key, $data_to_unset)) {
          unset($data[$key]);
        }
    }

    $to_be_sent = [
      "email" => $data['email'],
      "fields" => $data
    ];

    return $this->mailerlite->subscribers->create($to_be_sent);
  }

  /**
   * Get subscribers.
   */
  public function getSubscribers() {
    if (empty($this->config->get('api_key'))) {
      \Drupal::messenger()->addError(t('No mailerlite API key set.'));
      return;
    }

    return $this->mailerlite->subscribers->get();
  }

  /**
   * Get subscriber.
   *
   * @param string $subscriberId
   */
  public function getSubscriber($subscriberId) {
    if (empty($this->config->get('api_key'))) {
      \Drupal::messenger()->addError(t('No mailerlite API key set.'));
      return;
    }

    return $this->mailerlite->subscribers->find($subscriberId);
  }

  /**
   * Update subscriber.
   *
   * @param string $subscriberId
   * @param array $data
   * 'fields' => [
   *    'name' => 'Example',
   * ],
   */
  public function updateSubscriber($subscriberId, $data) {
    if (empty($this->config->get('api_key'))) {
      \Drupal::messenger()->addError(t('No mailerlite API key set.'));
      return;
    }

    return $this->mailerlite->subscribers->update($subscriberId, $data);
  }

  /**
   * Delete subscriber.
   *
   * @param string $subscriberId
   */
  public function deleteSubscriber($subscriberId) {
    if (empty($this->config->get('api_key'))) {
      \Drupal::messenger()->addError(t('No mailerlite API key set.'));
      return;
    }

    return $this->mailerlite->subscribers->delete($subscriberId);
  }

  /**
   * Create campaign.
   *
   * @param array $data
   *  [
   *    'type' => 'regular',
   *    'name' => 'My new campaign',
   *    'language_id' => 10,
   *    'emails' => [
   *      [
   *        'subject' => 'My new email',
   *        'from_name' => 'me',
   *        'from' => 'me@example.com',
   *        'content' => 'Hello World!',
   *      ]
   *  ],
   *  'filter' => [],
   * ],
   */
  public function createCampaign($data) {
    if (empty($this->config->get('api_key'))) {
      \Drupal::messenger()->addError(t('No mailerlite API key set.'));
      return;
    }

    return $this->mailerlite->campaigns->create($data);
  }

}
