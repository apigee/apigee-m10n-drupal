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
use Drupal\apigee_m10n_add_credit\AddCreditProductEntityManagerInterface;
use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\TopUpAmountItem;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\TopUpAmountNumberOutOfRangeConstraint;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\TopUpAmountUnitPriceConstraint;
use Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\TopUpAmountUnitPriceConstraintValidator;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\CurrencyFormatter;
use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Tests the TopUpAmountUnitPriceConstraint validator.
 *
 * @coversDefaultClass \Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\TopUpAmountUnitPriceConstraintValidator
 *
 * @group apigee_m10n
 * @group apigee_m10n_unit
 * @group apigee_m10n_add_credit
 * @group apigee_m10n_add_credit_unit
 */
class TopUpAmountUnitPriceConstraintValidatorTest extends TopUpAmountNumberOutOfRangeConstraintValidatorTest {

  /**
   * Tests TopUpAmountUnitPriceConstraintValidator::validate().
   *
   * @param mixed $value
   *   The price field instance.
   * @param bool $valid
   *   TRUE if valid is expected.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter service.
   *
   * @dataProvider providerValidate
   */
  public function testValidate($value, bool $valid, CurrencyFormatterInterface $currency_formatter) {
    $constraint = new TopUpAmountUnitPriceConstraint();

    $add_credit_product_entity_manager = $this->createMock(AddCreditProductEntityManagerInterface::class);
    $add_credit_product_entity_manager->expects($this->any())
      ->method('getApigeeTopUpAmountField')
      ->willReturn($value);

    $validator = new TopUpAmountUnitPriceConstraintValidator($currency_formatter, $add_credit_product_entity_manager);

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
          'minimum' => 10.00,
          'maximum' => 20.00,
          'number' => 15.00,
          'currency_code' => 'USD',
        ],
        'price' => [
          'number' => 12.00,
          'currency_code' => 'USD',
        ],
        'message' => NULL,
        'valid' => TRUE,
      ],
      [
        'range' => [
          'minimum' => 10.00,
          'maximum' => 20.00,
          'number' => 15.00,
          'currency_code' => 'USD',
        ],
        'price' => [
          'number' => 5.00,
          'currency_code' => 'USD',
        ],
        'message' => $constraint->rangeMessage,
        'valid' => FALSE,
      ],
      [
        'range' => [
          'minimum' => 10.00,
          'maximum' => NULL,
          'number' => 15.00,
          'currency_code' => 'USD',
        ],
        'price' => [
          'number' => 5.00,
          'currency_code' => 'USD',
        ],
        'message' => $constraint->minMessage,
        'valid' => FALSE,
      ],
      [
        'range' => [
          'minimum' => 10.00,
          'maximum' => 20.00,
          'number' => 15.00,
          'currency_code' => 'USD',
        ],
        'price' => [
          'number' => 12.00,
          'currency_code' => 'AUD',
        ],
        'message' => $constraint->currencyMessage,
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
        ->willReturn(new Price((string) $case['price']['number'], $case['price']['currency_code']));

      $items = $this->createMock(FieldItemListInterface::class);
      $items->expects($this->any())
        ->method('first')
        ->willReturn($value);

      $definition = $this->createMock(FieldDefinitionInterface::class);
      $definition->expects($this->any())
        ->method('getType')
        ->willReturn('apigee_top_up_amount');

      $variation = $this->createMock(ProductVariationInterface::class);
      $variation->expects($this->any())
        ->method('getFieldDefinitions')
        ->willReturn([$definition]);
      $variation->expects($this->any())
        ->method('get')
        ->willReturn($items);

      $order = $this->createMock(OrderItemInterface::class);
      $order->expects($this->any())
        ->method('getPurchasedEntity')
        ->willReturn($variation);

      $list = $this->createMock(FieldItemListInterface::class);
      $list->expects($this->any())
        ->method('getEntity')
        ->willReturn($order);

      $value->expects($this->any())
        ->method('getParent')
        ->willReturn($list);

      $currencyFormatter = $this->createMock(CurrencyFormatterInterface::class);
      $currencyFormatter->expects($this->any())
        ->method('format')
        ->willReturn("USD10.00");

      $data[] = [$value, $case['valid'], $currencyFormatter];
    }

    return $data;
  }

}
