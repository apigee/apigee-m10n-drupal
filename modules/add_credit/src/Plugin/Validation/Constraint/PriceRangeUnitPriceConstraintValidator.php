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
  public function getPrice($value): ?array {
    if ($this->getPurchasedEntity($value) && $price = $value->getValue()) {
      return $price;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRange($value): array {
    if ($purchased_entity = $this->getPurchasedEntity($value)) {
      $value = $purchased_entity->get('apigee_price_range')->getValue();
      return reset($value);
    }

    return [];
  }

  /**
   * Returns the purchased entity with a valid price range field.
   *
   * @param mixed $value
   *   The value instance.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The purchasable entity.
   */
  protected function getPurchasedEntity($value) {
    $order = $value->getParent()->getEntity();
    if (($order instanceof OrderItemInterface)
    && ($purchased_entity = $order->getPurchasedEntity())
    && ($purchased_entity->hasField('apigee_price_range'))) {
      return $purchased_entity;
    }

    return NULL;
  }

}
