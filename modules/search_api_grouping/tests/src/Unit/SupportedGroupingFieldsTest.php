<?php

namespace Drupal\Tests\search_api_grouping\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\Tests\search_api\Unit\Processor\ProcessorTestTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\search_api_grouping\Plugin\search_api\processor\Grouping;

/**
 * Test the getSupportedFields method.
 *
 * @group search_api_grouping
 */
class SupportedGroupingFieldsTest extends UnitTestCase {

  use StringTranslationTrait;
  use ProcessorTestTrait;

  /**
   * A mock of an index.
   *
   * @var \PHPUnit\Framework\MockObject\MockBuilder
   */
  protected $index;

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL) {
    parent::setUp();

    $this->processor = new Grouping([], 'grouping', []);

    $serviceTranslation = new TranslationManager(new LanguageDefault(['en']));
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('string_translation', $serviceTranslation);

    $this->index = $this->getMockBuilder(IndexInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Check if the supported fields of an index are correct.
   */
  public function testSupportedSortFields() {
    $field_list = [
      'author' => [
        'type' => 'string',
        'label' => 'Author Name',
      ],
      'type' => [
        'type' => 'integer',
        'label' => 'Content Type',
      ],
      'sticky' => [
        'type' => 'boolean',
        'label' => 'Sticky',
      ],
    ];
    $fields = [];
    foreach ($field_list as $item) {
      $field = $this->getMockBuilder(FieldInterface::class)
        ->disableOriginalConstructor()
        ->getMock();
      $field->method('getType')->willReturn($item['type']);
      $field->method('getLabel')->willReturn($item['label']);
      $fields[] = $field;
    }
    $this->index->method('getFields')->willReturn($fields);
    $this->processor->setIndex($this->index);
    $fields = $this->processor->getSupportedFields();
    $this->assertEquals('Author Name', $fields['field_sorts'][0]);
    $this->assertEquals('Content Type', $fields['field_sorts'][1]);
    $this->assertArrayNotHasKey(2, $fields['field_sorts']);
  }

  /**
   * Test if an integer field has the info message in the label.
   */
  public function testSupportedGroupingFields() {
    $fields = [];
    $field = $this->getMockBuilder(FieldInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $field->method('getType')->willReturn('integer');
    $field->method('getLabel')->willReturn('Content Type');
    $fields[] = $field;

    $this->index->method('getFields')->willReturn($fields);
    $this->processor->setIndex($this->index);
    $fields = $this->processor->getSupportedFields();
    $this->assertEquals('Content Type (Converted to string for indexing)', $fields['field_options'][0]);
    $this->assertArrayNotHasKey(2, $fields['field_sorts']);
  }

  /**
   * Test the return of the grouping fields.
   */
  public function testGetGroupingFields() {
    $config = [
      'grouping_fields' => [
        'type' => 'type',
        'uid' => 'uid',
        'author' => 0,
      ],
    ];
    $this->processor->setConfiguration($config);
    $fields = $this->processor->getGroupingFields();
    $this->assertEquals('type', $fields['type']);
    $this->assertEquals('uid', $fields['uid']);
    $this->assertArrayNotHasKey('author', $fields);
  }

}
