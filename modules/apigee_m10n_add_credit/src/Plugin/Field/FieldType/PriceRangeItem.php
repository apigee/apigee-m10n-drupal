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

use Drupal\apigee_m10n_add_credit\Element\PriceRange;
use Drupal\commerce_price\Price;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'apigee_price_range' field type.
 *
 * @FieldType(
 *   id = "apigee_price_range",
 *   label = @Translation("Apigee price range"),
 *   description = @Translation("Stores a price range with minimum, maximum and default values."),
 *   default_widget = "price_range_default",
 *   default_formatter = "price_range_default",
 *   constraints = {
 *     "PriceRangeDefaultOutOfRange" = {},
 *     "PriceRangeMinimumGreaterMaximum" = {},
 *     "PriceRangeMinimumTopUpAmount" = {}
 *   }
 * )
 */
class PriceRangeItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    foreach (PriceRange::getNumberFields() as $field => $name) {
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
    return 'default';
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

    foreach (PriceRange::getNumberFields() as $field => $name) {
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
  public static function defaultFieldSettings() {
    return [
      'available_currencies' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $currencies = \Drupal::entityTypeManager()
      ->getStorage('commerce_currency')
      ->loadMultiple();
    $currency_codes = array_keys($currencies);

    $element = [];
    $element['available_currencies'] = [
      '#type' => count($currency_codes) < 10 ? 'checkboxes' : 'select',
      '#title' => $this->t('Available currencies'),
      '#description' => $this->t('If no currencies are selected, all currencies will be available.'),
      '#options' => array_combine($currency_codes, $currency_codes),
      '#default_value' => $this->getSetting('available_currencies'),
      '#multiple' => TRUE,
      '#size' => 5,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->minimum)
      && empty($this->maximum)
      && empty($this->default)
      && empty($this->currency_code);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    // Set the variation price from the price range default.
    // Use the minimum otherwise use the default if set.
    $number = 0;
    if (isset($this->minimum)) {
      $number = $this->minimum;
    }
    elseif (isset($this->default)) {
      $number = $this->default;
    }

    $this->getEntity()->setPrice(new Price($number, $this->currency_code));
  }

}
