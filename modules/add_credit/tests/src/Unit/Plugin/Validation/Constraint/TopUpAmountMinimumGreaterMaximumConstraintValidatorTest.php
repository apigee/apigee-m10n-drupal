<?php

/*
 * Copyright 2018 Google Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2 as published by the
 * Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public
 * License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

namespace Drupal\Tests\apigee_m10n_add_credit\Unit\Plugin\Validation\Constraint;

use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\TopUpAmountItem;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\TopUpAmountMinimumGreaterMaximumConstraint;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\TopUpAmountMinimumGreaterMaximumConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Tests the TopUpAmountMinimumGreaterMaximumConstraint validator.
 *
 * @coversDefaultClass \Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\TopUpAmountMinimumAmountConstraintValidator
 *
 * @group apigee_m10n
 * @group apigee_m10n_unit
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_unit
 */
class TopUpAmountMinimumGreaterMaximumConstraintValidatorTest extends UnitTestCase {

  /**
   * Tests TopUpAmountMinimumGreaterMaximumConstraintValidator::validate().
   *
   * @param \Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\TopUpAmountItem $value
   *   The apigee_top_up_amount field instance.
   * @param bool $valid
   *   TRUE if valid is expected.
   *
   * @dataProvider providerValidate
   */
  public function testValidate(TopUpAmountItem $value, bool $valid) {
    $constraint = new TopUpAmountMinimumGreaterMaximumConstraint();
    $validator = new TopUpAmountMinimumGreaterMaximumConstraintValidator();

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($valid ? $this->never() : $this->once())
      ->method('addViolation');
    $validator->initialize($context);

    $validator->validate($value, $constraint);
  }

  /**
   * Provides data for self::testValidate().
   */
  public function providerValidate() {
    $data = [];

    $cases = [
      ['minimum' => 5.00, 'maximum' => 10.00, 'valid' => TRUE],
      ['minimum' => 10.50, 'maximum' => 10.50, 'valid' => TRUE],
      ['minimum' => 15.00, 'maximum' => 10.00, 'valid' => FALSE],
    ];

    foreach ($cases as $case) {
      $value = $this->createMock(TopUpAmountItem::class);
      $value->expects($this->any())
        ->method('getValue')
        ->willReturn([
          'minimum' => $case['minimum'],
          'maximum' => $case['maximum'],
        ]);

      $data[] = [$value, $case['valid']];
    }

    return $data;
  }

}
