<?php

namespace Drupal\drupaldev_search\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\drupaldev_search\DrupaldevSearchAliasInterface;

/**
 * Defines the drupaldev - search alias entity class.
 *
 * @ContentEntityType(
 *   id = "drupaldev_search_alias",
 *   label = @Translation("Search alias"),
 *   label_collection = @Translation("Search alias"),
 *   label_singular = @Translation("search alias"),
 *   label_plural = @Translation("search aliass"),
 *   label_count = @PluralTranslation(
 *     singular = "@count drupaldev - search alias",
 *     plural = "@count drupaldev - search alias",
 *   ),
 *   handlers = {
 *     "list_builder" =
 *   "Drupal\drupaldev_search\DrupaldevSearchAliasListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\drupaldev_search\Form\DrupaldevSearchAliasForm",
 *       "edit" = "Drupal\drupaldev_search\Form\DrupaldevSearchAliasForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" =
 *   "Drupal\drupaldev_search\Routing\DrupaldevSearchAliasHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "drupaldev_search_alias",
 *   admin_permission = "administer drupaldev search alias",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/drupaldev-search-alias",
 *     "add-form" = "/drupaldev-search-alias/add",
 *     "canonical" = "/drupaldev-search-alias/{drupaldev_search_alias}",
 *     "edit-form" = "/drupaldev-search-alias/{drupaldev_search_alias}",
 *     "delete-form" =
 *   "/drupaldev-search-alias/{drupaldev_search_alias}/delete",
 *   },
 *   field_ui_base_route = "entity.drupaldev_search_alias.settings",
 * )
 */
class DrupaldevSearchAlias extends ContentEntityBase implements DrupaldevSearchAliasInterface {

  use StringTranslationTrait;

  public function getPath() {
    $value = $this->get('path')->getValue();

    $array = array_map(function ($path_item) {
      return $path_item['value'];
    }, $value);

    return implode('&', $array);
  }

  public function getAliases() {
    $value = $this->get('old_aliases')->getValue();

    $array = array_map(function ($path_item) {
      return $path_item['value'];
    }, $value);

    return Markup::create(implode('<br/>', $array));
  }

  public function getAlias() {
    return $this->get('alias')->value;
  }

  public function getTitle() {
    return $this->get('title')->value;
  }

  public function getTitleWithFilters() {
    return str_replace('[filters]', $this->getFilterValues(), $this->getTitle());
  }

  public function getMetaDescription() {
    return $this->t($this->get('meta_description')->value);
  }

  public function getMetaDescriptionWithFilters() {
    return str_replace('[filters]', $this->getFilterValues(), $this->getMetaDescription());
  }

  public function getLongDescription() {
    return $this->get('long_description')->value;
  }

  public function getLongDescriptionWithFilters() {
    return str_replace('[filters]', $this->getFilterValues(), $this->getLongDescription());
  }

  public function getFilterQueryValues() {
    $url = Url::fromUserInput('?' . $this->getPath());

    return $url->getOptions()['query'];
  }

  public function getFilterValues() {
    return $this->get('filter_values')->value;
  }

  public function getLangcode() {
    return $this->get('langcode')->value;
  }

  public function getTitlePattern() {
    return $this->t('[filters]');
  }

  public function getDescriptionPattern() {
    return $this->t('[filters] HUF 1,590 delivery, but free delivery for orders over HUF 30,000.');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Set title.
    if ($this->isNew()) {
      $this->set('title', $this->getTitlePattern());
    }

    // Set description.
    if ($this->isNew()) {
      $this->set('meta_description', $this->getDescriptionPattern());
    }

    // Only update old_alias when it's an updated entity.
    if (!$this->isNew() && $this->getAlias() !== $this->original->getAlias()) {
      $old_aliases = $this->get('old_aliases')->getValue();

      $old_aliases[] = ['value' => $this->original->getAlias()];

      $this->set('old_aliases', $old_aliases);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Title'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['meta_description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Meta description'))
      ->setDescription(t('Meta description'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 2,
        'settings' => [
          'rows' => 4,
        ],
      ]);

    $fields['long_description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Long description'))
      ->setDescription(t('Long description'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 3,
        'settings' => [
          'rows' => 4,
        ],
      ]);

    $fields['path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Path'))
      ->setDescription(t('Path.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 4,
      ])
      ->setCardinality(-1)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['alias'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Alias'))
      ->setDescription(t('Alias.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Langcode'))
      ->setDescription(t('Langcode.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['old_aliases'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Old aliases'))
      ->setDescription(t('Old aliases.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setCardinality(-1);

    $fields['filter_values'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Filter values'))
      ->setDescription(t('Filter values.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 7,
      ]);

    $service = \Drupal::service('update.update_hook_registry');
    if ($service->getInstalledVersion('drupaldev_search') >= 9000) {
      $fields['query_path'] = BaseFieldDefinition::create('string_long')
        ->setLabel(t('Query path'))
        ->setDescription(t('Query path.'))
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => 8,
        ]);
      $fields['query_hash'] = BaseFieldDefinition::create('string_long')
        ->setLabel(t('Query hash'))
        ->setDescription(t('Query hash.'))
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => 9,
        ]);
    }

    return $fields;
  }

}
