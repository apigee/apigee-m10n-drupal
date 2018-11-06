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

namespace Drupal\apigee_m10n\Entity;

use Drupal\apigee_edge\Entity\FieldableEdgeEntityBaseTrait;

/**
 * Trait that allows to make Apigee Monetization entities fieldable.
 *
 * Contains implementations that were only available for content entities.
 *
 * A fieldable Edge entity's properties that are being exposed as base fields
 * should not be modified by using the original property setters (inherited
 * from the wrapper SDK entity), those should be modified through the field
 * API because that can keep field and property values in sync.
 *
 * @see \Drupal\Core\Entity\ContentEntityBase
 * @see \Drupal\Core\Entity\FieldableEntityStorageInterface
 * @see \Drupal\apigee_edge\Entity\FieldableEdgeEntityInterface
 */
trait FieldableMonetizationEntityBaseTrait {

  use FieldableEdgeEntityBaseTrait {
    set as edgeSet;
  }

  /**
   * {@inheritdoc}
   *
   * This is mostly copied from `FieldableEdgeEntityBaseTrait` but we are adding
   * support for using custom Apigee field values directly.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function set($field_name, $value, $notify = TRUE) {
    // Do not try to set values of fields that does not exists.
    // Also blacklisted properties does not have a field in Drupal and their
    // value changes should not be saved on entity properties either.
    if (!$this->hasField($field_name)) {
      return $this;
    }

    // Value that is compatible with what a mapped base field can accept.
    $fieldValue = $value;
    if (is_object($value)) {
      // Take care of timestamp fields that value from the SDK is a
      // date object.
      if (array_key_exists($field_name, static::propertyToFieldStaticMap()) && static::getFieldType($field_name) === 'timestamp') {
        /** @var \DateTimeImmutable $value */
        $fieldValue = $value->getTimestamp();
      }
      // Custom field types can take the objects as is.
      elseif (array_key_exists($field_name, static::propertyToFieldStaticMap())
        && in_array(static::getFieldType($field_name), FieldableMonetizationEntityInterface::APIGEE_SIMPLE_FIELD_TYPES)
      ) {
        $fieldValue = $value;
      }
      else {
        $fieldValue = (string) $value;
      }
    }

    // If a base field's cardinality is 1, it means that the
    // underlying entity property (inherited from the wrapped SDK entity)
    // only accepts a scalar value. However, some base fields returns its
    // value as an array. This is what we need to fix here.
    // (We do not change the structure of values that does not belong to
    // base fields).
    if (is_array($value) && $this->getFieldDefinition($field_name) instanceof BaseFieldDefinition && $this->getFieldDefinition($field_name)->getCardinality() === 1) {
      if (count($value) === 0) {
        $value = '';
      }
      else {
        $exists = FALSE;
        $new_value = NestedArray::getValue($value, ['0', 'value'], $exists);
        if ($exists) {
          $value = $new_value;
        }
        else {
          $new_value = NestedArray::getValue($value, ['value'], $exists);
          if ($exists) {
            $value = $new_value;
          }
          else {
            $called_class = get_called_class();
            throw new EdgeFieldException("Unable to retrieve value of {$field_name} base field on {$called_class}.");
          }
        }
      }
    }
    // Save field's value to the its related property (if there is one).
    $this->setPropertyValue($field_name, $value);

    $this->get($field_name)->setValue($fieldValue, $notify);

    return $this;
  }

}
