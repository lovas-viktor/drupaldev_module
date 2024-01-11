<?php

namespace Drupal\search_api_grouping\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Item\Item;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * This processor allows you to denormalize items.
 *
 * (see https://issues.apache.org/jira/browse/SOLR-10894 and
 * https://mail-archives.apache.org/mod_mbox/lucene-solr-user/201805.mbox/%3cCAE4tqLPXMDA8y3hzXXkJUtTm6jvUX8XZ0H6P5itcFPgmr1bQZA@mail.gmail.com%3e)
 *
 * @SearchApiProcessor(
 *   id = "denormalization",
 *   label = @Translation("Denormalization"),
 *   description = @Translation("This processor allows you to configure which multivalue fields are used for denormalization."),
 *   stages = {
 *     "add_properties" = 0,
 *     "alter_items" = -10,
 *   },
 * )
 */
class Denormalize extends FieldsProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();

    $configuration += [
      'permutation_limit' => NULL,
      'denormalization_field' => '',
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $denormalization_field = &$form_state->getValue('denormalization_field');
    $denormalization_field = array_keys(array_filter($denormalization_field));
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * Return the settings form for this processor.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['permutation_limit'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => t('Permutation limit'),
      '#description' => t('Defines how permutations should be processed. Can become handy if you just want to have one item of a multi-value field. Leave empty for no limit.'),
      '#weight' => 1,
    ];

    $options = [];
    foreach ($this->getIndex()->getFields() as $field_name => $field) {
      $label = $field->getLabel();
      if (stristr($field_name, ':')) {
        list($field_name, $property) = explode(':', $field_name, 2);
      }
      if (stristr($label, "»")) {
        list($type, $label) = explode('»', $label, 2);
      }
      if (($field_info = FieldStorageConfig::loadByName('node', $field_name))) {
        if (!empty($field_info->getCardinality()) && ($field_info->getCardinality() == -1 || $field_info->getCardinality() > 1)) {
          \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('node');
          $options[$field_name] = $label . ' (' . $field_name . ')';
          $form['permutation_limit'][$field_name] = [
            '#type' => 'textfield',
            '#title' => $label,
            '#size' => 4,
            '#maxlength' => 10,
            '#states' => [
              'visible' => [
                ':input[name$="[denormalization_field][' . $field_name . ']"]' => ['checked' => TRUE],
              ],
            ],
            '#default_value' => !empty($this->configuration['permutation_limit'][$field_name]) ? $this->configuration['permutation_limit'][$field_name] : NULL,
          ];
        }
      }
    }
    $form['fields']['#options'] = $options;

    // Re-use but modify the default form element.
    $form['fields']['#type'] = 'checkboxes';
    unset($form['fields']['#attributes']);

    $form['denormalization_field'] = $form['fields'];
    $form['fields']['#access'] = FALSE;

    $form['denormalization_field'] = [
      '#title' => t('The field to use to denormalize the items to index.'),
      '#description' => t('The field hast to be selected for indexing to use it for denormalization.'),
      '#default_value' => isset($this->configuration['denormalization_field']) ? $this->configuration['denormalization_field'] : NULL,
    ] + $form['denormalization_field'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    $all_nodes = [];
    foreach ($items as $item_id => $item) {
      $node = $this->getDenormalizationFields();
      foreach ($node as $field_name => $permutation_limit) {
        $values = $item->getField($field_name)->getValues();
        if (!empty($item->getField($field_name)->getValues())) {
          $all_nodes[$item_id][$field_name] = $values;
        }
      }
    }

    // All denormalized items.
    $denormalized_items = [];
    // Loop through every item.
    foreach ($items as $original_item_id => $original_item) {
      $part_denormalized_items[$original_item_id] = $original_item;
      $original_fields = $all_nodes[$original_item_id];
      // Loop through the fields to denormalize on.
      foreach ($original_fields as $field_name => $field) {
        // Loop through the already denormalized items
        // If the item has not been denormalized before,
        // the original item gets denormalized.
        foreach ($part_denormalized_items as $part_denormalized_item_name => $part_denormalized_item) {
          foreach ($field as $field_value) {
            $part_denormalized_items[$part_denormalized_item_name . $field_name . $field_value]
              = $this->createDocument($part_denormalized_item, $field_name, $field_value);
          }
          unset($part_denormalized_items[$part_denormalized_item_name]);
        }
        unset($part_denormalized_items[$original_item_id]);
      }
      $denormalized_items += $part_denormalized_items;
      $part_denormalized_items = [];
    }
    $items = $denormalized_items;
  }

  /**
   * Returns the fields to denormalize on.
   *
   * @return array
   *   Associative list of fields to use for denormalization. The value in the
   *   array defines the permutation limit. 0 means no limit.
   */
  public function getDenormalizationFields() {
    $fields = &drupal_static(__FUNCTION__, []);
    if (empty($fields)) {
      $fields = array_filter($this->configuration['denormalization_field']);
      foreach ($fields as $field_name => $field) {
        $fields[$field_name] = 0;
        if (!empty($this->configuration['permutation_limit'][$field]) && is_numeric($this->configuration['permutation_limit'][$field])) {
          $fields[$field_name] = (int) $this->configuration['permutation_limit'][$field];
        }
      }
    }
    return $fields;
  }

  /**
   * Create a denormalized item for indexing.
   *
   * @param \Drupal\search_api\Item\Item $item
   *   The item to index.
   * @param string $field_name
   *   The field name.
   * @param mixed $value
   *   The value of the field.
   *
   * @return \Drupal\search_api\Item\Item
   *   Denormalized item to index.
   */
  protected function createDocument(Item $item, $field_name, $value) {
    $item = clone $item;
    $item->getField($field_name)->setValues([$value]);
    return $item;
  }

  /**
   * {@inheritdoc}
   */
  protected function testType($type) {
    return $this->getDataTypeHelper()
      ->isTextType($type, ['text', 'string', 'integer']);
  }

}
