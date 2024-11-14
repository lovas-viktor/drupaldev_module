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
      drupaldev_search_clean_aliases ($created_before);
  }

}
