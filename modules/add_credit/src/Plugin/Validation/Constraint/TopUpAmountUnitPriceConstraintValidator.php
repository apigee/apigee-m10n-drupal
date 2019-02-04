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

use Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\TopUpAmountItem;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the TopUpAmountUnitPrice constraint.
 */
class TopUpAmountUnitPriceConstraintValidator extends TopUpAmountNumberOutOfRangeConstraintValidator {

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
  public function getRange($value): array {
    // This finds the apigee_top_up_amount field from the purchased entity.
    // Skipped if no valid field of type apigee_top_up_amount is found.
    if (($purchased_entity = $this->getPurchasedEntity($value)) && ($top_up_field = $this->getTopUpField($purchased_entity))) {
      return $top_up_field->toRange();
    }

    return [];
  }

  /**
   * Returns the purchased entity.
   *
   * @param mixed $value
   *   The value instance.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The purchasable entity.
   */
  protected function getPurchasedEntity($value) {
    $order = $value->getParent()->getEntity();
    if ($order instanceof OrderItemInterface) {
      return $order->getPurchasedEntity();
    }

    return NULL;
  }

  /**
   * Helper to get a field of type apigee_top_up_amount from an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The fieldable entity.
   *
   * @return \Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType\TopUpAmountItem|null
   *   The field of type apigee_top_up_amount or NULL if not found.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getTopUpField(FieldableEntityInterface $entity): ?TopUpAmountItem {
    // TODO: Extract this to a service if this is needed elsewhere.
    foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
      if ($definition->getType() === 'apigee_top_up_amount') {
        return $entity->get($field_name)->first();
      }
    }

    return NULL;
  }

}
