<?php

namespace Drupal\drupaldev_search\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\drupaldev_search\Entity\DrupaldevSearchAlias;
use Drupal\facets\FacetInterface;
use Drupal\facets\Result\Result;
use Drupal\facets\Widget\WidgetPluginBase;

/**
 * The links widget.
 *
 * @FacetsWidget(
 *   id = "drupaldev_links",
 *   label = @Translation("List of links (Drupaldev customized)"),
 *   description = @Translation("A simple widget that shows a list of links
 *   with working reset option"),
 * )
 */
class DrupaldevLinksWidget extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'soft_limit' => 0,
        'soft_limit_settings' => [
          'show_less_label' => 'Show less',
          'show_more_label' => 'Show more',
        ],
        'show_reset_link' => FALSE,
        'hide_reset_when_no_selection' => FALSE,
        'reset_text' => $this->t('Show all'),
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $build = parent::build($facet);
    $this->appendWidgetLibrary($build);
    $soft_limit = (int) $this->getConfiguration()['soft_limit'];
    if ($soft_limit !== 0) {
      $show_less_label = $this->getConfiguration()['soft_limit_settings']['show_less_label'];
      $show_more_label = $this->getConfiguration()['soft_limit_settings']['show_more_label'];
      $build['#attached']['library'][] = 'facets/soft-limit';
      $build['#attached']['drupalSettings']['facets']['softLimit'][$facet->id()] = $soft_limit;
      $build['#attached']['drupalSettings']['facets']['softLimitSettings'][$facet->id()]['showLessLabel'] = $show_less_label;
      $build['#attached']['drupalSettings']['facets']['softLimitSettings'][$facet->id()]['showMoreLabel'] = $show_more_label;
    }
    if ($facet->getUseHierarchy()) {
      $build['#attached']['library'][] = 'facets/drupal.facets.hierarchical';
    }

    $results = $facet->getResults();
    if ($this->getConfiguration()['show_reset_link'] && count($results) > 0 && (!$this->getConfiguration()['hide_reset_when_no_selection'] || $facet->getActiveItems())) {
      // Add reset link.
      $max_items = array_sum(array_map(function ($item) {
        return $item->getCount();
      }, $results));

      $urlProcessorManager = \Drupal::service('plugin.manager.facets.url_processor');
      $url_processor = $urlProcessorManager->createInstance($facet->getFacetSourceConfig()
        ->getUrlProcessorName(), ['facet' => $facet]);
      $active_filters = $url_processor->getActiveFilters();

      unset($active_filters[$facet->id()]);

      $urlGenerator = \Drupal::service('facets.utility.url_generator');
      if ($active_filters) {
        $url = $urlGenerator->getUrl($active_filters, FALSE);
      }
      else {
        $facet_id = $facet->getOriginalId();
        $request = \Drupal::request();
        $uri = $request->getRequestUri();

        $url = Url::fromUserInput('/products');
        $alias = str_replace('/products/', '', $uri);

        $search_alias = $this->getSearchAliasFromUri($alias);

        $query_values = [];
        if (!empty($search_alias)) {
          $query_values = $search_alias->getFilterQueryValues();

          foreach ($query_values['f'] as $key => $qv) {
            if (strpos($qv, $facet_id) !== FALSE) {
              unset($query_values['f'][$key]);
            }
          }
        }

        $url->setOption('query', $query_values);
      }

      $result_item = new Result($facet, 'reset_all', $this->t($this->getConfiguration()['reset_text']), $max_items);
      $result_item->setActiveState(FALSE);
      $result_item->setUrl($url);

      // Build item.
      $item = $this->buildListItems($facet, $result_item);

      // Add a class for the reset link wrapper.
      $item['#wrapper_attributes']['class'][] = 'facets-reset';

      // Put reset facet link on first place.
      array_unshift($build['#items'], $item);
    }

    $build['#theme'] = 'facets_item_list__checkbox__color';
    $build['#attributes']['class'][] = 'block-facet--checkbox';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function appendWidgetLibrary(array &$build) {
    $build['#attributes']['class'][] = 'js-facets-checkbox-links';
    $build['#attributes']['data-drupal-facet-show-only-one-result'] = (int) $this->facet->getShowOnlyOneResult();
    $build['#attached']['library'][] = 'facets/drupal.facets.checkbox-widget';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form = parent::buildConfigurationForm($form, $form_state, $facet);

    $options = [50, 40, 30, 20, 15, 10, 5, 3];
    $form['soft_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Soft limit'),
      '#default_value' => $this->getConfiguration()['soft_limit'],
      '#options' => [0 => $this->t('No limit')] + array_combine($options, $options),
      '#description' => $this->t('Limit the number of displayed facets via JavaScript.'),
    ];
    $form['soft_limit_settings'] = [
      '#type' => 'container',
      '#title' => $this->t('Soft limit settings'),
      '#states' => [
        'invisible' => [':input[name="widget_config[soft_limit]"]' => ['value' => 0]],
      ],
    ];
    $form['soft_limit_settings']['show_less_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Show less label'),
      '#description' => $this->t('This text will be used for "Show less" link.'),
      '#default_value' => $this->getConfiguration()['soft_limit_settings']['show_less_label'],
      '#states' => [
        'optional' => [':input[name="widget_config[soft_limit]"]' => ['value' => 0]],
      ],
    ];
    $form['soft_limit_settings']['show_more_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Show more label'),
      '#description' => $this->t('This text will be used for "Show more" link.'),
      '#default_value' => $this->getConfiguration()['soft_limit_settings']['show_more_label'],
      '#states' => [
        'optional' => [':input[name="widget_config[soft_limit]"]' => ['value' => 0]],
      ],
    ];

    $form['show_reset_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show reset link'),
      '#default_value' => $this->getConfiguration()['show_reset_link'],
    ];
    $form['reset_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reset text'),
      '#default_value' => $this->getConfiguration()['reset_text'],
      '#states' => [
        'visible' => [
          ':input[name="widget_config[show_reset_link]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="widget_config[show_reset_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['hide_reset_when_no_selection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide reset link when no facet item is selected'),
      '#default_value' => $this->getConfiguration()['hide_reset_when_no_selection'],
    ];
    return $form;
  }

  /**
   * Returns Drupaldev Search Alias based on alias string.
   *
   * @param string $alias
   *
   * @return \Drupal\drupaldev_search\Entity\DrupaldevSearchAlias|false
   */
  public function getSearchAliasFromUri($alias) {
    $query = \Drupal::entityQuery('drupaldev_search_alias')
      ->condition('alias', $alias);
    $query->condition('langcode', \Drupal::languageManager()
      ->getCurrentLanguage()
      ->getId());
    $query->accessCheck(FALSE);
    $results = $query->execute();
    $search_alias_id = reset($results);

    if (empty($search_alias_id)) {
      return FALSE;
    }

    $search_alias = DrupaldevSearchAlias::load($search_alias_id);

    if (empty($search_alias)) {
      return FALSE;
    }

    return $search_alias;
  }

}
