<?php

/**
 * @file
 * Contains \Drupal\drupaldev_mailerlite\Plugin\Block\Mailerlite.
 */

namespace Drupal\drupaldev_mailerlite\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @Block(
 *   id = "drupaldev_mailerlite_small",
 *   admin_label = @Translation("Mailerlite block - Small")
 * )
 */
class MailerliteSmall extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Form\FormBuilderInterface definition.
   *
   * @var formBuilder
   */
  protected $formBuilder;

  /**
   * Constructs a new ManagingActivitiesRegisterBlock object.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilderInterface $form_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'text_above_form' => ''
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['text_above_form'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text above form'),
      '#description' => $this->t('Text displayed above form'),
      '#default_value' => $config['text_above_form'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['text_above_form'] = $values['text_above_form'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = $this->formBuilder->getForm('Drupal\drupaldev_mailerlite\Form\MailerliteFormSmall');
    return [
      '#theme' => 'mailerlite',
      '#form' => $form,
      '#text_above_form' => $this->t($this->configuration['text_above_form']),
      '#cache' => [
        'contexts' => ['languages'],
      ],
    ];
  }

}
