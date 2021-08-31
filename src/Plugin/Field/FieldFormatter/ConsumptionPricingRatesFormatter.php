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

namespace Drupal\apigee_m10n\Plugin\Field\FieldFormatter;

use Apigee\Edge\Api\ApigeeX\Structure\Fee;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'apigee_rate_plan_details' field formatter.
 *
 * @FieldFormatter(
 *   id = "apigee_rate_plan_consumption_rates",
 *   label = @Translation("Consumption Pricing Rates formatter"),
 *   field_types = {
 *     "apigee_rate_plan_consumption_rates"
 *   }
 * )
 */
class ConsumptionPricingRatesFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // @todo: Implement settings summary.
    $summary = [];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Build a renderable value.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return array
   *   A render array.
   */
  protected function viewValue(FieldItemInterface $item) {
    /** @var \Apigee\Edge\Api\ApigeeX\Structure\ConsumptionPricingRates $detail */
    $detail = $item->value;
    $fee_details = $detail->getFee();
    $fee_currency_code = $fee_details->getCurrencyCode();
    $fee_units = $fee_details->getUnits();
    $fee_nanos = $fee_details->getNanos();
    $fee_start = $detail->getStart();
    $fee_end = $detail->getEnd();

    $multipleConsumptionText = '';
    $singleConsumptionText = '';
    if (empty($fee_start) && empty($fee_end)) {
      $singleConsumptionText = $fee_currency_code . ' ' . ($fee_units + $fee_nanos);
    }
    else {
      $endUnitStr = $fee_end ? 'up to' : '';
      $start = $fee_start ? $fee_start : 0;
      $multiple_consumption_template = 'Greater than @start @endUnitStr @end';
      // Build the "Consumption text".
      $multipleConsumptionText = $this->t($multiple_consumption_template, [
        '@start' => $start,
        '@endUnitStr' => $endUnitStr,
        '@end' => $fee_end,
      ]);
    }

    return [
      '#theme' => 'rate_plan_consumption_rates',
      '#detail' => $detail,
      '#fee_currency_code' => $fee_currency_code,
      '#fee_units' => $fee_units,
      '#fee_nanos' => $fee_nanos,
      '#multipleConsumptionText' => $multipleConsumptionText,
      '#singleConsumptionText' => $singleConsumptionText,
      '#entity' => $item->getEntity(),
      '#attached' => ['library' => ['apigee_m10n/rate_plan.details_field']],
    ];
  }

}
