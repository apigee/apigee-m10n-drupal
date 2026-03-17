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

use Drupal\Tests\UnitTestCase;
use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\PriceRangeItem;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\PriceRangeDefaultOutOfRangeConstraint;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\PriceRangeDefaultOutOfRangeConstraintValidator;
use Drupal\commerce_price\CurrencyFormatter;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Tests the PriceRangeDefaultOutOfRangeConstraint validator.
 *
 * @coversDefaultClass \Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\PriceRangeDefaultOutOfRangeConstraintValidator
 *
 * @group apigee_m10n
 * @group apigee_m10n_unit
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_unit
 */
class PriceRangeDefaultOutOfRangeConstraintValidatorTest extends UnitTestCase {

  /**
   * Tests PriceRangeDefaultOutOfRangeConstraintValidator::validate().
   *
   * @param array $case
   *   The test case data, containing 'range' and 'valid' keys.
   *
   * @dataProvider providerValidate
   */
  public function testValidate(array $case) {
    $constraint = new PriceRangeDefaultOutOfRangeConstraint();
    $value = $this->createMock(PriceRangeItem::class);
    $value->expects($this->any())
      ->method('getValue')
      ->willReturn($case['range']);

    $currencyFormatter = $this->createMock(CurrencyFormatter::class);
    $currencyFormatter->expects($this->any())
      ->method('format')
      ->willReturn('USD10.00');

    $validator = new PriceRangeDefaultOutOfRangeConstraintValidator($currencyFormatter);

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($case['valid'] ? $this->never() : $this->once())
      ->method('addViolation');
    $validator->initialize($context);

    $validator->validate($value, $constraint);
  }

  /**
   * Provides data for self::testValidate().
   */
  public static function providerValidate() {
    $constraint = new PriceRangeDefaultOutOfRangeConstraint();

    return [
      'valid' => [
        [
          'range' => [
            'minimum' => 5.00,
            'maximum' => 10.00,
            'default' => 6.00,
            'currency_code' => 'USD',
          ],
          'message' => NULL,
          'valid' => TRUE,
        ],
      ],
      'default lower than range' => [
        [
          'range' => [
            'minimum' => 20.00,
            'maximum' => 30.00,
            'default' => 10.00,
            'currency_code' => 'USD',
          ],
          'message' => $constraint->rangeMessage,
          'valid' => FALSE,
        ],
      ],
      'default lower than min' => [
        [
          'range' => [
            'minimum' => 5.00,
            'maximum' => NULL,
            'default' => 3.00,
            'currency_code' => 'USD',
          ],
          'message' => $constraint->minMessage,
          'valid' => FALSE,
        ],
      ],
      'min has comma' => [
        [
          'range' => [
            'minimum' => '5,00',
            'maximum' => NULL,
            'default' => 13.00,
            'currency_code' => 'USD',
          ],
          'message' => $constraint->formatMessage,
          'valid' => FALSE,
        ],
      ],
      'max has comma' => [
        [
          'range' => [
            'minimum' => 5.00,
            'maximum' => '13,00',
            'default' => 13.00,
            'currency_code' => 'USD',
          ],
          'message' => $constraint->formatMessage,
          'valid' => FALSE,
        ],
      ],
      'default is not a number' => [
        [
          'range' => [
            'minimum' => 5.00,
            'maximum' => 35.00,
            'default' => 'Word',
            'currency_code' => 'USD',
          ],
          'message' => $constraint->formatMessage,
          'valid' => FALSE,
        ],
      ],
    ];
  }

}

namespace Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint;

/**
 * Shadow t() system call.
 *
 * @param string $string
 *   A string containing the English text to translate.
 *
 * @return string
 *   The translate string.
 */
function t($string) {
  return $string;
}
