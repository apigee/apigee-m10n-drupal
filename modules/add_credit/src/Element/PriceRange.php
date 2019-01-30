<?php

/*
 * @file
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

namespace Drupal\apigee_m10n_add_credit\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a price range form element.
 *
 * Usage example:
 * @code
 * $form['price_range'] = [
 *   '#type' => 'price_range',
 *   '#title' => $this->t('Price range'),
 *   '#default_value' => [
 *      'minimum' => '99.99',
 *      'maximum' => '99.99',
 *      'default' => '99.99',
 *      'currency_code' => 'USD'
 *    ],
 *   '#allow_negative' => FALSE,
 *   '#size' => 60,
 *   '#maxlength' => 128,
 *   '#required' => TRUE,
 *   '#available_currencies' => ['USD', 'EUR'],
 * ];
 * @endcode
 *
 * @FormElement("price_range")
 */
class PriceRange extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;

    return [
      '#available_currencies' => [],
      '#size' => 10,
      '#maxlength' => 128,
      '#default_value' => NULL,
      '#allow_negative' => FALSE,
      '#process' => [
        [$class, 'processElement'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#input' => TRUE,
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Builds the price_range form element.
   *
   * @param array $element
   *   The initial price_range form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The built price_range form element.
   */
  public static function processElement(array $element, FormStateInterface $form_state, array &$complete_form) {
    $default_value = $element['#default_value'];

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $currency_storage */
    $currency_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_currency');
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface[] $currencies */
    $currencies = $currency_storage->loadMultiple();
    $currency_codes = array_keys($currencies);
    // Keep only available currencies.
    $available_currencies = $element['#available_currencies'];
    if (isset($available_currencies) && !empty($available_currencies)) {
      $currency_codes = array_intersect($currency_codes, $available_currencies);
    }
    // Stop rendering if there are no currencies available.
    if (empty($currency_codes)) {
      return $element;
    }
    $fraction_digits = [];
    foreach ($currencies as $currency) {
      $fraction_digits[] = $currency->getFractionDigits();
    }

    $element['price_range'] = [
      '#type' => 'fieldset',
      '#title' => $element['#title'],
      '#attributes' => [
        'class' => [
          'form-type-price-range',
        ],
      ],
      '#attached' => [
        'library' => [
          'apigee_m10n_add_credit/price_range'
        ],
      ],
      'fields' => [
        '#type' => 'container',
      ]
    ];

    // Add the help text if specified.
    if (!empty($element['#description'])) {
      $element['price_range']['#description'] = $element['#description'];
    }

    $number_fields = static::getNumberFields();

    foreach ($number_fields as $name => $title) {
      $element['price_range']['fields'][$name] = [
        '#type' => 'commerce_number',
        '#title' => $title,
        '#title_display' => $element['#title_display'],
        '#default_value' => $default_value ? $default_value[$name] : NULL,
        '#required' => $element['#required'],
        '#size' => $element['#size'],
        '#maxlength' => $element['#maxlength'],
        '#min_fraction_digits' => min($fraction_digits),
        '#min' => $element['#allow_negative'] ? NULL : 0,
        '#error_no_message' => TRUE,
      ];

      // The minimum price is required for minimum top up amount when buying a
      // product.
      $element['price_range']['fields']['minimum']['#required'] = TRUE;

      if (isset($element['#ajax'])) {
        // TODO: Explain why we have are copying ajax over to number fields.
        $element['price_range']['fields'][$name]['#ajax'] = $element['#ajax'];
      }
    }

    $element['price_range']['fields']['currency_code'] = [
      '#type' => 'select',
      '#title' => t('Currency'),
      '#default_value' => $default_value ? $default_value['currency_code'] : NULL,
      '#options' => array_combine($currency_codes, $currency_codes),
      '#field_suffix' => '',
      '#required' => $element['#required'],
    ];

    // Set currency code to use AJAX.
    if (isset($element['#ajax'])) {
      $element['price_range']['fields']['currency_code']['#ajax'] = $element['#ajax'];
    }

    // Remove the keys that were transferred to child elements.
    unset($element['#description']);
    unset($element['#maxlength']);
    unset($element['#ajax']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return isset($input['price_range']['fields']) ? array_filter($input['price_range']['fields']) : NULL;
  }

  /**
   * Helper to get the number fields for price range element.
   *
   * @return array
   *   An array of number fields.
   */
  public static function getNumberFields() {
    return [
      'minimum' => t('Minimum'),
      'maximum' => t('Maximum'),
      'default' => t('Default'),
    ];
  }

}
