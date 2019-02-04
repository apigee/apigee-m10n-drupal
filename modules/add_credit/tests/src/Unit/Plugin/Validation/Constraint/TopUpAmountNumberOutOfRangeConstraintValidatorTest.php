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
use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\TopUpAmountItem;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\TopUpAmountNumberOutOfRangeConstraint;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\TopUpAmountNumberOutOfRangeConstraintValidator;
use Drupal\commerce_price\CurrencyFormatter;
use Drupal\commerce_price\Price;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Tests the TopUpAmountNumberOutOfRangeConstraint validator.
 *
 * @coversDefaultClass \Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\TopUpAmountNumberOutOfRangeConstraintValidator
 *
 * @group apigee_m10n
 * @group apigee_m10n_unit
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_unit
 */
class TopUpAmountNumberOutOfRangeConstraintValidatorTest extends UnitTestCase {

  /**
   * Tests TopUpAmountNumberOutOfRangeConstraintValidator::validate().
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
    $constraint = new TopUpAmountNumberOutOfRangeConstraint();
    $validator = new TopUpAmountNumberOutOfRangeConstraintValidator($currency_formatter);

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

    $constraint = new TopUpAmountNumberOutOfRangeConstraint();

    $cases = [
      [
        'range' => [
          'minimum' => 5.00,
          'maximum' => 10.00,
          'number' => 6.00,
          'currency_code' => 'USD',
        ],
        'message' => NULL,
        'valid' => TRUE,
      ],
      [
        'range' => [
          'minimum' => 20.00,
          'maximum' => 30.00,
          'number' => 10.00,
          'currency_code' => 'USD',
        ],
        'message' => $constraint->rangeMessage,
        'valid' => FALSE,
      ],
      [
        'range' => [
          'minimum' => 5.00,
          'maximum' => NULL,
          'number' => 3.00,
          'currency_code' => 'USD',
        ],
        'message' => $constraint->minMessage,
        'valid' => FALSE,
      ],
    ];

    foreach ($cases as $case) {
      $value = $this->createMock(TopUpAmountItem::class);
      $value->expects($this->any())
        ->method('toRange')
        ->willReturn($case['range']);

      $value->expects($this->any())
        ->method('toPrice')
        ->willReturn(new Price((string) $case['range']['number'], $case['range']['currency_code']));

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
