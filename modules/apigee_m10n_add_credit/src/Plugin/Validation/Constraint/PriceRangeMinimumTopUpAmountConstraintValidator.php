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
use Drupal\apigee_m10n\MonetizationInterface;
use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\PriceRangeItem;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the PriceRangeMinimumTopUpAmount constraint.
 */
class PriceRangeMinimumTopUpAmountConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The monetization service.
   *
   * @var \Drupal\apigee_m10n\MonetizationInterface
   */
  protected $monetization;

  /**
   * The currency formatter service.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * PriceRangeMinimumTopUpAmountConstraintValidator constructor.
   *
   * @param \Drupal\apigee_m10n\MonetizationInterface $monetization
   *   The monetization service.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter service.
   */
  public function __construct(MonetizationInterface $monetization, CurrencyFormatterInterface $currency_formatter) {
    $this->monetization = $monetization;
    $this->currencyFormatter = $currency_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('apigee_m10n.monetization'),
      $container->get('commerce_price.currency_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!($value instanceof PriceRangeItem)) {
      throw new UnexpectedTypeException($value, PriceRangeItem::class);
    }

    $price_range = $value->getValue();

    if (!isset($price_range['minimum'])) {
      return;
    }

    $currency_code = $price_range['currency_code'];
    $minimum_top_up_amount = $this->getMinimumTopUpAmountForCurrency($currency_code);
    if ($price_range['minimum'] < $minimum_top_up_amount) {
      $this->context->addViolation($constraint->message, [
        '@currency_code' => $currency_code,
        '@amount' => $this->currencyFormatter->format($minimum_top_up_amount, $currency_code, [
          'currency_display' => 'code',
        ]),
      ]);
    }
  }

  /**
   * Get the minimum top up amount for a currency.
   *
   * @param string $currency_code
   *   The currency code. Example: usd or aud.
   *
   * @return float|int|null
   *   The minimum top up amount.
   */
  protected function getMinimumTopUpAmountForCurrency(string $currency_code) {
    $currency_code = strtolower($currency_code);

    /** @var \Apigee\Edge\Api\Monetization\Entity\SupportedCurrencyInterface[] $supported_currencies */
    $supported_currencies = $this->monetization->getSupportedCurrencies();
    if (isset($supported_currencies[$currency_code])) {
      return $supported_currencies[$currency_code]->getMinimumTopUpAmount();
    }

    return 0;
  }

}
