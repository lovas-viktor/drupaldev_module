<?php

namespace Drupal\googlereviews\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\googlereviews\GetGoogleDataInterface;

/**
 * Provides a block with Google reviews rating.
 *
 * @Block(
 *   id = "googlereviews_rating",
 *   admin_label = @Translation("Google Reviews Rating"),
 *   category = @Translation("Google Reviews")
 * )
 */
class GoogleRatingBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Get Google Data service.
   *
   * @var \Drupal\googlereviews\GetGoogleDataInterface
   */
  protected $getGoogleData;

  /**
   * Constructs a new GoogleRatingBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\googlereviews\GetGoogleDataInterface $getGoogleData
   *   The Google Data service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GetGoogleDataInterface $getGoogleData) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->getGoogleData = $getGoogleData;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('googlereviews.get_google_data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $rating = $this->getGoogleData->getGoogleReviews([
      'rating',
      'user_ratings_total',
    ]);

    if (!empty($rating)) {
      $rating_percentage = ($rating['rating'] / 5) * 100;

      $renderable = [
        '#attached' => ['library' => ['googlereviews/googlereviews.rating']],
        '#theme' => 'googlereviews_rating_block',
        '#user_ratings_total' => $rating['user_ratings_total'],
        '#rating' => $rating['rating'],
        '#rating_percentage' => $rating_percentage,
        '#place_id' => $rating['place_id'],
      ];

      return $renderable;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 86400;
  }

}
