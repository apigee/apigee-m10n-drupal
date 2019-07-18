<?php

/*
 * Copyright 2019 Google Inc.
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

namespace Drupal\Tests\apigee_m10n\Kernel\Entity\Render;

use Apigee\Edge\Api\Monetization\Entity\RatePlanInterface;
use Drupal\Tests\apigee_m10n\Kernel\MonetizationKernelTestBase;

/**
 * Kernel test for the rate plan storage.
 *
 * @coversDefaultClass \Drupal\apigee_m10n\Entity\Storage\RatePlanStorage
 * @group apigee_m10n
 * @group apigee_m10n_kernel
 */
class RatePlanStorageTest extends MonetizationKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();

    // Setup for creating a user.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig([
      'user',
      'system',
    ]);
    $this->installEntitySchema('user');
  }

  /**
   * Test rate plans.
   *
   * @covers ::loadRatePlansByProductBundle
   * @covers ::isValidId
   */
  public function testloadRatePlansByProductBundle() {
    $product_bundle = $this->createProductBundle();

    // Create one valid and one invalid rate plans.
    $rate_plans = [
      $this->createRatePlan($product_bundle, RatePlanInterface::TYPE_STANDARD, 'foobar'),
      $this->createRatePlan($product_bundle, RatePlanInterface::TYPE_STANDARD, 'foo/bar')
    ];
    $this->stack->queueMockResponse(['get_monetization_package_plans' => ['plans' => [$rate_plans]]]);
    $entities = \Drupal::entityTypeManager()->getStorage('rate_plan')->loadRatePlansByProductBundle($product_bundle->id());

    // Expect only one valid rate plan to be returned from storage.
    $this->assertCount(1, $entities);
  }

}
