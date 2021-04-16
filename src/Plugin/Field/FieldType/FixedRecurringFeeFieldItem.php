<?php

/*
 * Copyright 2021 Google Inc.
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

use Apigee\Edge\Api\Monetization\Structure\FixedRecurringFee;
use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'apigee_rate_plan_fixed_recurringfee' field type.
 *
 * @FieldType(
 *   id = "apigee_rate_plan_fixed_recurringfee",
 *   label = @Translation("Fixed recurring fee field item"),
 *   description = @Translation("Apigee X fixed recurring fee detail."),
 *   category = @Translation("Apigee"),
 *   no_ui = TRUE,
 *   default_formatter = "apigee_rate_plan_fixed_recurringfee"
 * )
 */
class FixedRecurringFeeFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('value'))
      ->setDescription(new TranslatableMarkup('The setup fee object.'))
      ->setComputed(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = new FixedRecurringFee([
      "currencyCode"              => 'USD',
      "units"                     => '1',
      "nanos"                     => 300000000,
    ]);

    return $values;
  }

}
