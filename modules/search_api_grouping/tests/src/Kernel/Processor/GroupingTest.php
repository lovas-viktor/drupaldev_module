<?php

namespace Drupal\Tests\search_api_grouping\Kernel;

use Drupal\Tests\search_api\Kernel\Processor\ProcessorTestBase;

/**
 * Tests the "Grouping" processor.
 *
 * @group search_api_grouping
 *
 * @see \Drupal\search_api_grouping\Plugin\search_api\processor\Grouping
 */
class GroupingTest extends ProcessorTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'devel',
    'search_api',
    'search_api_solr',
    'search_api_grouping',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL) {
    parent::setUp('grouping');
    $this->installConfig('search_api_grouping');
  }

  /**
   * Tests grouping backend.
   */
  public function testGrouping() {
    $processor = $this->index->getProcessor('grouping');
    $configuration = $processor->getConfiguration();

    // Check if default values are correct.
    $grouping_field = $configuration['grouping_fields'];
    $this->assertEmpty($grouping_field);

    $group_sort = $configuration['group_sort'];
    $this->assertEmpty($group_sort);

    $group_sort_direction = $configuration['group_sort_direction'];
    $this->assertEquals('asc', $group_sort_direction);

    $truncate = $configuration['truncate'];
    $this->assertEquals(0, $truncate);

    $group_limit = $configuration['group_limit'];
    $this->assertEquals('1', $group_limit);

    // Set fields to process.
    $configuration['grouping_fields'] = ['field_tags'];
    $configuration['group_sort'] = ['None'];
    $configuration['group_sort_direction'] = 'desc';
    $configuration['truncate'] = 1;
    $configuration['group_limit'] = '4';

    $processor->setConfiguration($configuration);
    $this->index->setProcessors(['grouping' => $processor]);
    $this->index->save();

    $processor = $this->index->getProcessor('grouping');
    $configuration = $processor->getConfiguration();

    // Check if the new values are correct.
    $grouping_field = $configuration['grouping_fields'][0];
    $this->assertEquals('field_tags', $grouping_field);

    $group_sort = $configuration['group_sort'][0];
    $this->assertEquals('None', $group_sort);

    $group_sort_direction = $configuration['group_sort_direction'];
    $this->assertEquals('desc', $group_sort_direction);

    $truncate = $configuration['truncate'];
    $this->assertEquals(1, $truncate);

    $group_limit = $configuration['group_limit'];
    $this->assertEquals('4', $group_limit);
  }

}
