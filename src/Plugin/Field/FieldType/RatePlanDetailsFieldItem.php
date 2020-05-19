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

use Apigee\Edge\Api\Monetization\Structure\RatePlanDetail;
use Apigee\Edge\Api\Monetization\Structure\RatePlanRateRateCard;
use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'apigee_rate_plan_details' field type.
 *
 * @FieldType(
 *   id = "apigee_rate_plan_details",
 *   label = @Translation("Rate plan detail field item"),
 *   description = @Translation("Apigee rate plan detail."),
 *   category = @Translation("Apigee"),
 *   no_ui = TRUE,
 *   default_formatter = "apigee_rate_plan_details"
 * )
 */
class RatePlanDetailsFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('value'))
      ->setDescription(new TranslatableMarkup('The rate plan detail object.'))
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
    $values['value'] = new RatePlanDetail([
      "aggregateFreemiumCounters" => TRUE,
      "aggregateStandardCounters" => TRUE,
      "aggregateTransactions"     => TRUE,
      'currency'                  => 'USD',
      "customPaymentTerm"         => TRUE,
      "duration"                  => 1,
      "durationType"              => "MONTH",
      "freemiumDuration"          => 1,
      "freemiumDurationType"      => "MONTH",
      "freemiumUnit"              => 110,
      "id"                        => strtolower($random->name(16)),
      "meteringType"              => "UNIT",
      "paymentDueDays"            => "30",
      'ratePlanRates'             => [
        new RatePlanRateRateCard([
          'id'        => strtolower($random->name(16)),
          'startUnit' => rand(5, 20),
          'rate'      => rand(5, 20),
        ]),
      ],
      "ratingParameter"           => "VOLUME",
      "type"                      => "RATECARD",
    ]);

    return $values;
  }

}
