<?php

namespace Drupal\Tests\search_api_grouping\Unit;

use Drupal\search_api\Query\Query;
use Drupal\search_api_grouping\Plugin\search_api\processor\Grouping;
use Drupal\Tests\search_api\Unit\Processor\ProcessorTestTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Test the getSupportedFields method.
 *
 * @group search_api_grouping
 */
class GroupingSolrTest extends UnitTestCase {

  use ProcessorTestTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->processor = new Grouping([], 'grouping', []);
  }

  /**
   * Test if the setOption Method is called with the expected values.
   */
  public function testPreProcessSearchQuery() {
    $query = $this->getMockBuilder(Query::class)
      ->disableOriginalConstructor()
      ->getMock();
    // Set configurations for the Grouping processor.
    $config = [
      'grouping_fields' => [
        'type' => 'type',
      ],
      'truncate' => TRUE,
      'group_limit' => 3,
      'group_sort' => 'type',
    ];
    $this->processor->setConfiguration($config);

    // Set expected option array.
    $query_option = [
      'use_grouping' => TRUE,
      'grouping_fields' => [
        'type' => 'type',
      ],
      'truncate' => TRUE,
      'group_limit' => 3,
      'group_sort' => [
        'type' => 'asc',
      ],
    ];

    $query->expects($this->once())
      ->method('setOption')
      ->with(
        $this->identicalTo('search_api_grouping'),
        $this->identicalTo($query_option)
      );
    $this->processor->preprocessSearchQuery($query);
  }

}
