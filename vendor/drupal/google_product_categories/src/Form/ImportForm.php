<?php

namespace Drupal\google_product_categories\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\google_product_categories\ProcessImportBatch;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImportForm extends FormBase {

  /**
   * @inheritDoc
   */
  protected $batchProcessor;

  /**
   * @inheritDoc
   */
  protected $languageManager;

  /**
   * @inheritDoc
   */
  protected $moduleHandler;

  /**
   * @inheritDoc
   */
  public function __construct(ProcessImportBatch $batch_processor, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler) {
    $this->batchProcessor = $batch_processor;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_product_categories.batch_processor'),
      $container->get('language_manager'),
      $container->get('module_handler')
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'google_product_categories_import_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['source'] = [
      '#type' => 'url',
      '#title' => $this->t('Google Product Categories source .txt file'),
      '#default_value' => GOOGLE_PRODUCT_CATEGORIES_SOURCE_URL,
      '#required' => TRUE,
    ];

    $existingLanguages = [];
    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      $existingLanguages[$langcode] = $language->getName();
    }

    $isTranslatable = FALSE;
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $isTranslatable = \Drupal::service("content_translation.manager")
        ->isEnabled('taxonomy_term', GOOGLE_PRODUCT_CATEGORIES_VOCABULARY_NAME);
    }

    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Please select destination language'),
      '#options' => $existingLanguages,
      '#default_value' => $this->languageManager->getDefaultLanguage()->getId(),
      '#access' => $isTranslatable,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'import',
      '#value' => $this->t('Import'),
      '#submit' => ['::importAction'],
      '#button_type' => 'primary',
    ];

    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#name' => 'delete',
      '#value' => $this->t('Delete existing terms'),
      '#submit' => ['::deleteAction'],
      '#button_type' => 'danger',
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $voc = Vocabulary::load(GOOGLE_PRODUCT_CATEGORIES_VOCABULARY_NAME);
    if (!$voc) {
      $form_state->setError($form, $this->t('`@vocName` vocabulary is missing. Please create it.', ['@vocName' => GOOGLE_PRODUCT_CATEGORIES_VOCABULARY_NAME]));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * @inheritDoc
   */
  public function importAction(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('source');
    $language = $form_state->getValue('language');

    if (!$content = @file_get_contents($url)) {
      return FALSE;
    }
    $lines = explode("\n", $content);
    $batch = [
      'operations' => [],
      'title' => $this->t('Importing Categories'),
      'finished' => [$this->batchProcessor, 'importFinish'],
      'progress_message' => $this->t('Imported @percentage%'),
    ];
    foreach (array_chunk($lines, 50) as $chunk) {
      $batch['operations'][] = [
        [$this->batchProcessor, 'processImport'],
        [$chunk, $language],
      ];
    }
    batch_set($batch);
  }

  /**
   * @inheritDoc
   */
  public function deleteAction(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('google_product_categories.delete');
  }

}