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

namespace Drupal\apigee_m10n\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;

/**
 * Plugin implementation of the 'apigee_datestamp' field formatter.
 *
 * @FieldFormatter(
 *   id = "apigee_datestamp",
 *   label = @Translation("Apigee date formatter"),
 *   field_types = {
 *     "apigee_datestamp"
 *   }
 * )
 */
class DatestampFormatter extends TimestampFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $items_numeric = clone $items;
    foreach ($items_numeric as $index => $value) {
      // The `TimestampFormatter` formatter expects values to be numeric.
      $items_numeric[$index] = $value->value->getTimestamp();
    }

    return parent::viewElements($items_numeric, $langcode);
  }

}
