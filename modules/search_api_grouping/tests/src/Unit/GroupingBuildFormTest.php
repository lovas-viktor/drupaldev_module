<?php

namespace Drupal\Tests\search_api_grouping\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api_grouping\Plugin\search_api\processor\Grouping;
use Drupal\Tests\search_api\Unit\Processor\ProcessorTestTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Test the getSupportedFields method.
 *
 * @group search_api_grouping
 */
class GroupingBuildFormTest extends UnitTestCase {

  use ProcessorTestTrait;
  use StringTranslationTrait;

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

    $serviceTranslation = new TranslationManager(new LanguageDefault(['en']));
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
    $container->set('string_translation', $serviceTranslation);

    $this->processor = new Grouping([], 'grouping', []);
  }

  /**
   * Test if the form of the processor is built correctly.
   */
  public function testBuildForm() {
    $form_state = $this->getMockBuilder(FormStateInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $form = [];

    $field = $this->getMockBuilder(FieldInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $field->method('getType')->willReturn('string');
    $field->method('getLabel')->willReturn('Author');
    $fields[] = $field;

    $this->index = $this->getMockBuilder(IndexInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->index->method('getFields')->willReturn($fields);

    $this->processor->setIndex($this->index);

    $form = $this->processor->buildConfigurationForm($form, $form_state);

    $this->assertEquals([], $form['grouping_fields']['#default_value']);
    $this->assertEquals([], $form['group_sort']['#default_value']);
    $this->assertEquals('asc', $form['group_sort_direction']['#default_value']);
    $this->assertFalse($form['truncate']['#default_value']);
    $this->assertEquals(1, $form['group_limit']['#default_value']);
  }

}
