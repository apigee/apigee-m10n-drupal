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

namespace Drupal\Tests\apigee_m10n\Kernel\Entity;

use Apigee\Edge\Api\Monetization\Entity\RatePlanInterface;
use Drupal\apigee_m10n\Entity\RatePlan;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Test the `rate_plan` entity.
 *
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class RatePlanEntityKernelTest extends MonetizationKernelTestBase {

  /**
   * A test rate plan.
   *
   * @var \Drupal\apigee_m10n\Entity\RatePlanInterface
   */
  protected $rate_plan;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $package = $this->createPackage();

    // Create a rate plan.
    $this->rate_plan = $this->createPackageRatePlan($package);
  }

  /**
   * Test that we can pass random data and create a rate plan.
   *
   * @throws \Exception
   */
  public function testEntityInstantiation() {
    static::assertInstanceOf(RatePlan::class, $this->rate_plan);
  }

  /**
   * Test loading a rate plan from drupal entity storage.
   *
   * @throws \Exception
   */
  public function testLoadRatePlan() {
    // Set the current user to a mock. Anon can no longer access packages.
    $account = $this->prophesizeCurrentUser();

    $this->stack
      ->queueMockResponse(['rate_plan' => ['plan' => $this->rate_plan]]);

    $rate_plan = RatePlan::loadById($this->rate_plan->getpackage()->id(), $this->rate_plan->id());

    static::assertInstanceOf(RatePlanInterface::class, $rate_plan);
    static::assertSame(gettype($rate_plan), gettype($this->rate_plan));

    static::assertSame($rate_plan->isAdvance(), $this->rate_plan->isAdvance());
    static::assertSame($rate_plan->getCurrency()->id(), $this->rate_plan->getCurrency()->id());
    // What about `customPaymentTerm`?
    static::assertSame($rate_plan->getDescription(), $this->rate_plan->getDescription());
    static::assertSame($rate_plan->getDisplayName(), $this->rate_plan->getDisplayName());
    static::assertSame($rate_plan->getEarlyTerminationFee(), $this->rate_plan->getEarlyTerminationFee());
    static::assertSame($rate_plan->getFrequencyDuration(), $this->rate_plan->getFrequencyDuration());
    static::assertSame($rate_plan->getFrequencyDurationType(), $this->rate_plan->getFrequencyDurationType());
    static::assertSame($rate_plan->id(), $this->rate_plan->id());
    static::assertSame($rate_plan->isPrivate(), $this->rate_plan->isPrivate());
    // What about `keepOriginalStartDate`?
    static::assertSame($rate_plan->getPackage()->id(), $this->rate_plan->getPackage()->id());
    static::assertSame($rate_plan->getName(), $this->rate_plan->getName());
    static::assertSame($rate_plan->getOrganization()->getName(), $this->rate_plan->getOrganization()->getName());
    static::assertSame($rate_plan->getPaymentDueDays(), $this->rate_plan->getPaymentDueDays());
    static::assertSame($rate_plan->isProrate(), $this->rate_plan->isProrate());
    static::assertSame($rate_plan->isPublished(), $this->rate_plan->isPublished());
    static::assertSame($rate_plan->getRecurringFee(), $this->rate_plan->getRecurringFee());
    static::assertSame($rate_plan->getRecurringStartUnit(), $this->rate_plan->getRecurringStartUnit());
    static::assertSame($rate_plan->getRecurringType(), $this->rate_plan->getRecurringType());
    static::assertSame($rate_plan->getSetUpFee(), $this->rate_plan->getSetUpFee());
    static::assertSame("/user/{$account->id()}/monetization/package/{$rate_plan->getPackage()->id()}/plan/{$rate_plan->id()}", $rate_plan->toUrl()->toString());
    // Check package API products.
    $product_ids = array_map(function ($product) {
      return ['target_id' => $product->id()];
    }, $rate_plan->getPackage()->getApiProducts());
    static::assertSame($product_ids, $this->rate_plan->getPackageProducts());
  }

}
