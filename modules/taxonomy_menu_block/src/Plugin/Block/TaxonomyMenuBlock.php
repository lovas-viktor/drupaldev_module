<?php

namespace Drupal\taxonomy_menu_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Taxonomy rendered as a block.
 *
 * @Block(
 *   id = "taxonomy_menu_block",
 *   admin_label = @Translation("Taxonomy menu block")
 * )
 */
class TaxonomyMenuBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'taxonomy_menu_block',
      '#tree' => $this->buildMenu(),
      '#attributes' => [
        'class' => [$this->configuration['block_classes']],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'taxonomy' => '',
      'block_classes' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $vocabs = Vocabulary::loadMultiple();
    $list = [];

    foreach ($vocabs as $vid => $vocab) {
      $list[$vid] = $vocab->get('name');
    }

    $form['taxonomy'] = [
      '#type' => 'select',
      '#title' => $this->t('Taxonomy'),
      '#default_value' => $this->configuration['taxonomy'],
      '#options' => $list,
    ];

    $form['block_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional classes for block'),
      '#default_value' => $this->configuration['block_classes'],
    ];

    return $form;
  }

  /**
   * Builds the menu.
   *
   * @return array
   *   Complete menu array.
   */
  public function buildMenu() {
    $vocabulary = $this->configuration['taxonomy'];

    if (empty($vocabulary)) {
      return [];
    }

    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $items = \Drupal::service('entity_type.manager')
      ->getStorage("taxonomy_term")
      ->loadTree($vocabulary, $parent = 0, $max_depth = NULL, $load_entities = FALSE, $language);

    return $this->getTaxonomyTree($items);
  }

  /**
   * Returns a nested array of taxonomy terms.
   *
   * @param array $tree
   *   Array of taxonomy terms.
   *
   * @return array
   *   A multi-dimensional array where each element consists of two keys:
   *   - (stdClass) data: the unaltered taxonomy term
   *   - (array) children: an array of child terms in the same array format
   */
  public function getTaxonomyTree(array $tree) {
    // Put all the terms into a new array and map their each term's children
    // in a temporary _children array.
    $terms = [];

    foreach ($tree as $term) {
      $hide_term = FALSE;

      \Drupal::moduleHandler()
        ->alter('taxonomy_menu_block_term_visibility', $hide_term, $term->tid);

      if ($hide_term) {
        continue;
      }


      $terms[$term->tid] = [
        'term' => $term,
        'below' => [],
        '_children' => [],
        'attributes' => [
          'class' => '',
        ],
      ];

      // Add icon if class exists: "with-icons"
      $terms[$term->tid]['icon'] = FALSE;
      if (strpos($this->configuration['block_classes'], 'with-icons') !== FALSE) {
        // Need to load term first.
        $termObject = Term::load($term->tid);
        if ($termObject instanceof Term) {
          //$terms[$term->tid]['icon'] = $termObject->get('field_category_icon')->view();
        }
      }

      foreach ($term->parents as $tid) {
        // Check for empty parent.
        if (!empty($tid)) {
          $terms[$tid]['url'] = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $tid]);
          $terms[$tid]['_children'][$term->tid] = $term->tid;
          $terms[$tid]['is_expanded'] = TRUE;
        }
      }
    }

    while (TRUE) {

      $break = TRUE;

      foreach ($terms as $tid => &$term) {
        $terms[$tid]['url'] = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $tid]);

        if (\Drupal::service('path.current')
            ->getPath() == $terms[$tid]['url']->toString()) {
          $terms[$tid]['in_active_trail'] = TRUE;

          $parents = $this->getAllParents($terms, $tid);

          foreach ($parents as $parent) {
            $terms[$parent->tid]['in_active_trail'] = TRUE;
          }
        }

        // If this term has children, it can't be moved yet. If it has no
        // parents it also can't be moved.
        if (!empty($term['_children']) || empty($term['term']->parents[0])) {
          continue;
        }


        // Remove the temporary children array, it's now empty and of no use.
        unset($term['_children']);

        // Move the term under it's parents.
        foreach ($term['term']->parents as $parent) {

          // Check for empty parent.
          if (!empty($parent)) {

            // Put term under parent, and update the parent's temporary children
            // array so we know this child has been processed.
            $terms[$parent]['below'][] = $term;
            unset($terms[$parent]['_children'][$tid]);

            $terms[$parent]['is_expanded'] = TRUE;

            // As we have altered the array, we need to loop through it again.
            $break = FALSE;

          }
        }

        // Remove this term from the base array.
        unset($terms[$tid]);

        // If the temporary children array for this term is now empty, remove
        // it. We do this check so that it's removed from the top level terms.
        if (empty($term['_children'])) {
          unset($term['_children']);
        }
      }

      if ($break) {
        break;
      }
    }

    // Re-key the array to remove the term ID based indexes from the top level.
    return array_values($terms);
  }

  /**
   * Get parents for specific term.
   *
   * @param array $terms
   * @param int $tid
   * @param array $result
   *
   * @return array|mixed
   */
  public function getAllParents($terms, $tid, &$result = []) {
      if (isset($terms[$tid])) {
          $term = $terms[$tid]['term'];
          $result[] = $term;
          if (!empty($term->parents)) {
              foreach ($term->parents as $parent_tid) {
                  $this->getAllParents($terms, $parent_tid, $result);
              }
          }
      }
      return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['taxonomy'] = $form_state->getValue('taxonomy');
    $this->configuration['block_classes'] = $form_state->getValue('block_classes');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), []);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $additional_tags = [];

    return Cache::mergeTags($cache_tags, $additional_tags);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
