<?php

namespace Drupal\drupaldev_search\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class DrupaldevSearchCommands extends DrushCommands {

  /**
   * Constructs a DrupaldevSearchCommands object.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'drupaldev_search:clean-alias-table', aliases: ['cat'])]
  #[CLI\Argument(name: 'created_before', description: 'Clean aliases created before the given date.')]
  #[CLI\Usage(name: 'drupaldev_search:command-name foo', description: 'Usage description')]
  public function commandName($created_before = "") {
    $database = \Drupal::database();
    if (!empty($created_before)) {
      $timestamp = strtotime($created_before . ' 00:00:00');
      $q = "SELECT id, `param_count` FROM ( SELECT id, LENGTH(`query_path`) - LENGTH(REPLACE(`query_path`, '&', '')) AS `param_count`, created_at FROM drupaldev_search_alias ) AS subquery WHERE created_at <= $timestamp AND param_count <= 2;";
      $query = $database->query($q);
      $result = $query->fetchAll();
    }
    else {
      $query = $database->query("SELECT id, `param_count` FROM ( SELECT id, LENGTH(`query_path`) - LENGTH(REPLACE(`query_path`, '&', '')) AS `param_count`, created_at FROM drupaldev_search_alias ) AS subquery WHERE created_at <= now() - INTERVAL 1 DAY AND param_count <= 2;");
      $result = $query->fetchAll();
    }

    $this->logger()->success(dt(count($result) . ' rows found.'));

    // Prepare array.
    $ids_to_delete = [];
    $num_deleted = 0;
    foreach ($result as $res) {
      $ids_to_delete[] = $res->id;
    }

    if (!empty($ids_to_delete)) {
      $num_deleted = $database->delete('drupaldev_search_alias')
        ->condition('id', $ids_to_delete, 'IN')
        ->execute();
    }

    $this->logger()->success(dt($num_deleted . ' rows deleted.'));
  }

}
