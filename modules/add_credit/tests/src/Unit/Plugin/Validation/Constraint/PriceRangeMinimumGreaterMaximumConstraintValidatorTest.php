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

use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\PriceRangeItem;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\PriceRangeMinimumGreaterMaximumConstraint;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\PriceRangeMinimumGreaterMaximumConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Tests the PriceRangeMinimumGreaterMaximumConstraint validator.
 *
 * @coversDefaultClass \Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\PriceRangeMinimumTopUpAmountConstraintValidator
 *
 * @group apigee_m10n
 * @group apigee_m10n_unit
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_unit
 */
class PriceRangeMinimumGreaterMaximumConstraintValidatorTest extends UnitTestCase {

  /**
   * Tests PriceRangeMinimumGreaterMaximumConstraintValidator::validate().
   *
   * @param \Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\PriceRangeItem $value
   *   The price range field instance.
   * @param bool $valid
   *   TRUE if valid is expected.
   *
   * @dataProvider providerValidate
   */
  public function testValidate(PriceRangeItem $value, bool $valid) {
    $constraint = new PriceRangeMinimumGreaterMaximumConstraint();
    $validator = new PriceRangeMinimumGreaterMaximumConstraintValidator();

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
      $value = $this->createMock(PriceRangeItem::class);
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
