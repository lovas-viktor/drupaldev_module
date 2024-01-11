<?php

namespace Drupal\googlereviews\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\googlereviews\GetGoogleDataInterface;

/**
 * Provides a block with Google reviews.
 *
 * @Block(
 *   id = "googlereviews_reviews",
 *   admin_label = @Translation("Google Reviews List"),
 *   category = @Translation("Google Reviews")
 * )
 */
class GoogleReviewsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Get Google Data service.
   *
   * @var \Drupal\googlereviews\GetGoogleDataInterface
   */
  protected $getGoogleData;

  /**
   * Constructs a new GoogleReviewsBlock object.
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
  public function defaultConfiguration() {
    return [
      'max_google_reviews' => 5,
      'google_reviews_sorting' => 'newest',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $max_rows_options = [
      '1' => '1',
      '2' => '2',
      '3' => '3',
      '4' => '4',
      '5' => '5',
    ];

    $form['max_google_reviews'] = [
      '#type' => 'select',
      '#title' => $this->t('Amount of reviews'),
      '#options' => $max_rows_options,
      '#description' => $this->t('The amount of reviews that need to be shown in this block.'),
      '#default_value' => $this->configuration['max_google_reviews'] ?? 5,
    ];

    $form['google_reviews_sorting'] = [
      '#type' => 'select',
      '#title' => $this->t('Reviews sorting'),
      '#options' => [
        'newest' => $this->t('Newest'),
        'most_relevant' => $this->t('Most relevant (according to Google)'),
      ],
      '#default_value' => $this->configuration['google_reviews_sorting'] ?? 'newest',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['max_google_reviews'] = $form_state->getValue('max_google_reviews');
    $this->configuration['google_reviews_sorting'] = $form_state->getValue('google_reviews_sorting');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $reviews = $this->getGoogleData->getGoogleReviews(['rating', 'reviews'], $config['max_google_reviews'], $config['google_reviews_sorting']);

    if (!empty($reviews)) {
      $renderable = [
        '#attached' => ['library' => ['googlereviews/googlereviews.reviews']],
        '#theme' => 'googlereviews_reviews_block',
        '#reviews' => $reviews['reviews'],
        '#place_id' => $reviews['place_id'],
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
