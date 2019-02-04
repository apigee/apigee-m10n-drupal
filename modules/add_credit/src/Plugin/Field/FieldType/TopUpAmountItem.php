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

namespace Drupal\apigee_m10n_add_credit\Plugin\Field\FieldType;

use Drupal\apigee_m10n_add_credit\Element\TopUpAmountRange;
use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
use Drupal\commerce_price\Price;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'apigee_top_up_amount' field type.
 *
 * @FieldType(
 *   id = "apigee_top_up_amount",
 *   label = @Translation("Apigee top up amount"),
 *   description = @Translation("Stores a top up amount with minimum, maximum and default values."),
 *   default_widget = "apigee_top_up_amount_price",
 *   default_formatter = "commerce_price_default",
 *   constraints = {
 *     "TopUpAmountMinimumGreaterMaximum" = {},
 *     "TopUpAmountNumberOutOfRange" = {},
 *     "TopUpAmountMinimumAmount" = {}
 *   }
 * )
 */
class TopUpAmountItem extends PriceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    foreach (TopUpAmountRange::getNumberFields() as $field => $name) {
      $properties[$field] = DataDefinition::create('string')
        ->setLabel($name)
        ->setRequired(FALSE);
    }

    $properties['currency_code'] = DataDefinition::create('string')
      ->setLabel(t('Currency code'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'number';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'currency_code' => [
          'description' => 'The currency code.',
          'type' => 'varchar',
          'length' => 3,
        ],
      ],
    ];

    foreach (TopUpAmountRange::getNumberFields() as $field => $name) {
      $schema['columns'][$field] = [
        'type' => 'numeric',
        'precision' => 19,
        'scale' => 6,
      ];
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->minimum)
      && empty($this->maximum)
      && empty($this->number)
      && empty($this->currency_code);
  }

  /**
   * {@inheritdoc}
   */
  public function toPrice() {
    $number = NULL;
    if ($this->number) {
      $number = $this->number;
    }
    elseif ($this->minimum) {
      $number = $this->minimum;
    }

    return $number ? new Price($number, $this->currency_code) : NULL;
  }

  /**
   * Gets the range for the current field item.
   *
   * @return array
   *   The range.
   */
  public function toRange() {
    return [
      'minimum' => $this->minimum ?? $this->number,
      'maximum' => $this->maximum,
      'number' => $this->number,
      'currency_code' => $this->currency_code,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    // If no minimum is provided, set the number as the minimum.
    if (empty($this->minimum) && !empty($this->number)) {
      $this->minimum = $this->number;
    }
    elseif (empty($this->number) && !empty($this->minimum)) {
      $this->number = $this->minimum;
    }

    // Set the variation price from the top up amount.
    $this->getEntity()->setPrice($this->toPrice());
  }

}
