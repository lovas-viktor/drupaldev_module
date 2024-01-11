<?php

namespace Drupal\google_product_categories;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\taxonomy\Entity\Term;

/**
 * Class ProcessImportBatch.
 */
class ProcessImportBatch {

  use MessengerTrait;

  /**
   * @inheritDoc
   */
  protected $taxonomyStorage;

  /**
   * @inheritDoc
   */
  public function __construct(EntityTypeManagerInterface $taxonomy_storage) {
    $this->taxonomyStorage = $taxonomy_storage->getStorage('taxonomy_term');
  }

  /**
   * @inheritDoc
   */
  public function processImport($chunk, $language, &$context) {
    $message = 'Importing Taxonomies...';

    foreach ($chunk as $key => $row) {

      // if row starts with # - skip it (comment) || skip if row is empty string
      if (substr($row, 0, 1) == '#' || $row === '') {
        continue;
      }

      list($googleCategoryId, $termPath) = explode(' - ', $row);

      $termPathElements = explode(' > ', $termPath);
      $termName = array_pop($termPathElements);
      $parentPath = implode(' > ', $termPathElements);

      // category id & term name can't be empty
      if ($googleCategoryId === '' || $termName === '') {
        continue;
      }

      $context['results']['tree'][$termPath] = [
        'full_path' => $termPath,
        'term_name' => $termName,
        'parent_path' => $parentPath,
      ];

      $terms = $this->taxonomyStorage->loadByProperties([
        'vid' => GOOGLE_PRODUCT_CATEGORIES_VOCABULARY_NAME,
        GOOGLE_PRODUCT_CATEGORIES_VOCABULARY_FIELD_ID => $googleCategoryId,
      ]);
      $term = reset($terms);

      // term found, let's update it if needed
      if (!empty($term)) {
        if ($term->hasTranslation($language)) {
          $term = $term->getTranslation($language);
          $term->setName($termName);
          $term->set(GOOGLE_PRODUCT_CATEGORIES_VOCABULARY_FIELD_PATH, $termPath);
          $term->save();
        }
        else {
          $term->addTranslation($language, [
            'name' => $termName,
            GOOGLE_PRODUCT_CATEGORIES_VOCABULARY_FIELD_PATH => $termPath,
          ])->save();
        }

      }
      // term not found, let's create it
      else {
        $term = Term::create([
          'name' => $termName,
          'vid' => GOOGLE_PRODUCT_CATEGORIES_VOCABULARY_NAME,
          GOOGLE_PRODUCT_CATEGORIES_VOCABULARY_FIELD_ID => $googleCategoryId,
          GOOGLE_PRODUCT_CATEGORIES_VOCABULARY_FIELD_PATH => $termPath,
          'langcode' => $language,
        ]);

        if (!empty($parentPath) && !empty($context['results']['tree'][$parentPath]['tid'])) {
          $term->parent = [$context['results']['tree'][$parentPath]['tid']];
        }
        $term->save();

        $context['results']['tree'][$termPath]['tid'] = $term->id();
      }
    }
    $context['message'] = $message;
  }

  /**
   * @inheritDoc
   */
  public function processDelete($chunk, &$context) {
    $message = 'Deleting Taxonomies...';
    $this->taxonomyStorage->delete($chunk);
    $context['results'] = array_merge($context['results'], $chunk);
    $context['message'] = $message;
  }

  /**
   * @inheritDoc
   */
  public function importFinish($success, $results, array $operations) {
    if ($success) {
      $message = t('@count imported successfully', ['@count' => count($results['tree'])]);
    }
    else {
      $message = t('Finished with an error.');
    }
    $this->messenger()->addMessage($message, 'status');
  }

  /**
   * @inheritDoc
   */
  public function deleteFinish($success, $results, array $operations) {
    if ($success) {
      $message = t('@count deleted successfully', ['@count' => count($results)]);
    }
    else {
      $message = t('Finished with an error.');
    }
    $this->messenger()->addMessage($message, 'status');
  }

}