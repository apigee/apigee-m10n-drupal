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

namespace Drupal\apigee_m10n\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'apigee_price' field type.
 *
 * @FieldType(
 *   id = "apigee_price",
 *   label = @Translation("Price"),
 *   description = @Translation("Stores a decimal number and a three letter currency code."),
 *   no_ui = TRUE,
 *   category = @Translation("Apigee"),
 *   default_formatter = "apigee_price",
 * )
 */
class PriceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return [
      'amount' => DataDefinition::create('string')
        ->setLabel(t('Amount'))
        ->setRequired(FALSE),
      'currency_code' => DataDefinition::create('string')
        ->setLabel(t('Currency code'))
        ->setRequired(FALSE),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'amount' => [
          'description' => 'The price amount.',
          'type' => 'numeric',
          'precision' => 19,
          'scale' => 6,
        ],
        'currency_code' => [
          'description' => 'The currency code.',
          'type' => 'varchar',
          'length' => 3,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->amount === NULL || $this->amount === '' || empty($this->currency_code);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    return [
      'value' => [
        'amount' => rand(0, 100) + (rand(0, 99) * .01),
        'currency_code' => 'usd',
      ],
    ];
  }

}
