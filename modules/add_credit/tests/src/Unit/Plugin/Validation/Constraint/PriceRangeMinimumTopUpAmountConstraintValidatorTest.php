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

use Apigee\Edge\Api\Monetization\Entity\SupportedCurrency;
use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\apigee_m10n\Monetization;
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\PriceRangeItem;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\PriceRangeMinimumTopUpAmountConstraint;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\PriceRangeMinimumTopUpAmountConstraintValidator;
use Drupal\commerce_price\CurrencyFormatter;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Tests the PriceRangeMinimumTopUpAmountConstraint validator.
 *
 * @coversDefaultClass \Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\PriceRangeMinimumTopUpAmountConstraintValidator
 *
 * @group apigee_m10n
 * @group apigee_m10n_unit
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_unit
 */
class PriceRangeMinimumTopUpAmountConstraintValidatorTest extends UnitTestCase {

  /**
   * Tests PriceRangeMinimumTopUpAmountConstraint::validate().
   *
   * @param \Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\PriceRangeItem $value
   *   The price item field instance.
   * @param bool $valid
   *   TRUE if valid is expected.
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The monetization service.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter service.
   *
   * @dataProvider providerValidate
   */
  public function testValidate(PriceRangeItem $value, bool $valid, MonetizationInterface $monetization, CurrencyFormatterInterface $currency_formatter) {
    $constraint = new PriceRangeMinimumTopUpAmountConstraint();
    $validator = new PriceRangeMinimumTopUpAmountConstraintValidator($monetization, $currency_formatter);

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
      ['minimum' => 13.00, 'currency_code' => 'USD', 'valid' => TRUE],
      ['minimum' => 5.00, 'currency_code' => 'USD', 'valid' => FALSE],
    ];

    foreach ($cases as $case) {
      $value = $this->createMock(PriceRangeItem::class);
      $value->expects($this->any())
        ->method('getValue')
        ->willReturn([
          'minimum' => $case['minimum'],
          'currency_code' => $case['currency_code'],
        ]);

      $supportedCurrency = $this->createMock(SupportedCurrency::class);
      $supportedCurrency->expects($this->any())
        ->method('getMinimumTopUpAmount')
        ->willReturn(11.00);

      $monetization = $this->createMock(Monetization::class);
      $monetization->expects($this->any())
        ->method('getSupportedCurrencies')
        ->willReturn([
          'usd' => $supportedCurrency,
        ]);

      $currencyFormatter = $this->createMock(CurrencyFormatter::class);
      $currencyFormatter->expects($this->any())
        ->method('format')
        ->willReturn('USD11.00');

      $data[] = [$value, $case['valid'], $monetization, $currencyFormatter];
    }

    return $data;
  }

}
