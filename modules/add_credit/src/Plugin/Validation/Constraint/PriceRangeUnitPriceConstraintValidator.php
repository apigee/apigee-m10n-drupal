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

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
use Drupal\commerce_price\Price;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the PriceRangeUnitPrice constraint.
 */
class PriceRangeUnitPriceConstraintValidator extends PriceRangeDefaultOutOfRangeConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validateInstance($value): void {
    if (!($value instanceof PriceItem)) {
      throw new UnexpectedTypeException($value, PriceItem::class);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice($value): ?Price {
    if ($price = $value->getValue()) {
      return Price::fromArray($price);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRange($value): array {
    $order = $value->getParent()->getEntity();
    if ($order instanceof OrderItemInterface) {

      // Get the range from the product variation.
      $purchased_entity = $order->getPurchasedEntity();
      if ($purchased_entity->hasField('apigee_price_range') && !$purchased_entity->get('apigee_price_range')->isEmpty()) {
        $value = $purchased_entity->get('apigee_price_range')->getValue();
        return reset($value);
      }
    }

    return [];
  }

}
