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

namespace Drupal\Tests\apigee_m10n\Traits;

use Apigee\Edge\Api\Monetization\Structure\RatePlanDetail;
use Apigee\Edge\Api\Monetization\Structure\RatePlanRateRateCard;
use Apigee\Edge\Api\Monetization\Structure\RatePlanRateRevShare;

/**
 * Assertions for rate plan details for kernel tests.
 */
trait RatePlanDetailsKernelTestAssertionTrait {

  /**
   * Tests rate plan details.
   *
   * @param \Apigee\Edge\Api\Monetization\Structure\RatePlanDetail $details
   *   The rate plan details.
   */
  public function assertRatePlanDetails(RatePlanDetail $details) {
    $ratecard_title = $details->getType() === 'REVSHARE_RATECARD' ? 'Rate Rate card & revenue' : ($details->getType() === 'REVSHARE' ? 'Revenue' : 'Rate card');
    $this->assertCssElementText(".rate-plan-detail > h2.rate-plan-detail__title", $ratecard_title);
    // Metering type.
    $this->assertCssElementText(".rate-plan-detail .rate-plan-detail__metering-type > .field__label", 'Rate card is based on');
    $this->assertCssElementText(".rate-plan-detail .rate-plan-detail__metering-type > .field__item", $details->getMeteringType());
    // Duration.
    $duration_type = $details->getDuration() === 1 ? $details->getDurationType() : "{$details->getDurationType()}s";
    $duration = "{$details->getDuration()} {$duration_type}";
    $this->assertCssElementText(".rate-plan-detail .rate-plan-detail__metering-basis > .field__label", 'Volume aggregation basis');
    $this->assertCssElementText(".rate-plan-detail .rate-plan-detail__metering-basis > .field__item", strtolower($duration));
    // Operator.
    $this->assertCssElementText(".rate-plan-detail .rate-plan-detail__overview__operator > .field__label", 'Operator');
    $this->assertCssElementText(".rate-plan-detail .rate-plan-detail__overview__operator > .field__item", $details->getOrganization()->getDescription());
    // Currency.
    $this->assertCssElementText(".rate-plan-detail .rate-plan-detail__overview__currency > .field__label", 'Currency');
    $this->assertCssElementText(".rate-plan-detail .rate-plan-detail__overview__currency > .field__item", $details->getCurrency()->getDisplayName());
    // Country.
    $this->assertCssElementText(".rate-plan-detail .rate-plan-detail__overview__country > .field__label", 'Country');
    $this->assertCssElementText(".rate-plan-detail .rate-plan-detail__overview__country > .field__item", $details->getOrganization()->getCountry());
    // Pricing Type.
    $this->assertCssElementText(".rate-plan-detail .rate-plan-detail__overview__pricing-type > .field__label", 'Pricing type');
    $this->assertCssElementText(".rate-plan-detail .rate-plan-detail__overview__pricing-type > .field__item", ucfirst($details->getRevenueType()));

    // Test rate plan rates.
    $prefix = '.rate-plan-detail:first-child';
    $ratecard_rates = array_filter($details->getRatePlanRates(), function ($rate) {
      return ($rate instanceof RatePlanRateRateCard);
    });
    $revshare_rates = array_filter($details->getRatePlanRates(), function ($rate) {
      return ($rate instanceof RatePlanRateRevShare);
    });
    // Ratecard rates.
    foreach ($ratecard_rates as $index => $rate) {
      /** @var \Apigee\Edge\Api\Monetization\Structure\RatePlanRateRateCard $rate */
      // Add one to the index for the header.
      $css_index = $index + 2;
      $row_prefix = "$prefix .rate-plan-detail__ratecard > .ratecard-rate.rate-plan-rates__row:nth-child({$css_index})";
      $rate_label = "Greater than {$rate->getStartUnit()}" . (!empty($rate->getEndUnit()) ? " up to {$rate->getEndUnit()}" : '');
      $this->assertCssElementText("{$row_prefix} .field__label", $rate_label);
      $this->assertCssElementText("{$row_prefix} .field__item", (string) $rate->getRate());
    }
    // Revshare rates.
    foreach ($revshare_rates as $index => $rate) {
      /** @var \Apigee\Edge\Api\Monetization\Structure\RatePlanRateRateCard $rate */
      // Add one to the index for the header.
      $css_index = $index + 2;
      $row_prefix = "$prefix .rate-plan-detail__revshare > .revshare-rate.rate-plan-rates__row:nth-child({$css_index})";
      $rate_label = "Greater than {$rate->getStartUnit()}" . (!empty($rate->getEndUnit()) ? " up to {$rate->getEndUnit()}" : '');
      $this->assertCssElementText("{$row_prefix} .field__label", $rate_label);
      $this->assertCssElementText("{$row_prefix} .field__item", (string) $rate->getRate());
    }
  }

}
