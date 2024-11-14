<?php

namespace Drupal\Tests\search_api_grouping\Unit;

use Drupal\Core\Form\FormState;
use Drupal\search_api_grouping\Plugin\search_api\processor\Grouping;
use Drupal\Tests\search_api\Unit\Processor\ProcessorTestTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Test the getSupportedFields method.
 *
 * @group search_api_grouping
 */
class GroupLimitValidationTest extends UnitTestCase {

  use ProcessorTestTrait;

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL) {
    parent::setUp();
    $this->processor = new Grouping([], 'grouping', []);
  }

  /**
   * Test the validation of the processor form.
   */
  public function testFormValidation() {
    // Insert an integer as value.
    $form['group_limit']['#value'] = "3";
    $form['group_limit']['#title'] = 'Form';
    $form['group_limit']['#parents'] = [];
    $form_state = (new FormState())->setValue('group_limit', 3);
    $this->processor->validateConfigurationForm($form, $form_state);
    $this->assertFalse($form_state->hasAnyErrors());
    // Insert a non integer.
    $form['group_limit']['#value'] = "3.2";
    $this->processor->validateConfigurationForm($form, $form_state);
    $this->assertTrue($form_state->hasAnyErrors());
  }

}
