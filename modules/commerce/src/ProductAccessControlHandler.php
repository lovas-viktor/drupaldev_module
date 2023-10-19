<?php

namespace Drupal\drupaldev_commerce;

use Drupal\commerce_store\Entity\EntityStoresInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\EntityAccessControlHandler;
use Drupal\Core\Language\LanguageInterface;
use rupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Language\Language;

class ProductAccessControlHandler extends EntityAccessControlHandler {

  protected $currentStore;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);

    $this->currentStore = \Drupal::service('commerce_store.current_store')->getStore();

  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $stores = $entity->getStoreIds();

    if(!empty(array_intersect(['administrator', 'store_admin'], \Drupal::currentUser()->getRoles())) || \Drupal::currentUser()->getAccountName() == 'admin'){
      return parent::checkAccess($entity, $operation, $account);
    }

    if (empty($stores)) {
      return AccessResult::forbidden();
    }
    else if (!in_array($this->currentStore->id(), $stores)) {
      return AccessResult::forbidden();
    }

    // Checking if Content language detection is enabled.
    if (in_array(LanguageInterface::TYPE_CONTENT, \Drupal::languageManager()->getLanguageTypes())) {
      $language = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    }
    else {
      $language = \Drupal::languageManager()->getCurrentLanguage();
    }

    if (is_object($entity) && $operation == 'view') {
      /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
      $entity_repository = \Drupal::service('entity.repository');
      $product_translation = $entity_repository->getTranslationFromContext($entity, $language->getId());
      $product_language = $product_translation->language()->getId();

      // Ignoring the language is neutral and not applicable.
      if ($product_language != Language::LANGCODE_NOT_SPECIFIED &&
        $product_language != Language::LANGCODE_NOT_APPLICABLE
      ) {
        if ($product_language != $language->getId()) {
          return AccessResult::forbidden();
        }
      }
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}

