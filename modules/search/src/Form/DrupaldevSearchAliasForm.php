<?php

namespace Drupal\drupaldev_search\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the drupaldev - search alias entity edit forms.
 */
class DrupaldevSearchAliasForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New drupaldev - search alias %label has been created.', $message_arguments));
        $this->logger('drupaldev_search')->notice('Created new drupaldev - search alias %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The drupaldev - search alias %label has been updated.', $message_arguments));
        $this->logger('drupaldev_search')->notice('Updated drupaldev - search alias %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.drupaldev_search_alias.collection');

    return $result;
  }

}
