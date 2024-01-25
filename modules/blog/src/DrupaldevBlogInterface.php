<?php declare(strict_types = 1);

namespace Drupal\drupaldev_blog;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a drupaldev blog entity type.
 */
interface DrupaldevBlogInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
