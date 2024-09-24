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

namespace Drupal\Tests\apigee_m10n\Kernel\ApigeeX\Entity;

use Apigee\Edge\Api\ApigeeX\Entity\RatePlanInterface;
use Drupal\Tests\apigee_m10n\Kernel\ApigeeX\MonetizationKernelTestBase;
use Drupal\apigee_m10n\Entity\XRatePlan;

/**
 * Test the `xrate_plan` entity.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class RatePlanEntityKernelTest extends MonetizationKernelTestBase {

  /**
   * A test rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\XRatePlanInterface
   */
  protected $rate_plan;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Get pre-configured token storage service for testing.
    $this->storeToken();

    $this->stack->reset();
    $xproduct = $this->createApigeexProduct();
    $this->stack->reset();
    // Create a rate plan.
    $this->rate_plan = $this->createRatePlan($xproduct);
    $this->stack->reset();

  }

  /**
   * Test that we can pass random data and create a rate plan.
   *
   * @throws \Exception
   */
  public function testEntityInstantiation() {
    static::assertInstanceOf(XRatePlan::class, $this->rate_plan);
  }

  /**
   * Test loading a rate plan from drupal entity storage.
   *
   * @throws \Exception
   */
  public function testLoadRatePlan() {
    // Set the current user to a mock. Anon can no longer access product
    // bundles.
    $account = $this->prophesizeCurrentUser();
    $this->stack->reset();
    $this->stack->queueMockResponse(['rate_plan_apigeex' => ['plan' => $this->rate_plan]]);
    $rate_plan = XRatePlan::loadById($this->rate_plan->getApiproduct(), $this->rate_plan->id());

    static::assertInstanceOf(RatePlanInterface::class, $rate_plan);
    static::assertSame(gettype($rate_plan), gettype($this->rate_plan));

    static::assertSame($rate_plan->id(), $this->rate_plan->id());
    static::assertSame($rate_plan->getBillingPeriod(), $this->rate_plan->getBillingPeriod());
    static::assertSame($rate_plan->getDescription(), $this->rate_plan->getDescription());
    static::assertSame($rate_plan->getDisplayName(), $this->rate_plan->getDisplayName());
    static::assertSame($rate_plan->getPaymentFundingModel(), $this->rate_plan->getPaymentFundingModel());
    static::assertSame($rate_plan->getCurrencyCode(), $this->rate_plan->getCurrencyCode());
    static::assertSame($rate_plan->getFixedFeeFrequency(), $this->rate_plan->getFixedFeeFrequency());
    static::assertSame($rate_plan->getConsumptionPricingType(), $this->rate_plan->getConsumptionPricingType());
    static::assertSame($rate_plan->getRevenueShareType(), $this->rate_plan->getRevenueShareType());
    static::assertSame($rate_plan->getStartTime(), $this->rate_plan->getStartTime());
    static::assertSame($rate_plan->getEndTime(), $this->rate_plan->getEndTime());
    static::assertSame($rate_plan->getApiproduct(), $this->rate_plan->getApiproduct());
    static::assertSame($rate_plan->getName(), $this->rate_plan->getName());

    // Revenue Share Rates.
    static::assertSame($rate_plan->getRevenueShareRates()[0]->getEnd(), $this->rate_plan->getRevenueShareRates()[0]->getEnd());
    static::assertSame($rate_plan->getRevenueShareRates()[0]->getStart(), $this->rate_plan->getRevenueShareRates()[0]->getStart());
    static::assertSame($rate_plan->getRevenueShareRates()[0]->getSharePercentage(), $this->rate_plan->getRevenueShareRates()[0]->getSharePercentage());

    // Consumption Pricing Rates.
    static::assertSame($rate_plan->getConsumptionPricingRates()[0]->getEnd(), $this->rate_plan->getConsumptionPricingRates()[0]->getEnd());
    static::assertSame($rate_plan->getConsumptionPricingRates()[0]->getStart(), $this->rate_plan->getConsumptionPricingRates()[0]->getStart());

    // Consumption Pricing Rates Fee.
    static::assertSame($rate_plan->getConsumptionPricingRates()[0]->getFee()->getCurrencyCode(), $this->rate_plan->getConsumptionPricingRates()[0]->getFee()->getCurrencyCode());
    static::assertSame($rate_plan->getConsumptionPricingRates()[0]->getFee()->getUnits(), $this->rate_plan->getConsumptionPricingRates()[0]->getFee()->getUnits());
    static::assertSame($rate_plan->getConsumptionPricingRates()[0]->getFee()->getNanos(), $this->rate_plan->getConsumptionPricingRates()[0]->getFee()->getNanos());

    // SetUp Fee.
    static::assertSame($rate_plan->getRatePlanXFee()[0]->getCurrencyCode(), $this->rate_plan->getRatePlanXFee()[0]->getCurrencyCode());
    static::assertSame($rate_plan->getRatePlanXFee()[0]->getUnits(), $this->rate_plan->getRatePlanXFee()[0]->getUnits());
    static::assertSame($rate_plan->getRatePlanXFee()[0]->getNanos(), $this->rate_plan->getRatePlanXFee()[0]->getNanos());

    static::assertSame("/user/{$account->id()}/monetization/xproduct/{$rate_plan->getProductId()}/plan/{$rate_plan->id()}", $rate_plan->toUrl()->toString());
  }

}
