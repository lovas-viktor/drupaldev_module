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

    return parent::checkAccess($entity, $operation, $account);
  }

}

