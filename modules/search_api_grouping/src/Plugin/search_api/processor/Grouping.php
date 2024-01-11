<?php

namespace Drupal\search_api_grouping\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;
use Drupal\search_api\Query\QueryInterface;

/**
 * Processor for grouping up items on behalf of user defined fields.
 *
 * (see https://issues.apache.org/jira/browse/SOLR-10894 and
 * https://mail-archives.apache.org/mod_mbox/lucene-solr-user/201805.mbox/%3cCAE4tqLPXMDA8y3hzXXkJUtTm6jvUX8XZ0H6P5itcFPgmr1bQZA@mail.gmail.com%3e)
 *
 * @SearchApiProcessor(
 *   id = "grouping",
 *   label = @Translation("Grouping"),
 *   description = @Translation("This processor will group the result items based on the configured fields"),
 *   stages = {
 *     "add_properties" = 0,
 *     "postprocess_query" = 0,
 *     "preprocess_query" = -6,
 *   },
 * )
 */
class Grouping extends FieldsProcessorPluginBase implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();

    $configuration += [
      'grouping_fields' => [],
      'group_sort' => [],
      'group_sort_direction' => 'asc',
      'truncate' => FALSE,
      'group_limit' => 1,
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $grouping_fields = &$form_state->getValue('grouping_fields');
    $grouping_fields = array_keys(array_filter($grouping_fields));
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * Return the settings form for this processor.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $supported_fields = $this->getSupportedFields();
    $form['grouping_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fields to collapse on'),
      '#options' => $supported_fields['field_options'],
      '#attributes' => ['class' => ['search-api-checkboxes-list']],
      '#description' => $this->t('Choose the fields upon which to collapse the results into groups. Note that while selecting multiple fields is technicially supported, it may result in unexpected behaviour.'),
      '#default_value' => $this->configuration['grouping_fields'],
    ];

    $form['group_sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Group sort'),
      '#options' => $supported_fields['field_sorts'],
      '#description' => $this->t('Choose the field by to sort within each group, the groups themselves will be sorted by the main query sorts.'),
      '#default_value' => $this->configuration['group_sort'],
    ];

    $form['group_sort_direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Group sort direction'),
      '#options' => ['asc' => $this->t('Ascending'), 'desc' => $this->t('Descending')],
      '#default_value' => $this->configuration['group_sort_direction'],
    ];

    $form['truncate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Truncate results before facets'),
      '#description' => $this->t('If checked, facet counts are based on the most relevant document of each group matching the query, otherwise they are calculated for all documents before grouping.'),
      '#default_value' => $this->configuration['truncate'],
    ];

    $form['group_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Results per group'),
      '#description' => $this->t('The number of results are limited per group. By default, 1 result per group is returned.'),
      '#default_value' => $this->configuration['group_limit'],
      '#element_validate' => ['element_validate_integer_positive'],
      '#size' => 3,
    ];

    return $form;
  }

  /**
   * Returns an array of supported fields to choose of.
   *
   * This function respects the server behind the index to provide only valid
   * fields.
   *
   * @return array
   *   An associative array with child arrays for the supported fields for each
   *   feature:
   *   [
   *    'field_options' => [],
   *    'field_sorts' => [],
   *    'field' => [],
   *   ];
   */
  public function getSupportedFields() {
    $fields = $this->index->getFields();
    $supported_fields = [
      'field_options' => [],
      'field_sorts' => [
        '' => $this->t('None'),
        'search_api_relevance' => $this->t('Score/Relevance'),
      ],
      'default_fields' => [],
    ];

    foreach ($fields as $name => $field) {
      if ($field->getType() == 'string' || $field->getType() == 'integer') {
        $conversion_msg = ($field->getType() != 'string') ? ' (' . $this->t('Converted to string for indexing') . ')' : NULL;
        $supported_fields['field_options'][$name] = $field->getLabel() . $conversion_msg;
        if (!empty($default_fields[$name]) || (!isset($this->configuration['grouping_fields']) && $this->testField($name, $field))) {
          $supported_fields['default_fields'][$name] = $name;
        }
        $supported_fields['field_sorts'][$name] = $field->getLabel();
      }
    }
    return $supported_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    $grouping_fields = $this->getGroupingFields();
    if (!empty($grouping_fields)) {
      // We move the options from our options array into where the Solr Service
      // is expecting them.
      $options = [
        'use_grouping' => TRUE,
        'grouping_fields' => $grouping_fields,
        'truncate' => isset($this->configuration['truncate']) ? $this->configuration['truncate'] : TRUE,
        'group_limit' => isset($this->configuration['group_limit']) ? $this->configuration['group_limit'] : NULL,
        'group_sort' => [],
      ];
      if (!empty($this->configuration['group_sort'])) {
        $options['group_sort'][$this->configuration['group_sort']] = isset($this->configuration['group_sort_direction']) ? $this->configuration['group_sort_direction'] : 'asc';
      }
      $query->setOption('search_api_grouping', $options);
    }
  }

  /**
   * Returns the fields to group on.
   *
   * @return array
   *   The list of fields to use for grouping.
   */
  public function getGroupingFields() {
    $fields = $this->configuration['grouping_fields'];
    foreach ($fields as $key => $field) {
      if ($field === 0) {
        unset($fields[$key]);
      }
    }
    return $fields;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $results_per_group = $form['group_limit']['#value'];
    if (!ctype_digit($results_per_group)) {
      $title = $form["group_limit"]["#title"];
      $form_state->setError($form["group_limit"], sprintf('%s must be numeric', $title));
    }
  }

}
