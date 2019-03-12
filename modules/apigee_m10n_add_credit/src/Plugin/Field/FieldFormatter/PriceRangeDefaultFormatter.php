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

namespace Drupal\apigee_m10n_add_credit\Plugin\Field\FieldFormatter;

use Drupal\commerce_price\Plugin\Field\FieldFormatter\PriceDefaultFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Plugin implementation of the 'price_range_default' formatter.
 *
 * @FieldFormatter(
 *   id = "price_range_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "apigee_price_range"
 *   }
 * )
 */
class PriceRangeDefaultFormatter extends PriceDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $options = $this->getFormattingOptions();
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $this->currencyFormatter->format($item->default, $item->currency_code, $options),
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
            'country',
          ],
        ],
      ];
    }

    return $elements;
  }

}
