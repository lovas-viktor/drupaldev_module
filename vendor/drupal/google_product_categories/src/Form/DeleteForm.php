<?php

namespace Drupal\google_product_categories\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\google_product_categories\ProcessImportBatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DeleteForm.
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * @inheritDoc
   */
  protected $batchProcessor;

  /**
   * @inheritDoc
   */
  protected $taxonomyStorage;

  /**
   * @inheritDoc
   */
  public function __construct(ProcessImportBatch $batch_processor, EntityStorageInterface $taxonomy_storage) {
    $this->batchProcessor = $batch_processor;
    $this->taxonomyStorage = $taxonomy_storage;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_product_categories.batch_processor'),
      $container->get('entity_type.manager')->getStorage('taxonomy_term')
    );
  }

  /**
   * @inheritDoc
   */
  public function getQuestion() {
    return $this->t('Delete all existing terms?');
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    return new Url('google_product_categories.settings');
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'google_product_categories_delete_form';
  }

  /**
   * @inheritDoc
   */
  public function getDescription() {
    return $this->t('Are you sure you want to delete all existing `@vocabulary` taxonomies? All existing references will be lost. This action cannot be undone.', ['@vocabulary' => GOOGLE_PRODUCT_CATEGORIES_VOCABULARY_NAME]);
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $tids = $this->taxonomyStorage->loadByProperties([
      'vid' => GOOGLE_PRODUCT_CATEGORIES_VOCABULARY_NAME,
    ]);

    $batch = [
      'operations' => [],
      'title' => $this->t('Deleting Taxonomies'),
      'finished' => [$this->batchProcessor, 'deleteFinish'],
      'progress_message' => $this->t('Deleted @percentage%'),
    ];

    foreach (array_chunk($tids, 50) as $chunk) {
      $batch['operations'][] = [
        [$this->batchProcessor, 'processDelete'],
        [$chunk],
      ];
    }

    batch_set($batch);

    $form_state->setRedirect('google_product_categories.settings');
  }

}