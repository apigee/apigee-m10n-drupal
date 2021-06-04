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

namespace Drupal\apigee_m10n\Plugin\Field\FieldFormatter;

use Apigee\Edge\Api\Monetization\Structure\RatePlanRateRateCard;
use Apigee\Edge\Api\Monetization\Structure\RatePlanRateRevShare;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'apigee_rate_plan_details' field formatter.
 *
 * @FieldFormatter(
 *   id = "apigee_rate_plan_details",
 *   label = @Translation("Rate plan detail formatter"),
 *   field_types = {
 *     "apigee_rate_plan_details"
 *   }
 * )
 */
class RatePlanDetailsFormatter extends FormatterBase {

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
    /** @var \Apigee\Edge\Api\Monetization\Structure\RatePlanDetail $detail */
    $detail = $item->value;

    $all_rates = $detail->getRatePlanRates();

    // Get only rate card entries.
    $ratecard_rates = array_filter($all_rates, function ($rate) {
      return ($rate instanceof RatePlanRateRateCard);
    });
    // Get only revshare entries.
    $revshare_rates = array_filter($all_rates, function ($rate) {
      return ($rate instanceof RatePlanRateRevShare);
    });

    // The logic for the free quantity is a little complicated for the template.
    $free_quantity_template = '';
    $freemium_unit = $item->getEntity()->getFreemiumUnit();
    $freemium_duration = $item->getEntity()->getFreemiumDuration();
    $freemium_type = $item->getEntity()->getFreemiumDurationType();

    if (!empty($freemium_unit) && !empty($freemium_duration) && !empty($freemium_type)) {
      $free_quantity_template = 'Up to @unit @transactions for @duration @duration_type';
    }
    elseif (!empty($freemium_unit) && empty($freemium_duration) && empty($freemium_type)) {
      $free_quantity_template = 'Up to @unit @transactions';
    }
    elseif (empty($freemium_unit) && !empty($freemium_duration) && !empty($freemium_type)) {
      $free_quantity_template = 'For @duration @duration_type';
    }

    // Build the "Free quantity" text.
    $free_quantity = $this->t($free_quantity_template, [
      '@unit' => $freemium_unit,
      '@transactions' => $freemium_unit === 1 ? 'transaction' : 'transactions',
      '@duration' => $freemium_duration,
      '@duration_type' => strtolower($freemium_duration === 1 ? $freemium_type : $freemium_type . 's'),
    ]);

    // Get the apiproduct details.
    $apiproduct_info = '';
    if ($apiproduct_info = $detail->getProduct()) {
      foreach ($apiproduct_info as $value) {
        $apiproduct_info = $value;
      }
    }

    return [
      '#theme' => 'rate_plan_detail',
      '#detail' => $detail,
      '#ratecard_rates' => $ratecard_rates,
      '#revshare_rates' => $revshare_rates,
      '#free_quantity' => !empty((string) $free_quantity) ? $free_quantity : NULL,
      '#entity' => $item->getEntity(),
      '#apiproduct' => $apiproduct_info,
      '#attached' => ['library' => ['apigee_m10n/rate_plan.details_field']],
    ];
  }

}
