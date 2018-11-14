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

namespace Drupal\apigee_m10n\TwigExtension;

/**
 * Provides a Twig extension to format currency.
 */
class FormatCurrencyTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('apigee_m10n_format_currency', [$this, 'formatCurrency']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'apigee_m10n.format_currency_twig_extension';
  }

  /**
   * Formats a price according to a specified currency code.
   *
   * Examples:
   * {{ '10.00' | apigee_m10n_format_currency('USD') }}
   *
   * @param mixed $amount
   *   The amount of currency that should be formatted.
   *
   *   Generally a string representation of a float, although a float will also
   *   be accepted.
   * @param string $currency_code
   *   The currency code.
   *
   * @see \CommerceGuys\Intl\Currency\CurrencyRepository::getBaseDefinitions()
   *
   * @return string
   *   A formatted price.
   *
   * @throws \InvalidArgumentException
   */
  public static function formatCurrency($amount, string $currency_code) {
    if (!is_numeric($amount)) {
      throw new \InvalidArgumentException('The "apigee_m10n_format_currency" filter must be passed a numeric amount.');
    }

    return \Drupal::service('apigee_m10n.monetization')->formatCurrency($amount, $currency_code);
  }

}
