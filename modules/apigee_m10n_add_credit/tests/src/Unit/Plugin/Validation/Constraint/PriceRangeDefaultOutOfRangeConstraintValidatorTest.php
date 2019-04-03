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

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\PriceRangeItem;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\PriceRangeDefaultOutOfRangeConstraint;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\PriceRangeDefaultOutOfRangeConstraintValidator;
use Drupal\commerce_price\CurrencyFormatter;
use Drupal\Tests\UnitTestCase;
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
   * @param mixed $value
   *   The price range field instance.
   * @param bool $valid
   *   TRUE if valid is expected.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter service.
   *
   * @dataProvider providerValidate
   */
  public function testValidate($value, bool $valid, CurrencyFormatterInterface $currency_formatter) {
    $constraint = new PriceRangeDefaultOutOfRangeConstraint();
    $validator = new PriceRangeDefaultOutOfRangeConstraintValidator($currency_formatter);

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

    $constraint = new PriceRangeDefaultOutOfRangeConstraint();

    $cases = [
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
    ];

    foreach ($cases as $case) {
      $value = $this->createMock(PriceRangeItem::class);
      $value->expects($this->any())
        ->method('getValue')
        ->willReturn($case['range']);

      $currencyFormatter = $this->createMock(CurrencyFormatter::class);
      $currencyFormatter->expects($this->any())
        ->method('format')
        ->willReturn('USD10.00');

      $data[] = [$value, $case['valid'], $currencyFormatter];
    }

    return $data;
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
