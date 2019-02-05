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

namespace Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\TopUpAmountItem;
use Drupal\commerce_price\Price;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the 'TopUpAmountDefaultOutOfRange' constraint.
 *
 * @package Drupal\apigee_m10n_add_credit.
 */
class TopUpAmountNumberOutOfRangeConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The currency formatter service.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * PriceRangeValidatorBase constructor.
   *
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter service.
   */
  public function __construct(CurrencyFormatterInterface $currency_formatter) {
    $this->currencyFormatter = $currency_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_price.currency_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $this->validateInstance($value);
    $price = $this->getPrice($value);
    $range = $this->getRange($value);

    // Do nothing if we do not have a price or a range.
    if (!$price || empty($price->getNumber()) || empty($price->getCurrencyCode()) || empty($range)) {
      return;
    }

    // Validate currency.
    /** @var \Drupal\apigee_m10n_add_credit\Plugin\Validation\Constraint\TopUpAmountNumberOutOfRangeConstraint $constraint */
    if ($price->getCurrencyCode() !== $range['currency_code']) {
      $this->context->addViolation(t($constraint->currencyMessage));
    }

    // Validate price against range.
    $number = $price->getNumber();
    if (isset($range['minimum']) && isset($range['maximum'])
      && ($number < $range['minimum'] || $number > $range['maximum'])) {
      $this->context->addViolation(t($constraint->rangeMessage, [
        '@minimum' => $this->formatPrice($range['minimum'], $range['currency_code']),
        '@maximum' => $this->formatPrice($range['maximum'], $range['currency_code']),
      ]));
    }
    elseif (isset($range['minimum']) && !isset($range['maximum'])
      && ($number < $range['minimum'])) {
      $this->context->addViolation(t($constraint->minMessage, [
        '@minimum' => $this->formatPrice($range['minimum'], $range['currency_code']),
      ]));
    }
    elseif (isset($range['maximum'])
      && ($number > $range['maximum'])) {
      $this->context->addViolation(t($constraint->maxMessage, [
        '@maximum' => $this->formatPrice($range['maximum'], $range['currency_code']),
      ]));
    }
  }

  /**
   * Validates the instance of value.
   *
   * @param mixed $value
   *   The value instance.
   */
  protected function validateInstance($value): void {
    if (!($value instanceof TopUpAmountItem)) {
      throw new UnexpectedTypeException($value, TopUpAmountItem::class);
    }
  }

  /**
   * Finds the price from the provided range.
   *
   * @param mixed $value
   *   The value instance.
   *
   * @return \Drupal\commerce_price\Price
   *   The price.
   */
  protected function getPrice($value): ?Price {
    return $value->toPrice();
  }

  /**
   * Helper to get the price range.
   *
   * @param mixed $value
   *   The value instance.
   *
   * @return array
   *   An array of price range with minimum, maximum, default and currency code.
   */
  protected function getRange($value): array {
    return $value->toRange();
  }

  /**
   * Helper to format price.
   *
   * @param string $number
   *   The number.
   * @param string $currency_code
   *   The currency code.
   *
   * @return string
   *   The formatted number with currency code prefixed.
   */
  protected function formatPrice(string $number, string $currency_code): string {
    return $this->currencyFormatter->format($number, $currency_code, [
      'currency_display' => 'code',
    ]);
  }

}